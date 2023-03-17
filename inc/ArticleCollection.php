<?php
/**
 * @package  Gitdown
 */
namespace WP\Plugin\Gitdown;
use Parsedown as GDParsedown;
use CzProject\GitPhp\Git as MGD_GIT;

class MGD_ArticleCollection {
    public $articles = [];
    public $reports;

    function __construct () {
        $this->reports = (object) array(
            'published_posts'  => 0,
            'found_posts'      => 0,
            'valid_posts'      => 0,
            'coerced_slugs'    => 0,
            'errors'           => array(),
        );
    }

    // This function checks if the articles have been parsed and if not does that.
    private function check_if_parsed() {
        if ($this->articles == []) $this->parse();
    }

    private function parse() {

        if (get_option(MGD_SETTING_GLOB) == '') {
            $this->push_report_error('Missing Glob Pattern', 'SETTINGS', 'You did not specify a glob pattern');
            return;
        } else if (get_option(MGD_SETTING_REPO) == '') {
            $this->push_report_error('Missing Repository URL', 'SETTINGS', 'You did not specify a repository url');
            return;
        }

        chdir(MGD_MIRROR_PATH);

        $git = new MGD_GIT;
        if (!MGD_REMOTE_IS_CLONED) {
            try {
                $repo = $git->cloneRepository(get_option(MGD_SETTING_REPO), '.');
            } catch (\Throwable $th) {
                $this->push_report_error('Repository Error', get_option(MGD_SETTING_REPO), 'There is something wrong with your repository. Maybe the link is wrong or it is a private repository.');
            }
        } else {
            // Try to pull the repository multiple times if they fail.
            $pull_success = false;

            while (!$pull_success) {
                try {
                    $repo = $git->open('.');
                    $repo->pull('origin');

                    $pull_success = true;
                } catch (\Throwable $th) {}
            }
        }

        
        // Get all Paths
        $paths = [];
        foreach (explode(',', get_option(MGD_SETTING_GLOB)) as $single_glob) {
            $paths = array_merge($paths, glob($single_glob));
        }


        // Resolve Articles
        foreach ($paths as $path) {
            $this->reports->found_posts++;
            
            // Creating the Std Object
            $post_data = new \stdClass();
            
            $post_data->remote = $this->resolver($path);
            
            if ( !$post_data->remote ) continue;
            
            if (!property_exists($post_data->remote, 'name')) {
                $this->push_report_error('Missing Name', $path, __('It seems like this post has no name.'));
            }

            if (!property_exists($post_data->remote, 'raw_content')) {
                $this->push_report_error('Missing Content', $path, __('It seems like this post has no content.'));
            }

            $this->reports->valid_posts++;

            // Add the name as the slug in case its not defined
            if (!property_exists($post_data->remote, 'slug')) {
                $this->reports->coerced_slugs++;
                $post_data->remote->slug = MGD_Helpers::string_to_slug($post_data->remote->name);

                $this->push_report_error('Coerced Slug', $path, __('This post does not define a slug so the name was turned into a slug. This is not advised.'));
            }
            if (!property_exists($post_data->remote, 'tags')) {
                $post_data->remote->tags = [];

                $this->push_report_error('Missing Tags', $path, __('This post does not define tags, which is not crucial but recommended either way.'));
            }
            if (!property_exists($post_data->remote, 'category')) {
                $post_data->remote->category = [];

                $this->push_report_error('Missing Category', $path, __('This post does not define categories, which is not crucial but recommended either way.'));
            }

            array_push($this->articles, $post_data);
        }

        // Merge Remote Articles with local articles if applicable
        foreach ($this->articles as $key => $article) {

            $localArticle = get_posts(array(
                'name'            =>  $article->remote->slug,
                'post_status'     =>  ['draft', 'publish', 'trash'],
                'posts_per_page'  =>  1
            ))[0] ?? [];

            $this->articles[$key]->local = $localArticle;
            $this->articles[$key]->_is_published = !!$localArticle;
            $this->articles[$key]->last_updated = 0;
            
            if (!!$localArticle) $this->articles[$key]->last_updated = (int) get_post_meta($localArticle->ID, 'mgd_last_updated', true);

            if (!!$localArticle) {
                $this->reports->published_posts++;
            }
        }

        chdir(MGD_ROOT_PATH);
    }

    private function resolver(string $document_path) {
        
        if (!file_get_contents($document_path)) {
            $this->push_report_error('Empty File', $document_path, 'This file is empty but still matches the glob pattern.');
            return false;
        }

        switch (get_option(MGD_SETTING_RESOLVER)) {
            case 'simple': {
                return MGD_Resolvers::simple($document_path);
            }

            case 'dir_cat': {
                return $currentData = MGD_Resolvers::directory_category($document_path);
            }

            default; {
                return MGD_Resolvers::simple($document_path);
            }
        }
    }   

    public function get_all(): array {
        $this->check_if_parsed();

        return $this->articles;
    }

    function get_by_slug(string $slug): object {
        $this->check_if_parsed();

        return MGD_Helpers::array_nested_find($this->articles, function($obj) use (&$slug) {
            return $obj->remote->slug == $slug;
        });
    }

    public function get_by_id(int $id): object {
        $this->check_if_parsed();

        return MGD_Helpers::array_nested_find($this->articles, function($obj) use (&$id) {
            return ($obj->local->ID ?? -1) == $id;
        }) ?? (object) array(
            '_is_published' => false,
        );
    }

    public function get_oldest(int $count = 1): array {
        $all_posts = $this->get_all();

        usort($all_posts, function($a, $b){
            return $a->last_updated <=> $b->last_updated;
        });

        return array_slice($all_posts, 0, $count);
    }

    public function update_post(string $slug) {
        $this->check_if_parsed();

        $post_data = $this->get_by_slug($slug);

        MGD_Helpers::write_log(sprintf('Updating: %s', $post_data->remote->name));

        $Parsedown = new GDParsedown();

        $new_post_data = array(
            'post_title'     =>  $post_data->remote->name,
            'post_name'      =>  $post_data->remote->slug,
            'post_excerpt'   =>  $post_data->remote->description ?? '',
            'post_content'   =>  wp_kses_post($Parsedown->text($post_data->remote->raw_content)),
            'post_status'    =>  $post_data->remote->status ?? 'publish',
            'post_category'  =>  MGD_Helpers::create_categories($post_data->remote->category),
            'tags_input'     =>  MGD_Helpers::coerce_to_array($post_data->remote->tags),
        );

        // Add the ID in case it is already published
        if ($post_data->_is_published) {
            $new_post_data['ID'] = $post_data->local->ID;
        }

        // Insert the post into the database
        try {
            $post_id = wp_insert_post($new_post_data);
            update_post_meta($post_id, 'mgd_last_updated', time());
        } catch (\Throwable $th) {
            MGD_Helpers::write_log($th);
            return;
        }


        // Uploading the Image
        $imagePath = MGD_MIRROR_PATH . $post_data->remote->featured_image;

        if (is_file($imagePath)) {
            $uploadPath = wp_upload_dir()['path'] . '/' . $new_post_data['post_name'] . '.png';

            copy($imagePath, $uploadPath);

            $thumbnailId = get_post_thumbnail_id($post_id);

            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $attachment_data = array(
                'ID' => $thumbnailId,
                'post_mime_type' => wp_check_filetype($uploadPath, null)['type'],
                'post_title' => $new_post_data['post_title'],
                'post_content' => '',
                'post_status' => 'inherit',
            );

            $attach_id = wp_insert_attachment($attachment_data, $uploadPath, $post_id);
            set_post_thumbnail($post_id, $attach_id);

            wp_generate_attachment_metadata($attach_id, $uploadPath);
        };


        // Return Post data so the frontend can process it
        return [
            'new_post' => get_post($post_id),
            'last_updated' => (int) get_post_meta($post_id, 'mgd_last_updated', true),
        ];
    }

    public function delete_post(string $slug)
    {
        $this->check_if_parsed();

        $article = $this->get_by_slug($slug);

        if (!$article->_is_published) return;

        $post_id = $article->local->ID;

        // Remove Thumbnail Image
        wp_delete_attachment(get_post_thumbnail_id($post_id));

        // Remove the Post itself
        $result = wp_delete_post($post_id, true);

        // Returning the result so The Frontend knows it
        return !!$result;
    }

    private function push_report_error($type, $location, $description) {
        array_push($this->reports->errors, (object) [
            'type' => $type,
            'location' => $location,
            'description' => $description,
        ]);
    }
}
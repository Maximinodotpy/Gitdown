<?php
/**
 * @package  Gitdown
 */
namespace Inc;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use CzProject\GitPhp\Git as MGD_GIT;

class ArticleCollection {
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
        $reserved_frontmatter_keys = ['name', 'slug', 'tags', 'category', 'published', 'raw_content', 'featured_image', 'description', 'status', 'mgd_last_updated', 'parent_page', 'post_type', 'author'];

        // Check if there is no glob pattern
        if (get_option('mgd_glob_setting') == '') {
            $this->push_report_error('Missing Glob Pattern', 'SETTINGS', 'You did not specify a glob pattern');
            return;
        }

        // Check if there is no repository url
        if (get_option('mgd_repo_setting') == '') {
            $this->push_report_error('Missing Repository URL', 'SETTINGS', 'You did not specify a repository url');
            return;
        }
        // Check if the link ends with .git
        if ( !str_ends_with(get_option('mgd_repo_setting'), '.git') ) {
            $this->push_report_error('Invalid Repository URL', get_option('mgd_repo_setting'), 'The repository url does not end with .git');
            return;
        }


        chdir(MGD_MIRROR_PATH);

        $git = new MGD_GIT;
        if (!MGD_REMOTE_IS_CLONED) {
            try {
                $repo = $git->cloneRepository(get_option('mgd_repo_setting'), '.');
            } catch (\Throwable $th) {
                $this->push_report_error('Repository Error', get_option('mgd_repo_setting'), 'There is something wrong with your repository. Maybe the link is wrong or it is a private repository: ' . $th->getMessage());
            }
        } else {
            // Try to pull the repository multiple times if they fail.
            $pull_success = false;
            $break_out_counter = 0;

            while (!$pull_success) {
                try {
                    $repo = $git->open('.');
                    $repo->pull('origin');

                    $pull_success = true;
                } catch (\Throwable $th) {
                    $this->push_report_error('Repository Error No ' . $break_out_counter, get_option('mgd_repo_setting'), 'Something went wrong when pulling your repository, we will try again: ' . $th->getMessage());
                }
                $break_out_counter++;

                if ($break_out_counter > 3) {
                    $this->push_report_error('Repository Error', get_option('mgd_repo_setting'), 'The repo Error occured to many times [10]');
                    break;
                }
            }
        }


        // Get all Paths
        $paths = [];
        foreach (explode(',', get_option('mgd_glob_setting')) as $single_glob) {
            $paths = array_merge($paths, glob($single_glob));
        }

        // Resolve Articles
        foreach ($paths as $path) {
            $this->reports->found_posts++;

            $post_data = new \stdClass();

            $post_data->remote = $this->resolver($path);

            // Adding the last commit hash to the article
            $command = 'git log -n 1 --pretty=format:%H -- "' . $path . '"';
            $output = [];
            exec($command, $output);
            $post_data->remote->last_commit = $output[0] ?? '';

            if ( !$post_data->remote ) continue;

            if (!property_exists($post_data->remote, 'name')) {
                $this->push_report_error('Missing Name', $path, __('It seems like this post has no name.'));
                continue;
            }

            if (!property_exists($post_data->remote, 'raw_content')) {
                $this->push_report_error('Missing Content', $path, __('It seems like this post has no content.'));
            }

            $this->reports->valid_posts++;

            // Add the name as the slug in case its not defined
            if (!property_exists($post_data->remote, 'slug')) {
                $this->reports->coerced_slugs++;
                $post_data->remote->slug = Helpers::string_to_slug($post_data->remote->name);

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
                'posts_per_page'  =>  1,
                'post_type'       =>  ['post', 'page'],
            ))[0] ?? [];

            $this->articles[$key]->local = $localArticle;
            $this->articles[$key]->_is_published = !!$localArticle;
            $this->articles[$key]->last_updated = 0;

            if (!!$localArticle) {
                $this->articles[$key]->last_updated = (int) get_post_meta($localArticle->ID, 'mgd_last_updated', true) ?? '';

                $this->articles[$key]->local->last_commit = get_post_meta($localArticle->ID, 'mgd_local_last_commit', true) ?? '';
            }

            if (!!$localArticle) {
                $this->reports->published_posts++;

                // Add unreserved keys to the Article as a post Meta
                foreach ($article->remote as $key => $value) {
                    if (!in_array($key, $reserved_frontmatter_keys)) {
                        update_post_meta($localArticle->ID, $key, $value);
                    }
                }
            }

        }

        chdir(MGD_ROOT_PATH);
    }

    private function resolver(string $document_path) {

        if (!file_get_contents($document_path)) {
            $this->push_report_error('Empty File', $document_path, 'This file is empty but still matches the glob pattern.');
            return false;
        }

        switch (get_option('mgd_resolver_setting')) {
            case 'dir_cat': {
                return Resolvers::directory_category($document_path);
            }

            default; {
                $result = Resolvers::simple($document_path);

                if ('Symfony\Component\Yaml\Exception\ParseException' == get_class($result)) {
                    return false;
                }

                return $result;
            }
        }
    }

    public function get_all(): array {
        $this->check_if_parsed();

        return $this->articles;
    }

    function get_by_slug(string $slug): object {
        $this->check_if_parsed();

        return Helpers::array_nested_find($this->articles, function($obj) use (&$slug) {
            return $obj->remote->slug == $slug;
        });
    }

    public function get_by_id(int $id): object {
        $this->check_if_parsed();

        return Helpers::array_nested_find($this->articles, function($obj) use (&$id) {
            return ($obj->local->ID ?? -1) == $id;
        }) ?? (object) array(
            '_is_published' => false,
        );
    }

    public function get_outdated(): array {
        $all_posts = $this->get_all();
        $outdated = [];

        foreach ($all_posts as $post) {
            if ($post->local->last_commit != $post->remote->last_commit) {
                array_push($outdated, $post);
            }
        }

        return $outdated;
    }

    public function update_post(string $slug) {
        $this->check_if_parsed();

        $post_data = $this->get_by_slug($slug);

        Helpers::log(sprintf('Updating: %s', $post_data->remote->name));

        $markdown_config = array(
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        );

        // Configure the Environment with all the CommonMark parsers/renderers
        $environment = new Environment($markdown_config);
        $environment->addExtension(new CommonMarkCoreExtension());

        // Add this extension
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableExtension());

        // Instantiate the converter engine and start converting some Markdown!
        $converter = new MarkdownConverter($environment);


        $new_post_data = array(
            'post_title'     =>  $post_data->remote->name,
            'post_name'      =>  $post_data->remote->slug,
            'post_excerpt'   =>  $post_data->remote->description ?? '',
            'post_content'   =>  wp_kses_post($converter->convert($post_data->remote->raw_content)),
            'post_status'    =>  $post_data->remote->status ?? 'publish',
            'post_category'  =>  Helpers::create_categories($post_data->remote->category),
            'post_type'      =>  $post_data->remote->post_type ?? 'post',
            'tags_input'     =>  Helpers::coerce_to_array($post_data->remote->tags),
        );

        // Add the ID in case it is already published
        if ($post_data->_is_published) {
            $new_post_data['ID'] = $post_data->local->ID;
        }

        // Add parent page id via the parent_page key if it is a page, but use all posts not only the ones in the gitdown
        if ($new_post_data['post_type'] == 'page' && property_exists($post_data->remote, 'parent_page')) {
            $parent_page = get_posts(array(
                'name'            =>  $post_data->remote->parent_page,
                'post_status'     =>  ['draft', 'publish', 'trash'],
                'post_type'       =>  ['post', 'page'],
                'posts_per_page'  =>  1,
            ))[0] ?? [];

            if (!!$parent_page) {
                Helpers::log('Adding as child of: ' . $post_data->remote->parent_page);
                $new_post_data['post_parent'] = $parent_page->ID;
            } else {
                Helpers::log('Parent page not found: ' . $post_data->remote->parent_page);
            }
        }


        // Insert the post into the database
        try {
            $post_id = wp_insert_post($new_post_data);
            update_post_meta($post_id, 'mgd_last_updated', time());
            update_post_meta($post_id, 'mgd_local_last_commit', $post_data->remote->last_commit);
            $revision_id = wp_save_post_revision($post_id);
            Helpers::log('Post Revision: ' . print_r(json_encode($revision_id), true));
        } catch (\Throwable $th) {
            Helpers::log($th);
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
                'ID'              => $thumbnailId,
                'post_mime_type'  => wp_check_filetype($uploadPath, null)['type'],
                'post_title'      => $new_post_data['post_title'],
                'post_content'    => '',
                'post_status'     => 'inherit',
            );

            $attach_id = wp_insert_attachment($attachment_data, $uploadPath, $post_id);
            set_post_thumbnail($post_id, $attach_id);

            wp_generate_attachment_metadata($attach_id, $uploadPath);
        };


        // Return Post data so the frontend can process it
        return [
            'new_post' => get_post($post_id),
            'last_updated' => (int) get_post_meta($post_id, 'mgd_last_updated', true),
            'last_commit' => get_post_meta($post_id, 'mgd_local_last_commit', true),
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
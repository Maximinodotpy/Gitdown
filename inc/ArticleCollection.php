<?php
namespace WP\Plugin\Gitdown;
use Parsedown as GDParsedown;
use Mni\FrontYAML as GDFrontYaml;
use CzProject\GitPhp\Git as MGD_GIT;
use MGD_Helpers;

class MGD_ArticleCollection {
    public $articles = [];
    public $reports;

    public $source;
    public $glob;

    function __construct (string $source, string $glob) {
        $this->reports = (object) array(
            'published_posts' => 0,
            'found_posts' => 0,
            'valid_posts' => 0,
            'coerced_slugs' => 0,
            'errors' => array(),
        );

        $this->source = $source;
        $this->glob = $glob;
    }

    // This function checks if the articles have been parsed and if not does that.
    private function check_if_parsed() {
        if ($this->articles == []) $this->parse();
    }

    private function parse() {

        if (get_option(MGD_SETTING_GLOB) == '') {
            $this->pushReportError('No Glob specified', '.', 'You did not specify a glob pattern');
            return;
        } else if (get_option(MGD_SETTING_REPO) == '') {
            $this->pushReportError('No Repository URL', '.', 'You did not specify a repository url');
            return;
        }

        chdir(MGD_MIRROR_PATH);

        $git = new MGD_GIT;
        if (!MGD_REMOTE_IS_CLONED) {
            $repo = $git->cloneRepository(get_option(MGD_SETTING_REPO), '.');
        }
        else {
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
        foreach (explode(',', $this->glob) as $single_glob) {
            $paths = array_merge($paths, glob($single_glob));
        }

        // Resolve Articles
        foreach ($paths as $path) {
            $this->reports->found_posts++;
            
            // Creating the Std Object
            $post_data = new \stdClass();
            
            $post_data->remote = $this->resolver($path) ?? [];
            
            if (!$post_data) continue;

            // Check if Post is valid
            if (!property_exists($post_data->remote, 'name')) {
                $this->pushReportError('Missing Name', $path, 'the Front matter of this post shows now name property which is crucial for it to be published.');
                continue;
            }
            
            if (!property_exists($post_data->remote, 'raw_content')) {
                $this->pushReportError('No Content', $path, 'It seems like this post has no content.');
            }

            $this->reports->valid_posts++;

            // Add the name as the slug in case its not defined
            if (!property_exists($post_data->remote, 'slug')) {
                $post_data->remote->slug = MGD_Helpers::string_to_slug($post_data->remote->name);
                $this->reports->coerced_slugs++;

                $this->pushReportError('Coerced Slug', $path, 'This post does not define a slug so the name was turned into a slug. This is not advised.');
            }
            if (!property_exists($post_data->remote, 'tags')) {
                $post_data->remote->tags = [];

                $this->pushReportError('No Tags found', $path, 'This post does not define tags ...');
            }

            array_push($this->articles, $post_data);
        }

        // Merge Remote Articles with local articles if applicable
        foreach ($this->articles as $key => $article) {

            $localArticle = get_posts(array(
                'name'           => $article->remote->slug,
                'post_status' => ['draft', 'publish', 'trash'],
                'posts_per_page' => 1
            ))[0] ?? [];

            $this->articles[$key]->local = $localArticle;
            $this->articles[$key]->_is_published = !!$localArticle;

            if (!!$localArticle) {
                $this->reports->published_posts++;
            }
        }

        chdir(MGD_ROOT_PATH);
    }

    private function resolver(string $document_path) {

        $resolver_simple = function ($path) {

            if (!file_exists($path)) return;

            $fileContent = file_get_contents($path);

            $parser = new GDFrontYaml\Parser;
            $postData = [];

            try {
                $document = $parser->parse($fileContent, false);
            } catch (\Exception $e) {
                $this->pushReportError('YAML Error', $path, $e);
                return false;
            }

            $postData = $document->getYAML() ?? [];

            $postData['raw_content'] = $document->getContent();
            $postData['featured_image'] = dirname($path) . '/preview.png';

            return $postData;
        };

        
        $resolver_dir_cat = function ($path) use ($resolver_simple) {
            $post_data = $resolver_simple($path);

            $post_data['category'] = [dirname($path)];
            $post_data['name'] = [basename($path, '.md')];

            return $post_data;
        };


        $currentData = '';
        switch (get_option(MGD_SETTING_RESOLVER)) {
            case 'simple':
                $currentData = $resolver_simple($document_path);
                break;

            case 'dir_cat':
                $currentData = $resolver_dir_cat($document_path);
                break;

            default;
                $currentData = $resolver_simple($document_path);
                break;
        }

        return (object) $currentData;
    } 

    private function _array_nested_find($array, $function) {
        foreach ($array as $value) {
            if ($function($value)) return $value;
        }
    }

    public function get_all() {
        $this->check_if_parsed();

        return $this->articles;
    }

    function get_by_slug(string $slug) {
        $this->check_if_parsed();

        return $this->_array_nested_find($this->articles, function($obj) use (&$slug) {
            return $obj->remote->slug == $slug;
        });
    }

    public function get_by_id($id) {
        $this->check_if_parsed();

        return $this->_array_nested_find($this->articles, function($obj) use (&$id) {
            return ($obj->local->ID ?? -1) == $id;
        }) ?? (object) array(
            '_is_published' => false,
        );
    }

    public function updateArticle(string $slug)
    {
        $this->check_if_parsed();

        $post_data = $this->get_by_slug($slug);

        $Parsedown = new GDParsedown();

        $new_post_data = array(
            'post_title'    => $post_data->remote->name,
            'post_name'    => $post_data->remote->slug,
            'post_excerpt' => $post_data->remote->description ?? '',
            'post_content'  => wp_kses_post($Parsedown->text($post_data->remote->raw_content)),
            'post_status'   => $post_data->remote->status ?? 'publish',
            'post_category' => MGD_Helpers::create_categories($post_data->remote->category),
            'tags_input' => MGD_Helpers::coerce_to_array($post_data->remote->tags),
        );

        /* Add the ID in case it is already published */
        if ($post_data->_is_published) {
            $new_post_data['ID'] = $post_data->local->ID;
        }

        // Insert the post into the database
        try {
            $post_id = wp_insert_post($new_post_data);
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
        return get_post($post_id);
    }

    public function deleteArticle(string $slug)
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

    private function pushReportError($type, $location, $description) {
        array_push($this->reports->errors, (object) [
            'type' => $type,
            'location' => $location,
            'description' => $description,
        ]);
    }
}
<?php

class GD_ArticleCollection {
    public $articles = [];
    public $reports;

    // Logic is provided by index file
    public $logger;

    // TODO: Add source and glob in the constructor but only fetch the data once it is needed for performance.
    function __construct () {
        $this->reports = (object) array(
            'published_posts' => 0,
            'found_posts' => 0,
            'valid_posts' => 0,
            'coerced_slugs' => 0,
            'errors' => array(),
        );
    }

    function parseDirectory($source, $glob) {
        /* $remote_defaults = [
            'name' => null,
            'slug' => null,
            'description' => '',
            'thumbnail' => null,
            'content' => null,
            'raw_content' => null,
            'category' => [],
            'tags' => [],
            'status' => null,
        ]; */

        chdir($source);


        // Get all Paths
        $paths = [];
        foreach (explode(',', $glob) as $single_glob) {
            $paths = array_merge($paths, glob($single_glob));
        }


        // Resolve Articles
        foreach ($paths as $path) {
            $this->reports->found_posts++;

            // Creating the Std Object
            $post_data = new stdClass();

            $post_data->remote = $this->resolver($path) ?? [];


            // Add the name as the slug in case its not defined
            if (!property_exists($post_data->remote, 'slug')) {
                $post_data->remote->slug = gd_stringToSlug($post_data->remote->name);
                $this->reports->coerced_slugs++;
                array_push($this->reports->errors, 'Warning: Had to coerce slug for '.$post_data->remote->slug);
            }

            array_push($this->articles, $post_data);
        }


        // Merge Remote Articles with local articles if applicable

        // TODO Search for the article by slug immediately
        $localArticles = get_posts([
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        foreach ($this->articles as $key => $article) {
            
            $localArticle = $this->_array_nested_find($localArticles, function($obj) use (&$article) {
                return $obj->post_name == $article->remote->slug;
            });

            $this->articles[$key]->local = $localArticle ?? [];
            $this->articles[$key]->_is_published = !!$localArticle;

            if (!!$localArticle) {
                $this->reports->published_posts++;
            }
        }

        chdir(GD_ROOT_PATH);
    }

    private function resolver($document_path) {

        $resolver_simple = function ($path) {

            if (!file_exists($path)) return;

            $fileContent = file_get_contents($path);

            $parser = new Mni\FrontYAML\Parser;
            $postData = [];
            $document = $parser->parse($fileContent, false);

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
        switch (get_option(GD_SETTING_RESOLVER)) {
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

    function _array_nested_find($array, $function) {
        foreach ($array as $value) {
            if ($function($value)) return $value;
        }
    }

    function get_all() {
        return $this->articles;
    }

    /* function set_all($data) {
        $this->articles = $data;
    } */

    function get_by_slug($slug) {
        return $this->_array_nested_find($this->articles, function($obj) use (&$slug) {
            return $obj->remote->slug == $slug;
        });
    }

    function get_by_id($id) {
        return $this->_array_nested_find($this->articles, function($obj) use (&$id) {
            return ($obj->local->ID ?? -1) == $id;
        }) ?? (object) array(
            '_is_published' => false,
        );
    }

    public function updateArticle($slug)
    {
        $this->logger->info('Updating Post ...');

        $post_data = $this->get_by_slug($slug);

        $Parsedown = new Parsedown();

        $new_post_data = array(
            'post_title'    => $post_data->remote->name,
            'post_name'    => $post_data->remote->slug,
            'post_excerpt' => $post_data->remote->description ?? '',
            'post_content'  => wp_kses_post($Parsedown->text($post_data->remote->raw_content)),
            'post_status'   => $post_data->remote->status ?? 'publish',
            'post_category' => $this->createCategories($post_data->remote->category ?? []),
        );

        /* Add the ID in case it is already published */
        if ($post_data->_is_published) {
            $new_post_data['ID'] = $post_data->local->ID;
        }

        // Insert the post into the database
        $post_id = wp_insert_post($new_post_data);

        // Uploading the Image
        $imagePath = GD_MIRROR_PATH . $post_data->remote->featured_image;

        if (is_file($imagePath)) {
            $uploadPath = wp_upload_dir()['path'] . '/' . $new_post_data['post_name'] . '.png';

            copy($imagePath, $uploadPath);

            $thumbnailId = get_post_thumbnail_id($post_id);

            $attachment_data = array(
                'ID' => $thumbnailId,
                'post_mime_type' => wp_check_filetype($uploadPath, null)['type'],
                'post_title' => $new_post_data['post_title'],
                'post_content' => '',
                'post_status' => 'inherit',
            );

            $attach_id = wp_insert_attachment($attachment_data, $uploadPath, $post_id);
            set_post_thumbnail($post_id, $attach_id);
        };

        $this->logger->info('Post Updated');

        return get_post($post_id);
    }

    public function deleteArticle($slug)
    {
        $article = $this->get_by_slug($slug);

        if (!$article->_is_published) return;

        $post_id = $article->local->ID;

        // Remove Thumbnail Image
        wp_delete_attachment(get_post_thumbnail_id($post_id));

        // Remove the Post itself
        $result = wp_delete_post($post_id, true);

        $this->logger->info('Post Deleted', $result);

        // Returning the result so The Frontend knows it
        return !!$result;
    }

    public function createCategories($name_paths) {
        $this->logger->info(
            'Creating Categories: '.print_r($name_paths, true),
            $name_paths,
        );

        $returned_ids = [];

        $name_paths = is_array($name_paths) ? $name_paths : array($name_paths);

        foreach ($name_paths as $name_path) {
            $current_last_id = 0;

            foreach (explode('/', $name_path) as $single_cat) {
                if (!get_category_by_slug($single_cat)) {
                    $current_last_id = wp_insert_term($single_cat, 'category', [
                        'parent' => $current_last_id,
                    ])['term_id'];
                } else {
                    $current_last_id = get_category_by_slug($single_cat)->term_id;
                }
            }

            array_push($returned_ids, $current_last_id);
        }
        return $returned_ids;
    }
}
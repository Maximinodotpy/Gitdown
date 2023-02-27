<?php

class GD_ArticleCollection {
    public $articles = [];
    // Logic is provided by index file
    public $logger;

    function __construct () {

    }

    function parseDirectory($source, $glob) {
        $remote_defaults = [
            'name' => null,
            'slug' => null,
            'description' => '',
            'thumbnail' => null,
            'content' => null,
            'raw_content' => null,
            'category' => [],
            'tags' => [],
            'status' => null,
        ];

        chdir($source);

        // Get all Paths
        $paths = [];
        foreach (explode(',', $glob) as $single_glob) {
            $paths = array_merge($paths, glob($single_glob));
        }


        // Resolve Articles
        foreach ($paths as $path) {

            $postData = [];

            $postData[GD_REMOTE_KEY] = $this->resolver($path) ?? [];

            // Add the name as the slug in case its not defined
            if (!array_key_exists('slug', $postData[GD_REMOTE_KEY])) {
                $postData[GD_REMOTE_KEY]['slug'] = gd_stringToSlug($postData[GD_REMOTE_KEY]['name']);
            }

            array_push($this->articles, $postData);
        }


        // Merge Remote Articles with local articles if applicable

        // TODO Search for the article by slug immediately
        $localArticles = get_posts([
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        foreach ($this->articles as $key => $article) {
            
            $localArticle = $this->_array_nested_find($localArticles, function($obj) use (&$article) {
                return $obj->post_name == $article[GD_REMOTE_KEY]['slug'];
            });

            $this->articles[$key][GD_LOCAL_KEY] = json_decode(json_encode($localArticle), true) ?? [];
            $this->articles[$key]['_is_published'] = !!$localArticle;
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


        switch (get_option(GD_SETTING_RESOLVER)) {
            case 'simple':
                return $resolver_simple($document_path);
                break;

            case 'dir_cat':
                return $resolver_dir_cat($document_path);
                break;

            default;
                return $resolver_simple($document_path);
                break;
        }

    } 

    function _array_nested_find($array, $function) {
        foreach ($array as $value) {
            if ($function($value)) return $value;
        }
    }

    function get_all() {
        return $this->articles;
    }

    function set_all($data) {
        $this->articles = $data;
    }

    function get_by_slug($slug) {
        return $this->_array_nested_find($this->articles, function($obj) use (&$slug) {
            return $obj[GD_REMOTE_KEY]['slug'] == $slug;
        });
    }

    function get_by_id($id) {
        return $this->_array_nested_find($this->articles, function($obj) use (&$id) {
            return ($obj[GD_LOCAL_KEY]['ID'] ?? -1) == $id;
        }) ?? [
            '_is_published' => false,
        ];
    }

    public function updateArticle($slug)
    {
        $this->logger->info('Updating Post ...');

        $post_data = $this->get_by_slug($slug);

        $Parsedown = new Parsedown();

        $new_post_data = array(
            'post_title'    => $post_data[GD_REMOTE_KEY]['name'],
            'post_name'    => $post_data[GD_REMOTE_KEY]['slug'],
            'post_excerpt' => $post_data[GD_REMOTE_KEY]['description'] ?? '',
            'post_content'  => wp_kses_post($Parsedown->text($post_data[GD_REMOTE_KEY]['raw_content'])),
            'post_status'   => $post_data[GD_REMOTE_KEY]['status'] ?? 'publish',
            'post_category' => $this->createCategories($post_data[GD_REMOTE_KEY]['category'] ?? []),
        );

        /* Add the ID in case it is already published */
        if ($post_data['_is_published']) {
            $new_post_data['ID'] = $post_data[GD_LOCAL_KEY]['ID'];
        }

        // Insert the post into the database
        $post_id = wp_insert_post($new_post_data);

        // Uploading the Image
        $imagePath = GD_MIRROR_PATH . $post_data[GD_REMOTE_KEY]['featured_image'];

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

            // Regenerate Image Sizes for Thumbnail
            /* $editor = wp_get_image_editor($uploadPath);
            $this->logger->info('Editor', $editor);

            foreach (wp_get_registered_image_subsizes() as $key => $size) {
                $this->logger->info($key, $size);
            } */

            wp_create_image_subsizes( $uploadPath, $attach_id );
        };

        $this->logger->info('Post Updated');

        return get_post($post_id);
    }

    public function deleteArticle($slug)
    {
        $article = $this->get_by_slug($slug);

        if (!$article['_is_published']) return;

        $post_id = $article[GD_LOCAL_KEY]['ID'];

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

    /* public function regenerateThumbnail() {

    } */
}
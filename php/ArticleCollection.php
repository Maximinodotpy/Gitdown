<?php

class GD_ArticleCollection {
    public $articles = [];
    // Logic is provided by index file
    public $logger;

    function __construct () {

    }

    function parseDirectory($source, $glob, $resolver) {
        $remote_defaults = [
            'name' => null,
            'slug' => null,
            'description' => null,
            'thumbnail' => null,
            'content' => null,
            'raw_content' => null,
            'category' => null,
            'tags' => null,
            'status' => null,
        ];

        chdir($source);

        $paths = glob($glob);

        foreach ($paths as $path) {

            $postData = [];

            $postData[GD_REMOTE_KEY] = array_merge($remote_defaults, $resolver($path) ?? []);

            array_push($this->articles, $postData);
        }

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
            'post_excerpt' => $post_data[GD_REMOTE_KEY]['description'],
            'post_content'  => wp_kses_post($Parsedown->text($post_data[GD_REMOTE_KEY]['raw_content'])),
            'post_status'   => $post_data[GD_REMOTE_KEY]['status'] ?? 'publish',
            'post_category' => $this->createCategories($post_data[GD_REMOTE_KEY]['category']),
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

            // Using the WP Cli to regenerate the image sizes.
            $out = [];

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = GD_ROOT_PATH . 'php/vendor/wp-cli/wp-cli/bin/wp media regenerate ' . $attach_id . ' --only-missing > nul';
            } else {
                $command = GD_ROOT_PATH . 'php/vendor/wp-cli/wp-cli/bin/wp media regenerate ' . $attach_id . ' --only-missing > /dev/null &';
            }

            exec($command, $out);
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
}
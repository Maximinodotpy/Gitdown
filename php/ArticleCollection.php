<?php

class GD_ArticleCollection {
    public $articles = [];

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
            return $obj[GD_LOCAL_KEY]['ID'] ?? -1 == $id;
        }) ?? [
            '_is_published' => false,
        ];
    }
}
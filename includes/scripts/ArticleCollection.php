<?php


class GTWArticleCollection {
    public $articles = [];

    function __construct ($source, $glob, $resolver) {

        chdir($source);

        $paths = glob($glob);

        foreach ($paths as $path) {
            array_push($this->articles, $resolver($path));
        }

        $localArticles = get_posts([
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        foreach ($this->articles as $key => $article) {
            
            $localArticle = $this->array_nested_find($localArticles, function($obj) use (&$article) {
                return $obj->post_name == $article['slug'];
            });

            $this->articles[$key]['_is_published'] = !!$localArticle;
            $this->articles[$key]['_local_post_data'] = $localArticle ?? [];
        }

        chdir(GTW_ROOT_PATH);
    }

    function array_nested_find($array, $function) {
        foreach ($array as $value) {
            if ($function($value)) return $value;
        }
    }

    function get_all() {
        return $this->articles;
    }

    function get_by_slug($slug) {
        return $this->array_nested_find($this->articles, function($obj) use (&$slug) {
            return $obj['slug'] == $slug;
        });
    }

    function get_by_id($id) {}
}
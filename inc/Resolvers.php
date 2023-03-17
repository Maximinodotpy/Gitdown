<?php
/**
 * @package  Gitdown
 */
namespace Inc;

class Resolvers {
    public static function simple($path) {
        if (!file_exists($path)) return false;

        $file_content = file_get_contents($path);

        $parsed = Helpers::parse_markdown_with_frontmatter($file_content);

        $post_data = $parsed->frontmatter;
        $post_data->raw_content = $parsed->content;
        $post_data->featured_image = dirname($path) . '/preview.png';

        return $post_data;
    }

    public static function directory_category($path) {
        $post_data = Resolvers::simple($path);

        $post_data->category = [dirname($path)];
        $post_data->name = [basename($path, '.md')];

        return $post_data;
    }

    public static function get_all_resolvers() {
        return [
            'Simple' => array('Resolvers', 'simple'),
            'Directory to Category' => array('Resolvers', 'directory_category'),
        ];
    }
}

?>
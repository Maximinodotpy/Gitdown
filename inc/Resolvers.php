<?php
/**
 * @package  Gitdown
 */
namespace Inc;

class Resolvers {
    public static function simple($path) {
        if (!file_exists($path)) return false;

        $file_content = file_get_contents($path);

        try {
            $parsed = Helpers::parse_markdown_with_frontmatter($file_content);
        } catch (\Exception $e) {
            return $e;
        };

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
            'Simple' => [
                'slug' => 'simple',
                'callback' => array('Resolvers', 'simple'),
                'description' => '',
            ],
            'Directory to Category' => [
                'slug' => 'dir_cat',
                'callback' => array('Resolvers', 'directory_category'),
                'description' => '',
            ],
        ];
    }
}

?>
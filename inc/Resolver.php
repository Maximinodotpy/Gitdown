<?php
/**
 * @package  Gitdown
 */
namespace WP\Plugin\Gitdown;

class MGD_Resolvers {
    public static function simple($path) {
        if (!file_exists($path)) return false;

        $file_content = file_get_contents($path);

        $parsed = MGD_Helpers::parse_markdown_with_frontmatter($file_content);

        $post_data = $parsed->frontmatter;
        $post_data->raw_content = $parsed->content;
        $post_data->featured_image = dirname($path) . '/preview.png';

        return $post_data;
    }

    public static function directory_category($path) {
        $post_data = MGD_Resolvers::simple($path);

        $post_data->category = [dirname($path)];
        $post_data->name = [basename($path, '.md')];

        return $post_data;
    }

    public static function get_all_resolvers() {
        return [
            'Simple' => array('MGD_Resolvers', 'simple'),
            'Directory to Category' => array('MGD_Resolvers', 'directory_category'),
        ];
    }
}

?>
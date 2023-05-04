<?php
/**
 * @package  Gitdown
 */
namespace Inc;

use Mni\FrontYAML\Parser;

class Helpers {
    public static function truncate_string($string, $max, $after = ' ...') {
        if (strlen($string) >= $max) {
            return substr($string, 0, $max - strlen($after)).$after;
        }
        return $string;
    }

    public static function string_to_slug($string) {
        $string = str_replace(' ', '-', $string);
        $string = str_replace('.', '', $string);
        $string = str_replace(',', '', $string);
        $string = str_replace('(', '', $string);
        $string = str_replace(')', '', $string);
        $string = str_replace('/', '', $string);
        $string = str_replace("'", '', $string);
        $string = str_replace(":", '', $string);
        $string = strtolower($string);

        return $string;
    }

    public static function create_categories($name_paths = []) {
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

    public static function coerce_to_array($input) {
        return (is_array($input) ? $input : array($input));
    }

    public static function log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

    public static function array_nested_find($array, $function) {
        foreach ($array as $value) {
            if ($function($value)) return $value;
        }
    }

    public static function parse_markdown_with_frontmatter($content): object {
        $parser = new \Mni\FrontYAML\Parser;

        $parsed_document = $parser->parse($content, false);

        return (object) [
            "frontmatter" => (object) $parsed_document->getYAML(),
            "content" => $parsed_document->getContent(),
        ];
    }

    public static function delete_directory($path) {
        if (!is_dir($path)) {
            Helpers::log('Folder not found: '.$path);
            return;
        } else if (!str_starts_with( basename($path), 'mgd_' )) {
            Helpers::log('Folder not deletable: '.$path);
            return;
        };

        Helpers::log('Deleting Folder: '.$path);

        switch (PHP_OS) {
            case 'WINNT': {
                exec("rmdir \"$path\" /s /q");
            }
            case 'LINUX': {
                exec("rm -rf -f \"$path\"");
            }
        }
    }
}

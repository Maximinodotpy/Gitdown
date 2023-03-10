<?php

class MGD_Helpers {
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
}
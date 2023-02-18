<?php

function gd_stringToSlug($string) {
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

function gd_truncateString($string, $max, $after = ' ...') {
    if (strlen($string) >= $max) {
        return substr($string, 0, $max - strlen($after)).$after;
    }
    return $string;
}

function gd_dumpJSON($json) {
    $truncationLength = 100;

    echo '<ul class="rawlist">';

    foreach ($json as $key => $value) {

        echo '<li>';

        echo '<span>';

        if (is_object($value) || is_array($value)) {
            echo '<details><summary>';
            echo esc_html($key);
            echo '</summary>';
            gd_dumpJSON($value);
            echo '</details>';
        }
        else {
            
            if (is_string($value) && strlen($value) > $truncationLength) {

                echo esc_html($key).': ';
                echo '<details class="inline-block"><summary class="inline-block">';
                echo gd_truncateString(esc_html($value), $truncationLength, false);
                echo '</summary>';
                echo substr(esc_html($value), $truncationLength);
                echo '</details>';
            }

            else echo esc_html($key).': '.esc_html($value);
        }

        echo '</span>';

        echo '</li>';
    }

    echo '</ul>';
}
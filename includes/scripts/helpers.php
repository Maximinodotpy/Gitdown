<?php

function stringToSlug($string) {
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

function truncateString($string, $max, $after = ' ...') {
    if (strlen($string) >= $max) {
        return substr($string, 0, $max - strlen($after)).$after;
    }
    return $string;
}

function dumpJSON($json) {
    $truncationLength = 100;

    echo '<ul class="rawlist">';

    foreach ($json as $key => $value) {

        echo '<li>';

        echo '<span>';

        if (is_object($value) || is_array($value)) {
            echo '<details><summary>';
            echo $key;
            echo '</summary>';
            dumpJSON($value);
            echo '</details>';
        }
        else {
            
            if (is_string($value) && strlen($value) > $truncationLength) {

                echo $key.': ';
                echo '<details class="inline-block"><summary class="inline-block">';
                echo truncateString(esc_html($value), $truncationLength, false);
                echo '</summary>';
                echo substr(esc_html($value), $truncationLength);
                echo '</details>';
            }

            else echo $key.': '.esc_html($value);
        }

        echo '</span>';

        echo '</li>';
    }

    echo '</ul>';
}
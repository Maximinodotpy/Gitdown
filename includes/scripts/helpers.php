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

function truncateString($string, $max) {
    if (strlen($string) >= $max) {
        return substr($string, 0, $max - 3).' ...';
    }
    return $string;
}
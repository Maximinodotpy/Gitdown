<?php

function stringToSlug($string) {
    $string = str_replace(' ', '-', $string);
    $string = str_replace('.', '', $string);
    $string = strtolower($string);

    return $string;
}

function truncateString($string, $max) {
    if (strlen($string) >= $max) {
        return substr($string, 0, $max - 3).' ...';
    }
    return $string;
}

function getPostOnWordpress($slug) {
    foreach (get_posts() as $key => $value) {
        if ($value->post_name == $slug) {
            return $value;
        }
    }
    return false;
}

function getPostOnRemote($slug) {
    foreach (GTW_REMOTE_ARTICLES as $key => $value) {
        if ($value->post_name == $slug) {
            return $value;
        }
    }
}

/* function deleteFiles($dir)
{
    foreach(glob($dir . '/*') as $file){
        if(is_file($file)){
            unlink($file);
        }
    }
} */
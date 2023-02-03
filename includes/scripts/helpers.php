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

function deleteFiles($dir)
{
    // loop through the files one by one
    foreach(glob($dir . '/*') as $file){
        // check if is a file and not sub-directory
        if(is_file($file)){
            // delete file
            unlink($file);
        }
    }
}
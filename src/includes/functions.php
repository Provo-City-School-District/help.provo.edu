<?php

//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

//limit characters in a string
function limitChars($string, $limit)
{
    if (strlen($string) > $limit) {
        $string = substr($string, 0, $limit) . '...';
    }
    return $string;
}
//simple validator that can be used for user input
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
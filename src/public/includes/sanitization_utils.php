<?php
require_once(from_root('/../vendor/autoload.php'));

function sanitize_html(string $data)
{
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($data);
}
?>
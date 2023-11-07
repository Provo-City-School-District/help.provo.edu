<?php

function from_root(string $path)
{
    return $_SERVER["DOCUMENT_ROOT"] . "/$path";
}
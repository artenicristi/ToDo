<?php

namespace Carteni\ToDo2;

class Filesystem
{

    function exists(string $path): bool
    {
        return file_exists($path);
    }

    function get(string $path): ?string
    {
        $content = file_get_contents($path);
        if (!$content) {
            return null;
        }
        return $content;
    }

    function put(string $path, string $content): bool
    {
        return (bool) file_put_contents($path, $content);
    }
}
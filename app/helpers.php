<?php

if (! function_exists('fixture')) {
    function fixture(string $path): string
    {
        $path = base_path("tests/Fixtures/{$path}");

        return file_get_contents($path);
    }
}

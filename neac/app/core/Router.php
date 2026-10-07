<?php

class Router
{
    public static function view(string $path, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../../views/' . $path . '.php';
    }
}

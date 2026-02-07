<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        ob_start();
        $viewPath = BASE_PATH . '/src/Views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        }
        $content = ob_get_clean();

        require BASE_PATH . '/src/Views/layouts/' . $layout . '.php';
    }

    public static function partial(string $partial, array $data = []): void
    {
        extract($data);
        $partialPath = BASE_PATH . '/src/Views/partials/' . $partial . '.php';
        if (file_exists($partialPath)) {
            require $partialPath;
        }
    }
}

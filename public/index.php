<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

$router = new App\Core\Router();
require BASE_PATH . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

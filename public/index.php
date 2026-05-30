<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

$bootstrap = require __DIR__ . '/../bootstrap.php';
$container = $bootstrap['container'];
$router = $bootstrap['router'];

$container->set(Response::class, static fn () => new Response());
$container->set(View::class, static fn () => new View());
$container->set(Session::class, static function () {
    $session = new Session();
    $session->start();
    return $session;
});

$request = new Request();
$router->dispatch($request);

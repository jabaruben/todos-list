<?php

require __DIR__ . '/vendor/autoload.php';

use Config\Config; 
use TodosList\Infraestructure\Router;
use TodosList\Infraestructure\Bootstrap;
use TodosList\Infraestructure\Cors;
use TodosList\Infraestructure\Middleware;
use TodosList\Infraestructure\Database\Mysql\Mysql;

Cors::cors();

error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", Config::DISPLAY_ERRORS);


try {
    $app = new Bootstrap(new Router(), new Middleware(), new Config());
    $app->setBasePath(Config::ROUTER_BASE_PATH);
    $app->setRoutesFile(Config::CONFIG_ROUTES_PATH);
    $app->loadAndMatchRoutes();
    if($app->matchRoute()) {
        $db = new Mysql(Config::HOST, Config::NAME, Config::USER, Config::PASSWORD);
        $app->addMiddlewareService("db", $db);
    }
    $app->run();
    die();
} catch (Exception $e) {
    echo "{$e->getMessage()}";
}

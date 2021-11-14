<?php

// use Psr\Http\Message\ResponseInterface as Response;
// use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use src\Infraestructure\HTTP\ServerRequest;
use src\Infraestructure\HTTP\Response;

return [
    "routes" => [
        "private" => [
            // HTTP_METHOD, ROUTE, TARGET or CONTROLLER:METHOD, NAME, PATHTOFOLDER, CALLBACK_FUNCTION(Request, Response, $bootstrapConfig, Bootstrap)
        ],
        "public" => [
            // HTTP_METHOD, ROUTE, TARGET or CONTROLLER:METHOD, NAME, PATHTOFOLDER, CALLBACK_FUNCTION(Request, Response, $bootstrapConfig, Bootstrap)
            ['GET', '/todos', 'ToDos:list', 'ListToDos', 'ToDos'],
            ['POST', '/todos', 'ToDos:add', 'AddToDos', 'ToDos'],
            ['DELETE', '/todos/[i:id]', 'ToDos:delete', 'DeleteToDos', 'ToDos'],
        ],
    ],
    "errors" => [
        "404" => ["controller" => "Errors", "method" => "error404", "target" => "404", "params" => ["controller" => "Errors", "method" => "404"], "name" => "404"],
    ],
];

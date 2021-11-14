<?php

namespace src\Domain;

use TodosList\Infraestructure\Bootstrap;
use src\Infraestructure\HTTP\Response;
use src\Infraestructure\HTTP\ServerRequest;

class Errors
{
    public function error404(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        return $res->withJson(["error" => "404"], 404);
    }
}
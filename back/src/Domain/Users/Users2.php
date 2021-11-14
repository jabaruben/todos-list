<?php

namespace src\Domain;

use TodosList\Infraestructure\Bootstrap;
use src\Infraestructure\HTTP\Response;
use src\Infraestructure\HTTP\ServerRequest;

class Users2
{
    public function list(Bootstrap $app, Response $res, ServerRequest $req): Response
    {
        return $res->withJson(["test" => "testing"]);
    }
}

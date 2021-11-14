<?php

/**
 * https://github.com/jabaruben/http-message-implementation
 */

declare(strict_types=1);

namespace src\Infraestructure\HTTP;

use src\Infraestructure\HTTP\Response;

final class JsonResponse
{
    public static function withJson(
        Response $response,
        string $data,
        int $status = 200
    ): Response {
        $response->getBody()->write($data);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    public static function withArray(
        Response $response,
        array $data,
        int $status = 200
    ): Response {
        if (!is_array($data))
            throw new \Exception("No se ha recibido array");
            
        $response->getBody()->write(json_encode($data));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}

<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class MaintenanceMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (true)  {
            $response = new Response();
            $message["error"] = true;
            $message["message"] = "Server under maintenance";
            $message["code"] = "Z101-Maintenance";

            $response->getBody()->write(json_encode($message));

            return $response;
        } else {
            return $handler->handle($request); // will return usual response
        }
    }
}
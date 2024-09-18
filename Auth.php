<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class BearerTokenMiddleware
{
    private $validToken;

    public function __construct()
    {
        $this->validToken = "*B@AGNBGAGBV7896VFG098A";
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader[0]);
            if ($token === $this->validToken) {
                return $handler->handle($request);
            }
        }

        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}

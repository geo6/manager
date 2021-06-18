<?php

declare(strict_types=1);

namespace API\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfigHandler implements RequestHandlerInterface
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        unset($this->config['connection']);
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $key = $request->getAttribute('key');

        if (!is_null($key)) {
            if (!isset($this->config[$key])) {
                return new TextResponse(sprintf('"%s" is not a valid configuration key.'), 500);
            }

            return new JsonResponse($this->config[$key]);
        } else {
            return new JsonResponse($this->config);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\ConfigAggregator\ZendConfigProvider;
use Zend\ConfigAggregator\ConfigAggregator;

class HomePageHandler implements RequestHandlerInterface
{
    /** @var string */
    const DIRECTORY = 'config/application';

    /** @var TemplateRendererInterface */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(
            'app::home',
            [
                'settings' => [
                    'public' => self::getPublic(),
                    'roles' => [
                        'test' => [],
                    ],
                    'users' => [
                        'test' => [],
                    ]
                ]
            ]
        ));
    }

    private static function getPublic(): array
    {
        $glob = glob(self::DIRECTORY . '/public/*', GLOB_ONLYDIR);

        $list = array_map(function ($path) {
            return self::extract($path);
        }, $glob);

        return $list;
    }

    private static function extract(string $path): array
    {

        $config = (new ConfigAggregator(
            [
                new ZendConfigProvider($path . '/*.{php,ini,xml,json,yaml}'),
                function () use ($path) {
                    return ['id' => basename($path)];
                }
            ]
        ))->getMergedConfig();

        return $config;
    }
}

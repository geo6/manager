<?php

declare(strict_types=1);

namespace App\Middleware;

use Exception;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\LaminasConfigProvider;
use Laminas\ConfigAggregator\PhpFileProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfigMiddleware implements MiddlewareInterface
{
    public const CONFIG_ATTRIBUTE = 'config';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $request->getAttribute('config');

        $projects = [
            'public' => array_map(function (string $path) {
                return basename($path);
            }, glob('config/application/public/*')),
            'roles' => array_map(function (string $path) {
                return basename($path);
            }, glob('config/application/roles/*/*')),
            'users' => array_map(function (string $path) {
                return basename($path);
            }, glob('config/application/users/*/*')),
        ];

        $data = [
            'global'   => self::getGlobalConfig(),
            'custom'   => $config,
            'config'   => null,
        ];

        if (isset($config) && strlen($config) > 0 && $config !== 'new') {
            $public = in_array($config, $projects['public'], true);
            $roles = in_array($config, $projects['roles'], true);
            $users = in_array($config, $projects['users'], true);

            if (!$public && !$roles && !$users) {
                throw new Exception(sprintf('Unable to find configuration file for "%s".', $config));
            }

            if (!($public xor $roles xor $users)) {
                throw new Exception(sprintf('Multiple configuration files found for "%s".', $config));
            }

            $data['custom'] = $config;
            $data['config'] = self::getCustomConfig($config);
        }

        return $handler->handle($request->withAttribute(self::CONFIG_ATTRIBUTE, $data));
    }

    private static function getGlobalConfig(): array
    {
        return (new ConfigAggregator([
            new PhpFileProvider('config/config.php'),
            new LaminasConfigProvider('config/application/*.{php,ini,xml,json,yaml}'),
        ]))->getMergedConfig();
    }

    private static function getCustomConfig(string $custom): array
    {
        $glob = array_merge(
            glob('config/application/public/*'),
            glob('config/application/{roles,users}/*/*', GLOB_BRACE)
        );

        $directory = array_values(array_filter($glob, function ($directory) use ($custom) {
            return basename($directory) === $custom;
        }));

        if (count($directory) === 0) {
            throw new Exception(sprintf('Unable to find configuration file for "%s".', $custom));
        }

        return (new ConfigAggregator([
            new LaminasConfigProvider($directory[0] . '/*.{php,ini,xml,json,yaml}'),
        ]))->getMergedConfig();
    }
}

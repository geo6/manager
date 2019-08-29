<?php

declare(strict_types=1);

namespace App\Model;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as FlysystemFilesystem;

class Filesystem extends FlysystemFilesystem
{
    public function __construct(array $config, int $cursor = 0)
    {
        $mode = array_keys($config)[$cursor];

        switch ($mode) {
            case 'local':
                $adapter = self::setLocalAdapter($config[$mode]);
                parent::__construct($adapter, $config[$mode]['config'] ?? []);
                break;

            default:
                throw new Exception(sprintf('Invalid Filesystem adapter (%s).', $mode));
        }
    }

    private static function setLocalAdapter(array $config): Local
    {
        $path = $config['path'] ?? null;

        if (is_null($path)) {
            throw new Exception('Missing parameter `path` for Filesystem Local adapter.');
        }
        if (!file_exists($path) || !is_dir($path)) {
            throw new Exception(
                sprintf('Parameter `path` for Filesystem Local adapter is invalid (%s).', $path)
            );
        }

        return new Local($path);
    }
}

<?php

declare(strict_types=1);

namespace App\Twig;

use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class FileExtensionFactory
{
    public function __invoke(ContainerInterface $container): FileExtension
    {
        return new FileExtension(
            $container->get(UrlHelper::class)
        );
    }
}

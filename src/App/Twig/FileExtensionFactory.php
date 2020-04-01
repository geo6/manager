<?php

declare(strict_types=1);

namespace App\Twig;

use Psr\Container\ContainerInterface;
use Mezzio\Helper\UrlHelper;

class FileExtensionFactory
{
    public function __invoke(ContainerInterface $container): FileExtension
    {
        return new FileExtension(
            $container->get(UrlHelper::class)
        );
    }
}

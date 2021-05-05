<?php

declare(strict_types=1);

namespace App\Twig;

use API\Handler\FileHandler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThumbnailExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('thumbnail', [$this, 'thumbnail'], ['is_safe' => ['html']]),
        ];
    }

    public function thumbnail($path): string
    {
        $realpath = sprintf('%s/%s', FileHandler::DIRECTORY, $path);

        $output = '<td class="text-nowrap">';

        if (is_null($path)) {
            $output .= ValueExtension::null();
        } elseif (strlen($path) === 0) {
            $output .= '';
        } elseif (!file_exists($realpath) || !is_readable($realpath)) {
            $output .= '<span class="text-muted">';
            $output .= '<i class="far fa-file-image"></i> ';
            $output .= basename($realpath);
            $output .= '</span>';
        } else {
            $output .= '<a href="" class="text-decoration-none thumbnail-link">';
            $output .= '<i class="far fa-file-image"></i> ';
            $output .= basename($realpath);
            $output .= '</a>';
        }

        $output .= '</td>';

        return $output;
    }
}

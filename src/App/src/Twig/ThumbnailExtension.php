<?php

declare(strict_types=1);

namespace App\Twig;

use API\Handler\File\ThumbnailHandler;
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

    public function thumbnail(int $id, string $column, ?string $value): string
    {
        $realpath = sprintf('%s/%s', ThumbnailHandler::DIRECTORY, $value);

        $output = '<td class="text-nowrap">';

        if (is_null($value)) {
            $output .= ValueExtension::null();
        } elseif (strlen($value) === 0) {
            $output .= '';
        } elseif (!file_exists($realpath) || !is_readable($realpath)) {
            $output .= '<span class="text-muted">';
            $output .= '<i class="far fa-file-image"></i> ';
            $output .= basename($realpath);
            $output .= '</span>';
        } else {
            $url = sprintf('/api/file/thumbnail/%d/%s', $id, $column);

            $output .= '<a href="'.$url.'" class="text-decoration-none thumbnail-link">';
            $output .= '<i class="far fa-file-image"></i> ';
            $output .= basename($realpath);
            $output .= '</a>';
        }

        $output .= '</td>';

        return $output;
    }
}

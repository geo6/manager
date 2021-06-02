<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UploadExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('upload', [$this, 'upload'], ['is_safe' => ['html']]),
        ];
    }

    public function upload(string $name, ?string $accept): string
    {
        $output = '<div>';
        $output .= sprintf('<input type="file" class="filepond" name="%s"', $name);
        $output .= !is_null($accept) ? sprintf(' accept="%s"', $accept) : '';
        $output .= '>';
        $output .= '</div>';

        return $output;
    }
}

<?php

declare(strict_types=1);

return [
    'monolog' => [
        'stream' => [
            'path' => 'data/log/error.log',
        ],
        'sentry' => [
            'dsn' => 'https://b8874217a9ef42ca96c05c1ec3b28bfd@sentry.io/1532415',
        ],
    ],
];

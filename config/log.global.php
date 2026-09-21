<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Logger\LoggerInterface;
use ExtendsSoftware\ExaPHP\Logger\Writer\File\FileWriter;

return [
    LoggerInterface::class => [
        'writers' => [
            [
                'name' => FileWriter::class,
                'options' => [
                    'location' => getenv('APP_LOG_DIRECTORY') ?: __DIR__ . '/../data/logs',
                ],
            ],
        ],
    ],
];

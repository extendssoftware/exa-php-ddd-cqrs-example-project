<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application;

use ExtendsSoftware\ExaPHP\Application\ApplicationBuilder;
use ExtendsSoftware\ExaPHP\Application\ApplicationBuilderException;
use ExtendsSoftware\ExaPHP\Application\ApplicationInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorException;
use ExtendsSoftware\ExaPHPExample\Task\TaskModule;

use function filter_var;
use function getenv;

use const FILTER_VALIDATE_BOOLEAN;

final readonly class ApplicationFactory
{
    /**
     * @throws ServiceLocatorException When the application or its dependencies cannot be resolved.
     * @throws ApplicationBuilderException When the application configuration cannot be loaded or merged.
     */
    public function create(string $projectRoot): ApplicationInterface
    {
        $configDirectory = getenv('APP_CONFIG_DIRECTORY') ?: $projectRoot . '/config';
        $configFilePattern = '/\.(global|local)\.php$/';
        $cacheDirectory = getenv('APP_CACHE_DIRECTORY') ?: $projectRoot . '/data/cache';
        $cacheEnabled = filter_var(getenv('APP_CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN);
        $modules = [
            new ApplicationModule(),
            new TaskModule(),
        ];

        return new ApplicationBuilder()
            ->addGlobalConfigDirectory($configDirectory, $configFilePattern)
            ->setCacheLocation($cacheDirectory)
            ->setCacheEnabled($cacheEnabled)
            ->addModule(...$modules)
            ->build();
    }
}

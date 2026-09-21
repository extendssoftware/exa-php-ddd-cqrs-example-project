<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application;

use ExtendsSoftware\ExaPHP\Application\Module\ModuleInterface;
use ExtendsSoftware\ExaPHP\Application\Module\Provider\ConfigProviderInterface;
use ExtendsSoftware\ExaPHP\Utility\Loader\File\FileLoader;
use ExtendsSoftware\ExaPHP\Utility\Loader\LoaderInterface;

class ApplicationModule implements ModuleInterface, ConfigProviderInterface
{
    public function getConfig(): LoaderInterface
    {
        return new FileLoader()->addPath(__DIR__ . '/../config/', '/\.config\.php$/');
    }
}

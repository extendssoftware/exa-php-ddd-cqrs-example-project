<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests;

use ExtendsSoftware\ExaPHPExample\Application\ApplicationFactory;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;

#[Group('integration')]
final class ApplicationFactoryTest extends TestCase
{
    #[Test]
    public function createsApplicationWithApplicationModule(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $application = new ApplicationFactory()->create($projectRoot);

        self::assertCount(1, $application->getModules());
        self::assertInstanceOf(ApplicationModule::class, $application->getModules()[0]);
    }
}

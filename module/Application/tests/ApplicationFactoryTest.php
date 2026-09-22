<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests;

use ExtendsSoftware\ExaPHPExample\Application\ApplicationFactory;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;
use ExtendsSoftware\ExaPHPExample\Task\TaskModule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function dirname;

#[Group('integration')]
final class ApplicationFactoryTest extends TestCase
{
    #[Test]
    public function createsApplicationWithApplicationAndTaskModules(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $application = new ApplicationFactory()->create($projectRoot);

        self::assertSame(
            [ApplicationModule::class, TaskModule::class],
            array_map(
                static fn(object $module): string => $module::class,
                $application->getModules(),
            ),
        );
    }
}

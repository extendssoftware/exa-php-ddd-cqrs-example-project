<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Support;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Closure\ClosureResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorFactory;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHP\Utility\Merger\Merger;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock\FrozenClock;
use ExtendsSoftware\ExaPHPExample\Shared\SharedModule;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\TaskModule;
use PDO;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function str_replace;

abstract class TaskCommandIntegrationTestCase extends TestCase
{
    protected const string ID = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

    protected PDO $pdo;
    protected ServiceLocatorInterface $services;
    protected TaskRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../../../../config/pdo.local.php.dist';
        foreach ([new ApplicationModule(), new SharedModule(), new TaskModule()] as $module) {
            foreach ($module->getConfig()->load() as $loaded) {
                $config = new Merger()->merge($config, $loaded);
            }
        }
        unset($config[ServiceLocatorInterface::class][ReflectionResolver::class][ClockInterface::class]);
        $config[ServiceLocatorInterface::class][ClosureResolver::class][ClockInterface::class] =
            static fn(): FrozenClock => new FrozenClock(new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $this->services = new ServiceLocatorFactory()->create($config);
        $this->pdo = $this->services->getService(PDO::class);
        foreach (['Task', 'Shared'] as $module) {
            $schema = file_get_contents(__DIR__ . '/../../../' . $module . '/resources/database/schema.sql');
            $this->pdo->exec(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $schema));
        }
        $this->repository = $this->services->getService(TaskRepositoryInterface::class);
    }

    protected function seed(TaskStatus $status): TaskState
    {
        $state = new TaskState(
            TaskId::fromString(self::ID),
            TaskTitle::fromString('Original task'),
            $status,
            new DateTimeImmutable('2020-01-01T00:00:00Z'),
            $status === TaskStatus::Completed ? new DateTimeImmutable('2026-09-28T12:00:00Z') : null,
        );
        $this->repository->add(Task::reconstitute($state));

        return $state;
    }

}

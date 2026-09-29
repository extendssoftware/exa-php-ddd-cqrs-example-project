<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\CreateTask;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorFactory;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Closure\ClosureResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHP\Utility\Merger\Merger;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock\FrozenClock;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\PdoOutbox;
use ExtendsSoftware\ExaPHPExample\Shared\SharedModule;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository\PdoTaskRepository;
use ExtendsSoftware\ExaPHPExample\Task\TaskModule;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function json_decode;
use function str_replace;

#[Group('integration')]
final class CreateTaskHandlerIntegrationTest extends TestCase
{
    private const string TASK_ID = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

    private PDO $pdo;
    private CreateTaskHandler $handler;
    private GetTaskHandler $queryHandler;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../../../../../../config/pdo.local.php.dist';
        foreach ([new ApplicationModule(), new SharedModule(), new TaskModule()] as $module) {
            foreach ($module->getConfig()->load() as $loaded) {
                $config = new Merger()->merge($config, $loaded);
            }
        }
        unset($config[ServiceLocatorInterface::class][ReflectionResolver::class][ClockInterface::class]);
        $config[ServiceLocatorInterface::class][ClosureResolver::class][ClockInterface::class] =
            static fn(): FrozenClock => new FrozenClock(new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $services = new ServiceLocatorFactory()->create($config);
        $this->pdo = $services->getService(PDO::class);
        $outbox = $services->getService(OutboxInterface::class);
        self::assertInstanceOf(PdoOutbox::class, $outbox);
        foreach (['Task', 'Shared'] as $module) {
            $schema = file_get_contents(__DIR__ . '/../../../../../' . $module . '/resources/database/schema.sql');
            $this->pdo->exec(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $schema));
        }
        self::assertInstanceOf(PdoTaskRepository::class, $services->getService(TaskRepositoryInterface::class));
        $this->handler = $services->getService(CreateTaskHandler::class);
        $this->queryHandler = $services->getService(GetTaskHandler::class);
    }

    #[Test]
    public function invokeCommitsTaskAndSerializedEventTogether(): void
    {
        ($this->handler)(new CreateTask(self::TASK_ID, 'Example task'));

        self::assertFalse($this->pdo->inTransaction());
        $task = new PdoTaskRepository($this->pdo)->find(TaskId::fromString(self::TASK_ID));
        self::assertNotNull($task);
        $rows = $this->pdo->query('SELECT event_type, event_version, payload FROM outbox')->fetchAll();
        self::assertCount(1, $rows);
        self::assertSame('task.created', $rows[0]['event_type']);
        self::assertSame(1, $rows[0]['event_version']);
        self::assertEquals([
            'taskId' => self::TASK_ID,
            'title' => 'Example task',
            'createdAt' => '2026-09-29T12:00:00.123456Z',
        ], json_decode($rows[0]['payload'], true));
        self::assertSame('2026-09-29 12:00:00.123456', $task->state()->createdAt->format('Y-m-d H:i:s.u'));
        $result = ($this->queryHandler)(new GetTask(self::TASK_ID));
        self::assertSame(self::TASK_ID, $result->taskId);
        self::assertSame('Example task', $result->title);
        self::assertEquals($task->state()->createdAt, $result->createdAt);
        self::assertSame(1, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
    }

    #[Test]
    public function invokeRollsBackTaskWhenOutboxWriteFails(): void
    {
        $this->pdo->exec('ALTER TABLE outbox MODIFY event_type VARCHAR(1) NOT NULL');
        $this->expectException(PDOException::class);

        try {
            ($this->handler)(new CreateTask(self::TASK_ID, 'Example task'));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM task')->fetchColumn());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }

    #[Test]
    public function invokeDoesNotAppendEventWhenTaskAlreadyExists(): void
    {
        ($this->handler)(new CreateTask(self::TASK_ID, 'Original task'));
        $this->expectException(TaskAlreadyExists::class);

        try {
            ($this->handler)(new CreateTask(self::TASK_ID, 'Duplicate task'));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(1, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
            self::assertSame('Original task', $this->pdo->query('SELECT title FROM task')->fetchColumn());
        }
    }
}

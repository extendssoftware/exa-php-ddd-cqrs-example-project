<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Factory\Exception\ServiceCreateFailed;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorFactory;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHP\Utility\Merger\Merger;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerRegistry;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\SerializedEvent;
use ExtendsSoftware\ExaPHPExample\Shared\SharedModule;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class EventSerializerRegistryFactoryIntegrationTest extends TestCase
{
    #[Test]
    public function mergesModuleRegistrationsAndResolvesSerializerDependencies(): void
    {
        $first = new readonly class implements DomainEventInterface {};
        $second = new readonly class implements DomainEventInterface {};
        $serializer = new readonly class implements EventSerializerInterface {
            public function serialize(DomainEventInterface $event): SerializedEvent
            {
                return new SerializedEvent($event::class, 1, '{}');
            }
        };
        $config = $this->sharedConfig();
        foreach (['FirstModule' => $first::class, 'SecondModule' => $second::class] as $module => $event) {
            $config = new Merger()->merge($config, [
                EventSerializerRegistry::class => [$module => [$event => $serializer::class]],
                ServiceLocatorInterface::class => [
                    ReflectionResolver::class => [$serializer::class => $serializer::class],
                ],
            ]);
        }
        $services = new ServiceLocatorFactory()->create($config);
        $registry = $services->getService(EventSerializerInterface::class);

        self::assertInstanceOf(EventSerializerRegistry::class, $registry);
        self::assertSame($registry, $services->getService(EventSerializerInterface::class));
        self::assertSame($first::class, $registry->serialize($first)->type);
        self::assertSame($second::class, $registry->serialize($second)->type);
    }

    #[Test]
    public function rejectsDuplicateEventMappingsAfterModuleConfigurationMerge(): void
    {
        $event = new readonly class implements DomainEventInterface {};
        $serializer = new readonly class implements EventSerializerInterface {
            public function serialize(DomainEventInterface $event): SerializedEvent
            {
                return new SerializedEvent('unused', 1, '{}');
            }
        };
        $config = $this->sharedConfig();
        foreach (['FirstModule', 'SecondModule'] as $module) {
            $config = new Merger()->merge($config, [
                EventSerializerRegistry::class => [$module => [$event::class => $serializer::class]],
            ]);
        }
        $services = new ServiceLocatorFactory()->create($config);

        try {
            $services->getService(EventSerializerInterface::class);
            self::fail('Duplicate event registrations must be rejected.');
        } catch (ServiceCreateFailed $exception) {
            self::assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            self::assertStringContainsString('Duplicate serializer registration', $exception->getPrevious()->getMessage());
        }
    }

    #[Test]
    public function emptyRegistryRejectsUnsupportedEvents(): void
    {
        $services = new ServiceLocatorFactory()->create($this->sharedConfig());
        $registry = $services->getService(EventSerializerInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $registry->serialize(new readonly class implements DomainEventInterface {});
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedConfig(): array
    {
        $config = [];
        foreach (new SharedModule()->getConfig()->load() as $loaded) {
            $config = new Merger()->merge($config, $loaded);
        }

        return $config;
    }
}

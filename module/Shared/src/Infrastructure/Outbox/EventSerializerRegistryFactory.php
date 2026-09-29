<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Factory\ServiceFactoryInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorException;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use InvalidArgumentException;

use function is_a;
use function is_array;
use function is_string;

final readonly class EventSerializerRegistryFactory implements ServiceFactoryInterface
{
    /**
     * @throws InvalidArgumentException When mappings are invalid, duplicated across modules, or reference the registry
     *                                  itself.
     * @throws ServiceLocatorException When a configured serializer cannot be resolved.
     */
    public function createService(
        string $class,
        ServiceLocatorInterface $serviceLocator,
        ?array $extra = null,
    ): EventSerializerRegistry {
        $modules = $serviceLocator
            ->getContainer()
            ->find(EventSerializerRegistry::class, []);
        if (!is_array($modules)) {
            throw new InvalidArgumentException('Event serializer configuration must contain module mappings.');
        }

        $mappings = [];
        foreach ($modules as $events) {
            if (!is_array($events)) {
                throw new InvalidArgumentException('Each module must provide an event-to-serializer mapping.');
            }

            foreach ($events as $event => $serializer) {
                if (!is_string($event) || !is_a($event, DomainEventInterface::class, true)
                    || !is_string($serializer) || !is_a($serializer, EventSerializerInterface::class, true)
                    || $serializer === EventSerializerInterface::class || $serializer === EventSerializerRegistry::class) {
                    throw new InvalidArgumentException('Invalid event serializer mapping.');
                }

                if (isset($mappings[$event])) {
                    throw new InvalidArgumentException('Duplicate serializer registration for event: ' . $event);
                }

                $mappings[$event] = $serializer;
            }
        }

        $serializers = [];
        foreach ($mappings as $event => $serializerClass) {
            $serializer = $serviceLocator->getService($serializerClass);
            if (!$serializer instanceof EventSerializerInterface || $serializer instanceof EventSerializerRegistry) {
                throw new InvalidArgumentException('Configured service must be an event serializer, not a registry.');
            }

            $serializers[$event] = $serializer;
        }

        return new EventSerializerRegistry($serializers);
    }
}

<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Authorization;

use ExtendsSoftware\ExaPHP\Authorization\Permission\Permission;
use ExtendsSoftware\ExaPHP\Authorization\Permission\PermissionInterface;
use ExtendsSoftware\ExaPHP\Authorization\Realm\RealmInterface;
use ExtendsSoftware\ExaPHP\Identity\IdentityInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\StaticFactory\StaticFactoryInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;

use function array_map;

final readonly class PublicPermissionRealm implements RealmInterface, StaticFactoryInterface
{
    /**
     * @param list<PermissionInterface> $permissions Permissions granted to every identity, including guests.
     */
    public function __construct(private array $permissions) {}

    public static function factory(string $key, ServiceLocatorInterface $serviceLocator, ?array $extra = null): self
    {
        return new self(
            array_map(
                static fn(string $notation): Permission => new Permission($notation),
                $extra['permissions'] ?? [],
            ),
        );
    }

    /**
     * @return list<PermissionInterface>
     */
    public function getPermissions(IdentityInterface $identity): array
    {
        return $this->permissions;
    }
}

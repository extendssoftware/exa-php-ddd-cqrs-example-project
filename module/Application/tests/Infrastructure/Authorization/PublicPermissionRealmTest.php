<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests\Infrastructure\Authorization;

use ExtendsSoftware\ExaPHP\Authorization\Authorizer;
use ExtendsSoftware\ExaPHP\Authorization\Permission\Permission;
use ExtendsSoftware\ExaPHP\Identity\Identity;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Authorization\PublicPermissionRealm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PublicPermissionRealmTest extends TestCase
{
    /**
     * @return array<string, array{Identity}>
     */
    public static function identities(): array
    {
        return [
            'guest' => [new Identity('guest', false)],
            'authenticated' => [new Identity('user', true)],
        ];
    }

    #[Test]
    #[DataProvider('identities')]
    public function grantsOnlyConfiguredPermissions(Identity $identity): void
    {
        $authorizer = new Authorizer()->addRealm(new PublicPermissionRealm([
            new Permission('v1/index/get'),
        ]));

        self::assertTrue($authorizer->isPermitted(new Permission('v1/index/get'), $identity));
        self::assertFalse($authorizer->isPermitted(new Permission('v1/index/post'), $identity));
        self::assertFalse($authorizer->isPermitted(new Permission('v1/orders/get'), $identity));
    }
}

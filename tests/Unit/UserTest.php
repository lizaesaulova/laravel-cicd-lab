<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_user_has_expected_fillable_fields(): void
    {
        $user = new User;

        self::assertSame(['name', 'email', 'password'], $user->getFillable());
    }

    public function test_password_and_token_are_hidden(): void
    {
        $user = new User;

        self::assertSame(['password', 'remember_token'], $user->getHidden());
    }
}

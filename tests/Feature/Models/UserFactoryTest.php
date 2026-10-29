<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserFactoryTest extends TestCase {
    public function testDefaultPasswordMatchesGeneratedEmail(): void {
        $user = User::factory()->make();

        $this->assertTrue(Hash::check($user->email, $user->password));
    }

    public function testDefaultPasswordMatchesOverriddenEmail(): void {
        $email = 'factory@example.com';
        $user = User::factory()->make(['email' => $email]);

        $this->assertTrue(Hash::check($email, $user->password));
    }

    public function testExplicitPasswordOverridesDefault(): void {
        $password = 'explicit-password';
        $user = User::factory()->make(['password' => $password]);

        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertFalse(Hash::check($user->email, $user->password));
    }
}

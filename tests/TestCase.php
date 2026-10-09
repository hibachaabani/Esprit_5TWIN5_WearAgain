<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Agit en tant qu'administrateur (back-office). */
    protected function enAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create());
    }
}

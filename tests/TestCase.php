<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->withoutAuthorization();
    }
    
    public function createUser()
    {
        return User::factory()->create();
    }

    public function createRole()
    {
        return Role::factory()->create();
    }

    public function createPermissions()
    {
        return Permission::factory()->create();
    }

    public function withoutAuthorization()
    {
    \Gate::before(function () {
        return true;
    });

    return $this;
    }
}

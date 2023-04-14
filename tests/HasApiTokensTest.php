<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Orchestra\Testbench\TestCase;
use Laravel\Sanctum\TransientToken;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;

class HasApiTokensTest extends TestCase
{
    public function testTokensCanBeCreated()
    {
        $class = new ClassThatHasApiTokens;
        $time = Carbon::now();

        $newToken = $class->createToken('test', ['foo'], $time);

        [$id, $token] = explode('|', $newToken->plainTextToken);

        $this->assertEquals(
            $newToken->accessToken->token,
            hash('sha256', $token)
        );

        $this->assertEquals(
            $newToken->accessToken->id,
            $id
        );

        $this->assertEquals(
            $time->toDateTimeString(),
            $newToken->accessToken->expires_at->toDateTimeString()
        );
    }

    public function testCanCheckTokenAbilities()
    {
        $class = new ClassThatHasApiTokens;

        $class->withAccessToken(new TransientToken);

        $this->assertTrue($class->tokenCan('foo'));
    }
}

class ClassThatHasApiTokens implements HasApiTokensContract
{
    use HasApiTokens;

    public function tokens()
    {
        return new class
        {
            public function create(array $attributes)
            {
                return new PersonalAccessToken($attributes);
            }
        };
    }
}

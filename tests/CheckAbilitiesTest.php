<?php

namespace Laravel\Sanctum\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

class CheckAbilitiesTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function testRequestIsPassedAlongIfAbilitiesArePresentOnToken()
    {
        $middleware = new CheckAbilities;
        $request = Mockery::mock();
        $request->shouldReceive('user')->andReturn($user = Mockery::mock());
        $user->shouldReceive('currentAccessToken')->andReturn($token = Mockery::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(true);
        $user->shouldReceive('tokenCan')->with('bar')->andReturn(true);

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');

        $this->assertSame('response', $response);
    }

    public function testExceptionIsThrownIfTokenDoesntHaveAbility()
    {
        $this->expectException('Laravel\Sanctum\Exceptions\MissingAbilityException');

        $middleware = new CheckAbilities;
        $request = Mockery::mock();
        $request->shouldReceive('user')->andReturn($user = Mockery::mock());
        $user->shouldReceive('currentAccessToken')->andReturn($token = Mockery::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(false);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function testExceptionIsThrownIfNoAuthenticatedUser()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckAbilities;
        $request = Mockery::mock();
        $request->shouldReceive('user')->once()->andReturn(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function testExceptionIsThrownIfNoToken()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckAbilities;
        $request = Mockery::mock();
        $request->shouldReceive('user')->andReturn($user = Mockery::mock());
        $user->shouldReceive('currentAccessToken')->andReturn(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}

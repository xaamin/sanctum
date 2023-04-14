<?php

namespace Laravel\Sanctum\Tests;

use PHPUnit\Framework\TestCase;
use Laravel\Sanctum\TransientToken;

class TransientTokenTest extends TestCase
{
    public function testCanDetermineWhatItCanAndCantDo()
    {
        $token = new TransientToken;

        $this->assertTrue($token->can('foo'));
        $this->assertTrue($token->can('bar'));
        $this->assertFalse($token->cant('foo'));
        $this->assertFalse($token->cant('bar'));
    }
}

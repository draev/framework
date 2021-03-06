<?php

use Mockery as m;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;

class CacheRateLimiterTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testTooManyAttemptsReturnTrueIfAlreadyLockedOut()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('has')->once()->with('key:lockout')->andReturn(true);
        $cache->shouldReceive('add')->never();
        $rateLimiter = new RateLimiter($cache);

        $this->assertTrue($rateLimiter->tooManyAttempts('key', 1, 1));
    }

    public function testTooManyAttemptsReturnsTrueIfMaxAttemptsExceeded()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(10);
        $cache->shouldReceive('has')->once()->with('key:lockout')->andReturn(false);
        $cache->shouldReceive('add')->once()->with('key:lockout', m::type('int'), 1);
        $rateLimiter = new RateLimiter($cache);

        $this->assertTrue($rateLimiter->tooManyAttempts('key', 1, 1));
    }

    public function testHitProperlyIncrementsAttemptCount()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('add')->once()->with('key', 1, 1);
        $cache->shouldReceive('increment')->once()->with('key');
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->hit('key', 1);
    }

    public function testClearClearsTheCacheKeys()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('forget')->once()->with('key');
        $cache->shouldReceive('forget')->once()->with('key:lockout');
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->clear('key');
    }
}

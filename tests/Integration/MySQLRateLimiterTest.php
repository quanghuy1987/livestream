<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Infrastructure\RateLimiter\MySQLRateLimiter;

final class MySQLRateLimiterTest extends IntegrationTestCase
{
    private $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new MySQLRateLimiter($this->pdo);
    }

    public function test_first_hit_is_allowed(): void
    {
        $allowed = $this->limiter->check('test_action', 1, 5, 60);
        $this->assertTrue($allowed);
    }

    public function test_hits_within_limit_are_all_allowed(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $allowed = $this->limiter->check('test_action', 1, 5, 60);
            $this->assertTrue($allowed, "Hit {$i} should be allowed");
        }
    }

    public function test_hit_exceeding_limit_is_rejected(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('test_action', 1, 5, 60);
        }

        $allowed = $this->limiter->check('test_action', 1, 5, 60);
        $this->assertFalse($allowed);
    }

    public function test_different_users_have_independent_limits(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('test_action', 1, 5, 60);
        }

        // User 2 should still be allowed
        $allowed = $this->limiter->check('test_action', 2, 5, 60);
        $this->assertTrue($allowed);
    }

    public function test_different_actions_have_independent_limits(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('action_a', 1, 5, 60);
        }

        // action_b is a separate key — should still be allowed
        $allowed = $this->limiter->check('action_b', 1, 5, 60);
        $this->assertTrue($allowed);
    }

    public function test_expired_window_resets_hit_count(): void
    {
        // Exhaust the limit
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check('test_action', 99, 5, 60);
        }
        $this->assertFalse($this->limiter->check('test_action', 99, 5, 60));

        // Manually expire the window so the next check sees a fresh counter
        $this->pdo->exec("UPDATE rate_limits SET window_end = DATE_SUB(NOW(), INTERVAL 1 SECOND)");

        $allowed = $this->limiter->check('test_action', 99, 5, 60);
        $this->assertTrue($allowed);
    }
}

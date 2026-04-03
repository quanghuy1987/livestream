<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimiter;

use App\Application\RateLimiter\RateLimiterInterface;

final class MySQLRateLimiter implements RateLimiterInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function check(string $action, int $userId, int $maxHits, int $windowSecs): bool
    {
        $key = $this->buildKey($action, $userId, $windowSecs);

        $sql = "INSERT INTO rate_limits (rate_key, hits, window_end, created_at)
                VALUES (:key, 1, DATE_ADD(NOW(), INTERVAL :secs SECOND), NOW())
                ON DUPLICATE KEY UPDATE
                    hits       = IF(window_end > NOW(), hits + 1, 1),
                    window_end = IF(window_end > NOW(), window_end, DATE_ADD(NOW(), INTERVAL :secs2 SECOND))";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':key',   $key,        \PDO::PARAM_STR);
        $stmt->bindValue(':secs',  $windowSecs, \PDO::PARAM_INT);
        $stmt->bindValue(':secs2', $windowSecs, \PDO::PARAM_INT);
        $stmt->execute();

        $stmt2 = $this->pdo->prepare(
            'SELECT hits FROM rate_limits WHERE rate_key = :key AND window_end > NOW()'
        );
        $stmt2->execute([':key' => $key]);
        $row = $stmt2->fetch(\PDO::FETCH_ASSOC);

        return $row && (int) $row['hits'] <= $maxHits;
    }

    private function buildKey(string $action, int $userId, int $windowSecs): string
    {
        $bucket = $windowSecs <= 1 ? (string) time() : date('YmdHi');
        return "{$action}:user:{$userId}:{$bucket}";
    }
}

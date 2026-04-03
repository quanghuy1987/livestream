<?php

declare(strict_types=1);

namespace App\Cli;

use App\Domain\Repository\LivestreamRepositoryInterface;

final class ArchiveLivestreamsCommand
{
    private const DEFAULT_OLDER_THAN_MINUTES = 60;
    private const DEFAULT_BATCH_SIZE         = 100;

    private $livestreamRepo;

    public function __construct(LivestreamRepositoryInterface $livestreamRepo)
    {
        $this->livestreamRepo = $livestreamRepo;
    }

    public function run(array $argv): void
    {
        $olderThanMinutes = isset($argv[2]) ? (int) $argv[2] : self::DEFAULT_OLDER_THAN_MINUTES;
        $batchSize        = isset($argv[3]) ? (int) $argv[3] : self::DEFAULT_BATCH_SIZE;

        $threshold = new \DateTimeImmutable("-{$olderThanMinutes} minutes");
        $count     = $this->livestreamRepo->archiveEnded($threshold, $batchSize);

        echo sprintf(
            "[%s] Archived %d livestream(s) ended more than %d minute(s) ago.\n",
            date('Y-m-d H:i:s'),
            $count,
            $olderThanMinutes
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Debug;

use App\Traits\LoadFromParentObject;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Debug_LogEntry',
    required: ['*'],
    type: 'object'
)]
final readonly class LogEntry
{
    use LoadFromParentObject;

    public function __construct(
        #[OA\Property(type: 'string', format: 'date-time')]
        public DateTimeImmutable $datetime,
        #[OA\Property]
        public string $channel,
        #[OA\Property(
            enum: Level::class
        )]
        public int $level,
        #[OA\Property]
        public string $message,
        #[OA\Property(
            items: new OA\Items()
        )]
        public array $context = [],
        #[OA\Property(
            items: new OA\Items()
        )]
        public array $extra = [],
        #[OA\Property(
            type: '{}'
        )]
        public mixed $formatted = null,
    ) {
    }

    public static function fromLogRecord(LogRecord $logRecord): self
    {
        return new self(
            $logRecord->datetime,
            $logRecord->channel,
            $logRecord->level->value,
            $logRecord->message,
            $logRecord->context,
            $logRecord->extra,
            $logRecord->formatted
        );
    }
}

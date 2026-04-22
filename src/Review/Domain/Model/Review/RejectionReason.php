<?php

declare(strict_types=1);

namespace App\Review\Domain\Model\Review;

enum RejectionReason: string
{
    case SPAM = 'SPAM';
    case INAPPROPRIATE = 'INAPPROPRIATE';
    case OFF_TOPIC = 'OFF_TOPIC';
    case DUPLICATE = 'DUPLICATE';
    case MISLEADING = 'MISLEADING';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SPAM => 'Spam',
            self::INAPPROPRIATE => 'Inappropriate content',
            self::OFF_TOPIC => 'Off topic',
            self::DUPLICATE => 'Duplicate review',
            self::MISLEADING => 'Misleading content',
            self::OTHER => 'Other',
        };
    }
}

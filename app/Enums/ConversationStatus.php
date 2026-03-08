<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case NeedsContext = 'needs_context';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::NeedsContext => 'Needs Context',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }
}

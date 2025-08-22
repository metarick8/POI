<?php

namespace App\Enums;

enum DebateStatus: string
{
    case ANNOUNCED = 'announced';
    case PLAYERS_CONFIRMED = 'playersConfirmed';
    case DEBATE_PREPARATION = 'debatePreperation';
    case ONGOING = 'ongoing';
    case FINISHED = 'finished';
    case BUGGED = 'bugged';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => ['name' => $case->name, 'value' => $case->value],
            self::cases()
        );
    }
}

<?php

namespace App\Enums;

enum FileOwner: string
{
    case User = 'user';
    case Coach = 'coach';
    case Judge = 'judge';
    case Debater = 'debater';
}

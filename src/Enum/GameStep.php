<?php

namespace App\Enum;

enum GameStep: string
{
    case NIGHT = 'night';
    case DAWN = 'dawn';
    case DAY = 'day';
    case DUSK = 'dusk';
}
<?php

namespace App\Enum;

enum CasinoGame: string
{
    case ROULETTE = 'roulette';
    case BLACKJACK = 'blackjack';
    case TOWER = 'tower';
    case MORE_OR_LESS = 'more_or_less';
}

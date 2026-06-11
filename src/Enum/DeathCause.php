<?php

namespace App\Enum;

enum DeathCause: string
{
    case WITCH_ATTACK = 'witch_attack';
    case INDEPENDENT_ATTACK = 'independent_attack';
    case VILLAGE_VOTE = 'village_vote';
    case VILLAGE_POWER = 'village_power';
    case DIVINE_LIGHTNING = 'divine_lightning';
}
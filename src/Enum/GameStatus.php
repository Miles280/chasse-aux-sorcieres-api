<?php

namespace App\Enum;

enum GameStatus: string
{
    case WAITING = 'waiting'; 
    case PLAYING = 'playing'; 
    case FINISHED = 'finished'; 
    case CANCELED = 'canceled';
}
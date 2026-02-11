<?php

namespace App\Enum;

enum Alignment: string
{
    case KILLER = 'killer';
    case INFORMER = 'informer';
    case LEADER = 'leader';
    case PROTECTOR = 'preotector';
    case SUPPORT = 'support';
}
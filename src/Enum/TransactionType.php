<?php

namespace App\Enum;

enum TransactionType: string
{
    case GAIN = 'gain';
    case LOSE = 'lose';
    case PURCHASE = 'purchase';
    case DONATION = 'donation';
    case RECEIVE = 'receive';
    case CONVERSION = 'conversion';
    case ADMIN = 'admin';
    case SET = 'set';
}

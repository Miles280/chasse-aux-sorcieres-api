<?php

namespace App\Enum;

enum TransactionType: string
{
    case GAIN = 'gain';
    case LOSE = 'lose';
    case DONATION = 'donation';
    case RECEIVE = 'receive';
    case ADD = 'add';
    case REMOVE = 'remove';
    case PURCHASE = 'purchase';
    case SELL = 'sell';
    case SET = 'set';
    case CONVERSION = 'conversion';
    case ADMIN = 'admin';
}

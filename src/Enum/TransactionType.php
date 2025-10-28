<?php

namespace App\Enum;

enum TransactionType: string
{
    case GAIN = 'gain';
    case PURCHASE = 'purchase';
    case DONATION = 'donation';
    case RECEIPT = 'receipt';
    case CONVERSION = 'conversion';
    case ADMIN = 'admin';
}

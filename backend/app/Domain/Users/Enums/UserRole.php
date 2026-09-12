<?php

namespace Domain\Users\Enums;

enum UserRole: string
{
    case FARMER = 'farmer';
    case BUYER = 'buyer';
}

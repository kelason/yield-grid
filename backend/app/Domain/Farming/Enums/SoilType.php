<?php

namespace Domain\Farming\Enums;

enum SoilType: string
{
    case CLAY = 'clay';
    case SANDY = 'sandy';
    case LOAMY = 'loamy';
    case SILT = 'silt';
    case PEAT = 'peat';
    case CHALKY = 'chalky';
}

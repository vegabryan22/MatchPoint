<?php

namespace App\Enums;

enum TournamentCapacity: int
{
    case Four = 4;
    case Eight = 8;
    case Sixteen = 16;
    case ThirtyTwo = 32;
    case SixtyFour = 64;
    case OneHundredTwentyEight = 128;
}

<?php

namespace App\Game;

enum Mode: string
{
    case Connect = 'connect';
    case Guess = 'guess';
    case Code = 'code';
}

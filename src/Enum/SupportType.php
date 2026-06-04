<?php

namespace App\Enum;

enum SupportType: string
{
    case LP = 'lp';
    case EP = 'ep';
    case CD = 'cd';
    case FANZINE = 'fanzine';
    case TAPE = 'tape';
}

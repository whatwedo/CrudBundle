<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enum;

enum PageMode: string
{
    case NORMAL = 'normal';
    case MODAL = 'modal';
}

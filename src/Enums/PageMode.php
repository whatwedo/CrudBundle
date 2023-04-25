<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enums;

enum PageMode: string implements PageModeInterface
{
    case NORMAL = 'normal';
    case MODAL = 'modal';
}

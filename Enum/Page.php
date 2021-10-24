<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enum;

enum Page
{
    case INDEX;
    case SHOW;
    case CREATE;
    case EDIT;
    case DELETE;
    case BATCH;
    case EXPORT;
    case AJAX;

    public function toRoute(): string
    {
        return strtolower($this->name);
    }
}

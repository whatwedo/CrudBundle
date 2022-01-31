<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enum;

enum Page
{
    case INDEX;
    case SHOW;
    case RELOAD;
    case CREATE;
    case CREATEMODAL;
    case EDIT;
    case DELETE;
    case BATCH;
    case EXPORT;
    case AJAXFORM;
    case JSONSEARCH;

    public function toRoute(): string
    {
        return strtolower($this->name);
    }
}

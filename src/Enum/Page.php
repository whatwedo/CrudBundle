<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enum;

enum Page: string implements PageInterface
{
    case INDEX = 'index';
    case SHOW = 'show';
    case RELOAD = 'reload';
    case CREATE = 'create';
    case CREATEMODAL = 'create_modal';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case BATCH = 'batch';
    case EXPORT = 'export';
    case AJAXFORM = 'ajaxform';
    case JSONSEARCH = 'jsonseach';

    public function toRoute(): string
    {
        return strtolower($this->name);
    }
}
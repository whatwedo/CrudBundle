<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enum;

interface PageInterface
{
    public function toRoute(): string;
}

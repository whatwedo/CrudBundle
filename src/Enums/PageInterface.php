<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Enums;

interface PageInterface
{
    public function toRoute(): string;
}

<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Collection;

use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Enum\PageInterface;

class ContentCollection extends ArrayCollection
{
    public function filterVisibility(PageInterface $page): self
    {
        return $this->filter(static fn (AbstractContent $content) => in_array($page, $content->getOption('visibility'), true));
    }
}

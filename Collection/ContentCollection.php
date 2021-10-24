<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Enum\Page;

class ContentCollection extends ArrayCollection
{
    public function filterVisibility(Page $page): self
    {
        return $this->filter(static fn(AbstractContent $content) => in_array($page, $content->getOption('visibility'), true));
    }
}

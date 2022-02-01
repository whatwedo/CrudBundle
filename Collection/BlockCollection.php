<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Collection;

use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Enum\Page;

class BlockCollection extends ArrayCollection
{
    public function filterVisibility(Page $page): self
    {
        return $this->filter(static fn(Block $block) => in_array($page, $block->getOption('visibility'), true));
    }
}

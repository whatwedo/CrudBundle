<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Collection;

use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Enums\PageInterface;

class BlockCollection extends ArrayCollection
{
    public function filterVisibility(PageInterface $page): self
    {
        /** @var self $filtered */
        $filtered = $this->filter(static fn (Block $block) => in_array($page, $block->getOption('visibility'), true));

        return $filtered;
    }
}

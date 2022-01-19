<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Builder;

use InvalidArgumentException;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionBuilder
{
    protected DefinitionInterface $definition;
    protected BlockCollection $blocks;

    public function __construct(
        protected BlockManager $blockManager,
        protected DefinitionManager $definitionManager,
    ) {
        $this->blocks = new BlockCollection();
    }

    /**
     * @param string      $acronym unique block acronym
     * @param string|null $type    block type (class name)
     * @param array       $options options
     */
    public function addBlock(string $acronym, ?string $type = Block::class, array $options = []): Block
    {
        $element = $this->blockManager->getBlock($type ?? Block::class);

        $element->setDefinition($this->definition);
        $element->setAcronym($acronym);

        if (!isset($options['label'])) {
            $options['label'] = sprintf('%s.block.%s', $this->definition::getPrefix(), $acronym);
        }

        $element->setOptions($options);
        $this->blocks->set($acronym, $element);

        return $element;
    }

    public function getBlock(string $acronym): Block
    {
        if (!$this->blocks->containsKey($acronym)) {
            throw new InvalidArgumentException(sprintf('Specified block "%s" not found.', $acronym));
        }

        return $this->blocks->get($acronym);
    }

    public function removeBlock(string $acronym): void
    {
        if (!$this->blocks->containsKey($acronym)) {
            throw new InvalidArgumentException(sprintf('Specified block "%s" not found.', $acronym));
        }

        $this->blocks->remove($acronym);
    }

    /**
     * @return BlockCollection<Block>
     */
    public function getBlocks(): BlockCollection
    {
        return $this->blocks;
    }

    public function setDefinition(DefinitionInterface $definition): void
    {
        $this->definition = $definition;
    }
}

<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Block;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Collection\ContentCollection;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\View\DefinitionView;

#[Autoconfigure(tags: ['whatwedo_crud.block'])]
class BlockBlock extends Block
{
    public const OPT_LAYOUT_OPTIONS = 'layout_options';

    public const OPT_LAYOUT_VERTICALLY = 'vertically';

    public const OPT_LAYOUT_HORIZONTALLY = 'horizontally';

    public const OPT_BLOCK_PREFIX_TAB = 'tab_block';

    public const OPT_BLOCK_PREFIX_GRID = 'grid_block';

    protected BlockCollection $blocks;

    public function __construct()
    {
        $this->blocks = new BlockCollection();
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            BlockManager::class,
        ]);
    }

    public function getBlocks(?DefinitionView $view = null, ?PageInterface $page = null): BlockCollection
    {
        $blockCollection = $page
            ? $this->blocks->filterVisibility($page)
            : $this->blocks
        ;

        if ($page && $view) {
            $attribute = match ($page) {
                Page::SHOW => self::OPT_SHOW_VOTER_ATTRIBUTE,
                Page::CREATE => self::OPT_CREATE_VOTER_ATTRIBUTE,
                Page::EDIT => self::OPT_EDIT_VOTER_ATTRIBUTE,
            };

            /** @var BlockCollection $blockCollection */
            $blockCollection = $blockCollection->filter(
                function (Block $block) use ($attribute, $view) {
                    return $block->getOption($attribute) === null || $this->getSecurity()->isGranted($block->getOption($attribute), $view->getData());
                }
            );
        }

        return $blockCollection;
    }

    public function getContents(?DefinitionView $view = null, ?PageInterface $page = null): ContentCollection
    {
        $contentCollection = new ContentCollection();
        foreach ($this->blocks as $block) {
            $contentCollection->addAll($block->getContents($view, $page));
        }

        return $contentCollection;
    }

    public function addBlock(string $acronym, ?string $type = null, array $options = [], ?int $position = null): Block
    {
        $element = $this->container->get(BlockManager::class)->getBlock($type ?? Block::class);
        $element->setDefinition($this->getDefinition());
        $element->setAcronym($acronym);
        $element->setParentBlock($this);
        if (! isset($options[self::OPT_LABEL])) {
            $options[self::OPT_LABEL] = sprintf('wwd.%s.block_block.%s', $this->definition::getEntityAlias(), $acronym);
        }
        $element->setOptions($options);
        $this->blocks->set($acronym, $element, $position);

        return $element;
    }

    public function addContent(string $acronym, ?string $type = null, array $options = [], ?int $position = null): static
    {
        throw new \Exception('cannot be used in BlockBlock');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(self::OPT_LAYOUT_OPTIONS, []);
        $resolver->setAllowedTypes(self::OPT_LAYOUT_OPTIONS, ['array']);
    }
}

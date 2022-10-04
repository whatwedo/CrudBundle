<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Block;

use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use whatwedo\CrudBundle\Collection\ContentCollection;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Content\Content;
use whatwedo\CrudBundle\Content\EnumContent;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\BlockSize;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Exception\BlockNotFoundException;
use whatwedo\CrudBundle\Manager\ContentManager;
use whatwedo\CrudBundle\Traits\VisibilityTrait;
use whatwedo\CrudBundle\Traits\VoterAttributeTrait;
use whatwedo\CrudBundle\View\DefinitionView;

#[Autoconfigure(tags: ['whatwedo_crud.block'])]
class Block implements ServiceSubscriberInterface
{
    use VisibilityTrait;
    use VoterAttributeTrait;

    public const OPT_LABEL = 'label';

    public const OPT_DESCRIPTION = 'description';

    public const OPT_ATTR = 'attr';

    public const OPT_SIZE = 'size';

    public const OPT_VISIBILITY = 'visibility';

    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    public const OPT_BLOCK_PREFIX = 'block_prefix';

    public const OPT_CUSTOM_OPTIONS = 'custom_options';

    public const OPT_COLLAPSIBLE = 'collapsible';

    public const OPT_COLLAPSED = 'collapsed';

    protected ContainerInterface $container;

    protected ?Block $parentBlock = null;

    protected string $acronym = '';

    protected array $options = [];

    protected ContentCollection $elements;

    protected DefinitionInterface $definition;

    public function __construct()
    {
        $this->elements = new ContentCollection();
    }

    public function setAcronym(string $acronym): static
    {
        $this->acronym = $acronym;

        return $this;
    }

    public function getAcronym(): string
    {
        return $this->acronym;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPT_LABEL => null,
            self::OPT_DESCRIPTION => null,
            self::OPT_ATTR => null,
            self::OPT_SIZE => BlockSize::SMALL,
            self::OPT_VISIBILITY => [Page::SHOW, Page::EDIT, Page::CREATE],
            self::OPT_SHOW_VOTER_ATTRIBUTE => Page::SHOW,
            self::OPT_EDIT_VOTER_ATTRIBUTE => Page::EDIT,
            self::OPT_CREATE_VOTER_ATTRIBUTE => Page::CREATE,
            self::OPT_BLOCK_PREFIX => StringUtil::fqcnToBlockPrefix(static::class),
            self::OPT_CUSTOM_OPTIONS => [],
            self::OPT_COLLAPSIBLE => false,
            self::OPT_COLLAPSED => false,
        ]);

        $resolver->setAllowedTypes(self::OPT_VISIBILITY, 'array');
        $resolver->setAllowedTypes(self::OPT_CUSTOM_OPTIONS, 'array');
        $resolver->setAllowedTypes(self::OPT_LABEL, ['null', 'string', 'bool']);
        $resolver->setAllowedTypes(self::OPT_DESCRIPTION, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPT_ATTR, ['null', 'array']);
        $resolver->setAllowedValues(self::OPT_SIZE, [BlockSize::LARGE, BlockSize::SMALL]);
        $resolver->setAllowedTypes(self::OPT_SHOW_VOTER_ATTRIBUTE, ['null', 'string', 'object']);
        $resolver->setAllowedTypes(self::OPT_EDIT_VOTER_ATTRIBUTE, ['null', 'string', 'object']);
        $resolver->setAllowedTypes(self::OPT_CREATE_VOTER_ATTRIBUTE, ['null', 'string', 'object']);
        $resolver->setAllowedTypes(self::OPT_BLOCK_PREFIX, 'string');
        $resolver->setAllowedTypes(self::OPT_COLLAPSIBLE, 'bool');
        $resolver->setAllowedTypes(self::OPT_COLLAPSED, 'bool');
    }

    public function setOption(string $name, mixed $value): static
    {
        if (! $this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        $this->options[$name] = $value;

        return $this;
    }

    public function setOptions(array $options): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        return $this;
    }

    public function getOption(string $name): mixed
    {
        if (! $this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        return $this->options[$name];
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]) || array_key_exists($name, $this->options);
    }

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionInterface $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * adds a new content to the block.
     *
     * @param string      $acronym acronym of the block
     * @param string|null $type    type of the block (class name)
     * @param array       $options options
     */
    public function addContent(string $acronym, ?string $type = null, array $options = [], ?int $position = null): static
    {
        /** @var AbstractContent $element */
        $element = $this->container->get(ContentManager::class)->getContent($type ?? $this->getType($acronym, $options));
        $element->setDefinition($this->definition);

        if ($element->getOptionsResolver()->isDefined(AbstractContent::OPT_LABEL) && ! isset($options[AbstractContent::OPT_LABEL])) {
            $options[self::OPT_LABEL] = sprintf('wwd.%s.property.%s', $this->definition::getEntityAlias(), $acronym);
        }
        if ($element->getOptionsResolver()->isDefined(AbstractContent::OPT_HELP) && ! isset($options[AbstractContent::OPT_HELP])) {
            $options[AbstractContent::OPT_HELP] = sprintf('wwd.%s.help.%s', $this->definition::getEntityAlias(), $acronym);
        }

        $element->setAcronym($acronym);
        $element->setOptions($options);
        $element->setBlock($this);

        $this->elements->set($acronym, $element, $position);

        return $this;
    }

    public function getContents(?DefinitionView $view = null, ?PageInterface $page = null): ContentCollection
    {
        $contentCollection = $page
            ? $this->elements->filterVisibility($page)
            : $this->elements;

        if ($page && $view) {
            $attribute = match ($page) {
                Page::SHOW => self::OPT_SHOW_VOTER_ATTRIBUTE,
                Page::CREATE => self::OPT_CREATE_VOTER_ATTRIBUTE,
                Page::EDIT => self::OPT_EDIT_VOTER_ATTRIBUTE,
            };

            /** @var ContentCollection $contentCollection */
            $contentCollection = $contentCollection->filter(
                function (AbstractContent $content) use ($attribute, $view) {
                    return $content->getOption($attribute) === null || $this->getSecurity()->isGranted($content->getOption($attribute), $view->getData());
                }
            );
        }

        return $contentCollection;
    }

    public function getContent(string $acronym): ?AbstractContent
    {
        return $this->elements[$acronym] ?? null;
    }

    public function removeContent(string $acronym): static
    {
        $this->elements->remove($acronym);

        return $this;
    }

    /**
     * @required
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            FormRegistryInterface::class,
            ContentManager::class,
            Security::class,
        ];
    }

    public function getParentBlock(): self
    {
        if (! $this->parentBlock) {
            throw new BlockNotFoundException('no Parent Block available');
        }

        return $this->parentBlock;
    }

    protected function getSecurity(): Security
    {
        return $this->container->get(Security::class);
    }

    protected function setParentBlock(?self $parentBlock): void
    {
        $this->parentBlock = $parentBlock;
    }

    private function getType(string $acronym, array $options): string
    {
        /** @var TypeGuess $typeGuess */
        $typeGuess = $this->container->get(FormRegistryInterface::class)->getTypeGuesser()->guessType(
            $this->definition::getEntity(),
            $options[AbstractContent::OPT_ACCESSOR_PATH] ?? $acronym
        );

        if ($typeGuess->getType() === EntityType::class
            && $typeGuess->getOptions()['multiple'] === true) {
            return RelationContent::class;
        }

        if (isset($options[EnumContent::OPT_CLASS]) && enum_exists($options[EnumContent::OPT_CLASS])) {
            return EnumContent::class;
        }

        return Content::class;
    }

    public function __clone(): void
    {
        $this->elements = new ContentCollection();
    }
}

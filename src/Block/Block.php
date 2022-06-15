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
use whatwedo\CrudBundle\Manager\ContentManager;
use whatwedo\CrudBundle\Traits\VisibilityTrait;
use whatwedo\CrudBundle\Traits\VoterAttributeTrait;
use whatwedo\CrudBundle\View\DefinitionView;

#[Autoconfigure(tags: ['whatwedo_crud.block'])]
class Block implements ServiceSubscriberInterface
{
    use VisibilityTrait;
    use VoterAttributeTrait;

    protected ContainerInterface $container;

    protected string $acronym = '';

    protected array $options = [];

    protected ContentCollection $elements;

    protected DefinitionInterface $definition;

    public function __construct(
        protected Security $security
    ) {
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
            'label' => null,
            'description' => null,
            'attr' => null,
            'size' => BlockSize::SMALL,
            'visibility' => [Page::SHOW, Page::EDIT, Page::CREATE],
            'show_voter_attribute' => Page::SHOW,
            'edit_voter_attribute' => Page::EDIT,
            'create_voter_attribute' => Page::CREATE,
            'block_prefix' => StringUtil::fqcnToBlockPrefix(static::class),
            'custom_options' => [],
        ]);

        $resolver->setAllowedTypes('custom_options', 'array');
        $resolver->setAllowedTypes('visibility', 'array');
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('description', ['null', 'string']);
        $resolver->setAllowedTypes('attr', ['null', 'array']);
        $resolver->setAllowedValues('size', [BlockSize::LARGE, BlockSize::SMALL]);
        $resolver->setAllowedTypes('show_voter_attribute', ['null', 'string', 'object']);
        $resolver->setAllowedTypes('edit_voter_attribute', ['null', 'string', 'object']);
        $resolver->setAllowedTypes('create_voter_attribute', ['null', 'string', 'object']);
        $resolver->setAllowedTypes('block_prefix', 'string');
    }

    public function setOption($name, $value): static
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

    public function getOption(string $name)
    {
        if (! $this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        return $this->options[$name];
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

        if ($element->getOptionsResolver()->isDefined('label') && ! isset($options['label'])) {
            $options['label'] = sprintf('wwd.%s.property.%s', $this->definition::getEntityAlias(), $acronym);
        }
        if ($element->getOptionsResolver()->isDefined('help') && ! isset($options['help'])) {
            $options['help'] = sprintf('wwd.%s.help.%s', $this->definition::getEntityAlias(), $acronym);
        }

        $element->setAcronym($acronym);
        $element->setOptions($options);
        $element->setBlock($this);

        $this->elements->set($acronym, $element, $position);

        return $this;
    }

    /**
     * @return ContentCollection<AbstractContent>|AbstractContent[]
     */
    public function getContents(?DefinitionView $view = null, ?PageInterface $page = null): ContentCollection
    {
        $contentCollection = $page
            ? $this->elements->filterVisibility($page)
            : $this->elements;

        if ($page && $view) {
            $attribute = match ($page) {
                Page::SHOW => 'show_voter_attribute',
                Page::CREATE => 'create_voter_attribute',
                Page::EDIT => 'edit_voter_attribute',
            };

            $contentCollection->filter(
                function (AbstractContent $content) use ($attribute, $view) {
                    return $content->getOption($attribute) === null || $this->security->isGranted($content->getOption($attribute), $view->getData());
                }
            );
        }

        return $contentCollection;
    }

    public function getContent($acronym): ?AbstractContent
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
        ];
    }

    private function getType(string $acronym, array $options): string
    {
        /** @var TypeGuess $typeGuess */
        $typeGuess = $this->container->get(FormRegistryInterface::class)->getTypeGuesser()->guessType(
            $this->definition::getEntity(),
            $options['accessor_path'] ?? $acronym
        );

        if ($typeGuess->getType() === EntityType::class
            && $typeGuess->getOptions()['multiple'] === true) {
            return RelationContent::class;
        }

        if (isset($options['class']) && enum_exists($options['class'])) {
            return EnumContent::class;
        }

        return Content::class;
    }

    public function __clone(): void
    {
        $this->elements = new ContentCollection();
    }
}

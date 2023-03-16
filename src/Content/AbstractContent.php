<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Traits\VisibilityTrait;
use whatwedo\CrudBundle\Traits\VoterAttributeTrait;

#[Autoconfigure(tags: ['whatwedo_crud.content'])]
abstract class AbstractContent implements ServiceSubscriberInterface
{
    use VisibilityTrait;
    use VoterAttributeTrait;

    /**
     * Defines the label of the content. Form labels in create and edit and data term in show.
     * Defaults to <code>wwd.[definition::getEntityAlias].property.[content->acronym]</code>
     * Accepts: <code>string|boolean|null</code>.
     */
    public const OPT_LABEL = 'label';

    /**
     * Defines the help text of the content. Is shown in a tooltip next to the label.
     * Defaults to <code>wwd.[definition::getEntityAlias].help.[content->acronym]</code>
     * Accepts: <code>string|boolean|null</code>.
     */
    public const OPT_HELP = 'help';

    /**
     * With the callable you can define custom data which is given to the content.
     * The callable is called with the entity as parameter and should return the data.
     * Defaults to <code>null</code>
     * Accepts: <code>callable|null</code>.
     */
    public const OPT_CALLABLE = 'callable';

    /**
     * Defines custom html attributes on the content. These attributes will be rendered on the form elements
     * and the data term in show. It will be rendered as following in the html: <code>key="value"</code>.
     * Defaults to an empty array <code>[]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_ATTR = 'attr';

    /**
     * Voter attribute for the show page. If the voter attribute is set, the content will only be shown if the voter
     * returns true. If the voter attribute is not set it will be shown too.
     * Defaults to <code>Page::SHOW</code>
     * Accepts: <code>null|object|string</code>.
     */
    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    /**
     * Defines the visibility of the content. Available options are the on the definition defined Capabilities.
     * Defaults to <code>[Page::SHOW, Page::EDIT, Page::CREATE]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_VISIBILITY = 'visibility';

    /**
     * Voter attribute for the edit page. If the voter attribute is set, the content will only be shown if the voter
     * returns true. If the voter attribute is not set it will be shown too.
     * Defaults to <code>Page::EDIT</code>
     * Accepts: <code>null|object|string</code>.
     */
    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    /**
     * Voter attribute for the create page. If the voter attribute is set, the content will only be shown if the voter
     * returns true. If the voter attribute is not set it will be shown too.
     * Defaults to <code>Page::CREATE</code>
     * Accepts: <code>null|object|string</code>.
     */
    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    /**
     * Defines the twig block to render the content with. See <code>views/includes/layout/_content.html.twig</code> for more information.
     * Be sure that your custom Content classes end with <code>Content</code>.
     * Defaults to Content's class name without the namespace in snake case.
     * Accepts: <code>string</code>.
     */
    public const OPT_BLOCK_PREFIX = 'block_prefix';

    /**
     * Defines the accessor path to the data.
     * Defaults to the <code>acronym</code> of the content.
     * Accepts: <code>string</code>.
     */
    public const OPT_ACCESSOR_PATH = 'accessor_path';

    /**
     * Define custom options here. You can use this additional array however it fits your need.
     * Defaults to an empty array <code>[]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_CUSTOM_OPTIONS = 'custom_options';

    protected ContainerInterface $container;

    protected string $acronym = '';

    protected array $options = [];

    protected ?DefinitionInterface $definition = null;

    protected ?Block $block = null;

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
            self::OPT_LABEL => $this->acronym,
            self::OPT_ACCESSOR_PATH => $this->acronym,
            self::OPT_HELP => null,
            self::OPT_CALLABLE => null,
            self::OPT_ATTR => [],
            self::OPT_VISIBILITY => [Page::SHOW, Page::EDIT, Page::CREATE],
            self::OPT_SHOW_VOTER_ATTRIBUTE => Page::SHOW,
            self::OPT_EDIT_VOTER_ATTRIBUTE => Page::EDIT,
            self::OPT_CREATE_VOTER_ATTRIBUTE => Page::CREATE,
            self::OPT_BLOCK_PREFIX => StringUtil::fqcnToBlockPrefix(static::class),
            self::OPT_CUSTOM_OPTIONS => [],
        ]);

        $resolver->setAllowedTypes(self::OPT_VISIBILITY, 'array');
        $resolver->setAllowedTypes(self::OPT_LABEL, ['string', 'boolean', 'null']);
        $resolver->setAllowedTypes(self::OPT_HELP, ['string', 'boolean', 'null']);
        $resolver->setAllowedTypes(self::OPT_CALLABLE, ['callable', 'null']);
        $resolver->setAllowedTypes(self::OPT_ATTR, 'array');
        $resolver->setAllowedTypes(self::OPT_SHOW_VOTER_ATTRIBUTE, ['null', 'object', 'string']);
        $resolver->setAllowedTypes(self::OPT_EDIT_VOTER_ATTRIBUTE, ['null', 'object', 'string']);
        $resolver->setAllowedTypes(self::OPT_CREATE_VOTER_ATTRIBUTE, ['null', 'object', 'string']);
        $resolver->setAllowedTypes(self::OPT_BLOCK_PREFIX, 'string');
        $resolver->setAllowedTypes(self::OPT_CUSTOM_OPTIONS, 'array');
        $resolver->setAllowedTypes(self::OPT_ACCESSOR_PATH, 'string');
    }

    public function setOptions(array $options): void
    {
        $this->options = $this->getOptionsResolver()->resolve($options);
    }

    public function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $resolver;
    }

    public function setOption(string $name, mixed $value): static
    {
        if (! $this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        $this->options[$name] = $value;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name): mixed
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

    public function getDefinition(): ?DefinitionInterface
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionInterface $definition): static
    {
        $this->definition = $definition;

        return $this;
    }

    public function getContents(mixed $row): mixed
    {
        if (is_callable($this->options[self::OPT_CALLABLE])) {
            if (is_array($this->options[self::OPT_CALLABLE])) {
                return call_user_func($this->options[self::OPT_CALLABLE], [$row]);
            }

            return $this->options[self::OPT_CALLABLE]($row);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor()
        ;

        try {
            return $propertyAccessor->getValue($row, $this->options[self::OPT_ACCESSOR_PATH]);
        } catch (UnexpectedTypeException) {
            return null;
        } catch (NoSuchPropertyException $noSuchPropertyException) {
            return $noSuchPropertyException->getMessage();
        }
    }

    public function getBlockPrefix(): string
    {
        return $this->options[self::OPT_BLOCK_PREFIX];
    }

    #[Required]
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getBlock(): ?Block
    {
        return $this->block;
    }

    public function setBlock(?Block $block): void
    {
        $this->block = $block;
    }

    public static function getSubscribedServices(): array
    {
        return [
        ];
    }
}

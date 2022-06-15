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

    public const OPT_LABEL = 'label';

    public const OPT_HELP = 'help';

    public const OPT_CALLABLE = 'callable';

    public const OPT_ATTR = 'attr';

    public const OPT_VISIBILITY = 'visibility';

    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    public const OPT_BLOCK_PREFIX = 'block_prefix';

    public const OPT_ACCESSOR_PATH = 'accessor_path';

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

    public function setOption($name, $value): static
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

    public function getDefinition(): ?DefinitionInterface
    {
        return $this->definition;
    }

    public function setDefinition(DefinitionInterface $definition): static
    {
        $this->definition = $definition;

        return $this;
    }

    public function getContents($row): mixed
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

    /**
     * @required
     */
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

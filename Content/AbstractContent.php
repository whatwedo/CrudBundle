<?php
declare(strict_types=1);


namespace whatwedo\CrudBundle\Content;

use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\VisibilityEnum;
use whatwedo\CrudBundle\Traits\VisibilityTrait;
use whatwedo\CrudBundle\Traits\VoterAttributeTrait;

#[Autoconfigure(tags: ['whatwedo_crud.content'])]
abstract class AbstractContent implements ServiceSubscriberInterface
{
    use VisibilityTrait;
    use VoterAttributeTrait;

    protected ContainerInterface $container;

    protected string $acronym = '';
    protected array $options = [];

    protected ?DefinitionInterface $definition = null;

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
            'label' => $this->acronym,
            'callable' => null,
            'attr' => [],
            'visibility' => [Page::SHOW, Page::EDIT, Page::CREATE],
            'show_voter_attribute' => Page::SHOW,
            'edit_voter_attribute' => Page::EDIT,
            'create_voter_attribute' => Page::CREATE,
            'block_prefix' => StringUtil::fqcnToBlockPrefix(static::class),
        ]);

        $resolver->setAllowedTypes('visibility', 'array');
    }

    public function setOptions(array $options): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function setOption($name, $value): static
    {
        if (!$this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
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
        if (!$this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
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
        if (is_callable($this->options['callable'])) {
            if (is_array($this->options['callable'])) {
                return call_user_func($this->options['callable'], [$row]);
            }

            return $this->options['callable']($row);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            return $propertyAccessor->getValue($row, $this->options['accessor_path']);
        } catch (UnexpectedTypeException) {
            return null;
        } catch (NoSuchPropertyException $noSuchPropertyException) {
            return $noSuchPropertyException->getMessage();
        }
    }

    public function getBlockPrefix(): string
    {
        return $this->options['block_prefix'];
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
        ];
    }
}

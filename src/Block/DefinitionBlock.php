<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Block;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionBlock extends Block
{
    /**
     * Defines the accessor path to the data.
     * Defaults to <code>null</code>
     * Accepts: <code>string|null</code>.
     */
    public const OPT_ACCESSOR_PATH = 'accessor_path';

    /**
     * With the callable you can define custom data which is given to the block.
     * The callable is called with the entity as parameter and should return the data.
     * Defaults to <code>null</code>
     * Accepts: <code>callable|null</code>.
     */
    public const OPT_CALLABLE = 'callable';

    /**
     * Defines the referencing Block to be rendered.
     * Defaults to <code>null</code>
     * Accepts: <code>string</code>.
     */
    public const OPT_BLOCK = 'block';

    /**
     * Defines the referencing Definition to be rendered.
     * Defaults to <code>null</code>
     * Accepts: <code>string|null</code>.
     */
    public const OPT_DEFINITION = 'definition';

    /**
     * Defines override options for the referenced Block.
     * Defaults to an empty array <code>[]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_OVERRIDE = 'override';

    /**
     * Defines a callable to additionally configure the block.
     * The callable is called with the block as parameter.
     * Defaults to <code>null</code>
     * Accepts: <code>callable|null</code>.
     */
    public const OPT_CONFIGURE = 'configure';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            self::OPT_ACCESSOR_PATH => null,
            self::OPT_CALLABLE => null,
            self::OPT_BLOCK => $this->acronym,
            self::OPT_DEFINITION => null,
            self::OPT_OVERRIDE => [],
            self::OPT_CONFIGURE => null,
        ]);
        $resolver->setRequired([
            self::OPT_BLOCK,
        ]);
        $resolver->setAllowedTypes(self::OPT_DEFINITION, ['string', 'null']);
        $resolver->setAllowedTypes(self::OPT_BLOCK, 'string');
        $resolver->setAllowedTypes(self::OPT_CALLABLE, ['null', 'callable', 'array']);
        $resolver->setAllowedTypes(self::OPT_OVERRIDE, 'array');
        $resolver->setAllowedTypes(self::OPT_CONFIGURE, ['null', 'callable']);
        $resolver->setAllowedTypes(self::OPT_ACCESSOR_PATH, ['null', 'string']);
    }

    public function getData(mixed $row): mixed
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

    public function getReferencingDefinition(mixed $data): ?DefinitionInterface
    {
        $optionDefinition = $this->getOption(self::OPT_DEFINITION);
        if ($optionDefinition === null) {
            $definition = $this->getDefinitionManager()->getDefinitionByEntity($data);
        } else {
            $definition = $this->getDefinitionManager()->getDefinitionByClassName($optionDefinition);
        }
        if ($this->getAccessorPath()) {
            $definition->setFormAccessorPrefix($this->definition->getFormAccessorPrefix() . $this->getAccessorPath() . '_');
        }

        return $definition;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [DefinitionManager::class]);
    }

    public function getAccessorPath(): ?string
    {
        return $this->options[self::OPT_ACCESSOR_PATH];
    }

    protected function getDefinitionManager(): DefinitionManager
    {
        return $this->container->get(DefinitionManager::class);
    }
}

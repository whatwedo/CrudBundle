<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Block;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DefinitionBlock extends Block
{
    public const OPT_ACCESSOR_PATH = 'accessor_path';

    public const OPT_CALLABLE = 'callable';

    public const OPT_BLOCK = 'block';

    public const OPT_DEFINITION = 'definition';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            self::OPT_ACCESSOR_PATH => null,
            self::OPT_CALLABLE => null,
            self::OPT_BLOCK => $this->acronym,
            self::OPT_DEFINITION => null,
        ]);
        $resolver->setRequired([
            self::OPT_BLOCK,
        ]);
        $resolver->setAllowedTypes(self::OPT_DEFINITION, ['string', 'null']);
        $resolver->setAllowedTypes(self::OPT_BLOCK, 'string');
        $resolver->setAllowedTypes(self::OPT_CALLABLE, ['null', 'callable', 'array']);
    }

    public function getData($row): mixed
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
}

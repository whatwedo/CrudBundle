<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Formatter\FormatterInterface;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Formatter\CrudDefaultFormatter;

class Content extends AbstractContent
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'formatter' => CrudDefaultFormatter::class,
            'formatter_options' => [],
            'help' => null,
            'preselect_definition' => null,
            'attr' => [],
            'form_type' => null,
            'form_options' => [],
            'ajax_form_trigger' => false,
        ]);
        $resolver->setAllowedTypes('formatter', ['string', 'null']);
        $resolver->setAllowedValues('formatter', function ($value) {
            $isNull = $value === null;
            $isFormatterFqdn = !$isNull && class_exists($value) && in_array(FormatterInterface::class, class_implements($value), true);
            return $isNull || $isFormatterFqdn;
        });

        $resolver->setAllowedTypes('formatter_options', 'array');
        $resolver->setAllowedTypes('help', ['null', 'string']);
        $resolver->setAllowedTypes('preselect_definition', ['null', 'string']);
        $resolver->setAllowedValues('preselect_definition', function ($value) {
            $isNull = $value === null;
            $isDefinitionFqdn = !$isNull && class_exists($value) && in_array(DefinitionInterface::class, class_implements($value), true);
            return $isNull || $isDefinitionFqdn;
        });
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('form_type', ['null', 'string']);
        $resolver->setAllowedValues('form_type', function ($value) {
            $isNull = $value === null;
            $isFormTypeFqdn = !$isNull && class_exists($value) && in_array(FormTypeInterface::class, class_implements($value), true);
            return $isNull || $isFormTypeFqdn;
        });
        $resolver->setAllowedTypes('form_options', 'array');
        $resolver->setAllowedTypes('ajax_form_trigger', 'boolean');
    }

    public function getFormOptions(array $options = []): array
    {
        // Override options for the EntityHiddenType and HiddenType
        if (in_array($this->getOption('form_type'), [EntityHiddenType::class, HiddenType::class], true)) {
            $this->options['label'] = false;
        }

        // Override help option
        if ($this->options['help']
            && (! isset($this->options['form_options']['attr']['help']))) {
            $this->options['form_options']['attr']['help'] = $this->options['help'];
        }

        // Override label
        return array_merge($options, [
            'label' => $this->options['label'],
        ], $this->options['form_options']);
    }

    public static function getSubscribedServices(): array
    {
        return [
            FormatterManager::class,
        ];
    }
}

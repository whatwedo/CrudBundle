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
    /**
     * Defines the formatter to be used. Formatters allow you to customize hot the data is being rendered.
     * Defaults to <code>whatwedo\CrudBundle\Formatter\CrudDefaultFormatter</code>
     * Accepts: <code>null|FormatterInterface</code>.
     */
    public const OPT_FORMATTER = 'formatter';

    /**
     * Defines the formatter options. Some formatters like the <code>DateTimeFormatter</code> allow you to pass options.
     * Defaults to an empty array <code>[]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_FORMATTER_OPTIONS = 'formatter_options';

    /**
     * Defines preset data in the form. For example you have a create form and you want to preset the relation
     * to another entity. You can do this by setting the <code>preselect_definition</code> to a desired Definition class
     * and pass the id in the url. E.g. like following: <code>/app_some_entity/create?[preselectDefinition::getAlias]=[otherEntity->id]</code>
     * Defaults to <code>null</code>
     * Accepts: <code>null|DefinitionInterface</code>.
     */
    public const OPT_PRESELECT_DEFINITION = 'preselect_definition';

    /**
     * Defines the form type to be used. Uses the same logic as symfony form types if kept null.
     * Defaults to <code>null</code>
     * Accepts: <code>null|FormTypeInterface</code>.
     */
    public const OPT_FORM_TYPE = 'form_type';

    /**
     * Defines the form type options.
     * Defaults to an empty array <code>[]</code>
     * Accepts: <code>array</code>.
     */
    public const OPT_FORM_OPTIONS = 'form_options';

    /**
     * Defines whether this content will trigger a ajax change to the definition or not.
     * Defaults to <code>false</code>
     * Accepts: <code>bool</code>.
     */
    public const OPT_AJAX_FORM_TRIGGER = 'ajax_form_trigger';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(self::OPT_CALLABLE, null);
        $resolver->setDefault(self::OPT_FORMATTER, CrudDefaultFormatter::class);
        $resolver->setDefault(self::OPT_FORMATTER_OPTIONS, []);
        $resolver->setDefault(self::OPT_PRESELECT_DEFINITION, null);
        $resolver->setDefault(self::OPT_FORM_TYPE, null);
        $resolver->setDefault(self::OPT_FORM_OPTIONS, []);
        $resolver->setDefault(self::OPT_AJAX_FORM_TRIGGER, false);

        $resolver->setAllowedTypes(self::OPT_FORMATTER, 'string');
        $resolver->setAllowedValues(self::OPT_FORMATTER, function ($value) {
            $isNull = $value === null;
            $isFormatterFqdn = ! $isNull && class_exists($value) && in_array(FormatterInterface::class, class_implements($value), true);

            return $isNull || $isFormatterFqdn;
        });

        $resolver->setAllowedTypes(self::OPT_FORMATTER_OPTIONS, 'array');
        $resolver->setAllowedTypes(self::OPT_PRESELECT_DEFINITION, ['null', 'string']);
        $resolver->setAllowedValues(self::OPT_PRESELECT_DEFINITION, function ($value) {
            $isNull = $value === null;
            $isDefinitionFqdn = ! $isNull && class_exists($value) && in_array(DefinitionInterface::class, class_implements($value), true);

            return $isNull || $isDefinitionFqdn;
        });
        $resolver->setAllowedTypes(self::OPT_FORM_TYPE, ['null', 'string']);
        $resolver->setAllowedValues(self::OPT_FORM_TYPE, function ($value) {
            $isNull = $value === null;
            $isFormTypeFqdn = ! $isNull && class_exists($value) && in_array(FormTypeInterface::class, class_implements($value), true);

            return $isNull || $isFormTypeFqdn;
        });
        $resolver->setAllowedTypes(self::OPT_FORM_OPTIONS, 'array');
        $resolver->setAllowedTypes(self::OPT_AJAX_FORM_TRIGGER, 'boolean');
    }

    public function getFormOptions(array $options = []): array
    {
        // Override options for the EntityHiddenType and HiddenType
        if (in_array($this->getOption(self::OPT_FORM_TYPE), [EntityHiddenType::class, HiddenType::class], true)) {
            $this->options['label'] = false;
        }

        // Override help option
        if ($this->options[self::OPT_HELP]
            && (! isset($this->options[self::OPT_FORM_OPTIONS][self::OPT_ATTR][self::OPT_HELP]))) {
            $this->options[self::OPT_FORM_OPTIONS][self::OPT_ATTR][self::OPT_HELP] = $this->options[self::OPT_HELP];
        }

        // Override label
        return array_merge($options, [
            'label' => $this->options['label'],
        ], $this->options[self::OPT_FORM_OPTIONS]);
    }

    public static function getSubscribedServices(): array
    {
        return [
            FormatterManager::class,
        ];
    }
}

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
    public const OPT_FORMATTER = 'formatter';

    public const OPT_FORMATTER_OPTIONS = 'formatter_options';

    public const OPT_HELP = 'help';

    public const OPT_PRESELECT_DEFINITION = 'preselect_definition';

    public const OPT_ATTR = 'attr';

    public const OPT_FORM_TYPE = 'form_type';

    public const OPT_FORM_OPTIONS = 'form_options';

    public const OPT_AJAX_FORM_TRIGGER = 'ajax_form_trigger';

    public const OPT_CALLABLE = 'callable';

    public const OPT_LABEL = 'label';

    public const OPT_VISIBILITY = 'visibility';

    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    public const OPT_BLOCK_PREFIX = 'block_prefix';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(self::OPT_CALLABLE, null);
        $resolver->setDefault(self::OPT_FORMATTER, CrudDefaultFormatter::class);
        $resolver->setDefault(self::OPT_FORMATTER_OPTIONS, []);
        $resolver->setDefault(self::OPT_HELP, null);
        $resolver->setDefault(self::OPT_PRESELECT_DEFINITION, null);
        $resolver->setDefault(self::OPT_ATTR, []);
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
        $resolver->setAllowedTypes(self::OPT_HELP, ['null', 'string', 'boolean']);
        $resolver->setAllowedTypes(self::OPT_PRESELECT_DEFINITION, ['null', 'string']);
        $resolver->setAllowedValues(self::OPT_PRESELECT_DEFINITION, function ($value) {
            $isNull = $value === null;
            $isDefinitionFqdn = ! $isNull && class_exists($value) && in_array(DefinitionInterface::class, class_implements($value), true);

            return $isNull || $isDefinitionFqdn;
        });
        $resolver->setAllowedTypes(self::OPT_ATTR, 'array');
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

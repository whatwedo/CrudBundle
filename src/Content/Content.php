<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Formatter\CrudDefaultFormatter;

class Content extends AbstractContent
{
    public const OPT_ACCESSOR_PATH = 'accessor_path';

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

        $resolver->setDefault(self::OPT_ACCESSOR_PATH, $this->acronym);
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
    }

    public function render($row): string
    {
        return $this->formatData($this->getContents($row), $this->options[self::OPT_FORMATTER], $this->options[self::OPT_FORMATTER_OPTIONS]);
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

    protected function formatData($data, $formatter, $formatterOptions): string
    {
        if (is_string($formatter)) {
            $formatterObj = $this->container->get(FormatterManager::class)->getFormatter($formatter);
            $formatterObj->processOptions($formatterOptions);

            return (string) $formatterObj->getHtml($data);
        }

        if (is_callable($formatter)) {
            return (string) $formatter($data);
        }

        return (string) $data;
    }
}

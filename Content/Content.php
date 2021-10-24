<?php
declare(strict_types=1);


namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;

class Content extends AbstractContent
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'callable' => null,
            'formatter' => DefaultFormatter::class,
            'formatter_options' => [],
            'help' => null,
            'preselect_definition' => null,
            'attr' => [],
            'form_type' => null,
            'form_options' => [],
        ]);
    }

    public function render($row): string
    {
        return $this->formatData($this->getContents($row), $this->options['formatter'], $this->options['formatter_options']);
    }

    public function getFormOptions(array $options = []): array
    {
        // Override options for the EntityHiddenType and HiddenType
        if (in_array($this->getOption('form_type'), [EntityHiddenType::class, HiddenType::class], true)) {
            $this->options['label'] = false;
        }

        // Override help option
        if ($this->options['help']
            && (!isset($this->options['form_options']['attr']['help']))) {
            $this->options['form_options']['attr']['help'] = $this->options['help'];
        }

        // Override label
        return array_merge($options, [
            'label' => $this->options['label'],
        ], $this->options['form_options']);
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

    public static function getSubscribedServices(): array
    {
        return [
            FormatterManager::class,
        ];
    }
}

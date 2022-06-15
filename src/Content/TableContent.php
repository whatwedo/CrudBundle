<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\TableBundle\Table\Table;

class TableContent extends AbstractContent
{
    public function isTable(): bool
    {
        return true;
    }

    public function renderTable(Table $table, $row)
    {
        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $actionColumnItems = [];

        if ($this->getOption('definition')
            && $this->hasCapability(Page::SHOW)) {
            $showRoute = $this->getRoute(Page::SHOW);

            $table->setShowRoute($showRoute);
            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $showRoute,
                'route_parameters' => [],
                'voter_attribute' => Page::SHOW,
            ];
        }

        $data = $this->getContents($row);

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        if (is_string($data)) {
            throw new \Exception($data);
        }

        $table->setResults(array_values($data));

        return $table->renderTable();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'table_configuration' => null,
            'definition' => null,
            'route_addition_key' => null,
            'show_index_button' => false,
        ]);

        $resolver->setAllowedTypes('accessor_path', 'string');
        $resolver->setAllowedTypes('table_configuration', ['null', 'callable']);
        $resolver->setAllowedTypes('definition', ['null', 'string']);
        $resolver->setAllowedValues('definition', function ($value) {
            $isNull = $value === null;
            $isDefinitionFqdn = !$isNull && class_exists($value) && in_array(DefinitionInterface::class, class_implements($value), true);
            return $isNull || $isDefinitionFqdn;
        });
        $resolver->setAllowedTypes('route_addition_key', ['null', 'string']);
        $resolver->setAllowedTypes('show_index_button', 'boolean');
    }

    protected function hasCapability($capability): bool
    {
        return call_user_func([$this->getOption('definition'), 'hasCapability'], $capability);
    }

    protected function getRoute($suffix): string
    {
        return call_user_func([$this->options['definition'], 'getRoute'], $suffix);
    }
}

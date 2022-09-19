<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\TableBundle\Table\Table;

class TableContent extends AbstractContent
{
    public const OPT_ACCESSOR_PATH = 'accessor_path';

    public const OPT_TABLE_CONFIGURATION = 'table_configuration';

    public const OPT_DEFINITION = 'definition';

    public const OPT_ROUTE_ADDITION_KEY = 'route_addition_key';

    public const OPT_SHOW_INDEX_BUTTON = 'show_index_button';

    public const OPT_LABEL = 'label';

    public const OPT_CALLABLE = 'callable';

    public const OPT_ATTR = 'attr';

    public const OPT_VISIBILITY = 'visibility';

    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    public const OPT_BLOCK_PREFIX = 'block_prefix';

    public function isTable(): bool
    {
        return true;
    }

    public function renderTable(Table $table, mixed $row): string
    {
        if (is_callable($this->options[self::OPT_TABLE_CONFIGURATION])) {
            $this->options[self::OPT_TABLE_CONFIGURATION]($table);
        }

        $actionColumnItems = [];

        if ($this->getOption(self::OPT_DEFINITION)
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
            self::OPT_ACCESSOR_PATH => $this->acronym,
            self::OPT_TABLE_CONFIGURATION => null,
            self::OPT_DEFINITION => null,
            self::OPT_ROUTE_ADDITION_KEY => null,
            self::OPT_SHOW_INDEX_BUTTON => false,
        ]);

        $resolver->setAllowedTypes('accessor_path', 'string');
        $resolver->setAllowedTypes('table_configuration', ['null', 'callable']);
        $resolver->setAllowedTypes('definition', ['null', 'string']);
        $resolver->setAllowedValues('definition', function ($value) {
            $isNull = $value === null;
            $isDefinitionFqdn = ! $isNull && class_exists($value) && in_array(DefinitionInterface::class, class_implements($value), true);

            return $isNull || $isDefinitionFqdn;
        });
        $resolver->setAllowedTypes('route_addition_key', ['null', 'string']);
        $resolver->setAllowedTypes('show_index_button', 'boolean');
    }

    protected function hasCapability(?PageInterface $capability): bool
    {
        return call_user_func([$this->getOption(self::OPT_DEFINITION), 'hasCapability'], $capability);
    }

    protected function getRoute(mixed $suffix): string
    {
        return call_user_func([$this->options[self::OPT_DEFINITION], 'getRoute'], $suffix);
    }
}

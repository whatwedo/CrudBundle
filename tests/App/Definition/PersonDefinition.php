<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests\App\Definition;

use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Tests\App\Entity\Person;
use whatwedo\TableBundle\Table\Table;

class PersonDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return Person::class;
    }

    /**
     * @param Person $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock('base')
            ->addContent('name', null, [
            ])
        ;
    }

    public function getFormOptions(Page $page, object $data): array
    {
        if ($page === Page::EDIT) {
            // only check in edit case if the name is not valid
            return [
                'validation_groups' => ['Default', 'check-not-valid'],
            ];
        }

        return [];
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
            ])
        ;
    }
}

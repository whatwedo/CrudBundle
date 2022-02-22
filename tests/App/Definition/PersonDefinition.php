<?php

namespace whatwedo\CrudBundle\Tests\App\Definition;

use whatwedo\CrudBundle\Tests\App\Entity\Person;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
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
            ->addBlock( 'base')
                ->addContent('name', null, [
                ])
        ;
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

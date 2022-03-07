<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests\App\Definition;

use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Tests\App\Entity\Category;
use whatwedo\TableBundle\Table\Table;

class CategoryDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return Category::class;
    }

    /**
     * @param Category $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock('base')
            ->addContent('name', null, [
            ])
            ->addContent('lft', null, [
            ])
            ->addContent('lvl', null, [
            ])
            ->addContent('rgt', null, [
            ])
        ;
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
            ])
            ->addColumn('lft', null, [
            ])
            ->addColumn('lvl', null, [
            ])
            ->addColumn('rgt', null, [
            ])
        ;
    }
}

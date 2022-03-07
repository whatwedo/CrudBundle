<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests\App\Definition;

use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Tests\App\Entity\Company;
use whatwedo\TableBundle\Table\Table;

class CompanyDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return Company::class;
    }

    /**
     * @param Company $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock('base')
            ->addContent('name', null, [
            ])
            ->addContent('city', null, [
            ])
            ->addContent('country', null, [
            ])
            ->addContent('taxIdentificationNumber', null, [
            ])
        ;
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
            ])
            ->addColumn('city', null, [
            ])
            ->addColumn('country', null, [
            ])
            ->addColumn('taxIdentificationNumber', null, [
            ])
        ;
    }
}

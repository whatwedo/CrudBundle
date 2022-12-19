<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_full_class_name; ?>;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\TableBundle\Table\Table;

class <?= $class_name; ?> extends AbstractDefinition
{

    public static function getEntity(): string
    {
        return <?= $entity_class_name; ?>::class;
    }

    /**
    * @param <?= $entity_class_name; ?> $data
    */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        parent::configureView($builder, $data);

        $builder
            ->addBlock( 'base')
    <?php foreach ($fields as $label => $field): ?>
            ->addContent('<?= $field; ?>', null, [
        <?php if (isset($fieldFormatters[$field])) {
            echo sprintf("'formatter' => \\%s::class,", $fieldFormatters[$field]);
        } ?>
        ])
    <?php endforeach; ?>
    ;
    }

    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
    <?php foreach ($fields as $label => $field): ?>
        ->addColumn('<?= $field; ?>', null, [
        <?php if (isset($fieldFormatters[$field])) {
            echo sprintf("'formatter' => \\%s::class,", $fieldFormatters[$field]);
        } ?>
        ])
    <?php endforeach; ?>
    ;
}
}

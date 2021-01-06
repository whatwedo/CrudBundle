<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use <?= $entity_full_class_name ?>;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Enum\BlockSizeEnum;
use whatwedo\TableBundle\Table\Table;

class <?= $class_name ?> extends AbstractDefinition
{
    public static function getEntityTitle(): string
    {
        return '<?= $entity_twig_var_singular ?>.title';
    }

    public static function getEntity(): string
    {
        return <?= $entity_class_name ?>::class;
    }

    /**
     * @param <?= $entity_class_name ?> $data
     */
    public function configureView(DefinitionBuilder $builder, $data): void
    {
        $builder
            ->addBlock(
                'basic',
                null,
                [
                    'label' => 'title.<?= $entity_twig_var_singular ?>',
                    'size' => BlockSizeEnum::LARGE,
                ]
            )
<?php foreach ($fields as $label => $field): ?>
            ->addContent(
                '<?= $field ?>',
                null,
                [
                    'label' => '<?= $entity_twig_var_singular ?>.<?= $label ?>',
                    <?php
    if (isset($fieldFormatters[$field]) ) {
        echo sprintf("'formatter' => \\%s::class,", $fieldFormatters[$field]);
    }
    ?>

                ]
            )
<?php endforeach ?>
            ;
    }

    public function configureTable(Table $table): void
    {
        $table
<?php foreach ($fields as $label => $field): ?>
            ->addColumn('<?= $field ?>', null, [
                'label' => '<?= $entity_twig_var_singular ?>.<?= $label ?>',
                <?php
        if (isset($fieldFormatters[$field]) ) {
            echo sprintf("'formatter' => \\%s::class,", $fieldFormatters[$field]);
        }
    ?>

            ])
<?php endforeach ?>
        ;
    }
}

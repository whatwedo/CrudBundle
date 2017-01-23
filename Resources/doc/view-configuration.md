# View Configuration

in the view configuration you define all detail views of your entity (create, edit, view). 

Every view is splitted in one or more blocks (like groups of properties).

## Build a simple view

In the following example we are creating one block with three properties. Those are similar to the Symfony Form Component. Every block has an acronym, an options array and every content has an acronym, a type (defaults to `null` for a simple content) and an options array.

you can add as many blocks as you want

```
/**
 * {@inheritdoc}
 */
public function configureView(DefinitionBuilder $builder, $data)
{
    $builder->addBlock('example', [
        'label' => 'Example Block',
    ])
        ->addContent('firstname', null, [
            'label' => 'Your Firstname',
        ])
        ->addContent('lastname', null, [
            'label' => 'Your Lastname',
        ])
        ->addContent('company', null, [
            'label' => 'Your Company',
        ])
        ->addContent('email', null, [
            'label' => 'Your Company',
            'formatter' => EmailFormatter::class,
        ])
    ;
}
```

### Block Options

- `label`: Title of the block

### Content Options

 - `accessor_path`: default to the acronym
 - `callable`: callable to get the data, no accessor_path needed
 - `formatter`: [formatter class](formatter.md) to format the output of the field
 - `read_only`: disables the form generation for this field
 - `form_type`: custom [form type](https://symfony.com/doc/current/reference/forms/types.html) (only needed if symfony takes the wrong one)
 - `form_options`: options given to the form type
 - `preselect_definition`: needed for relations, see below



## Add a relation
 
A relation will render a custom table inside the show-View with add, view and edit buttons. This is how to configure this:

```
// UserDefinition.php

$builder->addBlock('subscription', [
    'label' => 'Subscriptions',
])
    ->addContent('subscriptions', RelationContent::class, [
        'label' => 'Subscriptions',
        'definition' => SubscriptionDefinition::class,
        'route_addition_key' => static::getChildRouteAddition(), // this will add `?{route_addition_key}={id}` to the create route, to preselect the current entity - overwrite this in your definition to your desired parameter
        'table_configuration' => function(Table $table) {
            $table
                ->addColumn('product', null, [
                    'label' => 'Product',
                ])
// ...
            ;
        },
    ])
;
```

```
// SubscriptionDefinition.php

->addContent('user', null, [
    'label' => 'Client',
    'preselect_definition' => UserDefinition::class, // to know which parameter is the preselected user
    'form_type' => EntityType::class,
    'form_options' => [
        'class' => UserDefinition::getEntity(),
    ],
])
```
 
### Relation Content Options

- `accessor_path`: defaults to acronym
- `table_configuration`: callable with the table configuration
- `definition`: definition of the relation
- `route_addition_key`: parameter to be added to the query on create route

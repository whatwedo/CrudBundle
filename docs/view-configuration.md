# View Configuration

In the view configuration you define all detail views of your entity (create, edit, show). 

Every view is splitted into one or more blocks (like groups of properties).

## Build a simple view

In the following example we are creating one block with three properties. Those are similar to the Symfony Form Component. Every block has an acronym, an options array. Every content has an acronym, a type (defaults to `null` for a simple content) and an options array.

You can add as many blocks as you want.

```
/**
 * {@inheritdoc}
 */
public function configureView(DefinitionBuilder $builder, $data)
{
    $builder->addBlock('example', null, [
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

## Add a relation
 
### OneToMany / ManyToMany

Explain how to add a relation to a view.

### ManyToOne / OneToOne

Explain how to add a relation to a view.

### Ajax in Create/Edit

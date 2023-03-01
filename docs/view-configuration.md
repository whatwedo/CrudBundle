# View Configuration

In the view configuration you define all detail views of your entity (create, edit, show). 

Every view is splitted into one or more blocks (like groups of properties).

## Build a simple view

In the following example we are creating one block with three properties. Those are similar to the Symfony Form Component. Every block has an acronym, an options array. Every content has an acronym, a type (defaults to `null` for a simple content) and an options array.

You can add as many blocks as you want. There are different kind of blocks. Find the differences here [Block](/blocks/block.md).

```php
class ExampleDefinition extends AbstractDefinition
{
    // ...
    
    /**
     * {@inheritdoc}
     */
    public function configureView(DefinitionBuilder $builder, $data)
    {
        $builder
            ->addBlock('example', null, [
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
    // ...
}
```

## Add a relation
 
### OneToMany / ManyToMany / ManyToOne / OneToOne
All Relation are handled with the [RelationContent](/contents/relation-content.md).

For example to add the books relation the an author entity it would look like this:

````php
    $builder
        ->addBlock( 'base')
        ->addContent('books')
    ;
````
Under the hood we recognize the relation and create a [RelationContent](/contents/relation-content.md) for you.
````php
    $builder
        ->addBlock( 'base')
        ->addContent('books', RelationContent::class, [
            'label' => 'Books',
        ])
    ;
````

### Ajax in Create/Edit
You can use Ajax on the edit and create pages.
To do so active the capability on this definition:
```php
    public static function getCapabilities(): array
    {
        return array_merge(parent::getCapabilities(), [Page::AJAXFORM]);
    }
```
You also need to choose which fields will trigger an ajax request.
For example while creating a Book entity we want the authors name prefixed as book name.
```php
    $builder
        ->addBlock('base')
        ->addContent('author', null, [
            Content::OPT_AJAX_FORM_TRIGGER => true,
        ])
```
Then override the `ajaxForm` function. For the described example above this coud look something like this:
```php
public function ajaxForm(object $entity, PageInterface $page): void
{
    if ($page === Page::CREATE && $entity->getAuthor() !== null) {
        $entity->setName($entity->getAuthor()->getName() . ': ');
    }
    if ($entity->getAuthor() === null) {
        $entity->setName('');
    }
}
```

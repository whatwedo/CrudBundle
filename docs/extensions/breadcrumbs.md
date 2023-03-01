# Breadcrumbs Extension

This extension allows adding auto-generated breadcrumbs to your project.

## Requirements
This extension requires the [mhujer/breadcrumbs-bundle](https://github.com/mhujer/BreadcrumbsBundle) bundle.

## Installation

- 1. follow the installation instructions on [mhujer/breadcrumbs-bundle GitHub Page](https://github.com/mhujer/BreadcrumbsBundle).
- 2. configure the bundle:

```yml
whatwedo_crud:
    breadcrumbs:
        home_text: Dashboard
        home_route: my_project_dashboard_start
```

- 3. clear the cache of your application

## Usage

### Prepend custom routes

If you have objects with relations in your application, you might want to prepend custom attributes.

You can overwrite them in your definition-file. 

```php
class ProductDefinition extends AbstractDefinition
{
    public function buildBreadcrumbs(mixed $entity = null, ?PageInterface $route = null, ?Breadcrumbs $breadcrumbs = null): void
    {
        parent::buildBreadcrumbs($entity, $route, $breadcrumbs);   
        $this->getBreadcrumbs()->addItem('Some Custom Text', 'some_custom_route');
    }
}
```

You can let the bundle automatically build the breadcrumbs. For that to work you have to define the parents of each entity.

```php 
class ProductContentDefinition extends AbstractDefinition
{
    public function getParentDefinitionProperty(?object $data): ?string
    {
        return 'product';
    }
}
```

Where possible, we inject the entity and the current route, but try not to rely on it.

### Use it outside of Definitions and CrudController

To use the breadcrumbs outside of the definitions, you can use this snippet:

```php
class SomeController extends AbstractController
{
    public function __construct(protected BreadcrumbsExtension $breadcrumbsExtension)
    {
    }

    #[Route('/some', name: 'some')]
    public function someAction()
    {
        $this->breadcrumbsExtension->getBreadcrumbs()->addItem('Some Text', 'some_route');
        return $this->render('some.html.twig');
    }
}
```

And render it in your template like following:

```twig
{{ wo_render_breadcrumbs() }}
```

### Remove breadcrumb
For example, we have a company definition where we display all employees. Now when we want to view an employee the breadcrumb looks like this:

`companies -> company -> employees -> employee`

Now we would like to remove the breadcrumb employees, so that we do not come to the overview of all employees. Add the following code to the employee definition:

```php
public function getParentDefinitionProperty(?object $data): ?string
{
    return 'company';
}

public function buildBreadcrumbs(mixed $entity = null, ?PageInterface $route = null, ?Breadcrumbs $breadcrumbs = null): void
{
    parent::buildBreadcrumbs($entity, $route, $breadcrumbs);
    // Employees breadcrumb is the third. Offset starts at 0
    $this->getBreadcrumbs()->offsetUnset(2);
}
```

# Breadcrumbs Extension

This extension allows adding auto-generated breadcrumbs to your project.

## Requirements
This extension requires the [whiteoctober/BreadcrumbsBundle](https://github.com/whiteoctober/BreadcrumbsBundle) bundle.

## Installation

1. follow the installation instructions on [whiteoctober/BreadcrumbsBundle GitHub Page](https://github.com/whiteoctober/BreadcrumbsBundle).
2. configure the bundle:
```yml

whatwedo_crud:
    breadcrumbs:
        home_text: Dashboard
        home_route: my_project_dashboard_start

```
3. add the breadcrumbs to your frontend: `{{ wo_render_breadcrumbs() }}`
3. clear the cache of your application

## Usage

### Prepend custom routes

If you objects with relations in your application, you might want prepend custom attributes.

You can overwrite them in your Definition-File. 

```php
// Just add one unlinked item
...
class ProductDefinition extends AbstractDefinition
{
    public static function getEntityTitle()
    {
        return 'My Products';
    }

    public function buildBreadcrumbs($entity = null, $route = null)
    {
        $this->getBreadcrumbs()->addItem('Product Management');
        parent::buildBreadcrumbs($entity, $route);
    }
...




// add parent entity
class ProductContentDefinition extends AbstractDefinition
{
    public static function getEntityTitle()
    {
        return 'My Product Contents';
    }

    public function buildBreadcrumbs($entity = null, $route = null)
    {
        $this->getBreadcrumbs()->addItem('Product Management');

        $product = null;
        if ($entity instanceof Content
            && $entity->getProduct() instanceof Product) {
            $product = $entity->getProduct();
        }

        // if you create a new content from the product, we pass the product id by parameter
        if ($this->getRequestStack()->getCurrentRequest()->query->has(ProductDefinition::getQueryAlias())) {
            $product = $this->getDoctrine()->getRepository(ProductDefinition::getEntity())->find(
                $this->getRequestStack()->getCurrentRequest()->query->get(ProductDefinition::getQueryAlias())
            );
        }

        if ($product instanceof Product) {
            $this->getBreadcrumbs()->addRouteItem(ProductDefinition::getEntityTitle(), ProductDefinition::getRoutePrefix() . '_' . RouteEnum::INDEX);
            $this->getBreadcrumbs()->addRouteItem($product->__toString(), ProductDefinition::getRoutePrefix() . '_' . RouteEnum::SHOW, ['id' => $product->getId()]);
        }

        parent::buildBreadcrumbs($entity, $route);
    }
...
```
Where possible, we inject the entity and the current route, but try to not rely on it.

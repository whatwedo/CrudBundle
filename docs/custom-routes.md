# Custom Routes
If you add custom routes to your Controller and extend from the `CrudController`
you can get the `Definition` from the `CrudController`. This works as long as each controller 
is only used for one `Definition`. If a custom Controller is used for multiple Definitions you
need to mark which definition to bind in the Route annotations as following:


```php
#[Route('/feature/xxx', name: 'whatwedo_feature_xxx', defaults: ['_resource' => XXXDefinition::class])]
public function xxxAction(Request $request): Response
{
    // ...
}
```

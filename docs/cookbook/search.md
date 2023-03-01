# Search

The CrudBundle uses the whatwedo/search-bundle to enable fulltext search without dependencies.
The search bundle is documented in the [SearchBundle](https://whatwedo.github.io/SearchBundle/#/).

## Prerequisites

#### Enable doctrine functions

Doctrine does not support `MATCH AGAINST` per default. You can enable it by adding the following lines to your `config/packages/doctrine.yaml`

```yaml
doctrine:
    orm:
        dql:
            string_functions:
                MATCH_AGAINST: whatwedo\SearchBundle\Extension\Doctrine\Query\Mysql\MatchAgainst
```

Next, update your database schema.

```sh
php bin/console doctrine:schema:update --force
```


## Search on Definition


#### Enable fields for indexing

Use the ```#[Index]``` annotation in order to enable a field for indexing.


```php
use Doctrine\ORM\Mapping as ORM;
use whatwedo\SearchBundle\Annotation\Index;

#[ORM\Entity]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    
    #[ORM\Column(type: 'string', length: 255)]
    #[Index]
    private $title;

    #[ORM\Column(type: 'string', length: 255)]
    #[Index]
    private $description;

    // ...
}
```

After that you have to update your index

```sh
php bin/console whatwedo:search:populate
```

## Global Search

The bundle comes already with a predefined Controller.

Controller/SearchController.php

```php
class SearchController extends AbstractController
{
    use SearchTrait;

    public function search(Request $request, SearchManager $searchManager): Response
    {
        $templateParams = $this->getGlobalResults($request, $searchManager);

        return $this->render($this->getSearchTemplate(), $templateParams);
    }
}
```

If you want to restrict results to certain entities use ```SearchOptions::OPTION_ENTITIES```

```php
$searchParams = $this->getGlobalResults($request, $searchManager, [
    SearchOptions::OPTION_ENTITIES => [
        Post::class,
    ],
]);
```

### Configuration

#### Templates

Global search form is found here:  ```templates/base.html.twig```

```twig
{% block search_box %}
    <div class="whatwedo_crud-sidedar flex-shrink-0 flex border-t border-neutral-200 p-4">
        <label for="search" class="sr-only">Search</label>
        <div class="relative rounded-md shadow-sm w-full mt-1 z-0">
            <form action="{{ path('search') }}" method="get">
                <input
                    class="whatwedo_core-input"
                    name="query"
                    value=""
                    autocomplete="off"
                    placeholder="Suche ..."
                    type="text"
                >
            </form>
        </div>
    </div>
{% endblock %}
```

To customize the search results create a file ```index.html.twig``` in ```templates/bundles/whatwedoSearchBundle```

```twig
{% extends '@!whatwedoSearch/index.html.twig' %}

{% block results %}
    <h1>This is a custom heading</h1>
    {{ parent() }}
{% endblock %}
```


#### Index groups

You can define indexing groups and restrict search within these. If not specified the standard group is ```default```

```php
#[Index groups: ['default', 'posts']]
private $title;

#[Index]
private $description;
```

In the controller set which group(s) you want to include using ```SearchOptions::OPTION_GROUPS```

```php
$searchParams = $this->getGlobalResults($request, $searchManager, [
    SearchOptions::OPTION_GROUPS => [
        'posts'
    ],
]);
```


#### Formatters and hooks

You can use custom formatters, pre and post search hooks. 

Please check out the SearchBundle documentation for more information. 

https://whatwedo.github.io/SearchBundle/#/configuration

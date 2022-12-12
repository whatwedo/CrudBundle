# Search

The CrudBundle uses the whatwedo/search-bundle to enable fulltext search without dependencies.
The search bundle is documented in the [SearchBundle](https://whatwedo.github.io/SearchBundle/#/).

## Installation

Doctrine does not support `MATCH AGAINST` per default. You can enable it by adding the following lines to your `config/packages/doctrine.yaml`

```
doctrine:
    orm:
        dql:
            string_functions:
                MATCH_AGAINST: whatwedo\SearchBundle\Extension\Doctrine\Query\Mysql\MatchAgainst
```

Next, update your database schema.

```
php bin/console doctrine:schema:update --force
```



## Search on Definition
Explain how to enable search on a definition.

## Global Suche
Explanation of global search.

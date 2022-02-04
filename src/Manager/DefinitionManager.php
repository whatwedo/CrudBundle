<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Manager;

use whatwedo\CrudBundle\Definition\DefinitionInterface;

class DefinitionManager
{
    /**
     * @var DefinitionInterface[]
     */
    protected array $definitions = [];

    public function __construct(iterable $definitions)
    {
        foreach ($definitions as $definition) {
            $this->definitions[$definition::getAlias()] = $definition;
        }
    }

    public function getDefinitionByAlias(string $alias): DefinitionInterface
    {
        return $this->definitions[$alias]
            ?? throw new \InvalidArgumentException(sprintf('definition with the alias "%s" not found.', $alias));
    }

    public function getDefinitionByEntity($entity): DefinitionInterface
    {
        foreach ($this->definitions as $definition) {
            if ($definition::supports($entity)) {
                return $definition;
            }
        }

        throw new \InvalidArgumentException(sprintf('definition for entity "%s" not found.', is_string($entity) ? $entity : $entity::class));
    }

    public function getDefinitionByClassName(string $class): \whatwedo\CrudBundle\Definition\DefinitionInterface
    {
        foreach ($this->definitions as $definition) {
            if ($definition::class === $class) {
                return $definition;
            }
        }

        throw new \InvalidArgumentException(sprintf('definition "%s" not found.', $class));
    }

    public function getDefinitionByRoute($route): \whatwedo\CrudBundle\Definition\DefinitionInterface
    {
        // TODO: use Page-Enum for the matching of the Definition
        if (preg_match('#([\w\_\-]+)\_(\w+)#', $route, $routeMatches)) {
            foreach ($this->definitions as $definition) {
                if ($routeMatches[1] === $definition::getRoutePrefix()) {
                    return $definition;
                }
            }
        }

        throw new \InvalidArgumentException(sprintf('definition for route "%s" not found.', $route));
    }

    /**
     * @return DefinitionInterface[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
}

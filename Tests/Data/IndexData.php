<?php

namespace whatwedo\CrudBundle\Tests\Data;

class IndexData
{
    public function __construct(
        public bool $skip = false,
        public array $queryParameters = [],
    )
    {
    }

    public static function new(): self
    {
        return new self();
    }

    public function setSkip(bool $skip): self
    {
        $this->skip = $skip;
        return $this;
    }

    public function setQueryParameters(array $queryParameters): self
    {
        $this->queryParameters = $queryParameters;
        return $this;
    }
}

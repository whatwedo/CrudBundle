<?php

namespace whatwedo\CrudBundle\Tests\Data;

class ShowData
{
    public function __construct(
        public bool $skip = false,
        public array $queryParameters = [],
        public string $entityId = '1'
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

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }
}

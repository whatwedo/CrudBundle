<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Test\Data;

class ShowData
{
    public function __construct(
        protected bool $skip = false,
        protected array $queryParameters = [],
        protected string $entityId = '1',
        protected int $expectedStatusCode = 200
    ) {
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

    public function isSkip(): bool
    {
        return $this->skip;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getExpectedStatusCode(): int
    {
        return $this->expectedStatusCode;
    }

    public function setExpectedStatusCode(int $expectedStatusCode): self
    {
        $this->expectedStatusCode = $expectedStatusCode;

        return $this;
    }
}

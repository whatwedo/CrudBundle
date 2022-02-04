<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Test\Data;

class EditData
{
    public function __construct(
        protected bool $skip = false,
        protected array $queryParameters = [],
        protected string $entityId = '1',
        protected bool $fillForm = true,
        protected array $formData = [],
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

    public function setFillForm(bool $fillForm): self
    {
        $this->fillForm = $fillForm;

        return $this;
    }

    public function setFormData(array $formData): self
    {
        $this->formData = $formData;

        return $this;
    }

    public function setExpectedStatusCode(int $expectedStatusCode): self
    {
        $this->expectedStatusCode = $expectedStatusCode;

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

    public function isFillForm(): bool
    {
        return $this->fillForm;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getExpectedStatusCode(): int
    {
        return $this->expectedStatusCode;
    }
}

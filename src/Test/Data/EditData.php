<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Test\Data;

class EditData extends AbstractData
{
    public function __construct(
        protected bool $skip = false,
        protected array $queryParameters = [],
        protected string $entityId = '1',
        protected bool $fillForm = true,
        protected array $formData = [],
        protected int $expectedStatusCode = 302,
        protected bool $followRedirects = false,
        protected ?\Closure $assertCallback = null,
        protected ?\Closure $assertBeforeSendCallback = null,
    ) {
        parent::__construct(
            $this->skip,
            $this->queryParameters,
            $this->expectedStatusCode,
            $this->followRedirects,
            $this->assertCallback
        );
    }

    public static function new(): self
    {
        return new self();
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

    public function getAssertBeforeSendCallback(): ?\Closure
    {
        return $this->assertBeforeSendCallback;
    }

    public function setAssertBeforeSendCallback(\Closure $assertBeforeSendCallback): self
    {
        $this->assertBeforeSendCallback = $assertBeforeSendCallback;

        return $this;
    }
}

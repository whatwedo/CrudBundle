<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface HierarchicalEntityInterface
{
    public function getParent(): ?self;

    public function getLevel(): int;

    public function getChildren(): Collection;

    public function addChild(self $child): self;

    public function removeChild(self $child): self;

    public function getHierarchicalSorting(): ?string;

    public function setHierarchicalSorting(?string $hierarchicalSorting): void;

    public function resetHierarchicalSorting(): void;

    public function __toString(): string;
}

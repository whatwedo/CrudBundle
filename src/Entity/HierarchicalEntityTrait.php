<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait HierarchicalEntityTrait
{

    protected $parent = null;

    protected Collection $children;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $hierarchicalSorting = null;


    public function getLevel(): int
    {
        if ($this->getParent()) {
            return 1 + $this->getParent()->getLevel();
        }
        return 1;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(HierarchicalEntityInterface $child): self
    {
        if (! $this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(HierarchicalEntityInterface $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }



    public function getHierarchicalSorting(): ?string
    {
        return $this->hierarchicalSorting;
    }

    public function setHierarchicalSorting(?string $hierarchicalSorting): void
    {
        $this->hierarchicalSorting = $hierarchicalSorting;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function resetHierarchicalSorting(): void
    {
        /** @var HierarchicalEntityInterface $entity */
        $entity = $this;
        $sorting = '';
        do {
            $sorting = $entity->__toString() . $sorting;
            $entity = $entity->getParent();
        } while ($entity);
        $this->hierarchicalSorting = $sorting;

        foreach ($this->getChildren() as $child) {
            $child->resetHierarchicalSorting();
        }
    }


}

<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use whatwedo\CrudBundle\Entity\HierarchicalEntityInterface;
use whatwedo\CrudBundle\Entity\HierarchicalEntityTrait;

/**
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="whatwedo\CrudBundle\Tests\App\Repository\CategoryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Category implements HierarchicalEntityInterface
{
    use HierarchicalEntityTrait;
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\NotNull()
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="children")
     */
    protected $parent = null;

    /**
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="parent")
     */
    protected  Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }


    public function __toString(): string
    {
        return (string) $this->getName();
    }
}

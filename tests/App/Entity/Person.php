<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'person')]
#[ORM\Entity(repositoryClass: 'whatwedo\CrudBundle\Tests\App\Repository\PersonRepository')]
class Person
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private ?string $name = null;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private ?string $jobTitle = null;

    #[Assert\Callback(groups: ['check-not-valid'])]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->name === 'not-valid') {
            $context
                ->buildViolation('This name sounds totally fake!')
                ->atPath('name')
                ->addViolation()
            ;
        }
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

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}

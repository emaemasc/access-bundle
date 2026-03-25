<?php

namespace Ema\AccessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AccessRole
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    protected string $name;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $title;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}

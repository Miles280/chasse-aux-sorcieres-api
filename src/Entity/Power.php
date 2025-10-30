<?php

namespace App\Entity;

use App\Repository\PowerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PowerRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['power:read']],
    denormalizationContext: ['groups' => ['power:write']],
)]
class Power
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['power:read', 'role:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?string $title = null;
    
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?string $description = null;
    
    #[ORM\Column]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?bool $isDayPower = false;
    
    #[ORM\Column]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?bool $isPassive = false;
    
    #[ORM\Column(nullable: true)]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?int $usageLimit = null;
    
    #[ORM\Column]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?int $position = null;
    
    #[ORM\Column]
    #[Groups(['power:read', 'power:write', 'role:read', 'role:write'])]
    private ?bool $leavingHouse = false;
    
    #[ORM\ManyToOne(inversedBy: 'powers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isDayPower(): ?bool
    {
        return $this->isDayPower;
    }

    public function setIsDayPower(bool $isDayPower): static
    {
        $this->isDayPower = $isDayPower;

        return $this;
    }

    public function isPassive(): ?bool
    {
        return $this->isPassive;
    }

    public function setIsPassive(bool $isPassive): static
    {
        $this->isPassive = $isPassive;

        return $this;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(?int $usageLimit): static
    {
        $this->usageLimit = $usageLimit;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isLeavingHouse(): ?bool
    {
        return $this->leavingHouse;
    }

    public function setLeavingHouse(bool $leavingHouse): static
    {
        $this->leavingHouse = $leavingHouse;

        return $this;
    }
}

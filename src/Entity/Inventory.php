<?php

namespace App\Entity;

use App\Repository\InventoryRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: InventoryRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['inventory:read']],
    denormalizationContext: ['groups' => ['inventory:write']],
)]
class Inventory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['alignment:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['alignment:read', 'alignment:write'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['alignment:read'])]
    private ?\DateTimeImmutable $acquiredAt = null;

    #[ORM\ManyToOne(inversedBy: 'inventories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['alignment:read', 'alignment:write'])]
    private ?User $owner = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['alignment:read', 'alignment:write'])]
    private ?Shop $item = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getAcquiredAt(): ?\DateTimeImmutable
    {
        return $this->acquiredAt;
    }

    public function setAcquiredAt(\DateTimeImmutable $acquiredAt): static
    {
        $this->acquiredAt = $acquiredAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getItem(): ?Shop
    {
        return $this->item;
    }

    public function setItem(?Shop $item): static
    {
        $this->item = $item;

        return $this;
    }
}

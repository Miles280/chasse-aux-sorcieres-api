<?php

namespace App\Entity;

use App\Enum\Currency;
use App\Enum\ItemType;
use App\Repository\ItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['item:read']],
    denormalizationContext: ['groups' => ['item:write']],
)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['item:read','inventory:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?string $description = null;

    #[ORM\Column(enumType: Currency::class)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?Currency $currency = null;

    #[ORM\Column]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?int $price = null;

    #[ORM\Column(enumType: ItemType::class)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?ItemType $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?string $discordRoleId = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?int $quantity = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    #[MaxDepth(1)]
    private ?self $requiredItem = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private ?string $requiredRoleId = null;

    #[Gedmo\SortablePosition]
    #[ORM\Column(type: 'integer')]
    #[Groups(['item:read', 'item:write','inventory:read'])]
    private int $position = 0;

    #[ORM\Column(nullable: true)]
    private ?int $purchaseLimit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getType(): ?itemType
    {
        return $this->type;
    }

    public function setType(itemType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDiscordRoleId(): ?string
    {
        return $this->discordRoleId;
    }

    public function setDiscordRoleId(?string $discordRoleId): static
    {
        $this->discordRoleId = $discordRoleId;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getRequiredItem(): ?self
    {
        return $this->requiredItem;
    }

    public function setRequiredItem(?self $requiredItem): static
    {
        $this->requiredItem = $requiredItem;

        return $this;
    }

    public function getRequiredRoleId(): ?string
    {
        return $this->requiredRoleId;
    }

    public function setRequiredRoleId(?string $requiredRoleId): static
    {
        $this->requiredRoleId = $requiredRoleId;

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

    public function getPurchaseLimit(): ?int
    {
        return $this->purchaseLimit;
    }

    public function setPurchaseLimit(?int $purchaseLimit): static
    {
        $this->purchaseLimit = $purchaseLimit;

        return $this;
    }
}

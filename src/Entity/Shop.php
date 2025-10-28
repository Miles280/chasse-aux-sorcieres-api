<?php

namespace App\Entity;

use App\Enum\Currency;
use App\Enum\ShopType;
use App\Repository\ShopRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShopRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['shop:read']],
    denormalizationContext: ['groups' => ['shop:write']],
)]
class Shop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['shop:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['shop:read', 'shop:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['shop:read', 'shop:write'])]
    private ?string $description = null;

    #[ORM\Column(enumType: Currency::class)]
    #[Groups(['shop:read', 'shop:write'])]
    private ?Currency $currency = null;

    #[ORM\Column]
    #[Groups(['shop:read', 'shop:write'])]
    private ?int $price = null;

    #[ORM\Column(enumType: ShopType::class)]
    #[Groups(['shop:read', 'shop:write'])]
    private ?ShopType $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['shop:read', 'shop:write'])]
    private ?string $discordRoleId = null;

    #[ORM\Column]
    #[Groups(['shop:read', 'shop:write'])]
    private ?int $quantity = null;

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

    public function getType(): ?ShopType
    {
        return $this->type;
    }

    public function setType(ShopType $type): static
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

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}

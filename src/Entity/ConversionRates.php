<?php

namespace App\Entity;

use App\Repository\ConversionRatesRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ConversionRatesRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['conversionrates:read']],
    denormalizationContext: ['groups' => ['conversionrates:write']],
)]
class ConversionRates
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['conversionrates:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['conversionrates:read', 'conversionrates:write'])]
    private ?string $discordRoleId = null;

    #[ORM\Column(length: 100)]
    #[Groups(['conversionrates:read', 'conversionrates:write'])]
    private ?string $roleName = null;

    #[ORM\Column]
    #[Groups(['conversionrates:read', 'conversionrates:write'])]
    private ?float $gemsToRubiesRate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscordRoleId(): ?string
    {
        return $this->discordRoleId;
    }

    public function setDiscordRoleId(string $discordRoleId): static
    {
        $this->discordRoleId = $discordRoleId;

        return $this;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setRoleName(string $roleName): static
    {
        $this->roleName = $roleName;

        return $this;
    }

    public function getGemsToRubiesRate(): ?float
    {
        return $this->gemsToRubiesRate;
    }

    public function setGemsToRubiesRate(float $gemsToRubiesRate): static
    {
        $this->gemsToRubiesRate = $gemsToRubiesRate;

        return $this;
    }
}

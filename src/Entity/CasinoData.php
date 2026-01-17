<?php

namespace App\Entity;

use App\Repository\CasinoDataRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Enum\CasinoGame;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CasinoDataRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['casinodata:read']],
    denormalizationContext: ['groups' => ['casinodata:write']],
)]
class CasinoData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['casinodata:read'])]
    private ?int $id = null;

    #[ORM\Column(enumType: CasinoGame::class)]
    #[Groups(['casinodata:read', 'casinodata:write'])]
    private ?CasinoGame $game = null;

    #[ORM\Column]
    #[Groups(['casinodata:read', 'casinodata:write'])]
    private ?int $betAmount = null;

    #[ORM\Column]
    #[Groups(['casinodata:read', 'casinodata:write'])]
    private ?int $wonAmount = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['casinodata:read', 'casinodata:write'])]
    private ?array $details = null;
    
    #[ORM\Column]
    #[Groups(['casinodata:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['casinodata:read', 'casinodata:write'])]
    private ?User $player = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?string
    {
        return $this->game;
    }

    public function setGame(CasinoGame $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getBetAmount(): ?int
    {
        return $this->betAmount;
    }

    public function setBetAmount(int $betAmount): static
    {
        $this->betAmount = $betAmount;

        return $this;
    }

    public function getWonAmount(): ?int
    {
        return $this->wonAmount;
    }

    public function setWonAmount(int $wonAmount): static
    {
        $this->wonAmount = $wonAmount;

        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): static
    {
        $this->player = $player;

        return $this;
    }


    #[Groups(['casinodata:read'])]
    public function getNetProfit(): int
    {
        return $this->wonAmount - $this->betAmount;
    }
}

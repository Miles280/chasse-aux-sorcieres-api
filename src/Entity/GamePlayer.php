<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\GamePlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GamePlayerRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['gameplayer:read', 'game:read']],
    denormalizationContext: ['groups' => ['gameplayer:write']],
)]
class GamePlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:read', 'gameplayer:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['gameplayer:read', 'gameplayer:write'])]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'gamePlayers')] 
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['game:read', 'gameplayer:read', 'gameplayer:write'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['game:read', 'gameplayer:read', 'gameplayer:write'])]
    private ?bool $isSpectator = false;

    #[ORM\Column]
    #[Groups(['game:read', 'gameplayer:read', 'gameplayer:write'])]
    private ?bool $isAlive = null;

    #[ORM\ManyToOne]
    #[Groups(['game:read', 'gameplayer:read', 'gameplayer:write'])]
    private ?Role $trueRole = null;

    #[ORM\ManyToOne]
    #[Groups(['game:read', 'gameplayer:read', 'gameplayer:write'])]
    private ?Role $revealedRole = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['gameplayer:read', 'gameplayer:write'])]
    private ?int $gemsWon = null;

    public function __construct()
    {
        $this->isAlive = true; 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function isSpectator(): ?bool
    {
        return $this->isSpectator;
    }

    public function setIsSpectator(bool $isSpectator): static
    {
        $this->isSpectator = $isSpectator;
        return $this;
    }

    public function isAlive(): ?bool
    {
        return $this->isAlive;
    }

    public function setIsAlive(bool $isAlive): static
    {
        $this->isAlive = $isAlive;
        return $this;
    }

    public function getTrueRole(): ?Role
    {
        return $this->trueRole;
    }

    public function setTrueRole(?Role $trueRole): static
    {
        $this->trueRole = $trueRole;
        return $this;
    }

    public function getRevealedRole(): ?Role
    {
        return $this->revealedRole;
    }

    public function setRevealedRole(?Role $revealedRole): static
    {
        $this->revealedRole = $revealedRole;
        return $this;
    }

    public function getGemsWon(): ?int
    {
        return $this->gemsWon;
    }
    
    public function setGemsWon(?int $gemsWon): static
    {
        $this->gemsWon = $gemsWon;
        return $this;
    }
}
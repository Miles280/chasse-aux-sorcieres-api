<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\DeathCause;
use App\Enum\GameStep;
use App\Repository\GameLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GameLogRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['gamelog:read']],
    denormalizationContext: ['groups' => ['gamelog:write']],
)]
class GameLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:read', 'gamelog:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gameLogs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['gamelog:read', 'gamelog:write'])]
    private ?Game $game = null;

    #[ORM\ManyToOne]
    #[Groups(['game:read', 'gamelog:read', 'gamelog:write'])]
    private ?GamePlayer $deadPlayer = null;

    #[ORM\Column(length: 50, nullable: true, enumType: DeathCause::class)]
    #[Groups(['game:read', 'gamelog:read', 'gamelog:write'])]
    private ?DeathCause $deathCause = null;

    #[ORM\Column]
    #[Groups(['game:read', 'gamelog:read', 'gamelog:write'])]
    private ?int $dayNumber = null;

    #[ORM\Column(length: 50, nullable: true, enumType: GameStep::class)]
    #[Groups(['game:read', 'gamelog:read', 'gamelog:write'])]
    private ?GameStep $step = null;

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

    public function getDeadPlayer(): ?GamePlayer
    {
        return $this->deadPlayer;
    }

    public function setDeadPlayer(?GamePlayer $deadPlayer): static
    {
        $this->deadPlayer = $deadPlayer;
        return $this;
    }

    public function getDeathCause(): ?DeathCause
    {
        return $this->deathCause;
    }

    public function setDeathCause(?DeathCause $deathCause): static
    {
        $this->deathCause = $deathCause;
        return $this;
    }

    public function getDayNumber(): ?int
    {
        return $this->dayNumber;
    }

    public function setDayNumber(int $dayNumber): static
    {
        $this->dayNumber = $dayNumber;
        return $this;
    }

    public function getStep(): ?GameStep
    {
        return $this->step;
    }
    
    public function setStep(?GameStep $step): static
    {
        $this->step = $step;
        return $this;
    }
}
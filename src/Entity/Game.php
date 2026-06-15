<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\GameStatus;
use App\Enum\GameStep;
use App\Enum\Camp;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['game:read']],
    denormalizationContext: ['groups' => ['game:write']],
)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'masteredGames')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['game:read', 'game:write'])]
    private ?User $gameMaster = null;

    #[ORM\Column(length: 50, enumType: GameStatus::class)]
    #[Groups(['game:read', 'game:write'])]
    private ?GameStatus $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $gameMode = null;

    #[ORM\Column(length: 50, nullable: true, enumType: Camp::class)]
    #[Groups(['game:read', 'game:write'])]
    private ?Camp $winningCamp = null;

    #[ORM\Column]
    #[Groups(['game:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(length: 50, enumType: GameStep::class)]
    #[Groups(['game:read', 'game:write'])]
    private ?GameStep $currentStep = null;

    #[ORM\Column]
    #[Groups(['game:read', 'game:write'])]
    private ?int $dayNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $inscriptionMessageId = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $compoMessageId = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $publicTrackerMessageId = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?string $mjTrackerMessageId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['game:read', 'game:write'])]
    private ?array $discordChannels = [];

    /**
     * @var Collection<int, GamePlayer>
     */
    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'game', orphanRemoval: true)]
    #[Groups(['game:read'])]
    private Collection $gamePlayers;

    /**
     * @var Collection<int, GameLog>
     */
    #[ORM\OneToMany(targetEntity: GameLog::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $gameLogs;

    /**
     * @var Collection<int, GameComposition>
     */
    #[ORM\OneToMany(targetEntity: GameComposition::class, mappedBy: 'game', orphanRemoval: true)]
    private Collection $compositions;

    public function __construct()
    {
        $this->gamePlayers = new ArrayCollection();
        $this->gameLogs = new ArrayCollection();
        $this->compositions = new ArrayCollection();

        $this->createdAt = new \DateTimeImmutable();
        $this->status = GameStatus::WAITING;
        $this->currentStep = GameStep::DUSK;
        $this->dayNumber = 0; 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameMaster(): ?User
    {
        return $this->gameMaster;
    }

    public function setGameMaster(?User $gameMaster): static
    {
        $this->gameMaster = $gameMaster;
        return $this;
    }

    public function getStatus(): ?GameStatus
    {
        return $this->status;
    }

    public function setStatus(GameStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getGameMode(): ?string
    {
        return $this->gameMode;
    }

    public function setGameMode(string $gameMode): static
    {
        $this->gameMode = $gameMode;
        return $this;
    }

    public function getWinningCamp(): ?Camp 
    { 
        return $this->winningCamp; 
    }

    public function setWinningCamp(?Camp $winningCamp): static 
    { 
        $this->winningCamp = $winningCamp; 
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

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getCurrentStep(): ?GameStep
    {
        return $this->currentStep;
    }

    public function setCurrentStep(GameStep $currentStep): static
    {
        $this->currentStep = $currentStep;
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

    public function getInscriptionMessageId(): ?string
    {
        return $this->inscriptionMessageId;
    }

    public function setInscriptionMessageId(?string $inscriptionMessageId): static
    {
        $this->inscriptionMessageId = $inscriptionMessageId;
        return $this;
    }
    
    public function getCompoMessageId(): ?string
    {
        return $this->compoMessageId;
    }
    public function setCompoMessageId(?string $compoMessageId): static
    {
        $this->compoMessageId = $compoMessageId;
        return $this;
    }

    public function getPublicTrackerMessageId(): ?string
    {
        return $this->publicTrackerMessageId;
    }

    public function setPublicTrackerMessageId(?string $publicTrackerMessageId): static
    {
        $this->publicTrackerMessageId = $publicTrackerMessageId;
        return $this;
    }

    public function getMjTrackerMessageId(): ?string
    {
        return $this->mjTrackerMessageId;
    }

    public function setMjTrackerMessageId(?string $mjTrackerMessageId): static
    {
        $this->mjTrackerMessageId = $mjTrackerMessageId;
        return $this;
    }
    
    public function getDiscordChannels(): ?array
    {
        return $this->discordChannels;
    }

    public function setDiscordChannels(?array $discordChannels): static
    {
        $this->discordChannels = $discordChannels;
        return $this;
    }

    public function getGamePlayers(): Collection
    {
        return $this->gamePlayers;
    }

    public function addGamePlayer(GamePlayer $gamePlayer): static
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers->add($gamePlayer);
            $gamePlayer->setGame($this);
        }
        return $this;
    }

    public function removeGamePlayer(GamePlayer $gamePlayer): static
    {
        if ($this->gamePlayers->removeElement($gamePlayer)) {
            if ($gamePlayer->getGame() === $this) {
                $gamePlayer->setGame(null);
            }
        }
        return $this;
    }

    public function getGameLogs(): Collection
    {
        return $this->gameLogs;
    }

    public function addGameLog(GameLog $gameLog): static
    {
        if (!$this->gameLogs->contains($gameLog)) {
            $this->gameLogs->add($gameLog);
            $gameLog->setGame($this);
        }
        return $this;
    }

    public function removeGameLog(GameLog $gameLog): static
    {
        if ($this->gameLogs->removeElement($gameLog)) {
            if ($gameLog->getGame() === $this) {
                $gameLog->setGame(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, GameComposition>
     */
    public function getCompositions(): Collection
    {
        return $this->compositions;
    }

    public function addComposition(GameComposition $composition): static
    {
        if (!$this->compositions->contains($composition)) {
            $this->compositions->add($composition);
            $composition->setGame($this);
        }
        return $this;
    }

    public function removeComposition(GameComposition $composition): static
    {
        if ($this->compositions->removeElement($composition)) {
            if ($composition->getGame() === $this) {
                $composition->setGame(null);
            }
        }
        return $this;
    }
}

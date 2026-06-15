<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'game:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['user:read', 'user:write', 'game:read'])]
    private ?string $discordId = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $discordUsername = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $discordGlobalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $discordAvatar = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private ?int $gems = 0;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private ?int $rubies = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $discordAccessToken = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $discordRefreshToken = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTime $discordTokenExpiresAt = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTime $lastLoginAt = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = ['ROLE_USER'];

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $transactions;

    /**
     * @var Collection<int, Inventory>
     */
    #[ORM\OneToMany(targetEntity: Inventory::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $inventories;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jwtRefreshToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $jwtRefreshTokenExpiresAt = null;

    /**
     * @var Collection<int, UserCooldown>
     */
    #[ORM\OneToMany(targetEntity: UserCooldown::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userCooldowns;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'gameMaster')]
    private Collection $masteredGames;

    /**
     * @var Collection<int, GamePlayer>
     */
    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'user')]
    private Collection $gamePlayers;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->inventories = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->userCooldowns = new ArrayCollection();
        $this->masteredGames = new ArrayCollection();
        $this->gamePlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(string $discordId): static
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    public function setDiscordUsername(?string $discordUsername): static
    {
        $this->discordUsername = $discordUsername;

        return $this;
    }

    public function getDiscordGlobalName(): ?string
    {
        return $this->discordGlobalName;
    }

    public function setDiscordGlobalName(?string $discordGlobalName): static
    {
        $this->discordGlobalName = $discordGlobalName;

        return $this;
    }

    public function getDiscordAvatar(): ?string
    {
        return $this->discordAvatar;
    }

    public function setDiscordAvatar(?string $discordAvatar): static
    {
        $this->discordAvatar = $discordAvatar;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getGems(): ?int
    {
        return $this->gems;
    }

    public function setGems(int $gems): static
    {
        $this->gems = $gems;

        return $this;
    }

    public function getRubies(): ?int
    {
        return $this->rubies;
    }

    public function setRubies(int $rubies): static
    {
        $this->rubies = $rubies;

        return $this;
    }

    public function getDiscordAccessToken(): ?string
    {
        return $this->discordAccessToken;
    }

    public function setDiscordAccessToken(?string $discordAccessToken): static
    {
        $this->discordAccessToken = $discordAccessToken;

        return $this;
    }

    public function getDiscordRefreshToken(): ?string
    {
        return $this->discordRefreshToken;
    }

    public function setDiscordRefreshToken(?string $discordRefreshToken): static
    {
        $this->discordRefreshToken = $discordRefreshToken;

        return $this;
    }

    public function getDiscordTokenExpiresAt(): ?\DateTime
    {
        return $this->discordTokenExpiresAt;
    }

    public function setDiscordTokenExpiresAt(?\DateTime $discordTokenExpiresAt): static
    {
        $this->discordTokenExpiresAt = $discordTokenExpiresAt;

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

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTime $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Tous les utilisateurs ont au moins ce rôle
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }


    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setOwner($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getOwner() === $this) {
                $transaction->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Inventory>
     */
    public function getInventories(): Collection
    {
        return $this->inventories;
    }

    public function getInventoryForItem(Item $item): ?Inventory
    {
        foreach ($this->getInventories() as $inventory) {
            if ($inventory->getItem()->getId() === $item->getId()) {
                return $inventory;
            }
        }
        return null;
    }

    public function addInventory(Inventory $inventory): static
    {
        if (!$this->inventories->contains($inventory)) {
            $this->inventories->add($inventory);
            $inventory->setOwner($this);
        }

        return $this;
    }

    public function removeInventory(Inventory $inventory): static
    {
        if ($this->inventories->removeElement($inventory)) {
            // set the owning side to null (unless already changed)
            if ($inventory->getOwner() === $this) {
                $inventory->setOwner(null);
            }
        }

        return $this;
    }

    public function hasItem(Item $item): bool
    {
        foreach ($this->inventories as $inventory) {
            if ($inventory->getItem()->getId() === $item->getId()) {
                return true;
            }
        }
        return false;
    }

        public function getUserIdentifier(): string
    {
        // Identifiant unique de l'utilisateur
        return $this->discordId ?? (string) $this->id;
    }

    public function eraseCredentials(): void
    {
        // Cette méthode sert à effacer d'éventuelles données sensibles
        // (comme un mot de passe en clair). Ici, tu n’en as pas besoin.
    }

    public function getJwtRefreshToken(): ?string
    {
        return $this->jwtRefreshToken;
    }

    public function setJwtRefreshToken(?string $jwtRefreshToken): static
    {
        $this->jwtRefreshToken = $jwtRefreshToken;

        return $this;
    }

    public function getJwtRefreshTokenExpiresAt(): ?\DateTime
    {
        return $this->jwtRefreshTokenExpiresAt;
    }

    public function setJwtRefreshTokenExpiresAt(?\DateTime $jwtRefreshTokenExpiresAt): static
    {
        $this->jwtRefreshTokenExpiresAt = $jwtRefreshTokenExpiresAt;

        return $this;
    }

    /**
     * @return Collection<int, UserCooldown>
     */
    public function getUserCooldowns(): Collection
    {
        return $this->userCooldowns;
    }

    public function addUserCooldown(UserCooldown $userCooldown): static
    {
        if (!$this->userCooldowns->contains($userCooldown)) {
            $this->userCooldowns->add($userCooldown);
            $userCooldown->setUser($this);
        }

        return $this;
    }

    public function removeUserCooldown(UserCooldown $userCooldown): static
    {
        if ($this->userCooldowns->removeElement($userCooldown)) {
            // set the owning side to null (unless already changed)
            if ($userCooldown->getUser() === $this) {
                $userCooldown->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getMasteredGames(): Collection
    {
        return $this->masteredGames;
    }

    public function addMasteredGame(Game $masteredGame): static
    {
        if (!$this->masteredGames->contains($masteredGame)) {
            $this->masteredGames->add($masteredGame);
            $masteredGame->setGameMaster($this);
        }

        return $this;
    }

    public function removeMasteredGame(Game $masteredGame): static
    {
        if ($this->masteredGames->removeElement($masteredGame)) {
            // set the owning side to null (unless already changed)
            if ($masteredGame->getGameMaster() === $this) {
                $masteredGame->setGameMaster(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GamePlayer>
     */
    public function getGamePlayers(): Collection
    {
        return $this->gamePlayers;
    }

    public function addGamePlayer(GamePlayer $gamePlayer): static
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers->add($gamePlayer);
            $gamePlayer->setUser($this);
        }

        return $this;
    }

    public function removeGamePlayer(GamePlayer $gamePlayer): static
    {
        if ($this->gamePlayers->removeElement($gamePlayer)) {
            // set the owning side to null (unless already changed)
            if ($gamePlayer->getUser() === $this) {
                $gamePlayer->setUser(null);
            }
        }

        return $this;
    }

}

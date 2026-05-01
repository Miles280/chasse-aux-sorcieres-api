<?php

namespace App\Entity;

use App\Repository\ServerConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServerConfigRepository::class)]
class ServerConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: false)]
    private ?string $discordServerId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mjRoleId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $playerRoleId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $deadPlayerRoleId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $spectatorRoleId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $inscriptionVoiceChannelId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gameVoiceChannelId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $deadVoiceChannelId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $inscriptionChannelId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gameMjChannelId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gameCategoryId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gamePrivateCategoryId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscordServerId(): string
    {
        return $this->discordServerId;
    }

    public function setDiscordServerId(?string $discordServerId): static
    {
        $this->discordServerId = $discordServerId;

        return $this;
    }

    public function getMjRoleId(): ?string
    {
        return $this->mjRoleId;
    }

    public function setMjRoleId(?string $mjRoleId): static
    {
        $this->mjRoleId = $mjRoleId;

        return $this;
    }

    public function getPlayerRoleId(): ?string
    {
        return $this->playerRoleId;
    }

    public function setPlayerRoleId(?string $playerRoleId): static
    {
        $this->playerRoleId = $playerRoleId;

        return $this;
    }

    public function getDeadPlayerRoleId(): ?string
    {
        return $this->deadPlayerRoleId;
    }

    public function setDeadPlayerRoleId(?string $deadPlayerRoleId): static
    {
        $this->deadPlayerRoleId = $deadPlayerRoleId;

        return $this;
    }

    public function getSpectatorRoleId(): ?string
    {
        return $this->spectatorRoleId;
    }

    public function setSpectatorRoleId(?string $spectatorRoleId): static
    {
        $this->spectatorRoleId = $spectatorRoleId;

        return $this;
    }

    public function getInscriptionVoiceChannelId(): ?string
    {
        return $this->inscriptionVoiceChannelId;
    }

    public function setInscriptionVoiceChannelId(?string $inscriptionVoiceChannelId): static
    {
        $this->inscriptionVoiceChannelId = $inscriptionVoiceChannelId;

        return $this;
    }

        public function getGameVoiceChannelId(): ?string
    {
        return $this->gameVoiceChannelId;
    }

    public function setGameVoiceChannelId(?string $gameVoiceChannelId): static
    {
        $this->gameVoiceChannelId = $gameVoiceChannelId;

        return $this;
    }

    public function getDeadVoiceChannelId(): ?string
    {
        return $this->deadVoiceChannelId;
    }

    public function setDeadVoiceChannelId(?string $deadVoiceChannelId): static
    {
        $this->deadVoiceChannelId = $deadVoiceChannelId;

        return $this;
    }

    public function getInscriptionChannelId(): ?string
    {
        return $this->inscriptionChannelId;
    }

    public function setInscriptionChannelId(?string $inscriptionChannelId): static
    {
        $this->inscriptionChannelId = $inscriptionChannelId;

        return $this;
    }

    public function getGameMjChannelId(): ?string
    {
        return $this->gameMjChannelId;
    }

    public function setGameMjChannelId(?string $gameMjChannelId): static
    {
        $this->gameMjChannelId = $gameMjChannelId;

        return $this;
    }

    public function getGameCategoryId(): ?string
    {
        return $this->gameCategoryId;
    }

    public function setGameCategoryId(?string $gameCategoryId): static
    {
        $this->gameCategoryId = $gameCategoryId;

        return $this;
    }

    public function getGamePrivateCategoryId(): ?string
    {
        return $this->gamePrivateCategoryId;
    }

    public function setGamePrivateCategoryId(?string $gamePrivateCategoryId): static
    {
        $this->gamePrivateCategoryId = $gamePrivateCategoryId;

        return $this;
    }

}

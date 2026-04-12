<?php

namespace App\Service;

use App\Entity\ServerConfig;
use App\Repository\ServerConfigRepository;
use Doctrine\ORM\EntityManagerInterface;

class ServerConfigService
{
    private EntityManagerInterface $entityManager;
    private ServerConfigRepository $serverConfigRepository;

    public function __construct(EntityManagerInterface $entityManager, ServerConfigRepository $serverConfigRepository)
    {
        $this->entityManager = $entityManager;
        $this->serverConfigRepository = $serverConfigRepository;
    }

    /**
     * Récupère la configuration d'un serveur Discord par son ID.
     */
    public function getConfig(string $discordServerId): ?ServerConfig
    {
        return $this->serverConfigRepository->findOneBy(['discordServerId' => $discordServerId]);
    }

    /**
     * Met à jour la configuration existante d'un serveur ou la crée si elle n'existe pas.
     */
    public function updateOrCreateConfig(string $discordServerId, array $configData): ServerConfig
    {
        // On cherche si une config existe déjà pour ce serveur
        $serverConfig = $this->serverConfigRepository->findOneBy(['discordServerId' => $discordServerId]);

        if (!$serverConfig) {
            $serverConfig = new ServerConfig();
            $serverConfig->setDiscordServerId($discordServerId);
        }

        // On met à jour les champs fournis dynamiquement
        if (array_key_exists('mjRoleId', $configData)) {
            $serverConfig->setMjRoleId($configData['mjRoleId']);
        }
        if (array_key_exists('playerRoleId', $configData)) {
            $serverConfig->setPlayerRoleId($configData['playerRoleId']);
        }
        if (array_key_exists('deadPlayerRoleId', $configData)) {
            $serverConfig->setDeadPlayerRoleId($configData['deadPlayerRoleId']);
        }
        if (array_key_exists('inscriptionChannelId', $configData)) {
            $serverConfig->setInscriptionChannelId($configData['inscriptionChannelId']);
        }
        if (array_key_exists('gameMjChannelId', $configData)) {
            $serverConfig->setGameMjChannelId($configData['gameMjChannelId']);
        }
        if (array_key_exists('gameCategoryId', $configData)) {
            $serverConfig->setGameCategoryId($configData['gameCategoryId']);
        }
        if (array_key_exists('gamePrivateCategoryId', $configData)) {
            $serverConfig->setGamePrivateCategoryId($configData['gamePrivateCategoryId']);
        }
        if (array_key_exists('inscriptionVoiceChannelId', $configData)) {
            $serverConfig->setInscriptionVoiceChannelId($configData['inscriptionVoiceChannelId']);
        }
        if (array_key_exists('gameVoiceChannelId', $configData)) {
            $serverConfig->setGameVoiceChannelId($configData['gameVoiceChannelId']);
        }
        if (array_key_exists('deadVoiceChannelId', $configData)) {
            $serverConfig->setDeadVoiceChannelId($configData['deadVoiceChannelId']);
        }

        // Sauvegarde en base de données
        $this->entityManager->persist($serverConfig);
        $this->entityManager->flush();

        return $serverConfig;
    }
}
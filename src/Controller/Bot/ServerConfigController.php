<?php

namespace App\Controller\Bot;

use App\Exception\InvalidPayloadException;
use App\Service\RequestPayloadService;
use App\Service\ServerConfigService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bot/server-config')]
final class ServerConfigController extends AbstractBotController
{
    private ServerConfigService $serverConfigService;

    public function __construct(ServerConfigService $serverConfigService)
    {
        $this->serverConfigService = $serverConfigService;
    }

    #[Route('/{discordServerId}', name: 'app_bot_server_config_get', methods: ['GET'])]
    public function getConfig(string $discordServerId): JsonResponse
    {
        try {
            // On demande au service de récupérer la config
            $serverConfig = $this->serverConfigService->getConfig($discordServerId);

            // Si aucune configuration n'existe en base, on renvoie un objet avec des valeurs nulles
            // Cela évite de faire planter le bot qui s'attend à recevoir une structure de données
            if (!$serverConfig) {
                return $this->successResponse([
                    'discordServerId' => $discordServerId,
                    'mjRoleId' =>null,
                    'playerRoleId' => null,
                    'deadPlayerRoleId' => null,
                    'spectatorRoleId' => null,
                    'inscriptionVoiceChannelId'=> null, 
                    'gameVoiceChannelId'=> null,        
                    'deadVoiceChannelId'=> null,
                    'inscriptionChannelId' => null,
                    'gameMjChannelId' => null,
                    'gameCategoryId' => null,
                    'gamePrivateCategoryId' => null
                ]);
            }

            // Si on trouve la config, on renvoie les vraies données
            return $this->successResponse([
                'discordServerId' => $serverConfig->getDiscordServerId(),
                'mjRoleId' => $serverConfig->getMjRoleId(),
                'playerRoleId' => $serverConfig->getPlayerRoleId(),
                'deadPlayerRoleId' => $serverConfig->getDeadPlayerRoleId(),
                'spectatorRoleId' => $serverConfig->getSpectatorRoleId(),
                'inscriptionVoiceChannelId'=> $serverConfig->getInscriptionVoiceChannelId(),
                'inscriptionChannelId' => $serverConfig->getInscriptionChannelId(),
                'gameMjChannelId' => $serverConfig->getGameMjChannelId(),
                'gameCategoryId' => $serverConfig->getGameCategoryId(),
                'gamePrivateCategoryId' => $serverConfig->getGamePrivateCategoryId(),
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse("Erreur interne du serveur: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    #[Route('/update', name: 'app_bot_server_config_update', methods: ['POST', 'PUT'])]
    public function updateConfig(Request $request, RequestPayloadService $payloadService): JsonResponse
    {
        try {
            // On exige au moins le discordServerId pour savoir quelle configuration mettre à jour
            $payload = $payloadService->extractValidatedPayload($request, ['discordServerId']);
            $discordServerId = $payload['discordServerId'];

            // On récupère tout le reste du body (les autres champs sont potentiellement optionnels/nullables)
            $data = $request->toArray();

            // Liste des champs modifiables selon ton Entité
            $allowedFields = [
                'mjRoleId' ,
                'playerRoleId' ,
                'deadPlayerRoleId',
                'spectatorRoleId',
                'inscriptionVoiceChannelId', 
                'gameVoiceChannelId',        
                'deadVoiceChannelId',
                'inscriptionChannelId',
                'gameMjChannelId',
                'gameCategoryId',
                'gamePrivateCategoryId'
            ];

            $configData = [];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $configData[$field] = $data[$field];
                }
            }

            // Appel du service pour créer ou mettre à jour la configuration
            $serverConfig = $this->serverConfigService->updateOrCreateConfig($discordServerId, $configData);

            return $this->successResponse([
                'message' => 'Configuration du serveur mise à jour avec succès.',
                'discordServerId' => $serverConfig->getDiscordServerId()
            ]);

        } catch (InvalidPayloadException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            // Tu peux logger l'erreur ici si nécessaire
            return $this->errorResponse("Erreur interne du serveur: " . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
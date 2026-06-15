<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Role;
use App\Enum\GameStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface; // 👈 AJOUT DE L'IMPORT

class GameService
{
    // 1. On injecte le Normalizer de Symfony dans le constructeur
    public function __construct(
        private EntityManagerInterface $em,
        private NormalizerInterface $normalizer 
    ) {}

    /**
     * Génère une distribution aléatoire sans la persister en base de données.
     */
    public function generateRandomDistribution(Game $game): array
    {
        $allPlayers = $game->getGamePlayers()->toArray();
        $activePlayers = array_filter($allPlayers, fn($p) => !$p->getIsSpectator());
        $activePlayers = array_values($activePlayers); 
        
        $compositions = $game->getCompositions()->toArray();

        if (count($activePlayers) !== count($compositions)) {
            throw new \LogicException(sprintf(
                "Incohérence : %d joueurs pour %d rôles définis dans la composition.", 
                count($activePlayers), 
                count($compositions)
            ));
        }

        $roles = array_map(fn($comp) => $comp->getRole(), $compositions);
        shuffle($roles);

        $distribution = [];
        foreach ($activePlayers as $index => $player) {
            $role = $roles[$index];
            $distribution[] = [
                'discordId' => $player->getUser()->getDiscordId(), 
                'role' => $this->formatRole($role)
            ];
        }

        return $distribution;
    }

    /**
     * Démarre la partie en appliquant une distribution spécifique et en la sauvegardant.
     */
    public function startGame(Game $game, array $validatedDistribution = []): array
    {
        if (empty($validatedDistribution)) {
            $validatedDistribution = $this->generateRandomDistribution($game);
        }

        $players = $game->getGamePlayers();
        $finalDistribution = []; 

        foreach ($validatedDistribution as $assignment) {
            $player = $this->findPlayerByDiscordId($players, $assignment['discordId']);
            $roleData = $assignment['role'];
            
            if ($roleData instanceof Role) {
                $roleEntity = $roleData;
            } else {
                $roleEntity = $this->em->getRepository(Role::class)->find($roleData['id']);
                if (!$roleEntity) {
                    throw new \LogicException("Rôle avec l'ID {$roleData['id']} introuvable.");
                }
            }

            $player->setTrueRole($roleEntity);

            $finalDistribution[] = [
                'discordId' => $assignment['discordId'],
                'role' => $this->formatRole($roleEntity) 
            ];
        }

        $game->setStatus(GameStatus::PLAYING);
        $game->setStartedAt(new \DateTimeImmutable());
        
        $this->em->flush();

        return [
            'distribution' => $finalDistribution 
        ];
    }

    /**
     * Formate proprement l'entité Role et utilise le Normalizer pour les pouvoirs
     */
    private function formatRole(Role $role): array
    {
        $powersData = [];
        
        foreach ($role->getPowers() as $power) {
            $powersData[] = $this->normalizer->normalize($power, null, ['groups' => ['game:read']]);
        }

        return [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'description' => $role->getDescription(),
            'minPlayer' => $role->getMinPlayer(),
            'camp' => $role->getCamp(),
            'goal' => $role->getGoal(),
            'notes' => $role->getNotes(),
            'imageUrl' => $role->getImageUrl(),
            'powers' => $powersData, 
            'alignments' => $role->getAlignments(),
            'unique' => $role->isUnique(), 
        ];
    }

    private function findPlayerByDiscordId(iterable $players, string $discordId)
    {
        foreach ($players as $player) {
            if ($player->getUser()->getDiscordId() === $discordId) {
                return $player;
            }
        }
        throw new \LogicException("Le joueur avec l'identifiant Discord $discordId est introuvable.");
    }

    /**
     * Met à jour les salons Discord liés à la partie et aux joueurs
     */
    public function updateGameChannels(Game $game, array $gameChannels, array $playersChannels): void
    {
        // 1. Mise à jour des salons globaux de la partie (Votes, Vocaux, etc.)
        if (!empty($gameChannels)) {
            $game->setDiscordChannels($gameChannels);
        }

        // 2. Mise à jour des salons privés (Carnets) des joueurs
        if (!empty($playersChannels)) {
            // Indexation par discordId pour éviter de boucler inutilement
            $channelsByDiscordId = [];
            foreach ($playersChannels as $pc) {
                if (isset($pc['discordId']) && isset($pc['channelId'])) {
                    $channelsByDiscordId[$pc['discordId']] = $pc['channelId'];
                }
            }

            // Assignation des salons aux joueurs
            foreach ($game->getGamePlayers() as $gamePlayer) {
                // Assure-toi que la méthode getDiscordId() existe bien dans ton entité User
                $userDiscordId = $gamePlayer->getUser()->getDiscordId(); 
                
                if (isset($channelsByDiscordId[$userDiscordId])) {
                    $gamePlayer->setDiscordChannelId($channelsByDiscordId[$userDiscordId]);
                }
            }
        }

        // On sauvegarde les modifications en base de données
        $this->em->flush();
    }

    /**
     * Met à jour les IDs des messages de suivi (Trackers)
     */
    public function updateGameTrackers(Game $game, ?string $publicTrackerId, ?string $mjTrackerId): void
    {
        $game->setPublicTrackerMessageId($publicTrackerId);
        $game->setMjTrackerMessageId($mjTrackerId);
        
        $this->em->flush();
    }
}
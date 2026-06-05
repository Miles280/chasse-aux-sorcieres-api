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
        $activePlayers = array_filter($allPlayers, fn($p) => !$p->isSpectator());
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
     * 🛠️ Formate proprement l'entité Role et utilise le Normalizer pour les pouvoirs
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
            'powers' => $powersData, // 🔥 Contient maintenant absolument TOUTES les propriétés de ton entité Power
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
}
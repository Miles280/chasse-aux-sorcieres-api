<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Role;
use App\Enum\GameStatus;
use Doctrine\ORM\EntityManagerInterface;

class GameService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Génère une distribution aléatoire sans la persister en base de données.
     */
    public function generateRandomDistribution(Game $game): array
    {
        // 1. On récupère les joueurs et on EXCLUT les spectateurs
        $allPlayers = $game->getGamePlayers()->toArray();
        $activePlayers = array_filter($allPlayers, fn($p) => !$p->isSpectator());
        
        // Réindexe le tableau de 0 à X proprement après le filtre
        $activePlayers = array_values($activePlayers); 
        
        $compositions = $game->getCompositions()->toArray();

        // 2. Vérification de cohérence
        if (count($activePlayers) !== count($compositions)) {
            throw new \LogicException(sprintf(
                "Incohérence : %d joueurs pour %d rôles définis dans la composition.", 
                count($activePlayers), 
                count($compositions)
            ));
        }

        // 3. On extrait les rôles de la composition et on les mélange
        $roles = array_map(fn($comp) => $comp->getRole(), $compositions);
        shuffle($roles);

        // 4. On crée la distribution prête à être envoyée en JSON
        $distribution = [];
        foreach ($activePlayers as $index => $player) {
            $role = $roles[$index];
            $distribution[] = [
                'discordId' => $player->getUser()->getDiscordId(), 
                'role' => $role
            ];
        }

        return $distribution;
    }

    /**
     * Démarre la partie en appliquant une distribution spécifique et en la sauvegardant.
     */
    public function startGame(Game $game, array $validatedDistribution = []): array
    {
        // Si aucune distribution n'est fournie ("Fast start"), on utilise ta méthode
        if (empty($validatedDistribution)) {
            $validatedDistribution = $this->generateRandomDistribution($game);
        }

        $players = $game->getGamePlayers();

        // Application de la distribution
        foreach ($validatedDistribution as $assignment) {
            $player = $this->findPlayerByDiscordId($players, $assignment['discordId']);
            
            $roleData = $assignment['role'];
            
            // Si ça vient du JSON du bot, c'est un tableau, on récupère sa référence via l'id.
            if ($roleData instanceof Role) {
                $player->setTrueRole($roleData); 
            } else {
                $roleEntity = $this->em->getReference(Role::class, $roleData['id']);
                $player->setTrueRole($roleEntity); 
            }
        }

        // Mise à jour de la partie
        $game->setStatus(GameStatus::PLAYING);
        $game->setStartedAt(new \DateTimeImmutable());
        
        $this->em->flush();

        return [
            'distribution' => $validatedDistribution
        ];
    }

    /**
     * Ta fonction utilitaire adaptée pour chercher par Discord ID
     */
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
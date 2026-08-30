<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameLog;
use App\Entity\Role;
use App\Enum\Camp;
use App\Enum\DeathCause;
use App\Enum\GameStatus;
use App\Enum\GameStep;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface; // 👈 AJOUT DE L'IMPORT

class GameService
{
    // 1. On injecte le Normalizer de Symfony dans le constructeur
    public function __construct(
        private EntityManagerInterface $em,
        private NormalizerInterface $normalizer,
        private RoleRepository $roleRepository
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

    public function changeGameStep($game, string $nextStepRaw): void
    {
        // 1. Validation de la phase via l'Enum PHP
        $nextStep = GameStep::tryFrom($nextStepRaw);
        
        if (!$nextStep) {
            throw new \InvalidArgumentException("La phase '$nextStepRaw' n'est pas une phase de jeu valide.");
        }

        // 2. Logique métier : Si on repasse à la NUIT depuis le Crépuscule, on entame un nouveau jour !
        if ($game->getCurrentStep() === GameStep::DUSK && $nextStep === GameStep::NIGHT) {
            $game->setDayNumber($game->getDayNumber() + 1);
        }

        // 3. Mise à jour de la phase
        $game->setCurrentStep($nextStep);

        // 4. Sauvegarde en BDD
        $this->em->flush();
    }

    public function killPlayer(Game $game, string $discordId, string $deathCauseValue, bool $hideRole = false, ?int $fakeRoleId = null): void
    {
        // 1. Trouver le GamePlayer correspondant
        $targetPlayer = null;
        foreach ($game->getGamePlayers() as $gamePlayer) {
            if ($gamePlayer->getUser()->getDiscordId() === $discordId) { 
                $targetPlayer = $gamePlayer;
                break;
            }
        }

        if (!$targetPlayer) {
            throw new \InvalidArgumentException("Joueur introuvable dans cette partie.");
        }

        if (!$targetPlayer->getIsAlive()) {
            throw new \InvalidArgumentException("Ce joueur est déjà mort.");
        }

        // 3. Appliquer la mort
        $targetPlayer->setIsAlive(false);

        // 4. Gérer le rôle révélé (Ce qui sera affiché publiquement)
        if ($hideRole) {
            // S'il est caché, on pourrait le mettre à null, ou lui assigner un Role "Inconnu" depuis le repository
            $targetPlayer->setRevealedRole(null); 
        } elseif ($fakeRoleId) {
            $fakeRole = $this->roleRepository->find($fakeRoleId);
            if (!$fakeRole) {
                throw new \InvalidArgumentException("Le faux rôle indiqué n'existe pas.");
            }
            $targetPlayer->setRevealedRole($fakeRole);
        } else {
            // Par défaut, le rôle révélé devient le vrai rôle
            $targetPlayer->setRevealedRole($targetPlayer->getTrueRole());
        }

        // 5. Créer le GameLog
        $log = new GameLog();
        $log->setGame($game);
        $log->setDeadPlayer($targetPlayer);
        $log->setDeathCause(DeathCause::tryFrom($deathCauseValue)); 
        $log->setDayNumber($game->getDayNumber());
        $log->setStep($game->getCurrentStep());

        $this->em->persist($log);
        // $targetPlayer est déjà persisté par cascade/tracking de Doctrine
        $this->em->flush();
    }

    public function revealPlayer(Game $game, string $discordId): void
    {
        // Trouver le GamePlayer correspondant
        $targetPlayer = null;
        foreach ($game->getGamePlayers() as $gamePlayer) {
            if ($gamePlayer->getUser()->getDiscordId() === $discordId) { 
                $targetPlayer = $gamePlayer;
                break;
            }
        }

        if (!$targetPlayer) {
            throw new \InvalidArgumentException("Joueur introuvable dans cette partie.");
        }

        if ($targetPlayer->getRevealedRole()) {
            throw new \InvalidArgumentException("Ce joueur est déjà révélé.");
        }

        $targetPlayer->setRevealedRole($targetPlayer->getTrueRole());
        
        $this->em->persist($targetPlayer);
        $this->em->flush();
    }

    /**
     * Clôture une partie en enregistrant le camp vainqueur et la date de fin.
     * * @throws \InvalidArgumentException si le camp fourni est invalide
     * @throws \LogicException si la partie est déjà terminée
     */
    public function finishGame(Game $game, string $winningCamp): void
    {
        // 1. On vérifie que la partie n'est pas déjà terminée
        if ($game->getStatus() === GameStatus::FINISHED) {
            throw new \LogicException("Cette partie est déjà terminée.");
        }

        // 2. Conversion et validation de la chaîne vers l'Enum Camp
        $campEnum = Camp::tryFrom($winningCamp);

        if (!$campEnum) {
            throw new \InvalidArgumentException("Le camp '$winningCamp' n'est pas un camp valide.");
        }

        // 3. Mise à jour des propriétés de la partie
        $game->setStatus(GameStatus::FINISHED);
        $game->setWinningCamp($campEnum);
        $game->setFinishedAt(new \DateTimeImmutable());

        // 4. Sauvegarde en base de données
        $this->em->persist($game);
        $this->em->flush();
    }
}
<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Exception\GameException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class InscriptionService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie la partie avec l'id demandé
     */
    public function getGameById(int $id): ?Game
    {
        return $this->em->getRepository(Game::class)->find($id);
    }

    /**
     * Crée une nouvelle partie en vérifiant qu'aucune n'est active.
     */
    public function createWaitingGame(User $gameMaster): Game
    {     
        // 1. Vérifier si une partie est déjà en cours (WAITING ou PLAYING)
        $existingGame = $this->em->getRepository(Game::class)->findOneBy([
            'status' => [GameStatus::WAITING, GameStatus::PLAYING]
        ]);

        if ($existingGame) throw new GameException("Une partie est déjà en cours.");

        // 2. Si aucune partie, on crée
        $game = new Game();
        $game->setGameMaster($gameMaster);
        $game->setStatus(GameStatus::WAITING); 
        
        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    public function getCurrentWaitingGame(): ?Game
    {
        return $this->em->getRepository(Game::class)->findOneBy([
            'status' => GameStatus::WAITING
        ]);
    }

    public function updateDiscordMessageIds(int $gameId, string $inscriptionMsgId, string $compoMsgId): void
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);
        if (!$game) throw new GameException("Partie introuvable.", Response::HTTP_NOT_FOUND);

        $game->setInscriptionMessageId($inscriptionMsgId);
        $game->setCompoMessageId($compoMsgId);
        $this->em->flush();
    }

    /**
     * Gère l'état d'un utilisateur dans la partie (join, spectate ou leaveàju )
     */
    public function inscriptionPlayerInGame(int $gameId, User $user, string $action): array
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);
        if (!$game) throw new GameException("Partie introuvable.", Response::HTTP_NOT_FOUND);

        if ($game->getStatus() !== GameStatus::WAITING) {
            throw new GameException("Les inscriptions sont fermées.", Response::HTTP_FORBIDDEN);
        }

        $gamePlayerRepo = $this->em->getRepository(GamePlayer::class);
        $existing = $gamePlayerRepo->findOneBy(['game' => $game, 'user' => $user]);

        switch ($action) {
            case 'join':
                if ($existing) {
                    if (!$existing->isSpectator()) {
                        throw new GameException("Tu es déjà inscrit comme joueur.", Response::HTTP_BAD_REQUEST);
                    }
                    // Il était spectateur, il devient joueur
                    $existing->setIsSpectator(false);
                } else {
                    // Nouvelle inscription en tant que joueur
                    $this->createNewRegistration($game, $user, false);
                }
                break;

            case 'spectate':
                if ($existing) {
                    if ($existing->isSpectator()) {
                        throw new GameException("Tu es déjà spectateur.", Response::HTTP_BAD_REQUEST);
                    }
                    // Il est joueur, il ne peut pas devenir spectateur
                    throw new GameException("Tu es actuellement inscrit en tant que joueur.", Response::HTTP_BAD_REQUEST);
                } else {
                    // Nouvelle inscription en tant que spectateur
                    $this->createNewRegistration($game, $user, true);
                }
                break;

            case 'leave':
                if (!$existing) {
                    throw new GameException("Tu n'es pas dans cette partie.", Response::HTTP_BAD_REQUEST);
                } else if ($existing->isSpectator()) {
                    throw new GameException("Tu n'es pas inscrit à cette partie.", Response::HTTP_BAD_REQUEST);
                }
                $this->em->remove($existing);
                break;

            default:
                throw new GameException("Action non reconnue.", Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        // On retourne les deux listes pour que le bot puisse mettre à jour l'embed complet
        return [
            'players' => $this->getDiscordIdsFromPlayers($game),
            'spectators' => $this->getDiscordIdsFromSpectators($game)
        ];
    }

    /**
     * Petite fonction helper pour éviter la répétition de code
     */
    private function createNewRegistration(Game $game, User $user, bool $asSpectator): void
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setUser($user);
        $gamePlayer->setIsAlive(true);
        $gamePlayer->setIsSpectator($asSpectator);

        $this->em->persist($gamePlayer);
        $game->addGamePlayer($gamePlayer);
    }

    /**
     * Annule et supprime une partie en attente
     */
    public function cancelGame(int $gameId): void
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);
        if (!$game) {
            throw new GameException("Partie introuvable.", Response::HTTP_NOT_FOUND);
        }

        if ($game->getStatus() !== GameStatus::WAITING) {
            throw new GameException(
                "Impossible d'annuler une partie qui n'est pas en attente.", 
                Response::HTTP_BAD_REQUEST
            );
        }

        // Doctrine va automatiquement supprimer les GamePlayer associés grâce à orphanRemoval: true
        $this->em->remove($game);
        $this->em->flush();
    }

    /**
     * Récupère les IDs Discord des joueurs (isSpectator = false)
     */
    public function getDiscordIdsFromPlayers(Game $game): array
    {
        return array_values($game->getGamePlayers()
            ->filter(fn(GamePlayer $gp) => !$gp->isSpectator())
            ->map(fn(GamePlayer $gp) => $gp->getUser()->getDiscordId())
            ->toArray());
    }

    /**
     * Récupère les IDs Discord des spectateurs (isSpectator = true)
     */
    public function getDiscordIdsFromSpectators(Game $game): array
    {
        return array_values($game->getGamePlayers()
            ->filter(fn(GamePlayer $gp) => $gp->isSpectator())
            ->map(fn(GamePlayer $gp) => $gp->getUser()->getDiscordId())
            ->toArray());
    }
}
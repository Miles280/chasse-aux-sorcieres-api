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
            'status' => [GameStatus::PLAYING]
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
     * Gère l'inscription via l'entité GamePlayer
     */
    public function inscriptionPlayerInGame(int $gameId, User $user, string $action): array
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);
        if (!$game) throw new GameException("Partie introuvable.", Response::HTTP_NOT_FOUND);

        if ($game->getStatus() !== GameStatus::WAITING) {
            throw new GameException("Les inscriptions sont fermées.", Response::HTTP_FORBIDDEN);
        }

        // On cherche si le joueur est déjà dans la partie
        $gamePlayerRepo = $this->em->getRepository(GamePlayer::class);
        $existingRegistration = $gamePlayerRepo->findOneBy(['game' => $game, 'user' => $user]);

        if ($action === 'join') {
            if ($existingRegistration) {
                throw new GameException("Tu es déjà inscrit à cette partie.", Response::HTTP_BAD_REQUEST);
            }

            $gamePlayer = new GamePlayer();
            $gamePlayer->setGame($game);
            $gamePlayer->setUser($user);
            $gamePlayer->setIsAlive(true);

            $this->em->persist($gamePlayer);
            $game->addGamePlayer($gamePlayer);
        } else {
            if (!$existingRegistration) {
                throw new GameException("Tu n'es pas inscrit à cette partie.", Response::HTTP_BAD_REQUEST);
            }
            $this->em->remove($existingRegistration);
        }

        $this->em->flush();

        return $this->getDiscordIdsFromParticipants($game);
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

    public function getDiscordIdsFromParticipants(Game $game): array
    {
        return $game->getGamePlayers()->map(fn(GamePlayer $gp) => $gp->getUser()->getDiscordId())->toArray();
    }
}
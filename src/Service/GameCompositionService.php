<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameComposition;
use App\Repository\GameCompositionRepository;
use App\Repository\GameRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GameCompositionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepo,
        private RoleRepository $roleRepo,
        private GameCompositionRepository $compoRepo,
        private NormalizerInterface $normalizer,
    ) {}

    public function addRoles(int $gameId, array $roleIds): Game
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            throw new \Exception("Partie introuvable.");
        }

        foreach ($roleIds as $roleId) {
            $role = $this->roleRepo->find($roleId);
            
            if (!$role) {
                throw new \Exception("L'ID de rôle $roleId n'existe pas en base de données.");
            }

            // Si le rôle est unique, on vérifie s'il existe déjà dans la partie
            if ($role->isUnique()) {
                $exists = $this->compoRepo->findOneBy([
                    'game' => $game,
                    'role' => $role
                ]);

                if ($exists) {
                    throw new \Exception(sprintf("Le rôle « __%s__ » est déjà présent dans la composition de cette partie.", $role->getName()));
                }
            }

            // Dans tous les autres cas (ou si c'est un Paysan/Sorcière non unique), on crée une nouvelle ligne.
            $composition = new GameComposition();
            $composition->setGame($game);
            $composition->setRole($role);

            $this->em->persist($composition);
        }

        $this->em->flush();

        $this->em->refresh($game);

        return $game;
    }

    public function removeRoles(int $gameId, array $roleIds): Game
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            throw new \Exception("Partie introuvable.");
        }

        foreach ($roleIds as $roleId) {
            $role = $this->roleRepo->find($roleId);

            if (!$role) {
                throw new \Exception("Impossible de supprimer : l'ID de rôle $roleId n'existe pas.");
            }
            // Pour chaque ID reçu, on cherche la ligne la plus récente
            $composition = $this->compoRepo->findOneBy(
                ['game' => $game, 'role' => $roleId],
                ['id' => 'DESC']
            );

            if ($composition) {
                $this->em->remove($composition);
            } else {
                throw new \Exception(sprintf("Le rôle « __%s__ » n'est pas présent dans la composition de cette partie.", $role->getName()));
            }
        }

        $this->em->flush();
        
        $this->em->refresh($game);

        return $game;
    }

    public function resetRoles(int $gameId): Game
    {
        $game = $this->gameRepo->find($gameId);
        if (!$game) {
            throw new \Exception("Partie introuvable.");
        }

        $compositions = $this->compoRepo->findBy(['game' => $game]);

        foreach ($compositions as $composition) {
            $this->em->remove($composition);
        }

        $this->em->flush();
        $this->em->refresh($game);

        return $game;
    }

    public function formatComposition(Game $game): array
    {
        $roles = array_map(
            fn($composition) => $composition->getRole(), 
            $game->getCompositions()->toArray()
        );

        return $this->normalizer->normalize($roles, null, ['groups' => ['role:read']]);
    }
}
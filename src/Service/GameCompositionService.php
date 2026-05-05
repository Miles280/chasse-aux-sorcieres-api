<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameComposition;
use App\Repository\GameCompositionRepository;
use App\Repository\GameRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;

final class GameCompositionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepo,
        private RoleRepository $roleRepo,
        private GameCompositionRepository $compoRepo
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
                    throw new \Exception(sprintf("Le rôle %s est déjà présent dans la composition de cette partie.", $role->getName()));
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
                throw new \Exception(sprintf("Le rôle %s n'est pas présent dans la composition de cette partie.", $role->getName()));
            }
        }

        $this->em->flush();
        
        $this->em->refresh($game);

        return $game;
    }
}
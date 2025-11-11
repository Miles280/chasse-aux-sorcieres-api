<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiscordUserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {}

    public function findOrCreateUserByDiscordId(string $discordId): User
    {
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        if (!$user) {
            $user = new User();
            $user->setDiscordId($discordId);

            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}

<?php

namespace App\Service\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiscordUserManager
{
    public function __construct(
        private UserRepository $userRepository,
        private DiscordRoleSyncService $roleSync,
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

    public function updateUserFromDiscord(User $user, array $discordUser, array $tokenData): User
    {
        // Infos du token
        $user->setDiscordAccessToken($tokenData['access_token'] ?? null);
        $user->setDiscordRefreshToken($tokenData['refresh_token'] ?? null);
        $user->setDiscordTokenExpiresAt(
            isset($tokenData['expires_in']) 
                ? new \DateTime('+'.$tokenData['expires_in'].' seconds')
                : null
        );

        // Infos API Discord
        $user->setDiscordUsername($discordUser['username'] ?? null);
        $user->setDiscordGlobalName($discordUser['global_name'] ?? null);
        $user->setDiscordAvatar($discordUser['avatar'] ?? null);
        $user->setEmail($discordUser['email'] ?? null);
        $this->roleSync->syncUserRoles($user);
        
        $user->setLastLoginAt(new \DateTime());

        $this->em->flush();

        return $user;
    }

    public function updateJwtTokens(User $user,string $refreshToken): User
    {
        // Infos du token
        $user->setJwtRefreshToken($refreshToken);
        $user->setJwtRefreshTokenExpiresAt(new \DateTime('+30 days'));

        $this->em->flush();

        return $user;
    }

    public function findUserByJwtRefreshToken(string $refreshToken): ?User
    {
        $user = $this->userRepository->findOneBy(['jwtRefreshToken' => $refreshToken]);

        if (!$user) {
            return null; 
        }

        return $user; 
    }

}

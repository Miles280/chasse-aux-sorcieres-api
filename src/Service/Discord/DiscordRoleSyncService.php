<?php 

namespace App\Service\Discord;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DiscordRoleSyncService
{
    public function __construct(
        private DiscordOAuthService $oauth,
        private DiscordGuildService $guild,
        private EntityManagerInterface $em
    ) {}

    public function syncUserRoles(User $user): void
    {
        // 1. Refresh token si expiré
        if (!$user->getDiscordTokenExpiresAt() || $user->getDiscordTokenExpiresAt() < new \DateTime()) {
            if (!$user->getDiscordRefreshToken()) return;
            $newTokens = $this->oauth->refreshAccessToken($user->getDiscordRefreshToken());

            $user->setDiscordAccessToken($newTokens['access_token']);
            $user->setDiscordRefreshToken($newTokens['refresh_token']);
            $user->setDiscordTokenExpiresAt(new \DateTime('+'.$newTokens['expires_in'].' seconds'));
        }

        // 2. Rôles Discord
        $member = $this->guild->getUserGuildMember($user->getDiscordAccessToken());
        $discordRoles = $member['roles'];

        // 3. Mapping
        $roleMapping = [
            '1190727880355876985' => 'ROLE_MJ',
            '1190727880355876986' => 'ROLE_ADMIN',
            '1190727880355876987' => 'ROLE_ADMIN',
        ];

        $newRoles = ['ROLE_USER'];

        foreach ($roleMapping as $discordRoleId => $symfonyRole) {
            if (in_array($discordRoleId, $discordRoles)) {
                $newRoles[] = $symfonyRole;
            }
        }

        $user->setRoles($newRoles);
        $this->em->flush();
    }
}

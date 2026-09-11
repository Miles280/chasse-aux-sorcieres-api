<?php

namespace App\Security\Voter;

use App\Entity\Role;
use App\Entity\User;
use App\Service\Auth\DiscordRoleSyncService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter
{
    public const EDIT = 'ROLE_EDIT';
    public const DELETE = 'ROLE_DELETE';
    public const CREATE = 'ROLE_CREATE';

    public function __construct(
        private DiscordRoleSyncService $roleSync
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le voter s'active si l'attribut est dans notre liste 
        // ET si l'objet concerné est une instance de Role (ou null pour le CREATE)
        return in_array($attribute, [self::EDIT, self::DELETE, self::CREATE])
            && ($subject instanceof Role || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // 1. Si l'utilisateur n'est pas connecté, on refuse tout
        if (!$user instanceof User) {
            return false;
        }

        // 2. ACTION CRUCIALE : On synchronise avec Discord MAINTENANT
        try {
            $this->roleSync->syncUserRoles($user);
        } catch (\Exception $e) {
            dump($e);
            return false;
        }

        // 3. On vérifie les nouveaux rôles synchronisés
        return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_WRITER', $user->getRoles());
    }
}
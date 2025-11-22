<?php

namespace App\Command;

use App\Entity\User;
use App\Service\Discord\DiscordRoleSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncDiscordRolesCommand extends Command
{
    protected static $defaultName = 'app:sync-discord-roles';

    public function __construct(
        private EntityManagerInterface $em,
        private DiscordRoleSyncService $roleSync
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->em->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $this->roleSync->syncUserRoles($user);
            $output->writeln("Synced roles for user {$user->getDiscordId()}");
        }

        return Command::SUCCESS;
    }
}

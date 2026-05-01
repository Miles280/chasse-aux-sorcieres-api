<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412010828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE server_config (id INT AUTO_INCREMENT NOT NULL, discord_server_id VARCHAR(50) NOT NULL, mj_role_id VARCHAR(50) DEFAULT NULL, inscription_channel_id VARCHAR(50) DEFAULT NULL, game_category_id VARCHAR(50) DEFAULT NULL, game_mj_channel_id VARCHAR(50) DEFAULT NULL, game_private_category_id VARCHAR(50) DEFAULT NULL, player_role_id VARCHAR(50) DEFAULT NULL, dead_player_role_id VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE server_config');
    }
}

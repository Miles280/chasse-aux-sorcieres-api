<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413020532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, game_master_id INT NOT NULL, status VARCHAR(50) NOT NULL, game_mode VARCHAR(100) NOT NULL, winning_camp VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', current_step VARCHAR(50) NOT NULL, day_number INT NOT NULL, inscription_message_id VARCHAR(50) DEFAULT NULL, compo_message_id VARCHAR(50) DEFAULT NULL, public_tracker_message_id VARCHAR(50) DEFAULT NULL, mj_tracker_message_id VARCHAR(50) DEFAULT NULL, INDEX IDX_232B318CC1151A13 (game_master_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_log (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, dead_player_id INT DEFAULT NULL, death_cause VARCHAR(255) DEFAULT NULL, day_number INT NOT NULL, step VARCHAR(50) DEFAULT NULL, INDEX IDX_94657B00E48FD905 (game_id), INDEX IDX_94657B00331CE9D1 (dead_player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_player (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, user_id INT NOT NULL, true_role_id INT DEFAULT NULL, revealed_role_id INT DEFAULT NULL, is_alive TINYINT(1) NOT NULL, gems_won INT DEFAULT NULL, INDEX IDX_E52CD7ADE48FD905 (game_id), INDEX IDX_E52CD7ADA76ED395 (user_id), INDEX IDX_E52CD7AD3B2A0159 (true_role_id), INDEX IDX_E52CD7ADDCA49045 (revealed_role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CC1151A13 FOREIGN KEY (game_master_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_log ADD CONSTRAINT FK_94657B00E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_log ADD CONSTRAINT FK_94657B00331CE9D1 FOREIGN KEY (dead_player_id) REFERENCES game_player (id)');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7AD3B2A0159 FOREIGN KEY (true_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7ADDCA49045 FOREIGN KEY (revealed_role_id) REFERENCES role (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CC1151A13');
        $this->addSql('ALTER TABLE game_log DROP FOREIGN KEY FK_94657B00E48FD905');
        $this->addSql('ALTER TABLE game_log DROP FOREIGN KEY FK_94657B00331CE9D1');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADE48FD905');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADA76ED395');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7AD3B2A0159');
        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7ADDCA49045');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_log');
        $this->addSql('DROP TABLE game_player');
    }
}

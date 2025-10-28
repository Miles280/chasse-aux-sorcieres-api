<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023165107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alignment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE camp (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, color VARCHAR(20) DEFAULT NULL, emoji_name VARCHAR(50) DEFAULT NULL, emoji_id VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE casino_data (id INT AUTO_INCREMENT NOT NULL, player_id INT NOT NULL, game VARCHAR(50) NOT NULL, bet_amount INT NOT NULL, won_amount INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8EC1944999E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversion_rates (id INT AUTO_INCREMENT NOT NULL, discord_role_id VARCHAR(50) NOT NULL, role_name VARCHAR(100) NOT NULL, gems_to_rubies_rate DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE goal (id INT AUTO_INCREMENT NOT NULL, objective LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, item_id INT NOT NULL, quantity INT NOT NULL, acquired_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B12D4A367E3C61F9 (owner_id), INDEX IDX_B12D4A36126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE power (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, title VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, is_day_power TINYINT(1) NOT NULL, is_passive TINYINT(1) NOT NULL, usage_limit INT DEFAULT NULL, position INT NOT NULL, leaving_house TINYINT(1) NOT NULL, INDEX IDX_AB8A01A0D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, camp_id INT NOT NULL, goal_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, min_player INT NOT NULL, INDEX IDX_57698A6A77075ABB (camp_id), INDEX IDX_57698A6A667D1AFE (goal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_alignment (role_id INT NOT NULL, alignment_id INT NOT NULL, INDEX IDX_7D3ACD80D60322AC (role_id), INDEX IDX_7D3ACD80AB7AC2A0 (alignment_id), PRIMARY KEY(role_id, alignment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shop (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, currency VARCHAR(255) NOT NULL, price INT NOT NULL, type VARCHAR(255) NOT NULL, discord_role_id VARCHAR(50) DEFAULT NULL, quantity INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, related_user_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, currency VARCHAR(255) NOT NULL, amount INT NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_723705D17E3C61F9 (owner_id), INDEX IDX_723705D198771930 (related_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, discord_id VARCHAR(50) NOT NULL, discord_username VARCHAR(100) DEFAULT NULL, discord_global_name VARCHAR(100) DEFAULT NULL, discord_avatar VARCHAR(100) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, gems INT NOT NULL, rubies INT NOT NULL, access_token LONGTEXT DEFAULT NULL, refresh_token LONGTEXT DEFAULT NULL, token_expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_login_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE casino_data ADD CONSTRAINT FK_8EC1944999E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A367E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36126F525E FOREIGN KEY (item_id) REFERENCES shop (id)');
        $this->addSql('ALTER TABLE power ADD CONSTRAINT FK_AB8A01A0D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A77075ABB FOREIGN KEY (camp_id) REFERENCES camp (id)');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A667D1AFE FOREIGN KEY (goal_id) REFERENCES goal (id)');
        $this->addSql('ALTER TABLE role_alignment ADD CONSTRAINT FK_7D3ACD80D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_alignment ADD CONSTRAINT FK_7D3ACD80AB7AC2A0 FOREIGN KEY (alignment_id) REFERENCES alignment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D17E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D198771930 FOREIGN KEY (related_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE casino_data DROP FOREIGN KEY FK_8EC1944999E6F5DF');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A367E3C61F9');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A36126F525E');
        $this->addSql('ALTER TABLE power DROP FOREIGN KEY FK_AB8A01A0D60322AC');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A77075ABB');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A667D1AFE');
        $this->addSql('ALTER TABLE role_alignment DROP FOREIGN KEY FK_7D3ACD80D60322AC');
        $this->addSql('ALTER TABLE role_alignment DROP FOREIGN KEY FK_7D3ACD80AB7AC2A0');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D17E3C61F9');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D198771930');
        $this->addSql('DROP TABLE alignment');
        $this->addSql('DROP TABLE camp');
        $this->addSql('DROP TABLE casino_data');
        $this->addSql('DROP TABLE conversion_rates');
        $this->addSql('DROP TABLE goal');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('DROP TABLE power');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_alignment');
        $this->addSql('DROP TABLE shop');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE `user`');
    }
}

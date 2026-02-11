<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206174029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A77075ABB');
        $this->addSql('ALTER TABLE role_alignment DROP FOREIGN KEY FK_7D3ACD80AB7AC2A0');
        $this->addSql('ALTER TABLE role_alignment DROP FOREIGN KEY FK_7D3ACD80D60322AC');
        $this->addSql('DROP TABLE alignment');
        $this->addSql('DROP TABLE camp');
        $this->addSql('DROP TABLE role_alignment');
        $this->addSql('DROP INDEX IDX_57698A6A77075ABB ON role');
        $this->addSql('ALTER TABLE role ADD camp VARCHAR(255) NOT NULL, ADD alignments JSON NOT NULL, DROP camp_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alignment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE camp (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, color VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, emoji_name VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, emoji_id VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE role_alignment (role_id INT NOT NULL, alignment_id INT NOT NULL, INDEX IDX_7D3ACD80D60322AC (role_id), INDEX IDX_7D3ACD80AB7AC2A0 (alignment_id), PRIMARY KEY(role_id, alignment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE role_alignment ADD CONSTRAINT FK_7D3ACD80AB7AC2A0 FOREIGN KEY (alignment_id) REFERENCES alignment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_alignment ADD CONSTRAINT FK_7D3ACD80D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role ADD camp_id INT NOT NULL, DROP camp, DROP alignments');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A77075ABB FOREIGN KEY (camp_id) REFERENCES camp (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_57698A6A77075ABB ON role (camp_id)');
    }
}

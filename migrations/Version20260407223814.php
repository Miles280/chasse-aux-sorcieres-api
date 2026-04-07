<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407223814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A667D1AFE');
        $this->addSql('DROP TABLE goal');
        $this->addSql('DROP INDEX IDX_57698A6A667D1AFE ON role');
        $this->addSql('ALTER TABLE role ADD goal LONGTEXT DEFAULT NULL, DROP goal_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE goal (id INT AUTO_INCREMENT NOT NULL, objective LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE role ADD goal_id INT DEFAULT NULL, DROP goal');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A667D1AFE FOREIGN KEY (goal_id) REFERENCES goal (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_57698A6A667D1AFE ON role (goal_id)');
    }
}

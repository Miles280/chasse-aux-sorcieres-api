<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128194402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop ADD required_item_id INT DEFAULT NULL, ADD required_role_id VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA2338E759E FOREIGN KEY (required_item_id) REFERENCES shop (id)');
        $this->addSql('CREATE INDEX IDX_AC6A4CA2338E759E ON shop (required_item_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop DROP FOREIGN KEY FK_AC6A4CA2338E759E');
        $this->addSql('DROP INDEX IDX_AC6A4CA2338E759E ON shop');
        $this->addSql('ALTER TABLE shop DROP required_item_id, DROP required_role_id');
    }
}

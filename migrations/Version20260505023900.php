<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505023900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_composition (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_D22A0145E48FD905 (game_id), INDEX IDX_D22A0145D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game_composition ADD CONSTRAINT FK_D22A0145E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_composition ADD CONSTRAINT FK_D22A0145D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role ADD is_unique TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_composition DROP FOREIGN KEY FK_D22A0145E48FD905');
        $this->addSql('ALTER TABLE game_composition DROP FOREIGN KEY FK_D22A0145D60322AC');
        $this->addSql('DROP TABLE game_composition');
        $this->addSql('ALTER TABLE role DROP is_unique');
    }
}

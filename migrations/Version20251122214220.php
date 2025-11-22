<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122214220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD discord_access_token LONGTEXT DEFAULT NULL, ADD discord_refresh_token LONGTEXT DEFAULT NULL, ADD jwt_refresh_token LONGTEXT DEFAULT NULL, ADD jwt_refresh_token_expires_at DATETIME DEFAULT NULL, DROP access_token, DROP refresh_token, CHANGE token_expires_at discord_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` ADD access_token LONGTEXT DEFAULT NULL, ADD refresh_token LONGTEXT DEFAULT NULL, ADD token_expires_at DATETIME DEFAULT NULL, DROP discord_access_token, DROP discord_refresh_token, DROP discord_token_expires_at, DROP jwt_refresh_token, DROP jwt_refresh_token_expires_at');
    }
}

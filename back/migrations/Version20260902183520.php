<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902183520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout de l invitation d'admin par un super admin via un token d invitation";
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD invitation_token VARCHAR(255) DEFAULT NULL, ADD invitation_expires_at DATETIME DEFAULT NULL, ADD is_active TINYINT NOT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP invitation_token, DROP invitation_expires_at, DROP is_active, CHANGE password password VARCHAR(255) NOT NULL');
    }
}

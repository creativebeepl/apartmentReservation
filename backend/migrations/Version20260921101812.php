<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921101812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_tokens (id BLOB NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, user_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_2CAD560EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2CAD560EB3BC57DA ON api_tokens (token_hash)');
        $this->addSql('CREATE INDEX IDX_2CAD560EA76ED395 ON api_tokens (user_id)');
        $this->addSql('CREATE TABLE bookings (id BLOB NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, customer_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, resource_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_7A853C3589329D25 FOREIGN KEY (resource_id) REFERENCES resources (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX idx_bookings_resource_period ON bookings (resource_id, start_at, end_at)');
        $this->addSql('CREATE INDEX idx_bookings_start_at ON bookings (start_at)');
        $this->addSql('CREATE INDEX IDX_7A853C3589329D25 ON bookings (resource_id)');
        $this->addSql('CREATE TABLE resources (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EF66EBAE5E237E06 ON resources (name)');
        $this->addSql('CREATE TABLE users (id BLOB NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE api_tokens');
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE resources');
        $this->addSql('DROP TABLE users');
    }
}

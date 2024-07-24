<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240724162323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE status_change_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, event_type VARCHAR(255) NOT NULL, event_type_id INT NOT NULL, status VARCHAR(255) NOT NULL, event_timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5F49EE5EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE status_change_log ADD CONSTRAINT FK_5F49EE5EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE status_change_log DROP FOREIGN KEY FK_5F49EE5EA76ED395');
        $this->addSql('DROP TABLE status_change_log');
    }
}

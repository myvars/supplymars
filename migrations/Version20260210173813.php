<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210173813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, author_type VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, visibility VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, public_id BINARY(16) DEFAULT NULL, ticket_id INT NOT NULL, author_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_B6BD307FB5B48B91 (public_id), INDEX IDX_B6BD307F700047D2 (ticket_id), INDEX IDX_B6BD307FF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE pool (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT NOT NULL, is_customer_visible TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, public_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_AF91A986B5B48B91 (public_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE pool_subscriber (pool_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_AAD1D68B7B3406DF (pool_id), INDEX IDX_AAD1D68BA76ED395 (user_id), PRIMARY KEY (pool_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, snoozed_until DATETIME DEFAULT NULL, message_count INT NOT NULL, last_message_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, public_id BINARY(16) DEFAULT NULL, pool_id INT NOT NULL, customer_id INT NOT NULL, UNIQUE INDEX UNIQ_97A0ADA3B5B48B91 (public_id), INDEX IDX_97A0ADA37B3406DF (pool_id), INDEX IDX_97A0ADA39395C3F3 (customer_id), INDEX idx_ticket_pool_status_snooze (pool_id, status, snoozed_until), INDEX idx_ticket_last_message (last_message_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE pool_subscriber ADD CONSTRAINT FK_AAD1D68B7B3406DF FOREIGN KEY (pool_id) REFERENCES pool (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pool_subscriber ADD CONSTRAINT FK_AAD1D68BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA37B3406DF FOREIGN KEY (pool_id) REFERENCES pool (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA39395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F700047D2');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF675F31B');
        $this->addSql('ALTER TABLE pool_subscriber DROP FOREIGN KEY FK_AAD1D68B7B3406DF');
        $this->addSql('ALTER TABLE pool_subscriber DROP FOREIGN KEY FK_AAD1D68BA76ED395');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA37B3406DF');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA39395C3F3');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE pool');
        $this->addSql('DROP TABLE pool_subscriber');
        $this->addSql('DROP TABLE ticket');
    }
}

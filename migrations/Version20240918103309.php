<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240918103309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order ADD order_lock_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A375178C96 FOREIGN KEY (order_lock_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_3B1CE6A375178C96 ON customer_order (order_lock_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A375178C96');
        $this->addSql('DROP INDEX IDX_3B1CE6A375178C96 ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP order_lock_id');
    }
}

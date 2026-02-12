<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212172150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_customer_order_status_created ON customer_order (status, created_at)');
        $this->addSql('CREATE INDEX idx_purchase_order_supplier_status_created ON purchase_order (supplier_id, status, created_at)');
        $this->addSql('CREATE INDEX idx_poi_status_delivered ON purchase_order_item (status, delivered_at)');
        $this->addSql('CREATE INDEX idx_poi_created_status ON purchase_order_item (created_at, status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_customer_order_status_created ON customer_order');
        $this->addSql('DROP INDEX idx_purchase_order_supplier_status_created ON purchase_order');
        $this->addSql('DROP INDEX idx_poi_status_delivered ON purchase_order_item');
        $this->addSql('DROP INDEX idx_poi_created_status ON purchase_order_item');
    }
}

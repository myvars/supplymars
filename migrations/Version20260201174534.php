<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201174534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_geographic_summary CHANGE order_value order_value NUMERIC(14, 2) NOT NULL, CHANGE average_order_value average_order_value NUMERIC(14, 2) NOT NULL');
        $this->addSql('ALTER TABLE customer_sales_summary CHANGE total_revenue total_revenue NUMERIC(14, 2) NOT NULL, CHANGE average_clv average_clv NUMERIC(14, 2) NOT NULL, CHANGE average_aov average_aov NUMERIC(14, 2) NOT NULL');
        $this->addSql('ALTER TABLE customer_segment_summary CHANGE order_value order_value NUMERIC(14, 2) NOT NULL, CHANGE average_order_value average_order_value NUMERIC(14, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_geographic_summary CHANGE order_value order_value NUMERIC(10, 2) NOT NULL, CHANGE average_order_value average_order_value NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE customer_sales_summary CHANGE total_revenue total_revenue NUMERIC(10, 2) NOT NULL, CHANGE average_clv average_clv NUMERIC(10, 2) NOT NULL, CHANGE average_aov average_aov NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE customer_segment_summary CHANGE order_value order_value NUMERIC(10, 2) NOT NULL, CHANGE average_order_value average_order_value NUMERIC(10, 2) NOT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241111153305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_sales_summary (sales_id INT NOT NULL, sales_type VARCHAR(50) NOT NULL, duration VARCHAR(10) NOT NULL, period INT NOT NULL, sales_qty INT NOT NULL, sales_cost NUMERIC(10, 2) NOT NULL, sales_value NUMERIC(10, 2) NOT NULL, PRIMARY KEY(sales_id, sales_type, duration, period)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE product_sales_summary');
    }
}

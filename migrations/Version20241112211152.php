<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241112211152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_sales (date_string VARCHAR(10) NOT NULL, sales_qty INT NOT NULL, sales_cost NUMERIC(10, 2) NOT NULL, sales_value NUMERIC(10, 2) NOT NULL, sales_date DATE NOT NULL, product_id INT NOT NULL, supplier_id INT NOT NULL, INDEX IDX_CADD0B184584665A (product_id), INDEX IDX_CADD0B182ADD6D8C (supplier_id), PRIMARY KEY(product_id, supplier_id, date_string)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_sales_summary (sales_id INT NOT NULL, sales_type VARCHAR(50) NOT NULL, duration VARCHAR(10) NOT NULL, date_string VARCHAR(10) NOT NULL, sales_qty INT NOT NULL, sales_cost NUMERIC(10, 2) NOT NULL, sales_value NUMERIC(10, 2) NOT NULL, sales_date DATE NOT NULL, PRIMARY KEY(sales_id, sales_type, duration, date_string)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_sales ADD CONSTRAINT FK_CADD0B184584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_sales ADD CONSTRAINT FK_CADD0B182ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_sales DROP FOREIGN KEY FK_CADD0B184584665A');
        $this->addSql('ALTER TABLE product_sales DROP FOREIGN KEY FK_CADD0B182ADD6D8C');
        $this->addSql('DROP TABLE product_sales');
        $this->addSql('DROP TABLE product_sales_summary');
    }
}

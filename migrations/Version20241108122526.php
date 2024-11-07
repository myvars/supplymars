<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241108122526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `primary` ON product_sales');
        $this->addSql('ALTER TABLE product_sales ADD supplier_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_sales ADD CONSTRAINT FK_CADD0B182ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('CREATE INDEX IDX_CADD0B182ADD6D8C ON product_sales (supplier_id)');
        $this->addSql('ALTER TABLE product_sales ADD PRIMARY KEY (product_id, supplier_id, date_string)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_sales DROP FOREIGN KEY FK_CADD0B182ADD6D8C');
        $this->addSql('DROP INDEX IDX_CADD0B182ADD6D8C ON product_sales');
        $this->addSql('DROP INDEX `PRIMARY` ON product_sales');
        $this->addSql('ALTER TABLE product_sales DROP supplier_id');
        $this->addSql('ALTER TABLE product_sales ADD PRIMARY KEY (product_id, date_string)');
    }
}

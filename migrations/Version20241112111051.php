<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241112111051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `primary` ON product_sales_summary');
        $this->addSql('ALTER TABLE product_sales_summary ADD date_string VARCHAR(10) NOT NULL, ADD sales_date DATE NOT NULL, DROP period');
        $this->addSql('ALTER TABLE product_sales_summary ADD PRIMARY KEY (sales_id, sales_type, duration, date_string)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX `PRIMARY` ON product_sales_summary');
        $this->addSql('ALTER TABLE product_sales_summary ADD period INT NOT NULL, DROP date_string, DROP sales_date');
        $this->addSql('ALTER TABLE product_sales_summary ADD PRIMARY KEY (sales_id, sales_type, duration, period)');
    }
}

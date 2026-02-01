<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create customer reporting tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_sales (
            customer_id INT NOT NULL,
            date_string VARCHAR(10) NOT NULL,
            order_count INT NOT NULL,
            order_value NUMERIC(10, 2) NOT NULL,
            item_count INT NOT NULL,
            sales_date DATE NOT NULL,
            PRIMARY KEY (customer_id, date_string)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE customer_activity_sales (
            date_string VARCHAR(10) NOT NULL,
            total_customers INT NOT NULL,
            active_customers INT NOT NULL,
            new_customers INT NOT NULL,
            returning_customers INT NOT NULL,
            sales_date DATE NOT NULL,
            PRIMARY KEY (date_string)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE customer_sales_summary (
            duration VARCHAR(50) NOT NULL,
            date_string VARCHAR(10) NOT NULL,
            total_customers INT NOT NULL,
            active_customers INT NOT NULL,
            new_customers INT NOT NULL,
            returning_customers INT NOT NULL,
            total_revenue NUMERIC(10, 2) NOT NULL,
            average_clv NUMERIC(10, 2) NOT NULL,
            average_aov NUMERIC(10, 2) NOT NULL,
            repeat_rate NUMERIC(5, 2) NOT NULL,
            review_rate NUMERIC(5, 2) NOT NULL,
            average_orders_per_customer NUMERIC(5, 2) NOT NULL,
            sales_date DATE NOT NULL,
            PRIMARY KEY (duration, date_string)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE customer_geographic_summary (
            city VARCHAR(100) NOT NULL,
            duration VARCHAR(50) NOT NULL,
            date_string VARCHAR(10) NOT NULL,
            customer_count INT NOT NULL,
            order_count INT NOT NULL,
            order_value NUMERIC(10, 2) NOT NULL,
            average_order_value NUMERIC(10, 2) NOT NULL,
            sales_date DATE NOT NULL,
            PRIMARY KEY (city, duration, date_string)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE customer_segment_summary (
            segment VARCHAR(50) NOT NULL,
            duration VARCHAR(50) NOT NULL,
            date_string VARCHAR(10) NOT NULL,
            customer_count INT NOT NULL,
            order_count INT NOT NULL,
            order_value NUMERIC(10, 2) NOT NULL,
            average_order_value NUMERIC(10, 2) NOT NULL,
            average_items_per_order NUMERIC(5, 2) NOT NULL,
            sales_date DATE NOT NULL,
            PRIMARY KEY (segment, duration, date_string)
        ) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE customer_sales');
        $this->addSql('DROP TABLE customer_activity_sales');
        $this->addSql('DROP TABLE customer_sales_summary');
        $this->addSql('DROP TABLE customer_geographic_summary');
        $this->addSql('DROP TABLE customer_segment_summary');
    }
}

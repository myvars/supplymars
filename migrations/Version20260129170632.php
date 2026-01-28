<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129170632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_review (id INT AUTO_INCREMENT NOT NULL, rating SMALLINT NOT NULL, title VARCHAR(255) DEFAULT NULL, body LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, rejection_reason VARCHAR(255) DEFAULT NULL, moderation_notes LONGTEXT DEFAULT NULL, moderated_at DATETIME DEFAULT NULL, published_at DATETIME DEFAULT NULL, public_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INT NOT NULL, product_id INT NOT NULL, customer_order_id INT NOT NULL, moderated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1B3FC062B5B48B91 (public_id), INDEX IDX_1B3FC0629395C3F3 (customer_id), INDEX IDX_1B3FC0624584665A (product_id), INDEX IDX_1B3FC062A15A2E17 (customer_order_id), INDEX IDX_1B3FC0628EDA19B0 (moderated_by_id), UNIQUE INDEX unique_customer_product_review (customer_id, product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_review_summary (id INT AUTO_INCREMENT NOT NULL, review_count INT NOT NULL, average_rating NUMERIC(3, 2) NOT NULL, rating_distribution JSON NOT NULL, pending_count INT NOT NULL, public_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, product_id INT NOT NULL, UNIQUE INDEX UNIQ_6AFACB8B5B48B91 (public_id), UNIQUE INDEX unique_product_summary (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0629395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0624584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC062A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES customer_order (id)');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_1B3FC0628EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE product_review_summary ADD CONSTRAINT FK_6AFACB84584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC0629395C3F3');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC0624584665A');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC062A15A2E17');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_1B3FC0628EDA19B0');
        $this->addSql('ALTER TABLE product_review_summary DROP FOREIGN KEY FK_6AFACB84584665A');
        $this->addSql('DROP TABLE product_review');
        $this->addSql('DROP TABLE product_review_summary');
    }
}

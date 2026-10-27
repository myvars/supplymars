<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027144029 extends AbstractMigration
{
    private const array TABLES = [
        'address',
        'category',
        'customer_order',
        'customer_order_item',
        'manufacturer',
        'message',
        'pool',
        'product',
        'product_image',
        'product_review',
        'product_review_summary',
        'purchase_order',
        'purchase_order_item',
        'reset_password_request',
        'subcategory',
        'supplier',
        'supplier_category',
        'supplier_manufacturer',
        'supplier_product',
        'supplier_subcategory',
        'ticket',
        'user',
        'vat_rate',
    ];

    public function getDescription(): string
    {
        return 'Make publicId NOT NULL, and drop the unused one from status_change_log';
    }

    public function up(Schema $schema): void
    {
        // Each ALTER commits on its own, so check every table before changing any
        foreach (self::TABLES as $table) {
            $nulls = (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM `%s` WHERE public_id IS NULL', $table));
            $this->abortIf($nulls > 0, sprintf('%s has %d rows without a public_id: run app:backfill-ulids first', $table, $nulls));
        }

        $this->addSql('ALTER TABLE address CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE customer_order CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE customer_order_item CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE manufacturer CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE pool CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE product_image CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE product_review CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE product_review_summary CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE purchase_order CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE purchase_order_item CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE reset_password_request CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_5F49EE5EB5B48B91 ON status_change_log');
        $this->addSql('ALTER TABLE status_change_log DROP public_id');
        $this->addSql('ALTER TABLE subcategory CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE supplier CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE supplier_category CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE supplier_manufacturer CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE supplier_product CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE supplier_subcategory CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE public_id public_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE vat_rate CHANGE public_id public_id BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE category CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order_item CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE manufacturer CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE message CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE pool CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_image CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_review CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_review_summary CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE purchase_order CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE purchase_order_item CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE reset_password_request CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE status_change_log ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F49EE5EB5B48B91 ON status_change_log (public_id)');
        $this->addSql('ALTER TABLE subcategory CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier_category CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier_manufacturer CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier_product CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier_subcategory CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE public_id public_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE vat_rate CHANGE public_id public_id BINARY(16) DEFAULT NULL');
    }
}

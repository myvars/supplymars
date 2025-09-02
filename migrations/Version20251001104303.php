<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001104303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D4E6F81B5B48B91 ON address (public_id)');
        $this->addSql('ALTER TABLE category ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1B5B48B91 ON category (public_id)');
        $this->addSql('ALTER TABLE customer_order ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3B1CE6A3B5B48B91 ON customer_order (public_id)');
        $this->addSql('ALTER TABLE customer_order_item ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AF231B8BB5B48B91 ON customer_order_item (public_id)');
        $this->addSql('ALTER TABLE manufacturer ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3D0AE6DCB5B48B91 ON manufacturer (public_id)');
        $this->addSql('ALTER TABLE product ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04ADB5B48B91 ON product (public_id)');
        $this->addSql('ALTER TABLE product_image ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64617F03B5B48B91 ON product_image (public_id)');
        $this->addSql('ALTER TABLE purchase_order ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_21E210B2B5B48B91 ON purchase_order (public_id)');
        $this->addSql('ALTER TABLE purchase_order_item ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5ED948C3B5B48B91 ON purchase_order_item (public_id)');
        $this->addSql('ALTER TABLE reset_password_request ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CE748AB5B48B91 ON reset_password_request (public_id)');
        $this->addSql('ALTER TABLE subcategory ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DDCA448B5B48B91 ON subcategory (public_id)');
        $this->addSql('ALTER TABLE supplier ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9B2A6C7EB5B48B91 ON supplier (public_id)');
        $this->addSql('ALTER TABLE supplier_category ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8BC57140B5B48B91 ON supplier_category (public_id)');
        $this->addSql('ALTER TABLE supplier_manufacturer ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57637185B5B48B91 ON supplier_manufacturer (public_id)');
        $this->addSql('ALTER TABLE supplier_product ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_522F70B2B5B48B91 ON supplier_product (public_id)');
        $this->addSql('ALTER TABLE supplier_subcategory ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D46B0F59B5B48B91 ON supplier_subcategory (public_id)');
        $this->addSql('ALTER TABLE user ADD public_id BINARY(16) DEFAULT NULL, CHANGE roles roles JSON NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649B5B48B91 ON user (public_id)');
        $this->addSql('ALTER TABLE vat_rate ADD public_id BINARY(16) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F684F7C7B5B48B91 ON vat_rate (public_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D4E6F81B5B48B91 ON address');
        $this->addSql('ALTER TABLE address DROP public_id');
        $this->addSql('DROP INDEX UNIQ_64C19C1B5B48B91 ON category');
        $this->addSql('ALTER TABLE category DROP public_id');
        $this->addSql('DROP INDEX UNIQ_3B1CE6A3B5B48B91 ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP public_id');
        $this->addSql('DROP INDEX UNIQ_AF231B8BB5B48B91 ON customer_order_item');
        $this->addSql('ALTER TABLE customer_order_item DROP public_id');
        $this->addSql('DROP INDEX UNIQ_3D0AE6DCB5B48B91 ON manufacturer');
        $this->addSql('ALTER TABLE manufacturer DROP public_id');
        $this->addSql('DROP INDEX UNIQ_D34A04ADB5B48B91 ON product');
        $this->addSql('ALTER TABLE product DROP public_id');
        $this->addSql('DROP INDEX UNIQ_64617F03B5B48B91 ON product_image');
        $this->addSql('ALTER TABLE product_image DROP public_id');
        $this->addSql('DROP INDEX UNIQ_21E210B2B5B48B91 ON purchase_order');
        $this->addSql('ALTER TABLE purchase_order DROP public_id');
        $this->addSql('DROP INDEX UNIQ_5ED948C3B5B48B91 ON purchase_order_item');
        $this->addSql('ALTER TABLE purchase_order_item DROP public_id');
        $this->addSql('DROP INDEX UNIQ_7CE748AB5B48B91 ON reset_password_request');
        $this->addSql('ALTER TABLE reset_password_request DROP public_id');
        $this->addSql('DROP INDEX UNIQ_DDCA448B5B48B91 ON subcategory');
        $this->addSql('ALTER TABLE subcategory DROP public_id');
        $this->addSql('DROP INDEX UNIQ_9B2A6C7EB5B48B91 ON supplier');
        $this->addSql('ALTER TABLE supplier DROP public_id');
        $this->addSql('DROP INDEX UNIQ_8BC57140B5B48B91 ON supplier_category');
        $this->addSql('ALTER TABLE supplier_category DROP public_id');
        $this->addSql('DROP INDEX UNIQ_57637185B5B48B91 ON supplier_manufacturer');
        $this->addSql('ALTER TABLE supplier_manufacturer DROP public_id');
        $this->addSql('DROP INDEX UNIQ_522F70B2B5B48B91 ON supplier_product');
        $this->addSql('ALTER TABLE supplier_product DROP public_id');
        $this->addSql('DROP INDEX UNIQ_D46B0F59B5B48B91 ON supplier_subcategory');
        $this->addSql('ALTER TABLE supplier_subcategory DROP public_id');
        $this->addSql('DROP INDEX UNIQ_8D93D649B5B48B91 ON user');
        $this->addSql('ALTER TABLE user DROP public_id, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('DROP INDEX UNIQ_F684F7C7B5B48B91 ON vat_rate');
        $this->addSql('ALTER TABLE vat_rate DROP public_id');
    }
}

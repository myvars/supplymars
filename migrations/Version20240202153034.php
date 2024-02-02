<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240202153034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, vat_rate_id INT NOT NULL, name VARCHAR(255) NOT NULL, markup INT NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_64C19C17E3C61F9 (owner_id), INDEX IDX_64C19C143897540 (vat_rate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manufacturer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, vat_rate_id INT DEFAULT NULL, category_id INT NOT NULL, subcategory_id INT NOT NULL, manufacturer_id INT NOT NULL, owner_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, mfr_part_number VARCHAR(255) NOT NULL, stock INT NOT NULL, lead_time_days INT NOT NULL, weight INT NOT NULL, markup INT DEFAULT NULL, cost INT NOT NULL, sell_price INT NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_D34A04AD43897540 (vat_rate_id), INDEX IDX_D34A04AD12469DE2 (category_id), INDEX IDX_D34A04AD5DC6FE57 (subcategory_id), INDEX IDX_D34A04ADA23B42D (manufacturer_id), INDEX IDX_D34A04AD7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subcategory (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, category_id INT NOT NULL, vat_rate_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, markup INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_DDCA4487E3C61F9 (owner_id), INDEX IDX_DDCA44812469DE2 (category_id), INDEX IDX_DDCA44843897540 (vat_rate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vat_rate (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, rate INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C17E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C143897540 FOREIGN KEY (vat_rate_id) REFERENCES vat_rate (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD43897540 FOREIGN KEY (vat_rate_id) REFERENCES vat_rate (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD5DC6FE57 FOREIGN KEY (subcategory_id) REFERENCES subcategory (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADA23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subcategory ADD CONSTRAINT FK_DDCA4487E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subcategory ADD CONSTRAINT FK_DDCA44812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE subcategory ADD CONSTRAINT FK_DDCA44843897540 FOREIGN KEY (vat_rate_id) REFERENCES vat_rate (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C17E3C61F9');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C143897540');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD43897540');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD5DC6FE57');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADA23B42D');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7E3C61F9');
        $this->addSql('ALTER TABLE subcategory DROP FOREIGN KEY FK_DDCA4487E3C61F9');
        $this->addSql('ALTER TABLE subcategory DROP FOREIGN KEY FK_DDCA44812469DE2');
        $this->addSql('ALTER TABLE subcategory DROP FOREIGN KEY FK_DDCA44843897540');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE manufacturer');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE subcategory');
        $this->addSql('DROP TABLE vat_rate');
    }
}

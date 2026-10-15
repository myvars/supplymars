<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015115633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store API tokens as SHA-256 hashes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D6497BA2F5EB ON user');
        $this->addSql('ALTER TABLE user CHANGE api_token api_token_hash VARCHAR(64) DEFAULT NULL');
        // Revoke existing tokens: older backups hold them in plain text. Re-issue with app:user:issue-token.
        $this->addSql('UPDATE user SET api_token_hash = NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64974162BCB ON user (api_token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D64974162BCB ON user');
        // A hash cannot be turned back into its token: revoke them all.
        $this->addSql('UPDATE user SET api_token_hash = NULL');
        $this->addSql('ALTER TABLE user CHANGE api_token_hash api_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6497BA2F5EB ON user (api_token)');
    }
}

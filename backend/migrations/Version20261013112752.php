<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261013112752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD title VARCHAR(255) NOT NULL, ADD slug VARCHAR(255) NOT NULL, ADD summary LONGTEXT NOT NULL, ADD content LONGTEXT NOT NULL, ADD image_path VARCHAR(255) DEFAULT NULL, ADD image_alt VARCHAR(255) DEFAULT NULL, ADD category VARCHAR(50) NOT NULL, ADD published TINYINT NOT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, ADD published_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66989D9B62 ON article (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_23A0E66989D9B62 ON article');
        $this->addSql('ALTER TABLE article DROP title, DROP slug, DROP summary, DROP content, DROP image_path, DROP image_alt, DROP category, DROP published, DROP created_at, DROP updated_at, DROP published_at');
    }
}

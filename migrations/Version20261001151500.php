<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001151500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a call-for-applications document on each multi-location site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_location ADD aac_document_name VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_location DROP aac_document_name, DROP updated_at');
    }
}

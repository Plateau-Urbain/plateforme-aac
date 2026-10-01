<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional space distribution and FAQ documents on each multi-location site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_location ADD plan_document_name VARCHAR(255) DEFAULT NULL, ADD faq_document_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_location DROP plan_document_name, DROP faq_document_name');
    }
}

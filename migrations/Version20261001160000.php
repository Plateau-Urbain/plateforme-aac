<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sought activities on each multi-location site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_location ADD activity_description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_location DROP activity_description');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active flag to space_type referential';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_type ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_type DROP is_active');
    }
}

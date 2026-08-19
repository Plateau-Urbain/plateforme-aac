<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow excluding sites from multi-location application ranking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application_location_preference ADD excluded TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE application_location_preference CHANGE `rank` `rank` INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application_location_preference DROP excluded');
        $this->addSql('ALTER TABLE application_location_preference CHANGE `rank` `rank` INT NOT NULL');
    }
}

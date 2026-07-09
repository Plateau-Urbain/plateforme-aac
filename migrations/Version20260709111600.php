<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709111600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add application_location_preference table for multi-location AAC candidate ranking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE application_location_preference (id INT AUTO_INCREMENT NOT NULL, application_id INT NOT NULL, location_id INT NOT NULL, `rank` INT NOT NULL, INDEX IDX_APP_LOC_PREF_APPLICATION (application_id), INDEX IDX_APP_LOC_PREF_LOCATION (location_id), UNIQUE INDEX uniq_application_location (application_id, location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application_location_preference ADD CONSTRAINT FK_APP_LOC_PREF_APPLICATION FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_location_preference ADD CONSTRAINT FK_APP_LOC_PREF_LOCATION FOREIGN KEY (location_id) REFERENCES space_location (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application_location_preference DROP FOREIGN KEY FK_APP_LOC_PREF_APPLICATION');
        $this->addSql('ALTER TABLE application_location_preference DROP FOREIGN KEY FK_APP_LOC_PREF_LOCATION');
        $this->addSql('DROP TABLE application_location_preference');
    }
}

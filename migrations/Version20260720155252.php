<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720155252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application_file DROP FOREIGN KEY FK_7B735E98DF558641');
        $this->addSql('ALTER TABLE application_file ADD CONSTRAINT FK_7B735E98DF558641 FOREIGN KEY (space_document_id) REFERENCES space_document (id)');
        $this->addSql('ALTER TABLE application_location_preference RENAME INDEX idx_app_loc_pref_application TO IDX_DEFD5D863E030ACD');
        $this->addSql('ALTER TABLE application_location_preference RENAME INDEX idx_app_loc_pref_location TO IDX_DEFD5D8664D218E');
        $this->addSql('ALTER TABLE Parcel DROP FOREIGN KEY FK_CE375856C54C8C93');
        $this->addSql('ALTER TABLE Parcel ADD CONSTRAINT FK_C99B5D60C54C8C93 FOREIGN KEY (type_id) REFERENCES local_type (id)');
        $this->addSql('ALTER TABLE space_location ADD availability VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE space_location RENAME INDEX idx_space_location_space TO IDX_261954B223575340');
        $this->addSql('ALTER TABLE space_visit RENAME INDEX idx_space_visit_location TO IDX_4E78F57D64D218E');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE space_location DROP availability');
        $this->addSql('ALTER TABLE space_location RENAME INDEX idx_261954b223575340 TO IDX_SPACE_LOCATION_SPACE');
        $this->addSql('ALTER TABLE parcel DROP FOREIGN KEY FK_C99B5D60C54C8C93');
        $this->addSql('ALTER TABLE parcel ADD CONSTRAINT FK_CE375856C54C8C93 FOREIGN KEY (type_id) REFERENCES LocalType (id)');
        $this->addSql('ALTER TABLE space_visit RENAME INDEX idx_4e78f57d64d218e TO IDX_SPACE_VISIT_LOCATION');
        $this->addSql('ALTER TABLE application_location_preference RENAME INDEX idx_defd5d863e030acd TO IDX_APP_LOC_PREF_APPLICATION');
        $this->addSql('ALTER TABLE application_location_preference RENAME INDEX idx_defd5d8664d218e TO IDX_APP_LOC_PREF_LOCATION');
        $this->addSql('ALTER TABLE application_file DROP FOREIGN KEY FK_7B735E98DF558641');
        $this->addSql('ALTER TABLE application_file ADD CONSTRAINT FK_7B735E98DF558641 FOREIGN KEY (space_document_id) REFERENCES SpaceDocument (id)');
    }
}

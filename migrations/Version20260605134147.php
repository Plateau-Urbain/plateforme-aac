<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605134147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Parcel DROP FOREIGN KEY FK_CE375856C54C8C93');
        $this->addSql('ALTER TABLE Space DROP FOREIGN KEY FK_E8B3EE3EC54C8C93');
        $this->addSql('ALTER TABLE SpaceAttribute DROP FOREIGN KEY FK_E3A88D9923575340');
        $this->addSql('ALTER TABLE SpaceAttribute DROP FOREIGN KEY FK_E3A88D99B6E62EFA');
        $this->addSql('ALTER TABLE SpaceDocument DROP FOREIGN KEY FK_663D99E023575340');
        $this->addSql('ALTER TABLE UserDocument DROP FOREIGN KEY FK_156BCCAF4F912EC8');
        $this->addSql('DROP TABLE LocalType');
        $this->addSql('DROP TABLE migration_versions');
        $this->addSql('DROP TABLE SpaceAttribute');
        $this->addSql('DROP TABLE SpaceDocument');
        $this->addSql('DROP TABLE SpaceType');
        $this->addSql('DROP TABLE UserDocument');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE LocalType (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE migration_versions (version VARCHAR(14) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, executed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE SpaceAttribute (id INT AUTO_INCREMENT NOT NULL, space_id INT DEFAULT NULL, attribute_id INT DEFAULT NULL, availability SMALLINT NOT NULL, INDEX IDX_E3A88D9923575340 (space_id), INDEX IDX_E3A88D99B6E62EFA (attribute_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE SpaceDocument (id INT AUTO_INCREMENT NOT NULL, space_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_663D99E023575340 (space_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE SpaceType (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE UserDocument (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, updatedAt DATETIME NOT NULL, file_name VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, projectHolder_id INT DEFAULT NULL, INDEX IDX_156BCCAF4F912EC8 (projectHolder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE SpaceAttribute ADD CONSTRAINT FK_E3A88D9923575340 FOREIGN KEY (space_id) REFERENCES Space (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE SpaceAttribute ADD CONSTRAINT FK_E3A88D99B6E62EFA FOREIGN KEY (attribute_id) REFERENCES Attribute (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE SpaceDocument ADD CONSTRAINT FK_663D99E023575340 FOREIGN KEY (space_id) REFERENCES Space (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE UserDocument ADD CONSTRAINT FK_156BCCAF4F912EC8 FOREIGN KEY (projectHolder_id) REFERENCES fos_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE parcel ADD CONSTRAINT FK_CE375856C54C8C93 FOREIGN KEY (type_id) REFERENCES LocalType (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE space ADD CONSTRAINT FK_E8B3EE3EC54C8C93 FOREIGN KEY (type_id) REFERENCES SpaceType (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}

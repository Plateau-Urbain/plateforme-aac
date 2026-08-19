<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create space_location and space_visit tables (previously created ad-hoc, never migrated)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE space_location (
              id INT AUTO_INCREMENT NOT NULL,
              space_id INT DEFAULT NULL,
              name VARCHAR(255) NOT NULL,
              address VARCHAR(255) DEFAULT NULL,
              zip_code VARCHAR(5) DEFAULT NULL,
              city VARCHAR(255) DEFAULT NULL,
              latitude DOUBLE PRECISION DEFAULT NULL,
              longitude DOUBLE PRECISION DEFAULT NULL,
              description LONGTEXT DEFAULT NULL,
              is_erp TINYINT(1) DEFAULT 0 NOT NULL,
              display_order INT DEFAULT 0 NOT NULL,
              suspended TINYINT(1) DEFAULT 0 NOT NULL,
              suspension_message LONGTEXT DEFAULT NULL,
              suspended_at DATETIME DEFAULT NULL,
              INDEX idx_space_location_space (space_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE space_location ADD CONSTRAINT FK_261954B223575340 FOREIGN KEY (space_id) REFERENCES Space (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
            CREATE TABLE space_visit (
              id INT AUTO_INCREMENT NOT NULL,
              space_id INT DEFAULT NULL,
              location_id INT DEFAULT NULL,
              visit_date DATE NOT NULL,
              start_time TIME NOT NULL,
              end_time TIME NOT NULL,
              INDEX IDX_4E78F57D23575340 (space_id),
              INDEX idx_space_visit_location (location_id),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE space_visit ADD CONSTRAINT FK_4E78F57D23575340 FOREIGN KEY (space_id) REFERENCES Space (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE space_visit ADD CONSTRAINT FK_4E78F57D64D218E FOREIGN KEY (location_id) REFERENCES space_location (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE space_visit DROP FOREIGN KEY FK_4E78F57D23575340');
        $this->addSql('ALTER TABLE space_visit DROP FOREIGN KEY FK_4E78F57D64D218E');
        $this->addSql('DROP TABLE space_visit');
        $this->addSql('ALTER TABLE space_location DROP FOREIGN KEY FK_261954B223575340');
        $this->addSql('DROP TABLE space_location');
    }
}

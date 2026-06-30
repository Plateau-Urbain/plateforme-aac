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
        $conn = $this->connection;
        
        $dropFkIfExists = function(string $table, string $fk) use ($conn) {
            $sql = "SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS 
                    WHERE CONSTRAINT_SCHEMA = DATABASE() 
                      AND TABLE_NAME = ? 
                      AND CONSTRAINT_NAME = ?";
            $exists = $conn->fetchOne($sql, [$table, $fk]);
            if ($exists) {
                $this->addSql("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
            }
        };

        $dropTableIfExists = function(string $table) use ($conn) {
            $sql = "SELECT 1 FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND BINARY TABLE_NAME = ?";
            $exists = $conn->fetchOne($sql, [$table]);
            if ($exists) {
                // Also drop any foreign keys pointing TO this table, just in case!
                $fks = $conn->fetchAllAssociative("
                    SELECT TABLE_NAME, CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND REFERENCED_TABLE_NAME = ? 
                      AND REFERENCED_COLUMN_NAME IS NOT NULL
                ", [$table]);
                foreach ($fks as $fk) {
                    $this->addSql("ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
                }
                $this->addSql("DROP TABLE `{$table}`");
            }
        };

        $renameTableIfExists = function(string $old, string $new) use ($conn) {
            $oldExists = $conn->fetchOne("
                SELECT 1 FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND BINARY TABLE_NAME = ?
            ", [$old]);
            
            $newExists = $conn->fetchOne("
                SELECT 1 FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND BINARY TABLE_NAME = ?
            ", [$new]);
            
            if ($oldExists && !$newExists) {
                $tempName = $old . '_tmp_migration';
                $this->addSql("RENAME TABLE `{$old}` TO `{$tempName}`");
                $this->addSql("RENAME TABLE `{$tempName}` TO `{$new}`");
            }
        };

        // 1. Handle SpaceAttribute: Rename SpaceAttribute containing data, drop empty space_attribute join table
        $hasSpaceAttributeUpper = $conn->fetchOne("
            SELECT 1 FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND BINARY TABLE_NAME = 'SpaceAttribute'
        ");
        if ($hasSpaceAttributeUpper) {
            $this->addSql("RENAME TABLE `SpaceAttribute` TO `SpaceAttribute_tmp_migration`");
            
            // Drop empty space_attribute join table (and its foreign keys)
            $dropFkIfExists('space_attribute', 'FK_D3DBA1BE23575340');
            $dropFkIfExists('space_attribute', 'FK_D3DBA1BEB6E62EFA');
            $dropTableIfExists('space_attribute');
            
            $this->addSql("RENAME TABLE `SpaceAttribute_tmp_migration` TO `space_attribute`");
        } else {
            // Fallback just in case
            $dropFkIfExists('space_attribute', 'FK_D3DBA1BE23575340');
            $dropFkIfExists('space_attribute', 'FK_D3DBA1BEB6E62EFA');
            $dropTableIfExists('space_attribute');
        }

        // 2. Drop other obsolete tables
        $dropTableIfExists('migration_versions');

        // 3. Rename old PascalCase tables containing data to new lowercase/snake_case tables
        $renameTableIfExists('SpaceType', 'space_type');
        $renameTableIfExists('LocalType', 'local_type');
        $renameTableIfExists('SpaceDocument', 'space_document');
        $renameTableIfExists('UserDocument', 'user_document');
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

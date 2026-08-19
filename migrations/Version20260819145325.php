<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819145325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              Application
            ADD
              company_status VARCHAR(64) DEFAULT NULL,
            ADD
              local_usage_description LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE Application RENAME INDEX idx_22c7521623575340 TO IDX_A45BDDC123575340');
        $this->addSql('ALTER TABLE Application RENAME INDEX idx_22c7521612469de2 TO IDX_A45BDDC112469DE2');
        $this->addSql('ALTER TABLE Application RENAME INDEX idx_22c752164f912ec8 TO IDX_A45BDDC14F912EC8');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              Category
            ADD
              is_active TINYINT(1) DEFAULT 1 NOT NULL,
            ADD
              requires_erp TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->convertSerializedRolesToJson('fos_group');
        $this->addSql('ALTER TABLE fos_group CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->convertSerializedRolesToJson('fos_user');
        $this->addSql('DROP INDEX UNIQ_957A6479C05FB297 ON fos_user');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fos_user
            ADD
              youtube_url VARCHAR(255) DEFAULT NULL,
            ADD
              tiktok_url VARCHAR(255) DEFAULT NULL,
            ADD
              monthly_budget_max INT DEFAULT NULL,
            ADD
              local_usage_description LONGTEXT DEFAULT NULL,
            ADD
              preferred_departments LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)',
            DROP
              last_login,
            DROP
              updated_at,
            DROP
              date_of_birth,
            DROP
              website,
            DROP
              biography,
            DROP
              gender,
            DROP
              locale,
            DROP
              timezone,
            DROP
              facebook_uid,
            DROP
              facebook_name,
            DROP
              facebook_data,
            DROP
              twitter_uid,
            DROP
              twitter_name,
            DROP
              twitter_data,
            DROP
              gplus_uid,
            DROP
              gplus_name,
            DROP
              gplus_data,
            DROP
              token,
            DROP
              two_step_code,
            CHANGE
              username username VARCHAR(180) DEFAULT NULL,
            CHANGE
              username_canonical username_canonical VARCHAR(180) DEFAULT NULL,
            CHANGE
              email email VARCHAR(180) DEFAULT NULL,
            CHANGE
              email_canonical email_canonical VARCHAR(180) DEFAULT NULL,
            CHANGE
              roles roles JSON NOT NULL COMMENT '(DC2Type:json)',
            CHANGE
              created_at created_at DATETIME DEFAULT NULL,
            CHANGE
              firstname firstname VARCHAR(255) DEFAULT NULL,
            CHANGE
              lastname lastname VARCHAR(255) DEFAULT NULL,
            CHANGE
              phone phone VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479F85E0677 ON fos_user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479E7927C74 ON fos_user (email)');
        $this->addSql('ALTER TABLE Parcel ADD max_surface INT NOT NULL, CHANGE surface min_surface INT NOT NULL');
        $this->addSql('ALTER TABLE Parcel RENAME INDEX idx_ce375856c54c8c93 TO IDX_C99B5D60C54C8C93');
        $this->addSql('ALTER TABLE Parcel RENAME INDEX idx_ce375856854679e2 TO IDX_C99B5D60854679E2');
        $this->addSql('ALTER TABLE Parcel RENAME INDEX idx_ce37585623575340 TO IDX_C99B5D6023575340');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              Space
            ADD
              is_erp TINYINT(1) DEFAULT 0 NOT NULL,
            ADD
              workflow_type VARCHAR(30) DEFAULT 'standard' NOT NULL,
            ADD
              price_text VARCHAR(255) DEFAULT NULL,
            ADD
              nb_spaces INT DEFAULT NULL,
            ADD
              min_space INT DEFAULT NULL,
            ADD
              max_space INT DEFAULT NULL,
            ADD
              societaire_message_type VARCHAR(50) DEFAULT NULL,
            CHANGE
              limitAvailability limitAvailability DATETIME DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE Space RENAME INDEX idx_e8b3ee3e7e3c61f9 TO IDX_2972C13A7E3C61F9');
        $this->addSql('ALTER TABLE Space RENAME INDEX idx_e8b3ee3ec54c8c93 TO IDX_2972C13AC54C8C93');
        $this->addSql('ALTER TABLE space_attribute RENAME INDEX idx_e3a88d9923575340 TO IDX_D3DBA1BE23575340');
        $this->addSql('ALTER TABLE space_attribute RENAME INDEX idx_e3a88d99b6e62efa TO IDX_D3DBA1BEB6E62EFA');
        $this->addSql('ALTER TABLE space_document RENAME INDEX idx_663d99e023575340 TO IDX_A0EE570F23575340');
        $this->addSql('ALTER TABLE use_type ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user_document RENAME INDEX idx_156bccaf4f912ec8 TO IDX_38E46E764F912EC8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Category DROP is_active, DROP requires_erp');
        $this->addSql('ALTER TABLE space_attribute RENAME INDEX idx_d3dba1beb6e62efa TO IDX_E3A88D99B6E62EFA');
        $this->addSql('ALTER TABLE space_attribute RENAME INDEX idx_d3dba1be23575340 TO IDX_E3A88D9923575340');
        $this->addSql('ALTER TABLE fos_group CHANGE roles roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE Application DROP company_status, DROP local_usage_description');
        $this->addSql('ALTER TABLE Application RENAME INDEX idx_a45bddc112469de2 TO IDX_22C7521612469DE2');
        $this->addSql('ALTER TABLE Application RENAME INDEX idx_a45bddc14f912ec8 TO IDX_22C752164F912EC8');
        $this->addSql('ALTER TABLE Application RENAME INDEX idx_a45bddc123575340 TO IDX_22C7521623575340');
        $this->addSql('ALTER TABLE Parcel ADD surface INT NOT NULL, DROP min_surface, DROP max_surface');
        $this->addSql('ALTER TABLE Parcel RENAME INDEX idx_c99b5d60c54c8c93 TO IDX_CE375856C54C8C93');
        $this->addSql('ALTER TABLE Parcel RENAME INDEX idx_c99b5d60854679e2 TO IDX_CE375856854679E2');
        $this->addSql('ALTER TABLE Parcel RENAME INDEX idx_c99b5d6023575340 TO IDX_CE37585623575340');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              Space
            DROP
              is_erp,
            DROP
              workflow_type,
            DROP
              price_text,
            DROP
              nb_spaces,
            DROP
              min_space,
            DROP
              max_space,
            DROP
              societaire_message_type,
            CHANGE
              limitAvailability limitAvailability DATE DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE Space RENAME INDEX idx_2972c13a7e3c61f9 TO IDX_E8B3EE3E7E3C61F9');
        $this->addSql('ALTER TABLE Space RENAME INDEX idx_2972c13ac54c8c93 TO IDX_E8B3EE3EC54C8C93');
        $this->addSql('ALTER TABLE use_type DROP is_active');
        $this->addSql('ALTER TABLE user_document RENAME INDEX idx_38e46e764f912ec8 TO IDX_156BCCAF4F912EC8');
        $this->addSql('ALTER TABLE space_document RENAME INDEX idx_a0ee570f23575340 TO IDX_663D99E023575340');
        $this->addSql('DROP INDEX UNIQ_957A6479F85E0677 ON fos_user');
        $this->addSql('DROP INDEX UNIQ_957A6479E7927C74 ON fos_user');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              fos_user
            ADD
              last_login DATETIME DEFAULT NULL,
            ADD
              updated_at DATETIME NOT NULL,
            ADD
              date_of_birth DATETIME DEFAULT NULL,
            ADD
              website VARCHAR(64) DEFAULT NULL,
            ADD
              biography VARCHAR(1000) DEFAULT NULL,
            ADD
              gender VARCHAR(1) DEFAULT NULL,
            ADD
              locale VARCHAR(8) DEFAULT NULL,
            ADD
              timezone VARCHAR(64) DEFAULT NULL,
            ADD
              facebook_uid VARCHAR(255) DEFAULT NULL,
            ADD
              facebook_name VARCHAR(255) DEFAULT NULL,
            ADD
              facebook_data JSON DEFAULT NULL COMMENT '(DC2Type:json)',
            ADD
              twitter_uid VARCHAR(255) DEFAULT NULL,
            ADD
              twitter_name VARCHAR(255) DEFAULT NULL,
            ADD
              twitter_data JSON DEFAULT NULL COMMENT '(DC2Type:json)',
            ADD
              gplus_uid VARCHAR(255) DEFAULT NULL,
            ADD
              gplus_name VARCHAR(255) DEFAULT NULL,
            ADD
              gplus_data JSON DEFAULT NULL COMMENT '(DC2Type:json)',
            ADD
              token VARCHAR(255) DEFAULT NULL,
            ADD
              two_step_code VARCHAR(255) DEFAULT NULL,
            DROP
              youtube_url,
            DROP
              tiktok_url,
            DROP
              monthly_budget_max,
            DROP
              local_usage_description,
            DROP
              preferred_departments,
            CHANGE
              username username VARCHAR(180) NOT NULL,
            CHANGE
              username_canonical username_canonical VARCHAR(180) NOT NULL,
            CHANGE
              email_canonical email_canonical VARCHAR(180) NOT NULL,
            CHANGE
              roles roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
            CHANGE
              created_at created_at DATETIME NOT NULL,
            CHANGE
              firstname firstname VARCHAR(64) DEFAULT NULL,
            CHANGE
              lastname lastname VARCHAR(64) DEFAULT NULL,
            CHANGE
              email email VARCHAR(180) NOT NULL,
            CHANGE
              phone phone VARCHAR(64) DEFAULT NULL
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479C05FB297 ON fos_user (confirmation_token)');
    }

    /**
     * The legacy FOSUserBundle `roles` columns store PHP-serialized arrays
     * (Doctrine DC2Type:array). Converting the column to a native JSON type
     * adds a json_valid() CHECK constraint, so existing rows must be
     * re-encoded as JSON first or the ALTER fails with error 4025.
     */
    private function convertSerializedRolesToJson(string $table): void
    {
        $rows = $this->connection->fetchAllAssociative(sprintf('SELECT id, roles FROM `%s`', $table));
        foreach ($rows as $row) {
            $roles = @unserialize((string) $row['roles'], ['allowed_classes' => false]);
            if (!is_array($roles)) {
                $decoded = json_decode((string) $row['roles'], true);
                $roles = is_array($decoded) ? $decoded : [];
            }
            $this->connection->executeStatement(
                sprintf('UPDATE `%s` SET roles = ? WHERE id = ?', $table),
                [json_encode(array_values($roles)), $row['id']]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209144354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(50) NOT NULL, CHANGE familyname familyname VARCHAR(50) NOT NULL, CHANGE cin cin VARCHAR(8) NOT NULL, CHANGE numtel numtel VARCHAR(8) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649ABE530DA ON user (cin)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D649ABE530DA ON user');
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(255) NOT NULL, CHANGE familyname familyname VARCHAR(255) NOT NULL, CHANGE cin cin VARCHAR(255) NOT NULL, CHANGE numtel numtel VARCHAR(255) NOT NULL');
    }
}

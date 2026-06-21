<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250903093452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidat ADD lieu_naissance VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidat ADD groupe_sanguin VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidat_examen ALTER reste_apayer SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE candidat_examen ALTER reste_apayer DROP NOT NULL');
        $this->addSql('ALTER TABLE candidat DROP lieu_naissance');
        $this->addSql('ALTER TABLE candidat DROP groupe_sanguin');
    }
}

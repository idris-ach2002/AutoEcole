<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902084317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidat ADD reste_apayer NUMERIC(10, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE candidat ADD statut_paiement VARCHAR(20) DEFAULT \'en cours\' NOT NULL');
        $this->addSql('ALTER TABLE candidat DROP telephone');
        $this->addSql('ALTER TABLE candidat DROP email');
        $this->addSql('ALTER TABLE candidat DROP statut_permis');
        $this->addSql('ALTER TABLE candidat ALTER date_naissance SET NOT NULL');
        $this->addSql('ALTER TABLE candidat ALTER prix_permis DROP DEFAULT');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT FK_EF8772905C8659A');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT FK_EF8772908D0EB82');
        $this->addSql('ALTER TABLE candidat_examen ADD statut VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE candidat_examen DROP resultat');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT FK_EF8772905C8659A FOREIGN KEY (examen_id) REFERENCES examen (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT FK_EF8772908D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT fk_ef8772908d0eb82');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT fk_ef8772905c8659a');
        $this->addSql('ALTER TABLE candidat_examen ADD resultat VARCHAR(10) DEFAULT \'en attente\' NOT NULL');
        $this->addSql('ALTER TABLE candidat_examen DROP statut');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT fk_ef8772908d0eb82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT fk_ef8772905c8659a FOREIGN KEY (examen_id) REFERENCES examen (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidat ADD telephone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidat ADD email VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidat ADD statut_permis BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE candidat DROP reste_apayer');
        $this->addSql('ALTER TABLE candidat DROP statut_paiement');
        $this->addSql('ALTER TABLE candidat ALTER date_naissance DROP NOT NULL');
        $this->addSql('ALTER TABLE candidat ALTER prix_permis SET DEFAULT \'0\'');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema initial propre de l’application AutoEcole dockerisée.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE candidat (id SERIAL NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, date_naissance DATE NOT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(150) DEFAULT NULL, lieu_naissance VARCHAR(150) DEFAULT NULL, groupe_sanguin VARCHAR(5) DEFAULT NULL, prix_permis NUMERIC(10, 2) NOT NULL, reste_apayer NUMERIC(10, 2) DEFAULT 0 NOT NULL, statut_paiement VARCHAR(20) DEFAULT \'en cours\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_candidat_identity ON candidat (nom, prenom)');
        $this->addSql('CREATE INDEX idx_candidat_payment_status ON candidat (statut_paiement)');
        $this->addSql('CREATE TABLE examen (id SERIAL NOT NULL, type_examen VARCHAR(20) NOT NULL, date_passage DATE NOT NULL, frais NUMERIC(10, 2) NOT NULL, statut_examen BOOLEAN DEFAULT false NOT NULL, lieu VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_examen_type_date ON examen (type_examen, date_passage)');
        $this->addSql('CREATE TABLE candidat_examen (id SERIAL NOT NULL, candidat_id INT NOT NULL, examen_id INT NOT NULL, statut VARCHAR(20) NOT NULL, reste_apayer NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_candidat_examen ON candidat_examen (candidat_id, examen_id)');
        $this->addSql('CREATE INDEX idx_candidat_examen_statut ON candidat_examen (statut)');
        $this->addSql('CREATE INDEX IDX_EF8772908D0EB82 ON candidat_examen (candidat_id)');
        $this->addSql('CREATE INDEX IDX_EF8772905C8659A ON candidat_examen (examen_id)');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT FK_EF8772908D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT FK_EF8772905C8659A FOREIGN KEY (examen_id) REFERENCES examen (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT FK_EF8772908D0EB82');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT FK_EF8772905C8659A');
        $this->addSql('DROP TABLE candidat_examen');
        $this->addSql('DROP TABLE examen');
        $this->addSql('DROP TABLE candidat');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831194128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE message_ais_id_seq CASCADE');
        $this->addSql('CREATE TABLE candidat (id SERIAL NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, date_naissance DATE DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, prix_permis NUMERIC(10, 2) DEFAULT \'0\' NOT NULL, statut_permis BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE candidat_examen (id SERIAL NOT NULL, candidat_id INT NOT NULL, examen_id INT NOT NULL, resultat VARCHAR(10) DEFAULT \'en attente\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EF8772908D0EB82 ON candidat_examen (candidat_id)');
        $this->addSql('CREATE INDEX IDX_EF8772905C8659A ON candidat_examen (examen_id)');
        $this->addSql('CREATE TABLE examen (id SERIAL NOT NULL, type_examen VARCHAR(20) NOT NULL, date_passage DATE NOT NULL, frais NUMERIC(10, 2) NOT NULL, statut_examen BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT FK_EF8772908D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidat_examen ADD CONSTRAINT FK_EF8772905C8659A FOREIGN KEY (examen_id) REFERENCES examen (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE navire DROP CONSTRAINT fk_eed10382efc8468');
        $this->addSql('DROP TABLE quai');
        $this->addSql('DROP TABLE message_ais');
        $this->addSql('DROP TABLE navire');
        $this->addSql('DROP TABLE arborescence_navires');
        $this->addSql('DROP TABLE terminal');
        $this->addSql('DROP TABLE port');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE message_ais_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE quai (berth_id INT NOT NULL, berth_name VARCHAR(256) NOT NULL, berth_number VARCHAR(25) NOT NULL, berth_operator VARCHAR(256) DEFAULT NULL, berth_status VARCHAR(25) NOT NULL, berth_type VARCHAR(25) DEFAULT NULL, berth_latitude DOUBLE PRECISION NOT NULL, berth_longitude DOUBLE PRECISION NOT NULL, berth_serial_num VARCHAR(25) NOT NULL, berth_facility_type VARCHAR(256) DEFAULT NULL, port_serial_num VARCHAR(25) NOT NULL, terminal_serial_num VARCHAR(25) NOT NULL, port_id INT DEFAULT NULL, terminal_id INT DEFAULT NULL, PRIMARY KEY(berth_id))');
        $this->addSql('CREATE TABLE message_ais (id INT NOT NULL, mmsi VARCHAR(256) NOT NULL, tstamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, cog DOUBLE PRECISION NOT NULL, sog DOUBLE PRECISION NOT NULL, heading DOUBLE PRECISION NOT NULL, navstat INT NOT NULL, imo VARCHAR(256) DEFAULT NULL, name VARCHAR(256) DEFAULT NULL, callsign VARCHAR(256) DEFAULT NULL, type INT NOT NULL, a DOUBLE PRECISION DEFAULT NULL, b DOUBLE PRECISION DEFAULT NULL, c DOUBLE PRECISION DEFAULT NULL, d DOUBLE PRECISION DEFAULT NULL, draught DOUBLE PRECISION NOT NULL, dest VARCHAR(256) DEFAULT NULL, eta VARCHAR(256) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE navire (imo INT NOT NULL, ship_type VARCHAR(100) DEFAULT NULL, mmsi VARCHAR(10) DEFAULT NULL, name_of_ship VARCHAR(50) DEFAULT NULL, aux_engine_builder VARCHAR(50) DEFAULT NULL, aux_engine_design VARCHAR(128) DEFAULT NULL, aux_engine_model VARCHAR(128) DEFAULT NULL, aux_engine_stroke_type INT DEFAULT 0 NOT NULL, aux_engine_total_kw INT DEFAULT 0 NOT NULL, breadth DOUBLE PRECISION DEFAULT \'0\' NOT NULL, breadth_extreme DOUBLE PRECISION DEFAULT \'0\' NOT NULL, breadth_moulded DOUBLE PRECISION DEFAULT \'0\' NOT NULL, built VARCHAR(10) DEFAULT NULL, cabins TEXT DEFAULT NULL, call_sign VARCHAR(128) DEFAULT NULL, class VARCHAR(10) DEFAULT NULL, country_of_build VARCHAR(50) DEFAULT NULL, dead_weight VARCHAR(25) DEFAULT NULL, delivery_date VARCHAR(10) DEFAULT NULL, depth DOUBLE PRECISION DEFAULT \'0\' NOT NULL, displacement VARCHAR(25) DEFAULT NULL, doc_company VARCHAR(50) DEFAULT \'0.0\', doc_company_code INT DEFAULT 0 NOT NULL, docking_survey INT DEFAULT 0 NOT NULL, draugth DOUBLE PRECISION DEFAULT \'0\' NOT NULL, engine_bore VARCHAR(25) DEFAULT NULL, engine_builder VARCHAR(100) DEFAULT NULL, engine_cylinders INT DEFAULT 0 NOT NULL, engine_design VARCHAR(50) DEFAULT NULL, engine_model VARCHAR(128) DEFAULT NULL, engine_stroke VARCHAR(25) DEFAULT NULL, engine_stroke_type VARCHAR(5) DEFAULT NULL, engine_type VARCHAR(20) DEFAULT NULL, engine_number VARCHAR(20) DEFAULT NULL, engines_rpm VARCHAR(25) DEFAULT NULL, flag VARCHAR(128) DEFAULT NULL, fluel_capacity1 VARCHAR(25) DEFAULT NULL, fluel_capacity2 VARCHAR(25) DEFAULT NULL, fuel_consumption_main_engines DOUBLE PRECISION DEFAULT NULL, fuel_consumption_total DOUBLE PRECISION DEFAULT NULL, fuel_type1 VARCHAR(128) DEFAULT NULL, fuel_type2 VARCHAR(128) DEFAULT NULL, gas_capacity VARCHAR(128) DEFAULT NULL, group_owner VARCHAR(50) DEFAULT NULL, group_owner_code VARCHAR(128) DEFAULT NULL, gt VARCHAR(128) DEFAULT NULL, keel_laid VARCHAR(128) DEFAULT NULL, keel_to_mast_height DOUBLE PRECISION DEFAULT NULL, last_update VARCHAR(20) DEFAULT NULL, launch_date VARCHAR(20) DEFAULT NULL, length VARCHAR(20) DEFAULT NULL, operator VARCHAR(50) DEFAULT NULL, operator_code INT DEFAULT 0 NOT NULL, order_date VARCHAR(30) DEFAULT NULL, propulsion_type VARCHAR(50) DEFAULT NULL, reefer_points VARCHAR(25) DEFAULT NULL, registered_owner VARCHAR(50) DEFAULT NULL, registered_owner_code VARCHAR(50) DEFAULT NULL, roro_lanes_number VARCHAR(128) DEFAULT NULL, sale_date VARCHAR(128) DEFAULT NULL, sale_price_us VARCHAR(128) DEFAULT NULL, segregated_ballast_capacity VARCHAR(25) DEFAULT NULL, service_speed VARCHAR(128) DEFAULT NULL, ship_type_group VARCHAR(50) DEFAULT NULL, shipbuilder VARCHAR(50) DEFAULT NULL, shipbuilder_code VARCHAR(50) DEFAULT NULL, shipmanager VARCHAR(50) DEFAULT NULL, shipmanager_code INT DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, technical_manager VARCHAR(50) DEFAULT NULL, technical_manager_code INT DEFAULT NULL, teu VARCHAR(25) DEFAULT NULL, year INT DEFAULT NULL, type_of_navire VARCHAR(50) DEFAULT NULL, PRIMARY KEY(imo))');
        $this->addSql('CREATE INDEX idx_eed10382efc8468 ON navire (ship_type)');
        $this->addSql('CREATE TABLE arborescence_navires (niveau4 VARCHAR(100) NOT NULL, niveau1 VARCHAR(50) NOT NULL, niveau2 VARCHAR(50) NOT NULL, niveau3 VARCHAR(50) NOT NULL, PRIMARY KEY(niveau4))');
        $this->addSql('CREATE TABLE terminal (terminal_id INT NOT NULL, terminal_name VARCHAR(256) NOT NULL, terminal_operator VARCHAR(256) DEFAULT NULL, terminal_status VARCHAR(25) NOT NULL, terminal_serial_num VARCHAR(25) NOT NULL, terminal_latitude DOUBLE PRECISION NOT NULL, terminal_longitude DOUBLE PRECISION NOT NULL, terminal_facility_type VARCHAR(35) DEFAULT NULL, port_serial_num VARCHAR(25) NOT NULL, port_name VARCHAR(60) DEFAULT NULL, port_id INT DEFAULT NULL, PRIMARY KEY(terminal_id))');
        $this->addSql('CREATE TABLE port (port_id INT NOT NULL, port_name VARCHAR(256) NOT NULL, port_serial_num VARCHAR(25) NOT NULL, port_alternative_name VARCHAR(256) DEFAULT NULL, port_master_id VARCHAR(25) DEFAULT NULL, port_country VARCHAR(64) DEFAULT NULL, port_latitude DOUBLE PRECISION NOT NULL, port_longitude DOUBLE PRECISION NOT NULL, port_status VARCHAR(25) NOT NULL, port_world_num VARCHAR(25) DEFAULT NULL, PRIMARY KEY(port_id))');
        $this->addSql('ALTER TABLE navire ADD CONSTRAINT fk_eed10382efc8468 FOREIGN KEY (ship_type) REFERENCES arborescence_navires (niveau4) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT FK_EF8772908D0EB82');
        $this->addSql('ALTER TABLE candidat_examen DROP CONSTRAINT FK_EF8772905C8659A');
        $this->addSql('DROP TABLE candidat');
        $this->addSql('DROP TABLE candidat_examen');
        $this->addSql('DROP TABLE examen');
    }
}

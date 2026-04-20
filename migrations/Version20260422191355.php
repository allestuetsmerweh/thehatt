<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422191355 extends AbstractMigration {
    public function getDescription(): string {
        return 'Bike counters';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE bike_count_data_sources (id INT AUTO_INCREMENT NOT NULL, ident VARCHAR(31) NOT NULL, INDEX ident_index (ident), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bike_count_stations (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(127) NOT NULL, details LONGTEXT NOT NULL, operated_since DATETIME DEFAULT NULL, operated_until DATETIME DEFAULT NULL, measurement_duration_seconds INT NOT NULL, position_latitude DOUBLE PRECISION DEFAULT NULL, position_longitude DOUBLE PRECISION DEFAULT NULL, ident VARCHAR(31) NOT NULL, data_source_id INT NOT NULL, INDEX IDX_72765EB31A935C57 (data_source_id), INDEX data_source_id_ident_index (data_source_id, ident), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bike_count_statistics (year SMALLINT NOT NULL COMMENT \'-1: all years, 1970-: years\', month SMALLINT NOT NULL COMMENT \'-1: all months, 1-12: months\', day SMALLINT NOT NULL COMMENT \'-1: all days, 1-31: days\', hour SMALLINT NOT NULL COMMENT \'-1: all hours, 0-23: hours\', kind SMALLINT NOT NULL COMMENT \'-1: all kinds, 1: monday - 7: sunday\', count FLOAT NOT NULL, station_id INT NOT NULL, INDEX IDX_E3B123D21BDB235 (station_id), PRIMARY KEY (station_id, year, month, day, hour, kind)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bike_count_stations ADD CONSTRAINT FK_72765EB31A935C57 FOREIGN KEY (data_source_id) REFERENCES bike_count_data_sources (id)');
        $this->addSql('ALTER TABLE bike_count_statistics ADD CONSTRAINT FK_E3B123D21BDB235 FOREIGN KEY (station_id) REFERENCES bike_count_stations (id)');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE bike_count_stations DROP FOREIGN KEY FK_72765EB31A935C57');
        $this->addSql('ALTER TABLE bike_count_statistics DROP FOREIGN KEY FK_E3B123D21BDB235');
        $this->addSql('DROP TABLE bike_count_data_sources');
        $this->addSql('DROP TABLE bike_count_stations');
        $this->addSql('DROP TABLE bike_count_statistics');
    }
}

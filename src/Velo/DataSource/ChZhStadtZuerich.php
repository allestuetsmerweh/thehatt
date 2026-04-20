<?php

namespace App\Velo\DataSource;

use App\Entity\Velo\BikeCountStation;
use App\Entity\Velo\BikeCountStatistic;
use App\Velo\DataSource\Common\DataSource;

/**
 * @phpstan-import-type StationDataByIdent from DataSource
 */
class ChZhStadtZuerich extends DataSource {
    public static function getIdent(): string {
        return 'CH-ZH-STADT-ZUERICH';
    }

    public function getMeasurementDurationSeconds(): int {
        return 15 * 60; // 15 minutes
    }

    public function synchronize(): void {
        $station_data_by_ident = $this->getStations();
        $station_by_ident = $this->synchronizeStations($station_data_by_ident);
        $this_year = intval(date('Y'));
        for ($year = $this_year; $year >= 2009; $year--) {
            $this->downloadDataForYear($year);
            $this->synchronizeDataForYear($year, $station_by_ident);
        }
    }

    protected function getStationsGeoJsonPath(): string {
        $month = date('Y-m'); // Cache monthly
        return $this->getDataPath("stations-as-of-{$month}.csv");
    }

    protected function downloadStationsGeoJson(): void {
        $url = 'https://www.ogd.stadt-zuerich.ch'
            .'/wfs/geoportal/Standorte_der_automatischen_Fuss__und_Velozaehlungen'
            .'?service=WFS'
            .'&version=1.1.0'
            .'&request=GetFeature'
            .'&outputFormat=GeoJSON'
            .'&typename=view_eco_standorte';
        $path = $this->getStationsGeoJsonPath();
        $this->downloadCached($url, $path);
    }

    protected function getDataPathForYear(int $year): string {
        $is_current_year = intval(date('Y')) === $year;
        $day = date('Y-m-d'); // Cache daily
        $cache_key = $is_current_year ? "-as-of-{$day}" : '';
        return $this->getDataPath("data-{$year}{$cache_key}.csv");
    }

    protected function downloadDataForYear(int $year): void {
        $url = 'https://data.stadt-zuerich.ch/'
            .'dataset/ted_taz_verkehrszaehlungen_werte_fussgaenger_velo'
            ."/download/{$year}_verkehrszaehlungen_werte_fussgaenger_velo.csv";
        $path = $this->getDataPathForYear($year);
        $this->downloadCached($url, $path);
    }

    /** @return StationDataByIdent */
    protected function getStations(): array {
        $this->downloadStationsGeoJson();
        $path = $this->getStationsGeoJsonPath();
        $geojson = json_decode(file_get_contents($path) ?: '', true);
        if (!is_array($geojson)) {
            throw new \Exception("Invalid GeoJson: {$path}");
        }
        $stations = [];
        foreach ($geojson['features'] as $station_feature) {
            $coordinates = $station_feature['geometry']['coordinates'];
            $name = $station_feature['properties']['bezeichnung'];
            $id = $station_feature['properties']['id1'];
            $direction_in = $station_feature['properties']['richtung_in'];
            $direction_out = $station_feature['properties']['richtung_out'];
            $operated_since = $station_feature['properties']['von'] ?? null;
            $operated_until = $station_feature['properties']['bis'] ?? null;
            if ($direction_in) {
                $stations["{$id}_in"] = [
                    'name' => "{$name} -> {$direction_in}",
                    'details' => "",
                    'operatedSince' => $operated_since ? new \DateTimeImmutable($operated_since) : null,
                    'operatedUntil' => $operated_until ? new \DateTimeImmutable($operated_until) : null,
                    'measurementDurationSeconds' => $this->getMeasurementDurationSeconds(),
                    'positionLatitude' => $coordinates[1],
                    'positionLongitude' => $coordinates[0],
                ];
            }
            if ($direction_out) {
                $stations["{$id}_out"] = [
                    'name' => "{$name} -> {$direction_out}",
                    'details' => "",
                    'operatedSince' => $operated_since ? new \DateTimeImmutable($operated_since) : null,
                    'operatedUntil' => $operated_until ? new \DateTimeImmutable($operated_until) : null,
                    'measurementDurationSeconds' => $this->getMeasurementDurationSeconds(),
                    'positionLatitude' => $coordinates[1],
                    'positionLongitude' => $coordinates[0],
                ];
            }
        }
        return $stations;
    }

    /**
     * @param array<string, BikeCountStation> $station_by_ident
     */
    protected function synchronizeDataForYear(int $year, array $station_by_ident): void {
        $path = $this->getDataPathForYear($year);
        $fp = fopen($path, 'r');
        if (!$fp) {
            throw new \Exception("Could not open {$path}");
        }
        $header = fgets($fp, 1024);
        if ($header !== "FK_STANDORT,DATUM,VELO_IN,VELO_OUT,FUSS_IN,FUSS_OUT,OST,NORD\r\n") {
            $header_len = strlen("{$header}");
            throw new \Exception("Invalid CSV header ({$header_len}): {$header}");
        }
        $num_days = (strtotime("{$year}-12-31") - strtotime("{$year}-01-01")) / 86400 + 1;
        $easter_date = $this->getEasterDate($year);
        $easter_friday = $this->getDateTimeOffset($easter_date, '-2 days');
        $easter_monday = $this->getDateTimeOffset($easter_date, '+1 days');
        $ascension_thursday = $this->getDateTimeOffset($easter_date, '+39 days');
        $pentecost_monday = $this->getDateTimeOffset($easter_date, '+50 days');
        $this->io?->info("Easter: {$easter_date->format('Y-m-d')}");
        $this->io?->info("Ascension: {$ascension_thursday->format('Y-m-d')}");
        $this->io?->info("Pentecost: {$pentecost_monday->format('Y-m-d')}");
        $festive_days = [
            "{$year}-01-01" => true,
            "{$year}-01-02" => true,
            $easter_friday->format('Y-m-d') => true,
            $easter_monday->format('Y-m-d') => true,
            $ascension_thursday->format('Y-m-d') => true,
            $pentecost_monday->format('Y-m-d') => true,
            "{$year}-05-01" => true,
            "{$year}-08-01" => true,
            "{$year}-12-25" => true,
            "{$year}-12-26" => true,
        ];

        $interval = new \DateInterval("PT{$this->getMeasurementDurationSeconds()}S");
        $line_num = 1;
        $year_start = new \DateTimeImmutable("{$year}-01-01T00:00:00");
        $current_started_at = $year_start;
        $count_by_stat = [];
        $is_counted_by_day_by_stat = [];
        $station_by_id = [];
        while (($csv_row = fgetcsv($fp, 1024, ',', '"', '\\')) !== false) {
            $line_num++;

            $started_at = new \DateTimeImmutable("{$csv_row[1]}");
            if ($started_at > $current_started_at) {
                $expected_started_at = $current_started_at->add($interval);
                if ($started_at != $expected_started_at) {
                    $this->io?->note("{$year}.csv:{$line_num} Strange timestamp {$started_at->format('Y-m-d H:i')} after {$current_started_at->format('Y-m-d H:i')} (expected {$expected_started_at->format('Y-m-d H:i')})");
                }
                $current_started_at = $started_at;
            }

            $csv_station_id = intval($csv_row[0]);
            $station_in = $station_by_ident["{$csv_station_id}_in"] ?? null;
            $station_out = $station_by_ident["{$csv_station_id}_out"] ?? null;
            if ($station_in?->getId()) {
                $station_by_id[$station_in->getId()] = $station_in;
            }
            if ($station_out?->getId()) {
                $station_by_id[$station_out->getId()] = $station_out;
            }

            $velo_in = intval($csv_row[2]);
            $velo_out = intval($csv_row[3]);

            $this->updateStatisticsAt(
                $count_by_stat,
                $is_counted_by_day_by_stat,
                $station_in,
                $started_at,
                $festive_days,
                $velo_in,
            );
            $this->updateStatisticsAt(
                $count_by_stat,
                $is_counted_by_day_by_stat,
                $station_out,
                $started_at,
                $festive_days,
                $velo_out,
            );
        }

        $num_days_by_stat = [];
        foreach ($is_counted_by_day_by_stat as $stat => $is_counted_by_day) {
            $num_days_by_stat[$stat] = count($is_counted_by_day);
        }
        $enc_num_days_by_stat = json_encode($num_days_by_stat, JSON_PRETTY_PRINT);
        file_put_contents($this->getDataPath("num_days_by_stat_{$year}.csv"), "{$enc_num_days_by_stat}");
        $daily_count_by_stat = [];
        foreach ($count_by_stat as $stat => $count) {
            $num_days = $num_days_by_stat[$stat] ?? 0;
            if ($num_days === 0) {
                $this->io?->note("Zero days for stat {$stat}");
            } else {
                $daily_count_by_stat[$stat] = $count / $num_days;
            }
        }
        $enc_daily_count_by_stat = json_encode($daily_count_by_stat, JSON_PRETTY_PRINT);
        file_put_contents($this->getDataPath("daily_count_by_stat_{$year}.csv"), "{$enc_daily_count_by_stat}");
        unset($is_counted_by_day_by_stat, $count_by_stat, $num_days_by_stat);

        gc_collect_cycles();
        // $pretty_stats = json_encode($daily_count_by_stat, JSON_PRETTY_PRINT);
        // file_put_contents($stats_path, $pretty_stats);
        $this->saveStatistics($daily_count_by_stat, $station_by_id);
    }

    /**
     * @param array<string, int>                 $count_by_stat
     * @param array<string, array<string, bool>> $is_counted_by_day_by_stat
     * @param array<string, bool>                $festive_days
     */
    protected function updateStatisticsAt(
        array &$count_by_stat,
        array &$is_counted_by_day_by_stat,
        ?BikeCountStation $station,
        \DateTimeInterface $started_at,
        array $festive_days,
        int $count,
    ): void {
        if ($station === null) {
            return;
        }
        $station_id = $station->getId();
        if ($station_id === null) {
            return;
        }
        $iso_date = $started_at->format('Y-m-d');
        $year = intval($started_at->format('Y'));
        $month = intval($started_at->format('n'));
        $day = intval($started_at->format('j'));
        $hour = intval($started_at->format('G'));
        $weekday = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][intval($started_at->format('w'))];
        $is_festive = $festive_days[$iso_date] ?? false;
        $weekday = intval($started_at->format('w'));
        $kind = ($weekday === 0 || $is_festive) ? 7 : $weekday;

        $stats = [
            $this->serializeStat($station_id, $year, null, null, null, null),
            $this->serializeStat($station_id, $year, null, null, null, $kind),
            $this->serializeStat($station_id, $year, $month, null, null, null),
            $this->serializeStat($station_id, $year, $month, null, null, $kind),
            $this->serializeStat($station_id, $year, $month, $day, null, null),
            $this->serializeStat($station_id, $year, null, null, $hour, null),
            $this->serializeStat($station_id, $year, null, null, $hour, $kind),
            $this->serializeStat($station_id, $year, $month, null, $hour, null),
        ];

        foreach ($stats as $stat) {
            $count_by_stat[$stat] ??= 0;
            $count_by_stat[$stat] += $count;
            $is_counted_by_day_by_stat[$stat][$iso_date] ??= true;
        }
    }

    /**
     * @param array<string, int>           $daily_count_by_stat
     * @param array<int, BikeCountStation> $station_by_id
     */
    protected function saveStatistics(array $daily_count_by_stat, array $station_by_id): void {
        $replaces = [];
        foreach ($daily_count_by_stat as $stat => $count) {
            [$station_id, $year, $month, $day, $hour, $kind] = $this->deserializeStat($stat);
            $entity = new BikeCountStatistic();
            $entity->setStation($station_by_id[$station_id]);
            $entity->setYear($year);
            $entity->setMonth($month);
            $entity->setDay($day);
            $entity->setHour($hour);
            $entity->setKind($kind);
            $entity->setCount($count);
            $replaces[] = $entity->getRow();
        }
        $num_replaces = count($replaces);
        if ($num_replaces > 0) {
            $conn = $this->em->getConnection();
            $conn->beginTransaction();
            $values = substr(str_repeat(',(?,?,?,?,?,?,?)', $num_replaces), 1);
            $stmt = $conn->prepare('REPLACE INTO bike_count_statistics (station_id, year, month, day, hour, kind, count) VALUES '.$values);
            for ($i = 0; $i < $num_replaces; $i++) {
                for ($j = 0; $j < 7; $j++) {
                    $stmt->bindValue($i * 7 + $j + 1, $replaces[$i][$j]);
                }
            }
            $stmt->executeStatement();
            $conn->commit();
        }
        gc_collect_cycles();
    }

    protected function serializeStat(
        int $station_id,
        ?int $year,
        ?int $month,
        ?int $day,
        ?int $hour,
        ?int $kind,
    ): string {
        $year ??= '*';
        $month ??= '*';
        $day ??= '*';
        $hour ??= '*';
        $kind ??= '*';
        return "{$station_id}-{$year}-{$month}-{$day}-{$hour}-{$kind}";
    }

    /** @return array{0: int, 1: ?int, 2: ?int, 3: ?int, 4: ?int, 5: ?int} */
    protected function deserializeStat(string $stat): array {
        $arr = explode('-', $stat);
        if (count($arr) !== 6) {
            throw new \Exception("Invalid stat: {$stat}");
        }
        $station_id = intval($arr[0]);
        $year = $arr[1] === '*' ? null : intval($arr[1]);
        $month = $arr[2] === '*' ? null : intval($arr[2]);
        $day = $arr[3] === '*' ? null : intval($arr[3]);
        $hour = $arr[4] === '*' ? null : intval($arr[4]);
        $kind = $arr[5] === '*' ? null : intval($arr[5]);
        return [$station_id, $year, $month, $day, $hour, $kind];
    }

    protected function getEasterDate(int $year): \DateTimeImmutable {
        // Getting the date of easter is a giant pain.
        $day = (19 * ($year % 19) + 24) % 30;
        $day = 22 + $day + ((2 * ($year % 4) + 4 * ($year % 7) + 6 * $day + 5) % 7);
        $month = 3;
        if ($day > 31) {
            $month = 4;
            $day -= 31;
        }
        return new \DateTimeImmutable("{$year}-{$month}-{$day}");
    }

    protected function getDateTimeOffset(\DateTimeImmutable $date, string $offset): \DateTimeImmutable {
        $interval = \DateInterval::createFromDateString($offset);
        if (!$interval) {
            throw new \Exception("Invalid interval: {$offset}");
        }
        return $date->add($interval);
    }
}

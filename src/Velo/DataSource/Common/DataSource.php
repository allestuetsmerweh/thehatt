<?php

namespace App\Velo\DataSource\Common;

use App\Entity\Velo\BikeCountDataSource;
use App\Entity\Velo\BikeCountStation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @phpstan-type StationData array{
 *   name: string,
 *   details: string,
 *   operatedSince: ?\DateTimeImmutable,
 *   operatedUntil: ?\DateTimeImmutable,
 *   measurementDurationSeconds: int,
 *   positionLatitude: float,
 *   positionLongitude: float,
 * }
 * @phpstan-type StationDataByIdent array<string, StationData>
 */
abstract class DataSource {
    public ?SymfonyStyle $io;
    protected EntityManagerInterface $em;

    abstract public static function getIdent(): string;

    abstract public function synchronize(): void;

    #[Required]
    public function setEntityManager(EntityManagerInterface $em): void {
        $this->em = $em;
    }

    protected function getDataPath(string $relative_path): string {
        $ident = $this::getIdent();
        $velo_base = realpath(__DIR__."/../../");
        $path = "{$velo_base}/data/sources/{$ident}/{$relative_path}";
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0o777, true);
        }
        return $path;
    }

    protected function downloadCached(string $url, string $path): void {
        if (is_file($path)) {
            $this->io?->note("Using cached version of {$path}.");
        } else {
            $this->io?->note("Downloading {$path}...");
            $uh = fopen($url, 'r');
            $fp = fopen($path, 'w+');
            if (!$uh) {
                throw new \Exception("Could not read stations from {$url}");
            }
            if (!$fp) {
                throw new \Exception("Could not open stations cache at {$path}");
            }
            stream_copy_to_stream($uh, $fp);
        }
    }

    /**
     * @param StationDataByIdent $station_data_by_ident
     *
     * @return array<string, BikeCountStation>
     *
     * @throws \Exception
     */
    protected function synchronizeStations(array $station_data_by_ident): array {
        $data_source_repo = $this->em->getRepository(BikeCountDataSource::class);
        $this_data_source = $data_source_repo->findOneBy(['ident' => $this::getIdent()]);
        if (!$this_data_source) {
            $this_data_source = new BikeCountDataSource();
            $this_data_source->setIdent($this::getIdent());
            $this->em->persist($this_data_source);
            $this->em->flush();
        }

        $exists_by_ident = [];
        $station_by_ident = [];
        $station_repo = $this->em->getRepository(BikeCountStation::class);
        $stations = $station_repo->findBy(['data_source' => $this::getIdent()]);
        foreach ($stations as $station) {
            $ident = $station->getIdent();
            $station_data = $station_data_by_ident[$ident] ?? null;
            if ($station_data && $this->isStationChanged($station, $station_data)) {
                // Must be updated
                $this->updateStationWithData($station, $station_data);
                $this->em->flush();
            }
            if (!$station_data) {
                // Must be deleted
                $this->em->remove($station);
                $this->em->flush();
            }
            $station_by_ident[$ident] = $station;
            $exists_by_ident[$ident] = true;
        }
        foreach ($station_data_by_ident as $ident => $station_data) {
            $does_station_exist = $exists_by_ident[$ident] ?? false;
            if (!$does_station_exist) {
                // Must be created
                $station = new BikeCountStation();
                $station->setDataSource($this_data_source);
                $station->setIdent($ident);
                $this->updateStationWithData($station, $station_data);
                $this->em->persist($station);
                $this->em->flush();
                $station_by_ident[$ident] = $station;
            }
        }
        return $station_by_ident;
    }

    /**
     * @param StationData $station_data
     */
    protected function isStationChanged(BikeCountStation $station, array $station_data): bool {
        return
            $station->getName() !== $station_data['name']
            || $station->getDetails() !== $station_data['details']
            || $station->getOperatedSince() !== $station_data['operatedSince']
            || $station->getOperatedUntil() !== $station_data['operatedUntil']
            || $station->getMeasurementDurationSeconds() !== $station_data['measurementDurationSeconds']
            || $station->getPositionLatitude() !== $station_data['positionLatitude']
            || $station->getPositionLongitude() !== $station_data['positionLongitude'];
    }

    /**
     * @param StationData $station_data
     */
    protected function updateStationWithData(BikeCountStation $station, array $station_data): void {
        $station->setName($station_data['name']);
        $station->setDetails($station_data['details']);
        $station->setOperatedSince($station_data['operatedSince']);
        $station->setOperatedUntil($station_data['operatedUntil']);
        $station->setMeasurementDurationSeconds($station_data['measurementDurationSeconds']);
        $station->setPositionLatitude($station_data['positionLatitude']);
        $station->setPositionLongitude($station_data['positionLongitude']);
    }

    // protected function toIdent(string $text): string {
    //     return iconv('utf-8', 'ascii//TRANSLIT', str_replace([' '], ['_'], strtoupper($text)));
    // }
}

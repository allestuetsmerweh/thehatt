<?php

namespace App\Entity\Velo;

use App\Repository\Velo\BikeCountStationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'bike_count_stations')]
#[ORM\Index(name: 'data_source_id_ident_index', columns: ['data_source_id', 'ident'])]
#[ORM\Entity(repositoryClass: BikeCountStationRepository::class)]
class BikeCountStation {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BikeCountDataSource::class)]
    #[ORM\JoinColumn(name: 'data_source_id', referencedColumnName: 'id', nullable: false)]
    private BikeCountDataSource $data_source;

    #[ORM\Column(type: Types::STRING, length: 127, nullable: false)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private string $details;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $operated_since;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $operated_until;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $measurement_duration_seconds;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $position_latitude;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $position_longitude;

    #[ORM\Column(type: Types::ASCII_STRING, length: 31, nullable: false)]
    private string $ident;

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(int $new_value): void {
        $this->id = $new_value;
    }

    public function getDataSource(): BikeCountDataSource {
        return $this->data_source;
    }

    public function setDataSource(BikeCountDataSource $new_value): void {
        $this->data_source = $new_value;
    }

    public function getIdent(): string {
        return $this->ident;
    }

    public function setIdent(string $new_value): void {
        $this->ident = $new_value;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $new_value): void {
        $this->name = $new_value;
    }

    public function getDetails(): string {
        return $this->details;
    }

    public function setDetails(string $new_value): void {
        $this->details = $new_value;
    }

    public function getOperatedSince(): ?\DateTimeImmutable {
        return $this->operated_since;
    }

    public function setOperatedSince(?\DateTimeImmutable $new_value): void {
        $this->operated_since = $new_value;
    }

    public function getOperatedUntil(): ?\DateTimeImmutable {
        return $this->operated_until;
    }

    public function setOperatedUntil(?\DateTimeImmutable $new_value): void {
        $this->operated_until = $new_value;
    }

    public function getMeasurementDurationSeconds(): int {
        return $this->measurement_duration_seconds;
    }

    public function setMeasurementDurationSeconds(int $new_value): void {
        $this->measurement_duration_seconds = $new_value;
    }

    public function getPositionLatitude(): ?float {
        return $this->position_latitude;
    }

    public function setPositionLatitude(?float $new_value): void {
        $this->position_latitude = $new_value;
    }

    public function getPositionLongitude(): ?float {
        return $this->position_longitude;
    }

    public function setPositionLongitude(?float $new_value): void {
        $this->position_longitude = $new_value;
    }
}

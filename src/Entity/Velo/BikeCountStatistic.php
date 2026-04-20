<?php

namespace App\Entity\Velo;

use App\Repository\Velo\BikeCountStatisticRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'bike_count_statistics')]
// Primary index on ['station_id', 'year', 'month', 'day', 'hour', 'kind'] exists by default!
#[ORM\Entity(repositoryClass: BikeCountStatisticRepository::class)]
class BikeCountStatistic {
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: BikeCountStation::class)]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false)]
    private BikeCountStation $station;

    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['comment' => '-1: all years, 1970-: years'])]
    private int $year;

    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['comment' => '-1: all months, 1-12: months'])]
    private int $month;

    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['comment' => '-1: all days, 1-31: days'])]
    private int $day;

    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['comment' => '-1: all hours, 0-23: hours'])]
    private int $hour;

    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['comment' => '-1: all kinds, 1: monday - 7: sunday'])]
    private int $kind;

    #[ORM\Column(type: Types::SMALLFLOAT)]
    private float $count;

    public function getStation(): ?BikeCountStation {
        return $this->station;
    }

    public function setStation(BikeCountStation $new_value): void {
        $this->station = $new_value;
    }

    public function getYear(): ?int {
        return $this->year === -1 ? null : $this->year;
    }

    public function setYear(?int $new_value): void {
        if ($new_value !== null && $new_value < 1970) {
            throw new \Exception("Invalid year: {$new_value}");
        }
        $this->year = $new_value ?? -1;
    }

    public function getMonth(): ?int {
        return $this->month === -1 ? null : $this->month;
    }

    public function setMonth(?int $new_value): void {
        if ($new_value !== null && ($new_value < 1 || $new_value > 12)) {
            throw new \Exception("Invalid month: {$new_value}");
        }
        $this->month = $new_value ?? -1;
    }

    public function getDay(): ?int {
        return $this->day === -1 ? null : $this->day;
    }

    public function setDay(?int $new_value): void {
        $iso_date = "{$this->year}-{$this->month}-{$new_value}";
        if (
            $new_value !== null
            && (new \DateTime($iso_date))->format('Y-n-j') !== $iso_date
        ) {
            throw new \Exception("Invalid day: {$iso_date}");
        }
        $this->day = $new_value ?? -1;
    }

    public function getKind(): ?int {
        return $this->kind === -1 ? null : $this->kind;
    }

    public function setKind(?int $new_value): void {
        if ($new_value !== null && ($new_value < 1 || $new_value > 7)) {
            throw new \Exception("Invalid kind: {$new_value}");
        }
        $this->kind = $new_value ?? -1;
    }

    public function getHour(): ?int {
        return $this->hour === -1 ? null : $this->hour;
    }

    public function setHour(?int $new_value): void {
        if ($new_value !== null && ($new_value < 0 || $new_value > 23)) {
            throw new \Exception("Invalid hour: {$new_value}");
        }
        $this->hour = $new_value ?? -1;
    }

    public function getCount(): float {
        return $this->count;
    }

    public function setCount(float $new_value): void {
        $this->count = $new_value;
    }

    // ---

    /**
     * @return array{0: int, 1: ?int, 2: ?int, 3: ?int, 4: ?int, 5: ?int, 6: float}
     */
    public function getRow(): array {
        return [
            $this->station->getId() ?? 0,
            $this->year,
            $this->month,
            $this->day,
            $this->hour,
            $this->kind,
            $this->count,
        ];
    }
}

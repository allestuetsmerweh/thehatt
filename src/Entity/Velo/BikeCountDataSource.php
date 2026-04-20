<?php

namespace App\Entity\Velo;

use App\Repository\Velo\BikeCountDataSourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'bike_count_data_sources')]
#[ORM\Index(name: 'ident_index', columns: ['ident'])]
#[ORM\Entity(repositoryClass: BikeCountDataSourceRepository::class)]
class BikeCountDataSource {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::ASCII_STRING, length: 31, nullable: false)]
    private string $ident;

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(int $new_value): void {
        $this->id = $new_value;
    }

    public function getIdent(): string {
        return $this->ident;
    }

    public function setIdent(string $new_value): void {
        $this->ident = $new_value;
    }
}

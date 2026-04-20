<?php

namespace App\Velo\Command;

use App\Velo\DataSource\ChZhStadtZuerich;
use App\Velo\DataSource\Common\DataSource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand(
    name: 'prepare-velo-data',
    description: 'Add a short description for your command',
)]
class PrepareVeloDataCommand extends Command {
    protected ?SymfonyStyle $io;

    /** @var array<DataSource> */
    protected array $data_sources;

    #[Required]
    public function setEntityManager(ChZhStadtZuerich $ds0): void {
        $this->data_sources = [$ds0];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $this->io = new SymfonyStyle($input, $output);

        foreach ($this->data_sources as $data_source) {
            $data_source->io = $this->io;
            $data_source->synchronize();
        }

        return Command::SUCCESS;
    }
}

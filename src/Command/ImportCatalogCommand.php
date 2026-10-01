<?php

declare(strict_types=1);

namespace App\Command;

use App\Catalog\CatalogImporter;
use App\Catalog\CatalogImportException;
use Doctrine\DBAL\Exception as DatabaseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:catalog:import',
    description: 'Importe le stock du label (data/catalog/stock.yaml) dans le catalogue',
)]
class ImportCatalogCommand extends Command
{
    public function __construct(
        private readonly CatalogImporter $importer,
        #[Autowire('%kernel.project_dir%/data/catalog/stock.yaml')]
        private readonly string $defaultFile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Fichier YAML du catalogue', $this->defaultFile)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Tout valider puis annuler : rien n\'est enregistré')
            ->setHelp(<<<'HELP'
                Crée ce qui manque, ne modifie jamais l'existant : la commande peut être relancée sans
                risque, un disque déjà importé (même SKU) garde son stock et son prix actuels.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');
        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $report = $this->importer->import($file, $dryRun);
        } catch (CatalogImportException $exception) {
            $io->error(['Import annulé, rien n\'a été enregistré.', $exception->getMessage()]);

            return Command::FAILURE;
        } catch (DatabaseException $exception) {
            // A constraint the validation does not cover (column length...): rolled back as well.
            $io->error(['Import annulé, rien n\'a été enregistré : la base a refusé une valeur.', $exception->getMessage()]);

            return Command::FAILURE;
        }

        $created = $report->getCreated();
        $io->table(['Créé', 'Nombre'], array_map(null, array_keys($created), array_values($created)));

        if ([] !== $report->getSkipped()) {
            $io->section('Déjà présent, laissé tel quel');
            $io->listing($report->getSkipped());
        }

        $dryRun
            ? $io->note('Simulation (--dry-run) : tout a été annulé.')
            : $io->success(\sprintf('%d disque(s) importé(s).', $report->countCreated('disque')));

        return Command::SUCCESS;
    }
}

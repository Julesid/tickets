<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\TicketRepository;

#[AsCommand(
    name: 'app:tickets:stats',
    description: 'Add a short description for your command',
)]
class TicketsStatsCommand extends Command
{
    public function __construct(private readonly TicketRepository $ticketRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stats = $this->ticketRepository->countByStatus();

        $rows = [];
        foreach ($stats as $stat) {
            $rows[] = [$stat['status'], $stat['total']];
        }

        $io->title('Tickets par statut');
        $io->table(['Statut', 'Total'], $rows);

        return Command::SUCCESS;
    }
}

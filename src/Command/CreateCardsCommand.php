<?php

namespace App\Command;

use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-cards',
    description: 'Creates the initial deck of cards',
)]
class CreateCardsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $suits = ['Corazones', 'Diamantes', 'Picas', 'Tréboles'];
        $numbers = range(1, 13);

        foreach ($suits as $suit) {
            foreach ($numbers as $number) {
                $card = new Card();
                $card->setNumber($number);
                $card->setSuit($suit);
                $this->entityManager->persist($card);
            }
        }

        $this->entityManager->flush();
        $output->writeln('Cards created successfully!');

        return Command::SUCCESS;
    }
} 
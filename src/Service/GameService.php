<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Card;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GameService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function getRandomCards(int $count, ?Card $excludeCard = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Card::class, 'c');

        if ($excludeCard) {
            $qb->where('c.id != :excludeId')
               ->setParameter('excludeId', $excludeCard->getId());
        }

        $cards = $qb->getQuery()->getResult();
        
        // Mezclar las cartas de forma aleatoria
        shuffle($cards);
        
        // Devolver solo el número de cartas solicitado
        return array_slice($cards, 0, $count);
    }

    public function determineWinner(Game $game): ?User
    {
        $card1 = $game->getPlayer1Card();
        $card2 = $game->getPlayer2Card();

        if ($card1->getNumber() > $card2->getNumber()) {
            return $game->getPlayer1();
        } elseif ($card2->getNumber() > $card1->getNumber()) {
            return $game->getPlayer2();
        }

        return null; // Empate
    }
} 
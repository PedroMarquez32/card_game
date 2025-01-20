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
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $cards = [];
        
        foreach ($suits as $suit) {
            $qb = $this->entityManager->createQueryBuilder()
                ->select('c')
                ->from(Card::class, 'c')
                ->where('c.suit = :suit')
                ->setParameter('suit', $suit);

            if ($excludeCard) {
                $qb->andWhere('c.id != :excludeId')
                   ->setParameter('excludeId', $excludeCard->getId());
            }

            $cardsOfSuit = $qb->getQuery()->getResult();
            if (!empty($cardsOfSuit)) {
                // Seleccionar una carta aleatoria de este palo
                $randomCard = $cardsOfSuit[array_rand($cardsOfSuit)];
                $cards[] = $randomCard;
            }
        }
        
        return $cards;
    }

    public function determineWinner(Game $game): ?User
    {
        $card1 = $game->getPlayer1Card();
        $card2 = $game->getPlayer2Card();

        // Obtener los valores base de las cartas
        $value1 = $card1->getNumber();
        $value2 = $card2->getNumber();

        // Aplicar bonus basado en las ventajas entre palos
        $value1 += $this->calculateSuitBonus($card1->getSuit(), $card2->getSuit());
        $value2 += $this->calculateSuitBonus($card2->getSuit(), $card1->getSuit());

        if ($value1 > $value2) {
            return $game->getPlayer1();
        } elseif ($value2 > $value1) {
            return $game->getPlayer2();
        }

        return null; // Empate
    }

    private function calculateSuitBonus(string $suit1, string $suit2): int
    {
        $advantages = [
            'diamonds' => 'hearts',
            'hearts' => 'spades',
            'spades' => 'clubs',
            'clubs' => 'diamonds'
        ];

        // Si el palo1 tiene ventaja sobre el palo2, retorna el bonus
        if (isset($advantages[$suit1]) && $advantages[$suit1] === $suit2) {
            return 10;
        }

        return 0;
    }

    public function dealInitialCards(Game $game): void
    {
        $availableCards = [];
        
        // Crear una carta de cada palo
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        foreach ($suits as $suit) {
            $number = rand(1, 10);
            $availableCards[] = new Card($number, $suit);
        }
        
        // Guardar las cartas disponibles en el juego
        $game->setAvailableCards($availableCards);
    }
} 
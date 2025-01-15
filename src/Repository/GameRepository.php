<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findByPlayer(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.player1 = :user')
            ->orWhere('g.player2 = :user')
            ->setParameter('user', $user)
            ->orderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 
<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'games')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $player2 = null;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: "player1_card_id", nullable: true)]
    private ?Card $player1Card = null;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: "player2_card_id", nullable: true)]
    private ?Card $player2Card = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $winner = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $availableCards = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer1(): ?User
    {
        return $this->player1;
    }

    public function setPlayer1(?User $player1): self
    {
        $this->player1 = $player1;
        return $this;
    }

    public function getPlayer2(): ?User
    {
        return $this->player2;
    }

    public function setPlayer2(?User $player2): self
    {
        $this->player2 = $player2;
        return $this;
    }

    public function getPlayer1Card(): ?Card
    {
        return $this->player1Card;
    }

    public function setPlayer1Card(?Card $player1Card): self
    {
        $this->player1Card = $player1Card;
        return $this;
    }

    public function getPlayer2Card(): ?Card
    {
        return $this->player2Card;
    }

    public function setPlayer2Card(?Card $player2Card): self
    {
        $this->player2Card = $player2Card;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): self
    {
        $this->winner = $winner;
        return $this;
    }

    public function setAvailableCards(array $numbers): self
    {
        $this->availableCards = $numbers;
        return $this;
    }

    public function getAvailableCards(): array
    {
        return $this->availableCards ?? [];
    }
} 
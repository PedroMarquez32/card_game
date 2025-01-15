<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Card;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game')]
class GameController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameService $gameService
    ) {}

    #[Route('/', name: 'game_index', methods: ['GET'])]
    public function index(): Response
    {
        $games = $this->entityManager->getRepository(Game::class)->findAll();

        return $this->render('game/index.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $cardId = $request->request->get('card');
            $selectedCard = $this->entityManager->getRepository(Card::class)->find($cardId);
            
            $game = new Game();
            $game->setPlayer1($this->getUser());
            $game->setPlayer1Card($selectedCard);
            $game->setStatus('open');
            
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        $cards = $this->gameService->getRandomCards(2);
        
        return $this->render('game/new.html.twig', [
            'cards' => $cards,
        ]);
    }

    #[Route('/{id}', name: 'game_show', methods: ['GET'])]
    public function show(?Game $game = null): Response
    {
        if (!$game) {
            $this->addFlash('error', 'El juego no existe.');
            return $this->redirectToRoute('game_index');
        }

        return $this->render('game/show.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{id}/join', name: 'game_join', methods: ['POST'])]
    public function join(?Game $game = null): Response
    {
        if (!$game) {
            $this->addFlash('error', 'El juego no existe.');
            return $this->redirectToRoute('game_index');
        }

        if ($game->getStatus() !== 'open' || $game->getPlayer2() !== null) {
            throw $this->createAccessDeniedException('No puedes unirte a este juego.');
        }

        $game->setPlayer2($this->getUser());
        $this->entityManager->flush();

        return $this->redirectToRoute('game_play', ['id' => $game->getId()]);
    }

    #[Route('/{id}/play', name: 'game_play', methods: ['GET', 'POST'])]
    public function play(Request $request, ?Game $game = null): Response
    {
        if (!$game) {
            $this->addFlash('error', 'El juego no existe.');
            return $this->redirectToRoute('game_index');
        }

        if ($game->getStatus() !== 'open' || $game->getPlayer2() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No puedes jugar en este juego.');
        }

        if ($request->isMethod('POST')) {
            $cardId = $request->request->get('card');
            $selectedCard = $this->entityManager->getRepository(Card::class)->find($cardId);
            
            $game->setPlayer2Card($selectedCard);
            $game->setStatus('finished');
            
            $winner = $this->gameService->determineWinner($game);
            if ($winner) {
                $game->setWinner($winner);
            }
            
            $this->entityManager->flush();
            
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        $cards = $this->gameService->getRandomCards(2, $game->getPlayer1Card());
        
        return $this->render('game/play.html.twig', [
            'game' => $game,
            'cards' => $cards,
        ]);
    }

    #[Route('/game/my-games', name: 'game_my_games', methods: ['GET'])]
    public function myGames(): Response
    {
        $user = $this->getUser();
        $games = $this->entityManager->getRepository(Game::class)->findByPlayer($user);
        
        // Calcular estadísticas
        $stats = [
            'total' => count($games),
            'wins' => 0,
            'losses' => 0,
            'draws' => 0
        ];
        
        foreach ($games as $game) {
            if ($game->getStatus() === 'finished') {
                if (!$game->getWinner()) {
                    $stats['draws']++;
                } elseif ($game->getWinner() === $user) {
                    $stats['wins']++;
                } else {
                    $stats['losses']++;
                }
            }
        }

        return $this->render('game/my_games.html.twig', [
            'games' => $games,
            'stats' => $stats
        ]);
    }
} 
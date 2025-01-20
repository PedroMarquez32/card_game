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
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchQuery = $request->query->get('search');
        $queryBuilder = $entityManager->getRepository(Game::class)->createQueryBuilder('g')
            ->leftJoin('g.player1', 'p1')
            ->leftJoin('g.player2', 'p2')
            ->orderBy('g.id', 'DESC');

        if ($searchQuery) {
            $queryBuilder->where('g.id = :searchId OR p1.username LIKE :searchTerm OR p2.username LIKE :searchTerm')
                ->setParameter('searchId', is_numeric($searchQuery) ? $searchQuery : -1)
                ->setParameter('searchTerm', '%' . $searchQuery . '%');
        }

        $games = $queryBuilder->getQuery()->getResult();

        return $this->render('game/index.html.twig', [
            'games' => $games,
            'searchQuery' => $searchQuery
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

        // Verificar que el juego está en estado correcto
        if ($game->getStatus() !== 'open' && $game->getStatus() !== 'waiting') {
            throw $this->createAccessDeniedException('No puedes jugar en este juego.');
        }

        // Verificar que el usuario actual es uno de los jugadores y no ha seleccionado carta
        $currentUser = $this->getUser();
        $canPlay = false;

        if ($currentUser === $game->getPlayer1() && !$game->getPlayer1Card()) {
            $canPlay = true;
        } elseif ($currentUser === $game->getPlayer2() && !$game->getPlayer2Card()) {
            $canPlay = true;
        }

        if (!$canPlay) {
            $this->addFlash('error', 'No es tu turno o ya has seleccionado una carta.');
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        if ($request->isMethod('POST')) {
            $cardId = $request->request->get('card');
            $selectedCard = $this->entityManager->getRepository(Card::class)->find($cardId);
            
            if (!$selectedCard) {
                $this->addFlash('error', 'Carta no válida.');
                return $this->redirectToRoute('game_play', ['id' => $game->getId()]);
            }

            if ($currentUser === $game->getPlayer1()) {
                $game->setPlayer1Card($selectedCard);
            } else {
                $game->setPlayer2Card($selectedCard);
            }

            // Actualizar estado del juego
            if ($game->getPlayer1Card() && $game->getPlayer2Card()) {
                $game->setStatus('finished');
                $winner = $this->gameService->determineWinner($game);
                if ($winner) {
                    $game->setWinner($winner);
                }
            } elseif ($game->getStatus() === 'waiting') {
                $game->setStatus('open');
            }
            
            $this->entityManager->flush();
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        $cards = $this->gameService->getRandomCards(4);
        
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

    #[Route('/create', name: 'game_create', methods: ['POST'])]
    public function create(Request $request, GameService $gameService): Response
    {
        $game = new Game();
        $game->setPlayer1($this->getUser());
        $game->setStatus('waiting');
        $gameService->dealInitialCards($game);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($game);
        $entityManager->flush();
        
        return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
    }

    #[Route('/{id}/close', name: 'game_close', methods: ['POST'])]
    public function close(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        // Permitir cerrar si es admin o el creador de la partida
        if (!$this->isGranted('ROLE_ADMIN') && $game->getPlayer1() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tienes permiso para cerrar esta partida.');
        }

        if ($this->isCsrfTokenValid('close'.$game->getId(), $request->request->get('_token'))) {
            $game->setStatus('finished');
            $entityManager->flush();
            
            $this->addFlash('success', 'Partida cerrada correctamente');
        }

        return $this->redirectToRoute('game_index');
    }

    #[Route('/{id}/delete', name: 'game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        // Permitir borrar si es admin o el creador de la partida
        if (!$this->isGranted('ROLE_ADMIN') && $game->getPlayer1() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tienes permiso para eliminar esta partida.');
        }

        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->request->get('_token'))) {
            $entityManager->remove($game);
            $entityManager->flush();
            
            $this->addFlash('success', 'Partida eliminada correctamente');
        }

        return $this->redirectToRoute('game_index');
    }

    #[Route('/{id}/rematch', name: 'game_rematch', methods: ['POST'])]
    public function rematch(Request $request, Game $game, EntityManagerInterface $entityManager): Response
    {
        if ($game->getStatus() !== 'finished' || 
            ($game->getPlayer1() !== $this->getUser() && $game->getPlayer2() !== $this->getUser())) {
            throw $this->createAccessDeniedException('No puedes iniciar una revancha para esta partida.');
        }

        if (!$this->isCsrfTokenValid('rematch'.$game->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('game_show', ['id' => $game->getId()]);
        }

        // Verificar si ya existe una partida abierta entre estos jugadores
        $existingGame = $entityManager->getRepository(Game::class)->createQueryBuilder('g')
            ->where('g.status IN (:statuses)')
            ->andWhere('(g.player1 = :player1 AND g.player2 = :player2) OR (g.player1 = :player2 AND g.player2 = :player1)')
            ->setParameter('statuses', ['waiting', 'open'])
            ->setParameter('player1', $game->getPlayer1())
            ->setParameter('player2', $game->getPlayer2())
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingGame) {
            // Si ya existe una partida, redirigir a ella
            if ($this->getUser() === $existingGame->getPlayer1() && !$existingGame->getPlayer1Card()) {
                return $this->redirectToRoute('game_play', ['id' => $existingGame->getId()]);
            } elseif ($this->getUser() === $existingGame->getPlayer2() && !$existingGame->getPlayer2Card()) {
                return $this->redirectToRoute('game_play', ['id' => $existingGame->getId()]);
            } else {
                return $this->redirectToRoute('game_show', ['id' => $existingGame->getId()]);
            }
        }

        // Si no existe una partida, crear una nueva
        $newGame = new Game();
        $newGame->setPlayer1($this->getUser());
        $newGame->setPlayer2($this->getUser() === $game->getPlayer1() ? $game->getPlayer2() : $game->getPlayer1());
        $newGame->setStatus('open');
        
        // Generamos números aleatorios del 1 al 10
        $availableNumbers = range(1, 10);
        shuffle($availableNumbers);
        $newGame->setAvailableCards($availableNumbers);

        $entityManager->persist($newGame);
        $entityManager->flush();
        
        $this->addFlash('success', '¡Nueva partida creada! Es tu turno de elegir carta.');
        
        return $this->redirectToRoute('game_play', ['id' => $newGame->getId()]);
    }
} 
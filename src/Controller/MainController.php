<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(UserRepository $userRepository, CacheInterface $cache): Response
    {

       $html= $cache->get('users', function (ItemInterface $item) use ($userRepository) {
            var_dump("cosa");
            $item-> expiresAfter(30);
            $users = $userRepository->findAll();

            return $this->renderView('main/index.html.twig', [
                'users' => $users,
            ]);

        });
        return new Response ($html);
       
    }
}
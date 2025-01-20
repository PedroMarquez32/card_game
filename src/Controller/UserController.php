<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin/users', name: 'admin_users_index')]
    public function index(): Response
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/admin/users/{id}/toggle-role', name: 'admin_users_toggle_role', methods: ['POST'])]
    public function toggleRole(Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('toggle-role'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $role = $request->request->get('role');
        $validRoles = ['ROLE_ADMIN', 'ROLE_USER'];

        if (!in_array($role, $validRoles)) {
            $this->addFlash('error', 'Rol no válido');
            return $this->redirectToRoute('admin_users_index');
        }

        // No permitir que un admin se quite sus propios permisos
        if ($user === $this->getUser() && $role === 'ROLE_USER') {
            $this->addFlash('error', 'No puedes quitarte tus propios permisos de administrador');
            return $this->redirectToRoute('admin_users_index');
        }

        $currentRoles = $user->getRoles();
        
        if (in_array($role, $currentRoles)) {
            // Si ya tiene el rol, lo quitamos (excepto ROLE_USER que es base)
            if ($role !== 'ROLE_USER') {
                $currentRoles = array_diff($currentRoles, [$role]);
                $user->setRoles($currentRoles);
                $this->addFlash('success', 'Rol removido correctamente');
            }
        } else {
            // Si no tiene el rol, lo añadimos
            $currentRoles[] = $role;
            $user->setRoles(array_unique($currentRoles));
            $this->addFlash('success', 'Rol añadido correctamente');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('admin_users_index');
    }
} 
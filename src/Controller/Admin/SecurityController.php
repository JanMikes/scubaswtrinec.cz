<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    /** 2014: `/admin/sign/` redirected to `/admin/sign/in`. */
    #[Route('/admin/sign', name: 'admin_sign', methods: ['GET'])]
    public function sign(): Response
    {
        return $this->redirectToRoute('admin_sign_in');
    }

    #[Route('/admin/sign/in', name: 'admin_sign_in', methods: ['GET', 'POST'])]
    public function signIn(AuthenticationUtils $authUtils): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/sign_in.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/sign/out', name: 'admin_sign_out', methods: ['GET', 'POST'])]
    public function signOut(): never
    {
        throw new \LogicException('Handled by the firewall logout listener.');
    }
}

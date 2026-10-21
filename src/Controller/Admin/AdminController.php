<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\Admin\ChangePasswordType;
use App\Form\Admin\ContactType;
use App\Repository\ActualityRepository;
use App\Repository\ArticleRepository;
use App\Repository\ContactRepository;
use App\Repository\DiscussionRepository;
use App\Repository\GalleryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/admin/dashboard', name: 'admin_dashboard_legacy', methods: ['GET'])]
    #[Route('/admin/dashboard/default', name: 'admin_dashboard_legacy2', methods: ['GET'])]
    public function dashboard(ActualityRepository $actualities, ArticleRepository $articles, GalleryRepository $galleries, DiscussionRepository $discussions): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'counts' => [
                'actuality' => (int) $actualities->adminQb()->select('COUNT(r.id)')->getQuery()->getSingleScalarResult(),
                'article' => (int) $articles->adminQb()->select('COUNT(r.id)')->getQuery()->getSingleScalarResult(),
                'gallery' => (int) $galleries->adminQb()->select('COUNT(r.id)')->getQuery()->getSingleScalarResult(),
                'discussion' => (int) $discussions->adminQb()->select('COUNT(r.id)')->getQuery()->getSingleScalarResult(),
            ],
            'latest_posts' => $discussions->adminQb()->orderBy('r.id', \SortDirection::Descending)->setMaxResults(5)->getQuery()->getResult(),
            'latest_actualities' => $actualities->adminQb()->orderBy('r.date', \SortDirection::Descending)->addOrderBy('r.id', \SortDirection::Descending)->setMaxResults(5)->getQuery()->getResult(),
        ]);
    }

    /** One rich-text page; every save appends a row (2014 behaviour, keeps the history). */
    #[Route('/admin/contact', name: 'admin_contact', methods: ['GET', 'POST'])]
    #[Route('/admin/contact/default', name: 'admin_contact_legacy', methods: ['GET', 'POST'])]
    public function contact(Request $request, ContactRepository $contacts, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContactType::class, ['text' => $contacts->latest()?->text]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{text: ?string} $data */
            $data = $form->getData();
            $contact = new Contact();
            $contact->text = $data['text'];
            $em->persist($contact);
            $em->flush();
            $this->addFlash('success', 'Kontakt byl uložen.');

            return $this->redirectToRoute('admin_contact');
        }

        return $this->render('admin/contact.html.twig', ['form' => $form]);
    }

    #[Route('/admin/user/change-password', name: 'admin_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, #[CurrentUser] User $user, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{new_password: string} $data */
            $data = $form->getData();
            $user->password = $hasher->hashPassword($user, $data['new_password']);
            $em->flush();
            $this->addFlash('success', 'Heslo bylo úspěšně změněno.');

            return $this->redirectToRoute('admin_change_password');
        }

        return $this->render('admin/change_password.html.twig', ['form' => $form]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\AdminSection;
use App\Admin\AdminSections;
use App\Admin\RecordActions;
use App\Entity\Discussion;
use App\Entity\DiscussionBan;
use App\Entity\Gallery;
use App\Entity\Instructor;
use App\Entity\LegacyRecord;
use App\Entity\Link;
use App\Entity\PostPhoto;
use App\Repository\DiscussionBanRepository;
use App\Service\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * List / add / edit / row actions for every admin section (see AdminSections). Paths are the 2014
 * ones: /admin/<section>[/default], /admin/<section>/add, /admin/<section>/edit/<id>, photo sections
 * with ?actualityId= / ?articleId= / ?galleryId=.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/{section}', requirements: ['section' => AdminSections::SLUG_REQUIREMENT])]
final class AdminCrudController extends AbstractController
{
    public function __construct(
        private readonly RecordActions $actions,
        private readonly EntityManagerInterface $em,
        private readonly PhotoStorage $photos,
    ) {
    }

    #[Route('', name: 'admin_list', methods: ['GET'])]
    #[Route('/default', name: 'admin_list_legacy', methods: ['GET'])]
    public function list(string $section, Request $request, DiscussionBanRepository $bans): Response
    {
        $s = AdminSections::get($section);
        $parent = $this->parent($s, $request);
        if ($parent instanceof Response) {
            return $parent;
        }
        $rows = $this->actions->list($s, $parent);

        return $this->render($s->listTemplate, [
            'section' => $s,
            'parent' => $parent,
            'rows' => $rows,
            'inactive_count' => \count(array_filter($rows, static fn (LegacyRecord $r): bool => !$r->active)),
            'bans' => 'discussion' === $section ? $bans->adminQb('b')->orderBy('b.insDt', \SortDirection::Descending)->getQuery()->getResult() : [],
            'banned_ips' => 'discussion' === $section ? $bans->bannedIps() : [],
        ]);
    }

    #[Route('/add', name: 'admin_add', methods: ['GET', 'POST'])]
    public function add(string $section, Request $request): Response
    {
        $s = AdminSections::get($section);
        $parent = $this->parent($s, $request);
        if ($parent instanceof Response) {
            return $parent;
        }
        $record = new ($s->entityClass)();
        if ($record instanceof PostPhoto && null !== $s->parentField) {
            $record->{$s->parentField} = $parent;
        }

        return $this->handleForm($s, $record, $parent, $request, true);
    }

    #[Route('/edit/{id}', name: 'admin_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(string $section, int $id, Request $request): Response
    {
        $s = AdminSections::get($section);
        $record = $this->actions->find($s, $id);
        if (null === $record) {
            $this->addFlash('danger', 'Záznam nebyl nalezen.');

            return $this->redirectToList($s, $this->parentOfRecord($request, $s));
        }
        $parent = $record instanceof PostPhoto ? $record->getParent() : null;

        return $this->handleForm($s, $record, $parent, $request, false);
    }

    #[Route('/{id}/{op}', name: 'admin_record_action', requirements: ['id' => '\d+', 'op' => 'activate|deactivate|delete|up|down'], methods: ['POST'])]
    #[IsCsrfTokenValid('admin-action', tokenKey: '_token')]
    public function recordAction(string $section, int $id, string $op): Response
    {
        $s = AdminSections::get($section);
        $record = $this->actions->find($s, $id);
        if (null === $record) {
            $this->addFlash('danger', 'Záznam nebyl nalezen.');

            return $this->redirectToRoute('admin_dashboard');
        }
        $parent = $record instanceof PostPhoto ? $record->getParent() : null;

        switch ($op) {
            case 'activate':
                $this->actions->setActive($record, true);
                $this->addFlash('success', 'Záznam úspěšně aktivován.');

                break;
            case 'deactivate':
                $this->actions->setActive($record, false);
                $this->addFlash('success', 'Záznam úspěšně deaktivován.');

                break;
            case 'delete':
                $this->actions->delete($record);
                $this->addFlash('success', 'Záznam úspěšně smazán.');

                break;
            default: // up / down (route requirement)
                $moved = $this->actions->move($s, $record, $op);
                $this->addFlash($moved ? 'success' : 'danger', $moved ? 'Pořadí bylo úspěšně změněno.' : 'Pořadí nelze změnit.');
        }

        return $this->redirectToList($s, $parent, $id);
    }

    #[Route('/activate-all', name: 'admin_activate_all', methods: ['POST'])]
    #[IsCsrfTokenValid('admin-action', tokenKey: '_token')]
    public function activateAll(string $section, Request $request): Response
    {
        $s = AdminSections::get($section);
        $parent = $this->parent($s, $request);
        if ($parent instanceof Response) {
            return $parent;
        }
        $n = $this->actions->activateAll($s, $parent);
        $this->addFlash('success', \sprintf('Aktivováno záznamů: %d.', $n));

        return $this->redirectToList($s, $parent);
    }

    #[Route('/{id}/ban', name: 'admin_discussion_ban', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('admin-action', tokenKey: '_token')]
    public function ban(string $section, int $id, DiscussionBanRepository $bans): Response
    {
        $s = AdminSections::get($section);
        $post = $this->actions->find($s, $id);
        if (!$post instanceof Discussion || null === $post->ip || '' === $post->ip) {
            $this->addFlash('danger', 'Příspěvek nemá IP adresu, nelze blokovat.');

            return $this->redirectToList($s, null);
        }
        if (!$bans->isBanned($post->ip)) {
            $ban = new DiscussionBan();
            $ban->ip = $post->ip;
            $ban->reason = trim($post->author.' - '.$post->email);
            $this->em->persist($ban);
            $this->em->flush();
        }
        $this->addFlash('success', 'Blokace úspěšně přidána.');

        return $this->redirectToList($s, null);
    }

    #[Route('/unban/{banId}', name: 'admin_discussion_unban', requirements: ['banId' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid('admin-action', tokenKey: '_token')]
    public function unban(string $section, int $banId, DiscussionBanRepository $bans): Response
    {
        $s = AdminSections::get($section);
        $ban = $bans->findForAdmin($banId);
        if (null !== $ban) {
            // 2014 semantics: unban lifts every ban row of that IP.
            foreach ($bans->adminQb('b')->andWhere('b.ip = :ip')->setParameter('ip', $ban->ip)->getQuery()->getResult() as $b) {
                \assert($b instanceof DiscussionBan);
                $b->deleted = true;
            }
            $this->em->flush();
            $this->addFlash('success', 'Blokace úspěšně zrušena.');
        }

        return $this->redirectToList($s, null);
    }

    private function handleForm(AdminSection $s, LegacyRecord $record, ?LegacyRecord $parent, Request $request, bool $isNew): Response
    {
        $options = ['record' => $isNew ? null : $record];
        if ('discussion' === $s->slug) {
            $options['public'] = false;
        }
        $form = $this->createForm($s->formType, $record, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $created = $this->save($s, $record, $parent, $form, $isNew);
            $this->addFlash('success', \count($created) > 1 ? \sprintf('Uloženo %d fotografií.', \count($created)) : 'Záznam byl úspěšně uložen.');

            if ('continue' === $request->request->get('_next') && 1 === \count($created)) {
                return $this->redirectToRoute('admin_edit', ['section' => $s->slug, 'id' => $created[0]->getId()]);
            }

            return $this->redirectToList($s, $parent, 1 === \count($created) ? $created[0]->getId() : null);
        }

        return $this->render('admin/form.html.twig', [
            'section' => $s,
            'record' => $record,
            'parent' => $parent,
            'form' => $form,
            'is_new' => $isNew,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * @param FormInterface<mixed> $form
     *
     * @return list<LegacyRecord> the saved records (several for a multi-file photo upload)
     */
    private function save(AdminSection $s, LegacyRecord $record, ?LegacyRecord $parent, FormInterface $form, bool $isNew): array
    {
        // Photo section, new: one record per uploaded file.
        if ($record instanceof PostPhoto && $isNew) {
            /** @var list<UploadedFile> $files */
            $files = $form->get('images')->getData() ?? [];
            $saved = [];
            foreach ($files as $i => $file) {
                $photoRecord = $record;
                if ($i > 0) {
                    $photoRecord = new ($s->entityClass)();
                    \assert($photoRecord instanceof PostPhoto);
                    $photoRecord->{(string) $s->parentField} = $parent;
                    $photoRecord->description = $record->description;
                }
                $this->em->persist($photoRecord);
                $this->em->flush();
                $photoRecord->photo = $this->photos->store($file, $this->uploadDir($s, $photoRecord, $parent), $photoRecord->getId().'-'.bin2hex(random_bytes(3)));
                $this->em->flush();
                $saved[] = $photoRecord;
            }

            return $saved;
        }

        $this->em->persist($record);
        $this->em->flush();

        $file = $form->has('image') ? $form->get('image')->getData() : null;
        if ($file instanceof UploadedFile && ($record instanceof PostPhoto || $record instanceof Gallery || $record instanceof Instructor || $record instanceof Link)) {
            $this->photos->discard($record->photo);
            $basename = $record instanceof PostPhoto ? $record->getId().'-'.bin2hex(random_bytes(3)) : 'main-'.bin2hex(random_bytes(3));
            $record->photo = $this->photos->store($file, $this->uploadDir($s, $record, $parent), $basename);
            $this->em->flush();
        }

        return [$record];
    }

    private function uploadDir(AdminSection $s, LegacyRecord $record, ?LegacyRecord $parent): string
    {
        return strtr((string) $s->uploadDir, ['{parent}' => (string) $parent?->getId(), '{id}' => (string) $record->getId()]);
    }

    /** Resolves the parent record of a photo section from its query parameter; redirects when missing. */
    private function parent(AdminSection $s, Request $request): LegacyRecord|Response|null
    {
        if (!$s->hasParent()) {
            return null;
        }
        $parentSection = AdminSections::get((string) $s->parentSlug);
        $id = $request->query->getInt((string) $s->parentParam);
        $parent = $id > 0 ? $this->actions->find($parentSection, $id) : null;

        return $parent ?? $this->redirectToRoute('admin_list', ['section' => $parentSection->slug]);
    }

    private function parentOfRecord(Request $request, AdminSection $s): ?LegacyRecord
    {
        $p = $this->parent($s, $request);

        return $p instanceof LegacyRecord ? $p : null;
    }

    private function redirectToList(AdminSection $s, ?LegacyRecord $parent, ?int $anchorId = null): RedirectResponse
    {
        $params = ['section' => $s->slug];
        if (null !== $parent && null !== $s->parentParam) {
            $params[$s->parentParam] = $parent->getId();
        }
        $url = $this->generateUrl('admin_list', $params);

        return $this->redirect($url.(null !== $anchorId ? '#r'.$anchorId : ''));
    }
}

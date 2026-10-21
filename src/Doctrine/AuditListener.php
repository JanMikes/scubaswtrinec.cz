<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\LegacyRecord;
use App\Entity\OrderableInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Keeps writing the 2014 audit columns: ins_user_id / upd_user_id (admin user id),
 * ins_process / upd_process (what wrote the row — the route name when nothing more specific was set),
 * and the "new orderable rows get order = id * 10" rule.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postPersist)]
final readonly class AuditListener
{
    public function __construct(private Security $security, private RequestStack $requestStack)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof LegacyRecord) {
            return;
        }
        $entity->insUserId ??= $this->userId();
        $entity->insProcess ??= $this->process();
    }

    /** Stamps upd_user_id / upd_process on every updated record (unless the code set them itself). */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof LegacyRecord) {
                continue;
            }
            $changes = $uow->getEntityChangeSet($entity);
            if (!isset($changes['updUserId'])) {
                $entity->updUserId = $this->userId();
            }
            if (!isset($changes['updProcess'])) {
                $entity->updProcess = $this->process();
            }
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($entity::class), $entity);
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof OrderableInterface || null !== $entity->getOrder() || null === $entity->getId()) {
            return;
        }
        $order = $entity->getId() * 10;
        $entity->setOrder($order);
        $em = $args->getObjectManager();
        $table = $em->getClassMetadata($entity::class)->getTableName();
        $em->getConnection()->executeStatement(
            \sprintf('UPDATE `%s` SET `order` = ? WHERE `id` = ?', $table),
            [$order, $entity->getId()],
        );
        // Keep the unit of work in sync so the next flush does not re-write it.
        $em->getUnitOfWork()->setOriginalEntityProperty(spl_object_id($entity), 'order', $order);
    }

    private function userId(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof User && null !== $user->getId() ? (string) $user->getId() : null;
    }

    private function process(): string
    {
        $route = $this->requestStack->getCurrentRequest()?->attributes->get('_route');

        return \is_string($route) ? 'web:'.$route : 'cli';
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Photo;
use App\Service\PhotoStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pre-generates the thumbnails the public pages use (card, admin, large) for every live photo whose
 * original exists, so first visitors (and crawlers) do not pay for image decoding. Idempotent:
 * existing thumbnails are skipped. Run after a deploy (lily deploy.sh) or by hand.
 */
#[AsCommand(name: 'app:thumbnails:warm', description: 'Generate missing thumbnails for all live photos')]
final readonly class WarmThumbnailsCommand
{
    public function __construct(private EntityManagerInterface $em, private PhotoStorage $photos)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option('Stop after this many seconds')] int $timeLimit = 600): int
    {
        $started = time();
        $ids = $this->em->createQuery('SELECT p.id FROM '.Photo::class.' p WHERE p.deleted = false ORDER BY p.id DESC')->getSingleColumnResult();
        $this->photos->disableBudget();
        $done = 0;
        $missing = 0;
        foreach ($ids as $id) {
            if (time() - $started > $timeLimit) {
                $io->warning('Time limit reached; the rest is generated on demand.');

                break;
            }
            $photo = $this->em->find(Photo::class, $id);
            if (!$photo instanceof Photo || !$this->photos->originalExists($photo)) {
                ++$missing;

                continue;
            }
            foreach (['card', 'admin', 'large'] as $type) {
                $this->photos->thumbnailUrl($photo, $type);
            }
            ++$done;
            $this->em->clear();
            $this->photos->reset();
            $this->photos->disableBudget();
        }
        $io->success(\sprintf('%d photos checked, %d without an original on disk.', $done, $missing));

        return 0;
    }
}

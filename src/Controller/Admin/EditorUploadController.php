<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Image upload for the WYSIWYG editor (CKEditor 5 SimpleUploadAdapter). The 2014 editor had a
 * KCFinder browser that was never enabled; images now land in the user-data `upload/` directory.
 */
#[IsGranted('ROLE_ADMIN')]
final class EditorUploadController extends AbstractController
{
    private const array ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    #[Route('/admin/editor/upload', name: 'admin_editor_upload', methods: ['POST'])]
    public function __invoke(Request $request, string $publicDir): JsonResponse
    {
        if (!$this->isCsrfTokenValid('editor-upload', (string) $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['error' => ['message' => 'Neplatný bezpečnostní token, obnovte stránku.']], 403);
        }
        $file = $request->files->get('upload');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['error' => ['message' => 'Soubor se nepodařilo nahrát.']], 400);
        }
        $ext = self::ALLOWED[(string) $file->getMimeType()] ?? null;
        if (null === $ext || $file->getSize() > 25 * 1024 * 1024) {
            return new JsonResponse(['error' => ['message' => 'Povolené jsou obrázky JPG, PNG, GIF nebo WebP do 25 MB.']], 400);
        }

        $dir = 'upload/editor/'.date('Y/m');
        (new Filesystem())->mkdir($publicDir.'/'.$dir, 0o755);
        $name = bin2hex(random_bytes(8)).'.'.$ext;

        // Re-encode (strips anything that is not image data) and cap the size for the web.
        $image = (new ImageManager(new Driver()))->read($file->getPathname())->orient()->scaleDown(1920, 1920);
        $encoded = match ($ext) {
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            'webp' => $image->toWebp(85),
            default => $image->toJpeg(85),
        };
        $encoded->save($publicDir.'/'.$dir.'/'.$name);

        return new JsonResponse(['url' => '/'.$dir.'/'.$name]);
    }
}

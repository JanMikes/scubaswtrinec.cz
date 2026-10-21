<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Actuality;
use App\Entity\ActualityPhoto;
use App\Entity\Contact;
use App\Entity\Discussion;
use App\Entity\DiscussionBan;
use App\Entity\Gallery;
use App\Entity\GalleryPhoto;
use App\Entity\Instructor;
use App\Entity\Link;
use App\Entity\User;
use App\Tests\TestDatabase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AdminCrudTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $user = $this->em()->find(User::class, 1);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function reload(string $class, int $id): object
    {
        $this->em()->clear();
        $o = $this->em()->find($class, $id);
        self::assertInstanceOf($class, $o);

        return $o;
    }

    private function image(string $name = 'foto.jpg'): UploadedFile
    {
        $path = sys_get_temp_dir().'/'.uniqid('scuba', true).'.jpg';
        TestDatabase::jpeg($path, 1200, 900);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    private function csrf(): string
    {
        return (string) $this->client->getCrawler()->filter('input[name=_token]')->first()->attr('value');
    }

    /** @return iterable<string, array{string}> */
    public static function adminPages(): iterable
    {
        foreach ([
            '/admin', '/admin/dashboard', '/admin/dashboard/default',
            '/admin/actuality', '/admin/actuality/default', '/admin/actuality/add', '/admin/actuality/edit/1',
            '/admin/actuality-photo?actualityId=1', '/admin/actuality-photo/default?actualityId=1', '/admin/actuality-photo/add?actualityId=1', '/admin/actuality-photo/edit/1',
            '/admin/article', '/admin/article/add', '/admin/article/edit/1', '/admin/article-photo?articleId=1', '/admin/article-photo/add?articleId=1',
            '/admin/gallery', '/admin/gallery/add', '/admin/gallery/edit/1', '/admin/gallery-photo?galleryId=1', '/admin/gallery-photo/add?galleryId=1', '/admin/gallery-photo/edit/1',
            '/admin/instructor', '/admin/instructor/add', '/admin/instructor/edit/1',
            '/admin/link', '/admin/link/add', '/admin/link/edit/1',
            '/admin/discussion', '/admin/discussion/add', '/admin/discussion/edit/1',
            '/admin/contact', '/admin/contact/default', '/admin/user/change-password',
        ] as $url) {
            yield $url => [$url];
        }
    }

    /** Every 2014 admin URL shape still resolves. */
    #[DataProvider('adminPages')]
    public function testAdminPageRenders(string $url): void
    {
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
    }

    public function testPhotoSectionWithoutParentRedirectsToParentList(): void
    {
        $this->client->request('GET', '/admin/gallery-photo');
        self::assertResponseRedirects('/admin/gallery');
    }

    public function testCreateAndEditActuality(): void
    {
        $crawler = $this->client->request('GET', '/admin/actuality/add');
        $this->client->submit($crawler->selectButton('Přidat')->form([
            'post[date]' => '2026-10-21',
            'post[name]' => 'Nová aktualita',
            'post[text]' => '<p>Text <iframe src="https://www.youtube.com/embed/x"></iframe></p>',
        ]));
        self::assertResponseRedirects();
        $a = $this->em()->getRepository(Actuality::class)->findOneBy(['name' => 'Nová aktualita']);
        self::assertInstanceOf(Actuality::class, $a);
        self::assertSame('1', $a->insUserId, 'audit: ins_user_id');
        self::assertStringContainsString('<iframe', (string) $a->text);

        $crawler = $this->client->request('GET', '/admin/actuality/edit/'.$a->getId());
        $this->client->submit($crawler->selectButton('Uložit')->form(['post[name]' => 'Upravená aktualita']));
        $a = $this->reload(Actuality::class, (int) $a->getId());
        self::assertSame('Upravená aktualita', $a->name);
        self::assertSame('1', $a->updUserId, 'audit: upd_user_id');

        $this->client->request('GET', '/');
        self::assertSelectorTextContains('main', 'Upravená aktualita');
    }

    public function testRequiredFieldsAreValidated(): void
    {
        $crawler = $this->client->request('GET', '/admin/actuality/add');
        $this->client->submit($crawler->selectButton('Přidat')->form(['post[name]' => '', 'post[text]' => '']));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Nadpis je povinný!');
    }

    public function testRecordActionsActivateDeactivateDelete(): void
    {
        $this->client->request('GET', '/admin/actuality');
        $token = $this->csrf();

        $this->client->request('POST', '/admin/actuality/2/deactivate', ['_token' => $token]);
        self::assertResponseRedirects();
        self::assertFalse($this->reload(Actuality::class, 2)->active);

        $this->client->request('POST', '/admin/actuality/activate-all', ['_token' => $token]);
        self::assertTrue($this->reload(Actuality::class, 2)->active);
        self::assertTrue($this->reload(Actuality::class, 7)->active);

        $this->client->request('POST', '/admin/actuality/2/delete', ['_token' => $token]);
        $deleted = $this->reload(Actuality::class, 2);
        self::assertTrue($deleted->deleted, 'soft delete: the row stays');

        $this->client->request('POST', '/admin/actuality/3/delete', ['_token' => 'forged']);
        self::assertFalse($this->client->getResponse()->isSuccessful());
        self::assertFalse($this->reload(Actuality::class, 3)->deleted);
    }

    public function testReorderGalleries(): void
    {
        $this->client->request('GET', '/admin/gallery');
        $token = $this->csrf();
        // Display order is `order DESC`: 1 (30), 2 (20), 3 (10). Move 2 up -> 2, 1, 3.
        $this->client->request('POST', '/admin/gallery/2/up', ['_token' => $token]);
        self::assertResponseRedirects();
        self::assertGreaterThan($this->reload(Gallery::class, 1)->getOrder(), $this->reload(Gallery::class, 2)->getOrder());

        $this->client->request('POST', '/admin/gallery/2/down', ['_token' => $token]);
        self::assertGreaterThan($this->reload(Gallery::class, 2)->getOrder(), $this->reload(Gallery::class, 1)->getOrder());
    }

    public function testGalleryWithCoverUpload(): void
    {
        $crawler = $this->client->request('GET', '/admin/gallery/add');
        $form = $crawler->selectButton('Přidat')->form(['gallery[name]' => 'Nová galerie']);
        $file = $form['gallery[image]'];
        \assert($file instanceof \Symfony\Component\DomCrawler\Field\FileFormField);
        $file->upload($this->image()->getPathname());
        $this->client->submit($form);
        self::assertResponseRedirects();

        $g = $this->em()->getRepository(Gallery::class)->findOneBy(['name' => 'Nová galerie']);
        self::assertInstanceOf(Gallery::class, $g);
        self::assertSame((int) $g->getId() * 10, $g->getOrder(), 'new orderable rows get order = id * 10');
        self::assertNotNull($g->photo);
        self::assertSame('img/gallery/'.$g->getId(), $g->photo->path);
        self::assertFileExists(TestDatabase::publicDir().'/'.$g->photo->relativePath());
        self::assertFileExists(TestDatabase::publicDir().'/img/gallery/'.$g->getId().'/admin/'.$g->photo->filename, 'admin thumbnail generated on upload');
    }

    public function testMultiplePhotoUploadIntoGallery(): void
    {
        $crawler = $this->client->request('GET', '/admin/gallery-photo/add?galleryId=1');
        $form = $crawler->selectButton('Přidat')->form(['photo[description]' => 'Hromadně']);
        $this->client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), [
            'photo' => ['images' => [$this->image('a.jpg'), $this->image('b.jpg'), $this->image('c.jpg')]],
        ]);
        self::assertResponseRedirects('/admin/gallery-photo?galleryId=1');

        $photos = $this->em()->getRepository(GalleryPhoto::class)->findBy(['description' => 'Hromadně']);
        self::assertCount(3, $photos);
        foreach ($photos as $p) {
            self::assertSame(1, $p->getParent()->getId());
            self::assertNotNull($p->photo);
            self::assertFileExists(TestDatabase::publicDir().'/'.$p->photo->relativePath());
        }

        $this->client->request('GET', '/detail-galerie/1');
        self::assertCount(4, $this->client->getCrawler()->filter('a.glightbox'));
    }

    public function testReplaceActualityPhotoKeepsOldFile(): void
    {
        $crawler = $this->client->request('GET', '/admin/actuality-photo/edit/1');
        $form = $crawler->selectButton('Uložit')->form(['photo[description]' => 'Nový popis']);
        $file = $form['photo[image]'];
        \assert($file instanceof \Symfony\Component\DomCrawler\Field\FileFormField);
        $file->upload($this->image()->getPathname());
        $this->client->submit($form);
        self::assertResponseRedirects();

        $p = $this->reload(ActualityPhoto::class, 1);
        self::assertSame('Nový popis', $p->description);
        self::assertNotNull($p->photo);
        self::assertNotSame(3, $p->photo->getId());
        self::assertFileExists(TestDatabase::publicDir().'/img/actuality/1/3.jpg', 'the replaced original is not deleted');
    }

    public function testInstructorAndLinkCrud(): void
    {
        $crawler = $this->client->request('GET', '/admin/instructor/edit/2');
        $this->client->submit($crawler->selectButton('Uložit')->form(['instructor[name]' => 'Jiří Valenta ml.']));
        self::assertSame('Jiří Valenta ml.', $this->reload(Instructor::class, 2)->name);

        $crawler = $this->client->request('GET', '/admin/link/add');
        $this->client->submit($crawler->selectButton('Přidat')->form([
            'link[name]' => 'Divers Alert Network',
            'link[url]' => 'https://www.daneurope.org',
        ]));
        self::assertResponseRedirects();
        self::assertInstanceOf(Link::class, $this->em()->getRepository(Link::class)->findOneBy(['name' => 'Divers Alert Network']));
    }

    public function testDiscussionBanAndUnban(): void
    {
        $this->client->request('GET', '/admin/discussion');
        $token = $this->csrf();
        $this->client->request('POST', '/admin/discussion/2/ban', ['_token' => $token]);
        self::assertResponseRedirects('/admin/discussion');
        $ban = $this->em()->getRepository(DiscussionBan::class)->findOneBy(['ip' => '192.0.2.66', 'deleted' => false]);
        self::assertInstanceOf(DiscussionBan::class, $ban);
        self::assertSame('Spammer - spam@example.invalid', $ban->reason);

        $this->client->request('POST', '/admin/discussion/unban/'.$ban->getId(), ['_token' => $token]);
        self::assertTrue($this->reload(DiscussionBan::class, (int) $ban->getId())->deleted);
    }

    public function testPendingFilterShowsHeldPostsAndApprovePublishesThem(): void
    {
        $this->client->request('GET', '/admin');
        self::assertSelectorTextContains('main', 'čeká 1 příspěvek na schválení');

        $crawler = $this->client->request('GET', '/admin/discussion?stav=ke-schvaleni');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Čekatel');
        self::assertSelectorTextNotContains('table', 'Potápěč', 'only held posts are listed');
        $token = (string) $crawler->filter('input[name=_token]')->first()->attr('value');

        $this->client->request('GET', '/diskuze');
        self::assertSelectorTextNotContains('main', 'Held for moderation');

        $this->client->request('POST', '/admin/discussion/3/activate', ['_token' => $token]);
        self::assertResponseRedirects();
        self::assertTrue($this->reload(Discussion::class, 3)->active);

        $this->client->request('GET', '/admin/discussion?stav=ke-schvaleni');
        self::assertSelectorTextContains('main', 'Žádné příspěvky nečekají na schválení.');
        $this->client->request('GET', '/diskuze');
        self::assertSelectorTextContains('main', 'Held for moderation');
    }

    public function testDiscussionEdit(): void
    {
        $crawler = $this->client->request('GET', '/admin/discussion/edit/1');
        $this->client->submit($crawler->selectButton('Uložit')->form(['discussion_post[text]' => 'Upraveno adminem']));
        self::assertSame('Upraveno adminem', $this->reload(Discussion::class, 1)->text);
    }

    public function testContactSaveAppendsAVersion(): void
    {
        $before = $this->em()->getRepository(Contact::class)->count([]);
        $crawler = $this->client->request('GET', '/admin/contact');
        $this->client->submit($crawler->selectButton('Uložit')->form(['contact[text]' => '<p>Nový kontakt</p>']));
        self::assertResponseRedirects('/admin/contact');
        self::assertSame($before + 1, $this->em()->getRepository(Contact::class)->count([]));

        $this->client->request('GET', '/kontakt');
        self::assertSelectorTextContains('main', 'Nový kontakt');
    }

    public function testEditorImageUpload(): void
    {
        $crawler = $this->client->request('GET', '/admin/actuality/add');
        $csrf = (string) $crawler->filter('body')->attr('data-editor-csrf');

        $this->client->request('POST', '/admin/editor/upload', [], ['upload' => $this->image()], ['HTTP_X_CSRF_TOKEN' => 'forged']);
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/admin/editor/upload', [], ['upload' => $this->image()], ['HTTP_X_CSRF_TOKEN' => $csrf]);
        self::assertResponseIsSuccessful();
        $url = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR)['url'];
        self::assertMatchesRegularExpression('#^/upload/editor/\d{4}/\d{2}/[0-9a-f]{16}\.jpg$#', $url);
        self::assertFileExists(TestDatabase::publicDir().$url);
    }
}

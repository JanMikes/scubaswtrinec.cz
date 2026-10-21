<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\TestDatabase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Existing admins must keep their passwords: the 2014 app stored md5('scuba' . $password . 'sw').
 * User 1 of the seed carries exactly such a hash (the same scheme as all 4 production accounts).
 */
final class AdminAuthTest extends WebTestCase
{
    private function login(KernelBrowser $client, string $email, string $password, bool $remember = false): void
    {
        $crawler = $client->request('GET', '/admin/sign/in');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Přihlásit se')->form([
            'email' => $email,
            'password' => $password,
        ]);
        if ($remember) {
            $checkbox = $form['remember'];
            \assert($checkbox instanceof \Symfony\Component\DomCrawler\Field\ChoiceFormField);
            $checkbox->tick();
        }
        // Stateless CSRF checks the Origin header, which every browser sends on a form POST.
        $client->submit($form, [], ['HTTP_ORIGIN' => 'http://localhost']);
    }

    private function user(int $id): User
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->find(User::class, $id);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    public function testLegacyMd5HashLogsInAndIsTransparentlyUpgraded(): void
    {
        $client = self::createClient();
        self::assertSame(md5('scuba'.TestDatabase::LEGACY_PASSWORD.'sw'), $this->user(1)->password, 'precondition: seed holds a legacy hash');

        $this->login($client, TestDatabase::ADMIN_EMAIL, TestDatabase::LEGACY_PASSWORD);
        self::assertResponseRedirects('/admin');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Testovací Admin');

        $upgraded = $this->user(1)->password;
        self::assertNotNull($upgraded);
        self::assertStringStartsWith('$2y$', $upgraded, 'the legacy hash is replaced by a bcrypt/argon hash');
        self::assertTrue(password_verify(TestDatabase::LEGACY_PASSWORD, $upgraded));

        // The same password keeps working with the upgraded hash.
        $client->request('GET', '/admin/sign/out');
        $this->login($client, TestDatabase::ADMIN_EMAIL, TestDatabase::LEGACY_PASSWORD);
        self::assertResponseRedirects('/admin');
        self::assertSame($upgraded, $this->user(1)->password, 'a modern hash is not re-hashed again');
    }

    public function testEmailIsCaseInsensitiveLikeTheOldMysqlLookup(): void
    {
        $client = self::createClient();
        $this->login($client, 'ADMIN@Example.Invalid', TestDatabase::LEGACY_PASSWORD);
        self::assertResponseRedirects('/admin');
    }

    public function testWrongPasswordIsRejectedAndHashIsUntouched(): void
    {
        $client = self::createClient();
        $this->login($client, TestDatabase::ADMIN_EMAIL, 'wrong-password');
        self::assertResponseRedirects('/admin/sign/in');
        $client->followRedirect();
        self::assertSelectorTextContains('[role=alert]', 'Byly zadány neplatné přihlašovací údaje!');
        self::assertSame(md5('scuba'.TestDatabase::LEGACY_PASSWORD.'sw'), $this->user(1)->password);
    }

    public function testSoftDeletedUserCannotLogIn(): void
    {
        $client = self::createClient();
        $this->login($client, 'deleted@example.invalid', TestDatabase::LEGACY_PASSWORD);
        self::assertResponseRedirects('/admin/sign/in');
    }

    public function testAnonymousIsSentToSignInAndBackAfterLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/admin/gallery');
        self::assertResponseRedirects('/admin/sign/in');
        $this->login($client, TestDatabase::ADMIN_EMAIL, TestDatabase::LEGACY_PASSWORD);
        self::assertResponseRedirects('/admin/gallery');
    }

    public function testRememberMeSetsLongLivedCookie(): void
    {
        $client = self::createClient();
        $this->login($client, TestDatabase::ADMIN_EMAIL, TestDatabase::LEGACY_PASSWORD, remember: true);
        $cookie = $client->getCookieJar()->get('REMEMBERME', '/admin');
        self::assertNotNull($cookie);
        self::assertGreaterThan(time() + 13 * 86400, (int) $cookie->getExpiresTime());
    }

    public function testChangePassword(): void
    {
        $client = self::createClient();
        $client->loginUser($this->user(1));
        $crawler = $client->request('GET', '/admin/user/change-password');
        $form = $crawler->selectButton('Změnit heslo')->form([
            'change_password[old_password]' => 'not-the-password',
            'change_password[new_password][first]' => 'Nove-heslo-2026',
            'change_password[new_password][second]' => 'Nove-heslo-2026',
        ]);
        $client->submit($form);
        self::assertSelectorTextContains('body', 'Bylo zadáno chybné staré heslo!');

        $form = $client->getCrawler()->selectButton('Změnit heslo')->form([
            'change_password[old_password]' => TestDatabase::LEGACY_PASSWORD,
            'change_password[new_password][first]' => 'Nove-heslo-2026',
            'change_password[new_password][second]' => 'Nove-heslo-2026',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/admin/user/change-password');
        self::assertTrue(password_verify('Nove-heslo-2026', (string) $this->user(1)->password));
    }

    public function testLogout(): void
    {
        $client = self::createClient();
        $client->loginUser($this->user(1));
        $client->request('GET', '/admin/sign/out');
        self::assertResponseRedirects('/admin/sign/in');
        $client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/sign/in');
    }
}

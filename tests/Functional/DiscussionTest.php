<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Controller\DiscussionController;
use App\Entity\Discussion;
use App\Service\FormTimeToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DiscussionTest extends WebTestCase
{
    /** @param array<string, string> $overrides */
    private function submit(KernelBrowser $client, string $ip, array $overrides = [], ?int $renderedAgo = 10): void
    {
        $crawler = $client->request('GET', '/diskuze', server: ['REMOTE_ADDR' => $ip]);
        $form = $crawler->selectButton('Odeslat příspěvek')->form();
        $token = self::getContainer()->get(FormTimeToken::class);
        $values = [
            'discussion_post[author]' => 'Nový potápěč',
            'discussion_post[email]' => 'novy@example.invalid',
            'discussion_post[subject]' => 'Trénink',
            'discussion_post[text]' => 'Ahoj, kdy se plave? 🤿',
            'discussion_post[ts]' => null === $renderedAgo ? 'garbage' : $token->issue(time() - $renderedAgo),
        ];
        $client->submit($form, array_merge($values, $overrides), ['REMOTE_ADDR' => $ip]);
    }

    public function testValidPostIsPublishedWithIpAndUserAgent(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.7');
        self::assertResponseRedirects('/diskuze');
        $client->followRedirect();
        self::assertSelectorTextContains('main', 'Vaše zpráva byla úspěšně odeslána!');
        self::assertSelectorTextContains('main', 'Ahoj, kdy se plave? 🤿');

        $post = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Discussion::class)->findOneBy(['author' => 'Nový potápěč']);
        self::assertInstanceOf(Discussion::class, $post);
        self::assertSame('198.51.100.7', $post->ip);
        self::assertTrue($post->active);
        self::assertNotEmpty($post->additionalInfo);
    }

    public function testRequiredFieldsAreValidated(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.8', ['discussion_post[author]' => '', 'discussion_post[email]' => 'not-an-email']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', 'Jméno je povinné!');
        self::assertSelectorTextContains('main', 'E-mail musí být zadán ve správném formátu!');
    }

    public function testHoneypotRejectsBots(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.9', ['discussion_post[website]' => 'http://spam.example']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', DiscussionController::GENERIC_ERROR);
        $this->assertNotStored('Ahoj, kdy se plave? 🤿');
    }

    public function testTooFastOrForgedSubmissionIsRejected(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.10', renderedAgo: 1);
        self::assertResponseStatusCodeSame(422);
        $this->submit($client, '198.51.100.10', renderedAgo: 7 * 3600);
        self::assertResponseStatusCodeSame(422);
        $this->submit($client, '198.51.100.10', renderedAgo: null);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', DiscussionController::GENERIC_ERROR);
        $this->assertNotStored('Ahoj, kdy se plave? 🤿');
    }

    public function testBannedIpCannotPost(): void
    {
        $client = self::createClient();
        $this->submit($client, '192.0.2.99');
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', DiscussionController::GENERIC_ERROR);
        $this->assertNotStored('Ahoj, kdy se plave? 🤿');
    }

    public function testRateLimitPerIp(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        for ($i = 0; $i < 3; ++$i) {
            $this->submit($client, '198.51.100.11', ['discussion_post[text]' => 'Zpráva '.$i]);
            self::assertResponseRedirects('/diskuze');
        }
        $this->submit($client, '198.51.100.11', ['discussion_post[text]' => 'Čtvrtá zpráva']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', DiscussionController::GENERIC_ERROR);
        $this->assertNotStored('Čtvrtá zpráva');
    }

    /** The real 2026 bot shape: every field a random mixed-case letter run. */
    public function testRandomLetterBotPostIsRejectedAndNotStored(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.12', [
            'discussion_post[author]' => 'fwvrdrrZbkLadZRJVJmJtPAI',
            'discussion_post[subject]' => 'XtxWVNguknNHjqiMUm',
            'discussion_post[text]' => 'rBbLvmlBHVDkVfOTuODJJFw',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', DiscussionController::GENERIC_ERROR);
        $this->assertNotStored('rBbLvmlBHVDkVfOTuODJJFw');
    }

    public function testSuspiciousPostIsStoredHiddenForModeration(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.13', [
            'discussion_post[text]' => 'Koukněte https://a.example https://b.example https://c.example',
        ]);
        self::assertResponseRedirects('/diskuze');
        $client->followRedirect();
        self::assertSelectorTextContains('main', DiscussionController::PENDING_NOTICE);
        self::assertSelectorTextNotContains('main', 'https://c.example');

        $post = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Discussion::class)->findOneBy(['ip' => '198.51.100.13']);
        self::assertInstanceOf(Discussion::class, $post);
        self::assertFalse($post->active, 'held hidden for moderation');
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.14', ['discussion_post[_token]' => 'forged']);
        self::assertResponseStatusCodeSame(422);
        $this->assertNotStored('Ahoj, kdy se plave? 🤿');
    }

    public function testPostedTextIsEscapedAndEmailIsNeverShown(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.15', [
            'discussion_post[author]' => 'Petr <3 >',
            'discussion_post[text]' => 'Ahoj "všichni" & <blink>kurzíva</blink>',
            'discussion_post[email]' => 'skryty@example.invalid',
        ]);
        self::assertResponseRedirects('/diskuze');
        $client->followRedirect();
        $html = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Petr &lt;3 &gt;', $html);
        self::assertStringContainsString('&lt;blink&gt;kurzíva&lt;/blink&gt;', $html);
        self::assertStringNotContainsString('<blink>', $html);
        self::assertStringNotContainsString('skryty@example.invalid', $html);
        self::assertStringNotContainsString('diver@example.invalid', $html, 'seeded e-mails are not published either');
    }

    /** The real client IP comes from Traefik's X-Forwarded-For (the proxy itself is on a private range). */
    public function testRealClientIpIsTakenFromTrustedProxyHeader(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/diskuze', server: ['REMOTE_ADDR' => '172.18.0.5', 'HTTP_X_FORWARDED_FOR' => '203.0.113.77']);
        $form = $crawler->selectButton('Odeslat příspěvek')->form();
        $client->submit($form, [
            'discussion_post[author]' => 'Za proxy',
            'discussion_post[email]' => 'proxy@example.invalid',
            'discussion_post[text]' => 'Test skutečné IP adresy',
            'discussion_post[ts]' => self::getContainer()->get(FormTimeToken::class)->issue(time() - 10),
        ], ['REMOTE_ADDR' => '172.18.0.5', 'HTTP_X_FORWARDED_FOR' => '203.0.113.77']);
        self::assertResponseRedirects('/diskuze');
        $post = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Discussion::class)->findOneBy(['author' => 'Za proxy']);
        self::assertInstanceOf(Discussion::class, $post);
        self::assertSame('203.0.113.77', $post->ip);
    }

    /** A spoofed X-Forwarded-For from a public (untrusted) peer is ignored. */
    public function testForwardedHeaderFromUntrustedPeerIsIgnored(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/diskuze', server: ['REMOTE_ADDR' => '198.51.100.16', 'HTTP_X_FORWARDED_FOR' => '192.0.2.99']);
        $form = $crawler->selectButton('Odeslat příspěvek')->form();
        $client->submit($form, [
            'discussion_post[author]' => 'Podvrh',
            'discussion_post[email]' => 'spoof@example.invalid',
            'discussion_post[text]' => 'Pokus obejít blokaci',
            'discussion_post[ts]' => self::getContainer()->get(FormTimeToken::class)->issue(time() - 10),
        ], ['REMOTE_ADDR' => '198.51.100.16', 'HTTP_X_FORWARDED_FOR' => '192.0.2.99']);
        self::assertResponseRedirects('/diskuze', message: 'the banned IP in the spoofed header must not apply');
        $post = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Discussion::class)->findOneBy(['author' => 'Podvrh']);
        self::assertInstanceOf(Discussion::class, $post);
        self::assertSame('198.51.100.16', $post->ip);
    }

    private function assertNotStored(string $text): void
    {
        self::assertNull(self::getContainer()->get(EntityManagerInterface::class)->getRepository(Discussion::class)->findOneBy(['text' => $text]));
    }
}

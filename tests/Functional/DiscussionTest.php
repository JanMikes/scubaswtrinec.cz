<?php

declare(strict_types=1);

namespace App\Tests\Functional;

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
        self::assertSelectorTextContains('main', 'Byl detekován pokus o spam.');
    }

    public function testTooFastOrForgedSubmissionIsRejected(): void
    {
        $client = self::createClient();
        $this->submit($client, '198.51.100.10', renderedAgo: 1);
        self::assertResponseStatusCodeSame(422);
        $this->submit($client, '198.51.100.10', renderedAgo: null);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', 'Byl detekován pokus o spam.');
    }

    public function testBannedIpCannotPost(): void
    {
        $client = self::createClient();
        $this->submit($client, '192.0.2.99');
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('main', 'V diskuzi již nemůžete přispívat!');
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
        self::assertSelectorTextContains('main', 'Příliš mnoho příspěvků');
    }
}

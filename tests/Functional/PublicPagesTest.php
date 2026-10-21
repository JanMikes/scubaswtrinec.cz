<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicPagesTest extends WebTestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function pages(): iterable
    {
        yield 'home (aktuality)' => ['/', 'Aktuality'];
        yield 'home page 2' => ['/?page=2', 'Šestá aktualita'];
        yield 'home page clamped' => ['/?page=999', 'Šestá aktualita'];
        yield 'clanky' => ['/clanky', 'Sidemount diving'];
        yield 'galerie' => ['/galerie', 'Svobodné Heřmanice únor 2019'];
        yield 'instruktori' => ['/instruktori', 'Mgr. Roman Kudela'];
        yield 'diskuze' => ['/diskuze', 'Kdy je další trénink?'];
        yield 'odkazy' => ['/odkazy', 'Jezerní důl'];
        yield 'kontakt (newest row)' => ['/kontakt', 'Klub založen v roce 1986'];
        yield 'detail aktuality' => ['/detail-aktuality/1', 'Potápění pod ledem'];
        yield 'detail clanku' => ['/detail-clanku/1', 'Článek o sidemountu.'];
        yield 'detail galerie' => ['/detail-galerie/1', 'Ponor pod ledem'];
    }

    #[DataProvider('pages')]
    public function testPageRenders(string $url, string $expectedText): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $expectedText);
        self::assertSelectorExists('html[lang="cs"]');
        self::assertSelectorTextContains('title', 'SCUBASW');
    }

    public function testListsHideInactiveAndDeletedRecords(): void
    {
        $client = self::createClient();
        $client->request('GET', '/?page=2');
        self::assertSelectorTextNotContains('main', 'Neaktivní aktualita');
        self::assertSelectorTextNotContains('main', 'Smazaná aktualita');

        $client->request('GET', '/galerie');
        self::assertSelectorTextNotContains('main', 'Skrytá galerie');
    }

    public function testHomeShowsFivePerPageNewestFirst(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/');
        $titles = $crawler->filter('#aktuality article h2')->each(static fn ($n): string => $n->text());
        self::assertSame(['Potápění pod ledem', 'Gruner see', 'Velké Košariská', 'Historie potápění 1', 'Historie potápění 2'], $titles);
    }

    public function testRichTextIsRenderedRawIncludingYoutubeIframes(): void
    {
        $client = self::createClient();
        $client->request('GET', '/detail-aktuality/1');
        self::assertSelectorExists('.prose-dive iframe[src="https://www.youtube.com/embed/abc123"]');
        self::assertSelectorTextContains('.prose-dive', '🧊');
    }

    public function testMissingOriginalRendersPlaceholderNotBrokenImage(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/detail-galerie/1');
        // photo 1 exists on disk -> lightbox link + thumbnail; photo 2 is missing -> placeholder
        self::assertCount(1, $crawler->filter('a.glightbox'));
        self::assertGreaterThanOrEqual(1, $crawler->filter('.photo-placeholder')->count());
        self::assertStringNotContainsString('placehold.it', (string) $client->getResponse()->getContent());
        self::assertSame('/img/gallery/1/card/1.jpg', $crawler->filter('a.glightbox img')->attr('src'));
        self::assertFileExists(\App\Tests\TestDatabase::publicDir().'/img/gallery/1/card/1.jpg');
    }

    /** @return iterable<string, array{string, string}> */
    public static function detailRedirects(): iterable
    {
        yield 'inactive aktualita' => ['/detail-aktuality/7', '/'];
        yield 'deleted aktualita' => ['/detail-aktuality/8', '/'];
        yield 'missing aktualita' => ['/detail-aktuality/99999', '/'];
        yield 'aktualita without id' => ['/detail-aktuality', '/'];
        yield 'inactive clanek' => ['/detail-clanku/2', '/clanky'];
        yield 'clanek without id' => ['/detail-clanku', '/clanky'];
        yield 'inactive galerie' => ['/detail-galerie/3', '/galerie'];
        yield 'galerie without id' => ['/detail-galerie', '/galerie'];
    }

    /** 2014 behaviour: a missing/inactive detail 302-redirected to its list. */
    #[DataProvider('detailRedirects')]
    public function testDetailOfMissingRecordRedirectsToList(string $url, string $target): void
    {
        $client = self::createClient();
        $client->request('GET', $url);
        self::assertResponseRedirects($target, 302);
    }

    /** @return iterable<string, array{string, string}> */
    public static function legacyUrls(): iterable
    {
        yield 'aktuality' => ['/aktuality', '/'];
        yield 'aktuality page' => ['/aktuality?page=2', '/?page=2'];
        yield 'default demo page' => ['/default', '/'];
        yield 'trailing slash' => ['/clanky/', '/clanky'];
        yield 'upper case' => ['/KONTAKT', '/kontakt'];
        yield 'list with id' => ['/kontakt/5', '/kontakt'];
        yield 'galerie with id' => ['/galerie/3', '/galerie'];
        yield 'camel case detail' => ['/detailAktuality/1', '/detail-aktuality/1'];
        yield 'camel case galerie' => ['/detailGalerie/1', '/detail-galerie/1'];
        yield 'detail trailing slash' => ['/detail-clanku/1/', '/detail-clanku/1'];
        yield 'admin trailing slash' => ['/admin/sign/', '/admin/sign'];
    }

    #[DataProvider('legacyUrls')]
    public function testLegacyUrlRedirectsPermanently(string $url, string $target): void
    {
        $client = self::createClient();
        $client->request('GET', $url);
        self::assertResponseRedirects($target, 301);
    }

    public function testUnknownPageIs404(): void
    {
        $client = self::createClient();
        $client->request('GET', '/tohle-neexistuje');
        self::assertResponseStatusCodeSame(404);
    }

    public function testSitemapListsVisibleDetails(): void
    {
        $client = self::createClient();
        $client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $xml = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('/detail-aktuality/1</loc>', $xml);
        self::assertStringContainsString('/detail-galerie/1</loc>', $xml);
        self::assertStringNotContainsString('/detail-aktuality/7<', $xml);
    }

    public function testHealthChecks(): void
    {
        $client = self::createClient();
        $client->request('GET', '/-/health-check/liveness');
        self::assertResponseIsSuccessful();
        $client->request('GET', '/-/health-check/readiness');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('"database":"ok"', (string) $client->getResponse()->getContent());
    }
}

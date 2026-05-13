<?php

declare(strict_types=1);

namespace Artopia_Gallery\Tests\Unit;

use Artopia_Gallery\Shortcodes;
use PHPUnit\Framework\TestCase;
use WP_Term;

final class TestableShortcodes extends Shortcodes
{
    public function callResolveGalleryTerm(string $gallerySlug, int $artistId): ?WP_Term
    {
        return $this->resolve_gallery_term($gallerySlug, $artistId);
    }

    public function callBuildGalleryTaxQuery(string $gallerySlug, int $artistId): array
    {
        return $this->build_gallery_tax_query($gallerySlug, $artistId);
    }
}

final class ShortcodesTest extends TestCase
{
    private TestableShortcodes $shortcodes;

    protected function setUp(): void
    {
        $this->shortcodes = new TestableShortcodes();

        $GLOBALS['artopia_test_term_meta'] = [];
        $GLOBALS['artopia_test_terms'] = [];
        $GLOBALS['artopia_test_next_term_id'] = 1000;
    }

    private function makeTerm(int $termId, string $name, string $slug): WP_Term
    {
        return new WP_Term((object) [
            'term_id' => $termId,
            'name' => $name,
            'slug' => $slug,
        ]);
    }

    private function setTerms(array $terms): void
    {
        $GLOBALS['artopia_test_terms'] = $terms;
    }

    private function setTermMeta(int $termId, string $key, $value): void
    {
        $GLOBALS['artopia_test_term_meta'][$termId][$key] = $value;
    }

    public function testResolveGalleryTermReturnsOwnedTermWhenArtistScopedMatchExists(): void
    {
        $owned = $this->makeTerm(10, 'Landscapes', 'landscapes');
        $other = $this->makeTerm(11, 'Landscapes', 'landscapes-2');

        $this->setTerms([$owned, $other]);
        $this->setTermMeta(10, '_artopia_artist_id', 12);
        $this->setTermMeta(11, '_artopia_artist_id', 99);

        $term = $this->shortcodes->callResolveGalleryTerm('landscapes', 12);

        self::assertInstanceOf(WP_Term::class, $term);
        self::assertSame(10, $term->term_id);
    }

    public function testResolveGalleryTermFallsBackToGlobalSlugMatchWhenArtistOwnedTermMissing(): void
    {
        $legacy = $this->makeTerm(20, 'Landscapes', 'landscapes');

        $this->setTerms([$legacy]);

        $term = $this->shortcodes->callResolveGalleryTerm('landscapes', 12);

        self::assertInstanceOf(WP_Term::class, $term);
        self::assertSame(20, $term->term_id);
    }

    public function testResolveGalleryTermReturnsGlobalSlugMatchWhenNoArtistProvided(): void
    {
        $legacy = $this->makeTerm(30, 'Landscapes', 'landscapes');

        $this->setTerms([$legacy]);

        $term = $this->shortcodes->callResolveGalleryTerm('landscapes', 0);

        self::assertInstanceOf(WP_Term::class, $term);
        self::assertSame(30, $term->term_id);
    }

    public function testResolveGalleryTermReturnsNullWhenGallerySlugIsEmpty(): void
    {
        self::assertNull($this->shortcodes->callResolveGalleryTerm('', 12));
    }

    public function testBuildGalleryTaxQueryUsesResolvedTermIdWhenOwnedGalleryIsFound(): void
    {
        $owned = $this->makeTerm(40, 'Landscapes', 'landscapes');

        $this->setTerms([$owned]);
        $this->setTermMeta(40, '_artopia_artist_id', 12);

        $taxQuery = $this->shortcodes->callBuildGalleryTaxQuery('landscapes', 12);

        self::assertSame(
            [
                [
                    'taxonomy' => 'gallery',
                    'field' => 'term_id',
                    'terms' => [40],
                ],
            ],
            $taxQuery
        );
    }

    public function testBuildGalleryTaxQueryFallsBackToSlugWhenNoOwnedGalleryFound(): void
    {
        $legacy = $this->makeTerm(50, 'Landscapes', 'landscapes');

        $this->setTerms([$legacy]);

        $taxQuery = $this->shortcodes->callBuildGalleryTaxQuery('landscapes', 12);

        self::assertSame(
            [
                [
                    'taxonomy' => 'gallery',
                    'field' => 'term_id',
                    'terms' => [50],
                ],
            ],
            $taxQuery
        );
    }

    public function testBuildGalleryTaxQueryUsesSlugFallbackWhenNoArtistProvided(): void
    {
        $legacy = $this->makeTerm(60, 'Landscapes', 'landscapes');

        $this->setTerms([$legacy]);

        $taxQuery = $this->shortcodes->callBuildGalleryTaxQuery('landscapes', 0);

        self::assertSame(
            [
                [
                    'taxonomy' => 'gallery',
                    'field' => 'term_id',
                    'terms' => [60],
                ],
            ],
            $taxQuery
        );
    }

    public function testBuildGalleryTaxQueryReturnsEmptyArrayWhenGallerySlugIsEmpty(): void
    {
        self::assertSame([], $this->shortcodes->callBuildGalleryTaxQuery('', 12));
    }
}

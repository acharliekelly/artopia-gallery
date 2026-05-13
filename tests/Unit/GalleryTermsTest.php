<?php

declare(strict_types=1);

namespace Artopia_Gallery\Tests\Unit;

use Artopia_Gallery\Gallery_Terms;
use PHPUnit\Framework\TestCase;
use WP_Term;

final class GalleryTermsTest extends TestCase
{
    private Gallery_Terms $galleryTerms;

    protected function setUp(): void
    {
        $this->galleryTerms = new Gallery_Terms();
        $this->resetTermStore();
    }

    private function resetTermStore(): void
    {
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

    private function setTermMeta(int $termId, string $key, $value): void
    {
        $GLOBALS['artopia_test_term_meta'][$termId][$key] = $value;
    }

    /**
     * @param array<int, WP_Term> $terms
     */
    private function setTerms(array $terms): void
    {
        $GLOBALS['artopia_test_terms'] = $terms;
    }

    private function getTermMetaStore(): array
    {
        /** @disregard */
        return $GLOBALS['artopia_test_term_meta'];
    }

    /**
     * @return array<int, WP_Term>
     */
    private function getTermsStore(): array
    {
        /** @disregard */
        return $GLOBALS['artopia_test_terms'];
    }

    public function testArtistMetaKeyReturnsExpectedConstant(): void
    {
        self::assertSame('_artopia_artist_id', Gallery_Terms::artist_meta_key());
    }

    public function testGetArtistIdForTermReturnsNormalizedValue(): void
    {
        $this->setTermMeta(15, Gallery_Terms::artist_meta_key(), '12');

        self::assertSame(12, $this->galleryTerms->get_artist_id_for_term(15));
    }

    public function testGetArtistIdForTermReturnsZeroWhenMissing(): void
    {
        self::assertSame(0, $this->galleryTerms->get_artist_id_for_term(99));
    }

    public function testTermBelongsToArtistReturnsTrueForMatchingOwner(): void
    {
        $this->setTermMeta(20, Gallery_Terms::artist_meta_key(), '8');

        self::assertTrue($this->galleryTerms->term_belongs_to_artist(20, 8));
    }

    public function testTermBelongsToArtistReturnsFalseForDifferentOwner(): void
    {
        $this->setTermMeta(20, Gallery_Terms::artist_meta_key(), '8');

        self::assertFalse($this->galleryTerms->term_belongs_to_artist(20, 9));
    }

    public function testTermBelongsToArtistReturnsFalseForMissingOwner(): void
    {
        self::assertFalse($this->galleryTerms->term_belongs_to_artist(20, 8));
    }

    public function testTermMatchesArtistAndNameRequiresMatchingOwnerAndName(): void
    {
        $term = $this->makeTerm(31, 'Landscapes', 'landscapes');
        $this->setTermMeta(31, Gallery_Terms::artist_meta_key(), '12');

        self::assertTrue(
            $this->galleryTerms->term_matches_artist_and_name($term, 12, ' landscapes ')
        );
    }

    public function testTermMatchesArtistAndNameReturnsFalseForDifferentArtist(): void
    {
        $term = $this->makeTerm(31, 'Landscapes', 'landscapes');
        $this->setTermMeta(31, Gallery_Terms::artist_meta_key(), '12');

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_name($term, 99, 'Landscapes')
        );
    }

    public function testTermMatchesArtistAndNameReturnsFalseForDifferentName(): void
    {
        $term = $this->makeTerm(31, 'Landscapes', 'landscapes');
        $this->setTermMeta(31, Gallery_Terms::artist_meta_key(), '12');

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_name($term, 12, 'Portraits')
        );
    }

    public function testTermMatchesArtistAndSlugRequiresMatchingOwnerAndSlug(): void
    {
        $term = $this->makeTerm(44, 'Blue Hill', 'blue-hill');
        $this->setTermMeta(44, Gallery_Terms::artist_meta_key(), '7');

        self::assertTrue(
            $this->galleryTerms->term_matches_artist_and_slug($term, 7, 'Blue Hill')
        );
    }

    public function testTermMatchesArtistAndSlugReturnsFalseForDifferentArtist(): void
    {
        $term = $this->makeTerm(44, 'Blue Hill', 'blue-hill');
        $this->setTermMeta(44, Gallery_Terms::artist_meta_key(), '7');

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_slug($term, 99, 'Blue Hill')
        );
    }

    public function testTermMatchesArtistAndSlugReturnsFalseForDifferentSlug(): void
    {
        $term = $this->makeTerm(44, 'Blue Hill', 'blue-hill');
        $this->setTermMeta(44, Gallery_Terms::artist_meta_key(), '7');

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_slug($term, 7, 'Maine Woods')
        );
    }

    public function testFindByArtistAndNameReturnsMatchingOwnedTerm(): void
    {
        $owned = $this->makeTerm(51, 'Landscapes', 'landscapes');
        $other = $this->makeTerm(52, 'Landscapes', 'landscapes-2');

        $this->setTerms([$owned, $other]);
        $this->setTermMeta(51, Gallery_Terms::artist_meta_key(), 12);
        $this->setTermMeta(52, Gallery_Terms::artist_meta_key(), 99);

        $found = $this->galleryTerms->find_by_artist_and_name(12, 'Landscapes');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(51, $found->term_id);
    }

    public function testFindByArtistAndNameReturnsNullWhenOnlyDifferentArtistMatches(): void
    {
        $other = $this->makeTerm(52, 'Landscapes', 'landscapes-2');

        $this->setTerms([$other]);
        $this->setTermMeta(52, Gallery_Terms::artist_meta_key(), 99);

        self::assertNull($this->galleryTerms->find_by_artist_and_name(12, 'Landscapes'));
    }

    public function testFindByArtistAndSlugReturnsMatchingOwnedTerm(): void
    {
        $owned = $this->makeTerm(61, 'Blue Hill', 'blue-hill');
        $other = $this->makeTerm(62, 'Blue Hill', 'blue-hill-2');

        $this->setTerms([$owned, $other]);
        $this->setTermMeta(61, Gallery_Terms::artist_meta_key(), 7);
        $this->setTermMeta(62, Gallery_Terms::artist_meta_key(), 99);

        $found = $this->galleryTerms->find_by_artist_and_slug(7, 'Blue Hill');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(61, $found->term_id);
    }

    public function testGetOrCreateForArtistReturnsExistingOwnedTermId(): void
    {
        $owned = $this->makeTerm(71, 'Landscapes', 'landscapes');

        $this->setTerms([$owned]);
        $this->setTermMeta(71, Gallery_Terms::artist_meta_key(), 12);

        $termId = $this->galleryTerms->get_or_create_for_artist(12, 'Landscapes');

        self::assertSame(71, $termId);
        self::assertCount(1, $this->getTermsStore());
    }

    public function testGetOrCreateForArtistCreatesNewOwnedTermWhenMissing(): void
    {
        $termId = $this->galleryTerms->get_or_create_for_artist(12, 'Landscapes');

        self::assertSame(1000, $termId);
        self::assertCount(1, $this->getTermsStore());

        $termMeta = $this->getTermMetaStore();
        $terms = $this->getTermsStore();

        self::assertSame(12, $termMeta[1000][Gallery_Terms::artist_meta_key()]);
        self::assertSame('Landscapes', $terms[0]->name);
        self::assertSame('landscapes', $terms[0]->slug);
    }

    public function testGetOrCreateForArtistReturnsErrorForInvalidArtist(): void
    {
        $result = $this->galleryTerms->get_or_create_for_artist(0, 'Landscapes');

        self::assertInstanceOf(\WP_Error::class, $result);
        self::assertSame('artopia_invalid_artist', $result->get_error_code());
    }

    public function testGetOrCreateForArtistReturnsErrorForEmptyGalleryName(): void
    {
        $result = $this->galleryTerms->get_or_create_for_artist(12, '   ');

        self::assertInstanceOf(\WP_Error::class, $result);
        self::assertSame('artopia_invalid_gallery_name', $result->get_error_code());
    }

    public function testTermIsUnownedReturnsTrueWhenNoArtistMetaExists(): void
    {
        self::assertTrue($this->galleryTerms->term_is_unowned(88));
    }

    public function testTermIsUnownedReturnsFalseWhenArtistMetaExists(): void
    {
        $this->setTermMeta(88, Gallery_Terms::artist_meta_key(), 12);

        self::assertFalse($this->galleryTerms->term_is_unowned(88));
    }

    public function testFindLegacyUnownedByNameReturnsMatchingUnownedTerm(): void
    {
        $legacy = $this->makeTerm(90, 'Landscapes', 'landscapes');
        $owned = $this->makeTerm(91, 'Landscapes', 'landscapes-2');

        $this->setTerms([$legacy, $owned]);
        $this->setTermMeta(91, Gallery_Terms::artist_meta_key(), 12);

        $found = $this->galleryTerms->find_legacy_unowned_by_name('Landscapes');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(90, $found->term_id);
    }

    public function testFindLegacyUnownedBySlugReturnsMatchingUnownedTerm(): void
    {
        $legacy = $this->makeTerm(92, 'Blue Hill', 'blue-hill');
        $owned = $this->makeTerm(93, 'Blue Hill', 'blue-hill-2');

        $this->setTerms([$legacy, $owned]);
        $this->setTermMeta(93, Gallery_Terms::artist_meta_key(), 12);

        $found = $this->galleryTerms->find_legacy_unowned_by_slug('Blue Hill');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(92, $found->term_id);
    }

    public function testFindByArtistAndNameWithLegacyFallbackPrefersOwnedTerm(): void
    {
        $owned = $this->makeTerm(94, 'Landscapes', 'landscapes');
        $legacy = $this->makeTerm(95, 'Landscapes', 'landscapes-legacy');

        $this->setTerms([$legacy, $owned]);
        $this->setTermMeta(94, Gallery_Terms::artist_meta_key(), 12);

        $found = $this->galleryTerms->find_by_artist_and_name_with_legacy_fallback(12, 'Landscapes');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(94, $found->term_id);
    }

    public function testFindByArtistAndNameWithLegacyFallbackReturnsLegacyWhenOwnedTermMissing(): void
    {
        $legacy = $this->makeTerm(96, 'Landscapes', 'landscapes');

        $this->setTerms([$legacy]);

        $found = $this->galleryTerms->find_by_artist_and_name_with_legacy_fallback(12, 'Landscapes');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(96, $found->term_id);
    }


    public function testFindByArtistAndSlugWithLegacyFallbackPrefersOwnedTerm(): void
    {
        $owned = $this->makeTerm(97, 'Blue Hill', 'blue-hill');
        $legacy = $this->makeTerm(98, 'Blue Hill', 'blue-hill-legacy');

        $this->setTerms([$legacy, $owned]);
        $this->setTermMeta(97, Gallery_Terms::artist_meta_key(), 7);

        $found = $this->galleryTerms->find_by_artist_and_slug_with_legacy_fallback(7, 'Blue Hill');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(97, $found->term_id);
    }

    public function testFindByArtistAndSlugWithLegacyFallbackReturnsLegacyWhenOwnedTermMissing(): void
    {
        $legacy = $this->makeTerm(99, 'Blue Hill', 'blue-hill');

        $this->setTerms([$legacy]);

        $found = $this->galleryTerms->find_by_artist_and_slug_with_legacy_fallback(7, 'Blue Hill');

        self::assertInstanceOf(WP_Term::class, $found);
        self::assertSame(99, $found->term_id);
    }

    public function testGetOrCreateForArtistAdoptsLegacyUnownedTerm(): void
    {
        $legacy = $this->makeTerm(120, 'Landscapes', 'landscapes');

        $this->setTerms([$legacy]);

        $termId = $this->galleryTerms->get_or_create_for_artist(12, 'Landscapes');

        self::assertSame(120, $termId);

        $termMeta = $this->getTermMetaStore();
        self::assertSame(12, $termMeta[120][Gallery_Terms::artist_meta_key()]);
    }

}

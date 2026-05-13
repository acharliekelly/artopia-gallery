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
        $GLOBALS['artopia_test_term_meta'] = [];
    }

    private function makeTerm(int $termId, string $name, string $slug): WP_Term
    {
        return new WP_Term((object) [
            'term_id' => $termId,
            'name' => $name,
            'slug' => $slug,
        ]);
    }


    public function testArtistMetaKeyReturnsExpectedConstant(): void
    {
        self::assertSame('_artopia_artist_id', Gallery_Terms::artist_meta_key());
    }

    public function testGetArtistIdForTermReturnsNormalizedValue(): void
    {
        $GLOBALS['artopia_test_term_meta'][15][Gallery_Terms::artist_meta_key()] = '12';

        self::assertSame(12, $this->galleryTerms->get_artist_id_for_term(15));
    }

    public function testGetArtistIdForTermReturnsZeroWhenMissing(): void
    {
        self::assertSame(0, $this->galleryTerms->get_artist_id_for_term(99));
    }

    public function testTermBelongsToArtistReturnsTrueForMatchingOwner(): void
    {
        $GLOBALS['artopia_test_term_meta'][20][Gallery_Terms::artist_meta_key()] = '8';

        self::assertTrue($this->galleryTerms->term_belongs_to_artist(20, 8));
    }

    public function testTermBelongsToArtistReturnsFalseForDifferentOwner(): void
    {
        $GLOBALS['artopia_test_term_meta'][20][Gallery_Terms::artist_meta_key()] = '8';

        self::assertFalse($this->galleryTerms->term_belongs_to_artist(20, 9));
    }

    public function testTermBelongsToArtistReturnsFalseForMissingOwner(): void
    {
        self::assertFalse($this->galleryTerms->term_belongs_to_artist(20, 8));
    }

    public function testTermMatchesArtistAndNameRequiresMatchingOwnerAndName(): void
    {
        $term = $this->makeTerm(31, 'Landscapes', 'landscapes');
        $GLOBALS['artopia_test_term_meta'][31][Gallery_Terms::artist_meta_key()] = '12';

        self::assertTrue(
            $this->galleryTerms->term_matches_artist_and_name($term, 12, ' landscapes ')
        );
    }

    public function testTermMatchesArtistAndNameReturnsFalseForDifferentArtist(): void
    {
        $term = $this->makeTerm(31, 'Landscapes', 'landscapes');
        
        $GLOBALS['artopia_test_term_meta'][31][Gallery_Terms::artist_meta_key()] = '12';

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_name($term, 99, 'Landscapes')
        );
    }

    public function testTermMatchesArtistAndNameReturnsFalseForDifferentName(): void
    {
        $term = $this->makeTerm(31, 'Landscapes', 'landscapes');

        $GLOBALS['artopia_test_term_meta'][31][Gallery_Terms::artist_meta_key()] = '12';

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_name($term, 12, 'Portraits')
        );
    }

    public function testTermMatchesArtistAndSlugRequiresMatchingOwnerAndSlug(): void
    {
        $term = $this->makeTerm(44, 'Blue Hill', 'blue-hill');

        $GLOBALS['artopia_test_term_meta'][44][Gallery_Terms::artist_meta_key()] = '7';

        self::assertTrue(
            $this->galleryTerms->term_matches_artist_and_slug($term, 7, 'Blue Hill')
        );
    }

    public function testTermMatchesArtistAndSlugReturnsFalseForDifferentArtist(): void
    {
        $term = $this->makeTerm(44, 'Blue Hill', 'blue-hill');

        $GLOBALS['artopia_test_term_meta'][44][Gallery_Terms::artist_meta_key()] = '7';

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_slug($term, 99, 'Blue Hill')
        );
    }

    public function testTermMatchesArtistAndSlugReturnsFalseForDifferentSlug(): void
    {
        $term = $this->makeTerm(44, 'Blue Hill', 'blue-hill');

        $GLOBALS['artopia_test_term_meta'][44][Gallery_Terms::artist_meta_key()] = '7';

        self::assertFalse(
            $this->galleryTerms->term_matches_artist_and_slug($term, 7, 'Maine Woods')
        );
    }
}

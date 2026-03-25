<?php

namespace Drupal\Tests\socialfeed\Unit;

use Drupal\socialfeed\FacebookPageNormalizerTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FacebookPageNormalizerTrait.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\FacebookPageNormalizerTrait
 */
class FacebookPageNormalizerTraitTest extends UnitTestCase {

  /**
   * The test class using the trait.
   *
   * @var object
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->normalizer = new class() {

      use FacebookPageNormalizerTrait {
        normalizePageInput as public;
      }

    };
  }

  /**
   * Tests empty and null input.
   *
   * @covers ::normalizePageInput
   */
  public function testEmptyInput(): void {
    $this->assertEquals('', $this->normalizer->normalizePageInput(NULL));
    $this->assertEquals('', $this->normalizer->normalizePageInput(''));
    $this->assertEquals('', $this->normalizer->normalizePageInput('   '));
  }

  /**
   * Tests numeric page ID input.
   *
   * @covers ::normalizePageInput
   */
  public function testNumericId(): void {
    $this->assertEquals('123456789', $this->normalizer->normalizePageInput('123456789'));
  }

  /**
   * Tests simple slug input.
   *
   * @covers ::normalizePageInput
   */
  public function testSimpleSlug(): void {
    $this->assertEquals('testpage', $this->normalizer->normalizePageInput('testpage'));
  }

  /**
   * Tests Facebook URL input.
   *
   * @covers ::normalizePageInput
   */
  public function testFacebookUrl(): void {
    $this->assertEquals('testpage', $this->normalizer->normalizePageInput('https://www.facebook.com/testpage'));
    $this->assertEquals('testpage', $this->normalizer->normalizePageInput('https://facebook.com/testpage'));
    $this->assertEquals('testpage', $this->normalizer->normalizePageInput('facebook.com/testpage'));
  }

  /**
   * Tests fb.com short URL.
   *
   * @covers ::normalizePageInput
   */
  public function testFbShortUrl(): void {
    $this->assertEquals('testpage', $this->normalizer->normalizePageInput('https://fb.com/testpage'));
  }

  /**
   * Tests profile.php URL with numeric ID.
   *
   * @covers ::normalizePageInput
   */
  public function testProfilePhpUrl(): void {
    $this->assertEquals('123456', $this->normalizer->normalizePageInput('https://www.facebook.com/profile.php?id=123456'));
  }

}

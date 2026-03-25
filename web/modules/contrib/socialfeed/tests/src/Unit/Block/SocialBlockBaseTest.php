<?php

namespace Drupal\Tests\socialfeed\Unit\Block;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\socialfeed\Plugin\Block\SocialBlockBase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for SocialBlockBase.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Plugin\Block\SocialBlockBase
 */
class SocialBlockBaseTest extends UnitTestCase {

  /**
   * The config mock.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config = $this->createMock(ImmutableConfig::class);
  }

  /**
   * Creates a testable SocialBlockBase instance.
   *
   * @param array $configuration
   *   The block configuration.
   *
   * @return \Drupal\Tests\socialfeed\Unit\Block\TestSocialBlock
   *   A concrete test double of SocialBlockBase.
   */
  protected function createBlockInstance(array $configuration = []): TestSocialBlock {
    $block = new TestSocialBlock(
      $configuration,
      'test_block',
      ['provider' => 'socialfeed']
    );
    $block->setConfig($this->config);

    return $block;
  }

  /**
   * Tests getSetting() returns config value when override is not set.
   *
   * This is the key regression test for the "Undefined array key" bug.
   *
   * @covers ::getSetting
   */
  public function testGetSettingWithoutOverrideKey(): void {
    $block = $this->createBlockInstance([
      'provider' => 'socialfeed',
    ]);

    $this->config->expects($this->once())
      ->method('get')
      ->with('no_feeds')
      ->willReturn(10);

    $result = $block->getSetting('no_feeds');
    $this->assertEquals(10, $result);
  }

  /**
   * Tests getSetting() returns config value when override is FALSE.
   *
   * @covers ::getSetting
   */
  public function testGetSettingWithOverrideFalse(): void {
    $block = $this->createBlockInstance([
      'override' => FALSE,
      'provider' => 'socialfeed',
    ]);

    $this->config->expects($this->once())
      ->method('get')
      ->with('no_feeds')
      ->willReturn(5);

    $result = $block->getSetting('no_feeds');
    $this->assertEquals(5, $result);
  }

  /**
   * Tests getSetting() returns block setting when override is TRUE.
   *
   * @covers ::getSetting
   */
  public function testGetSettingWithOverrideTrue(): void {
    $block = $this->createBlockInstance([
      'override' => TRUE,
      'no_feeds' => 20,
      'provider' => 'socialfeed',
    ]);

    $this->config->expects($this->never())
      ->method('get');

    $result = $block->getSetting('no_feeds');
    $this->assertEquals(20, $result);
  }

  /**
   * Tests getSetting() with override set to 0 (falsy) uses config.
   *
   * @covers ::getSetting
   */
  public function testGetSettingWithOverrideZero(): void {
    $block = $this->createBlockInstance([
      'override' => 0,
      'no_feeds' => 99,
      'provider' => 'socialfeed',
    ]);

    $this->config->expects($this->once())
      ->method('get')
      ->with('no_feeds')
      ->willReturn(5);

    $result = $block->getSetting('no_feeds');
    $this->assertEquals(5, $result);
  }

}

/**
 * Concrete test double for the abstract SocialBlockBase.
 */
class TestSocialBlock extends SocialBlockBase {

  /**
   * Sets the config object.
   */
  public function setConfig(ImmutableConfig $config): void {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}

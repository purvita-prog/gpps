<?php

namespace Drupal\Tests\socialfeed\Unit\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\socialfeed\Plugin\Block\FacebookPostBlock;
use Drupal\socialfeed\Services\FacebookPostCollector;
use Drupal\socialfeed\Services\FacebookPostCollectorFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for FacebookPostBlock.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Plugin\Block\FacebookPostBlock
 */
class FacebookPostBlockTest extends UnitTestCase {

  /**
   * The Facebook factory mock.
   *
   * @var \Drupal\socialfeed\Services\FacebookPostCollectorFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $facebookFactory;

  /**
   * The Facebook collector mock.
   *
   * @var \Drupal\socialfeed\Services\FacebookPostCollector|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $facebookCollector;

  /**
   * The config mock.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The current user mock.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The logger mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The logger factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->facebookFactory = $this->createMock(FacebookPostCollectorFactory::class);
    $this->facebookCollector = $this->createMock(FacebookPostCollector::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->configFactory->method('get')
      ->with('socialfeed.facebook.settings')
      ->willReturn($this->config);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    // Set up container for $this->t() calls in the block.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Creates a FacebookPostBlock instance.
   *
   * @param array $configuration
   *   The block configuration.
   *
   * @return \Drupal\socialfeed\Plugin\Block\FacebookPostBlock
   *   The block instance.
   */
  protected function createBlock(array $configuration = []): FacebookPostBlock {
    return new FacebookPostBlock(
      $configuration,
      'facebook_post',
      ['provider' => 'socialfeed'],
      $this->facebookFactory,
      $this->configFactory,
      $this->currentUser,
      $this->loggerFactory
    );
  }

  /**
   * Tests build() without override uses global config credentials.
   *
   * This tests the fix for the "Undefined array key 'override'" warning
   * when using Block Field module with hidden configuration form.
   *
   * @covers ::build
   */
  public function testBuildWithoutOverrideUsesGlobalConfig(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['app_id', 'global_app_id'],
        ['secret_key', 'global_secret'],
        ['user_token', 'global_token'],
        ['page_name', 'global_page'],
        ['page_id', 'global_page_id'],
        ['all_types', TRUE],
        ['no_feeds', 10],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->facebookFactory->expects($this->once())
      ->method('createInstance')
      ->with('global_app_id', 'global_secret', 'global_token', 'global_page')
      ->willReturn($this->facebookCollector);

    $this->facebookCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([]);

    $build = $block->build();

    $this->assertIsArray($build);
    $this->assertArrayHasKey('posts', $build);
  }

  /**
   * Tests build() with override=TRUE uses block-level credentials.
   *
   * @covers ::build
   */
  public function testBuildWithOverrideUsesBlockConfig(): void {
    $block = $this->createBlock([
      'override' => TRUE,
      'app_id' => 'block_app_id',
      'secret_key' => 'block_secret',
      'user_token' => 'block_token',
      'page_name' => 'block_page',
      'all_types' => TRUE,
      'no_feeds' => 5,
    ]);

    $this->config->method('get')
      ->willReturnMap([
        ['page_id', 'some_page_id'],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->facebookFactory->expects($this->once())
      ->method('createInstance')
      ->with('block_app_id', 'block_secret', 'block_token', 'block_page')
      ->willReturn($this->facebookCollector);

    $this->facebookCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([]);

    $build = $block->build();

    $this->assertIsArray($build);
  }

  /**
   * Tests build() handles API exceptions gracefully.
   *
   * @covers ::build
   */
  public function testBuildHandlesException(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['app_id', 'app_id'],
        ['secret_key', 'secret'],
        ['user_token', 'token'],
        ['page_name', 'page'],
        ['page_id', ''],
      ]);

    $this->facebookFactory->expects($this->once())
      ->method('createInstance')
      ->willThrowException(new \Exception('API error'));

    $this->logger->expects($this->once())
      ->method('error');

    $build = $block->build();

    $this->assertIsArray($build);
    $this->assertEmpty($build['posts']['#items']);
  }

  /**
   * Tests build() renders posts with correct theme suggestions.
   *
   * @covers ::build
   */
  public function testBuildRendersPostsWithThemeSuggestions(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['app_id', 'app_id'],
        ['secret_key', 'secret'],
        ['user_token', 'token'],
        ['page_name', 'page'],
        ['page_id', 'page_id'],
        ['all_types', TRUE],
        ['no_feeds', 10],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn(['config:socialfeed.facebook.settings']);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->facebookFactory->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->facebookCollector);

    $this->facebookCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([
        [
          'status_type' => 'added_photos',
          'message' => 'Test post',
          'permalink_url' => 'https://www.facebook.com/TestPage/posts/123',
        ],
        [
          'message' => 'Post without status_type',
        ],
      ]);

    $build = $block->build();

    $this->assertCount(2, $build['posts']['#items']);
    $this->assertEquals(
      ['socialfeed_facebook_post__added_photos', 'socialfeed_facebook_post'],
      $build['posts']['#items'][0]['#theme']
    );
    $this->assertEquals(
      ['socialfeed_facebook_post__default', 'socialfeed_facebook_post'],
      $build['posts']['#items'][1]['#theme']
    );
  }

  /**
   * Tests build() passes permalink_url in the post render array.
   *
   * @covers ::build
   */
  public function testBuildPassesPermalinkUrl(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['app_id', 'app_id'],
        ['secret_key', 'secret'],
        ['user_token', 'token'],
        ['page_name', 'page'],
        ['page_id', 'page_id'],
        ['all_types', TRUE],
        ['no_feeds', 10],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->facebookFactory->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->facebookCollector);

    $permalink = 'https://www.facebook.com/TestPage/posts/987654321';
    $this->facebookCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([
        [
          'status_type' => 'added_photos',
          'message' => 'Test post with link',
          'permalink_url' => $permalink,
          'picture' => 'https://example.com/image.jpg',
        ],
      ]);

    $build = $block->build();

    $this->assertCount(1, $build['posts']['#items']);
    $post_data = $build['posts']['#items'][0]['#post'];
    $this->assertEquals($permalink, $post_data['permalink_url']);
  }

}

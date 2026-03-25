<?php

namespace Drupal\Tests\socialfeed\Unit\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\socialfeed\Plugin\Block\TwitterPostBlock;
use Drupal\socialfeed\Services\TwitterPostCollector;
use Drupal\socialfeed\Services\TwitterPostCollectorFactory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for TwitterPostBlock.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Plugin\Block\TwitterPostBlock
 */
class TwitterPostBlockTest extends UnitTestCase {

  /**
   * The Twitter factory mock.
   *
   * @var \Drupal\socialfeed\Services\TwitterPostCollectorFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $twitterFactory;

  /**
   * The Twitter collector mock.
   *
   * @var \Drupal\socialfeed\Services\TwitterPostCollector|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $twitterCollector;

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

    $this->twitterFactory = $this->createMock(TwitterPostCollectorFactory::class);
    $this->twitterCollector = $this->createMock(TwitterPostCollector::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->configFactory->method('get')
      ->with('socialfeed.twitter.settings')
      ->willReturn($this->config);

    // Set up logger factory for DI and container for $this->t() calls.
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->willReturn($this->logger);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Creates a TwitterPostBlock instance.
   *
   * @param array $configuration
   *   The block configuration.
   *
   * @return \Drupal\socialfeed\Plugin\Block\TwitterPostBlock
   *   The block instance.
   */
  protected function createBlock(array $configuration = []): TwitterPostBlock {
    return new TwitterPostBlock(
      $configuration,
      'twitter_post_block',
      ['provider' => 'socialfeed'],
      $this->twitterFactory,
      $this->configFactory,
      $this->currentUser,
      $this->loggerFactory
    );
  }

  /**
   * Tests build() without override key uses global config.
   *
   * Regression test for "Undefined array key 'override'" warning.
   *
   * @covers ::build
   */
  public function testBuildWithoutOverrideUsesGlobalConfig(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['consumer_key', 'global_ck'],
        ['consumer_secret', 'global_cs'],
        ['access_token', 'global_at'],
        ['access_token_secret', 'global_ats'],
        ['bearer_token', 'global_bt'],
        ['account_id', 'global_aid'],
        ['tweets_count', 10],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->twitterFactory->expects($this->once())
      ->method('createInstance')
      ->with('global_ck', 'global_cs', 'global_at', 'global_ats', 'global_bt', 'global_aid')
      ->willReturn($this->twitterCollector);

    $this->twitterCollector->expects($this->once())
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
      'consumer_key' => 'block_ck',
      'consumer_secret' => 'block_cs',
      'access_token' => 'block_at',
      'access_token_secret' => 'block_ats',
      'bearer_token' => 'block_bt',
      'account_id' => 'block_aid',
      'tweets_count' => 5,
    ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->twitterFactory->expects($this->once())
      ->method('createInstance')
      ->with('block_ck', 'block_cs', 'block_at', 'block_ats', 'block_bt', 'block_aid')
      ->willReturn($this->twitterCollector);

    $this->twitterCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([]);

    $build = $block->build();

    $this->assertIsArray($build);
  }

  /**
   * Tests build() renders tweet posts correctly.
   *
   * @covers ::build
   */
  public function testBuildRendersTweets(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['consumer_key', 'ck'],
        ['consumer_secret', 'cs'],
        ['access_token', 'at'],
        ['access_token_secret', 'ats'],
        ['bearer_token', 'bt'],
        ['account_id', 'aid'],
        ['tweets_count', 10],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn(['config:socialfeed.twitter.settings']);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->twitterFactory->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->twitterCollector);

    $mock_post = (object) [
      'full_text' => 'Hello world',
      'user' => (object) ['screen_name' => 'test'],
    ];

    $this->twitterCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([$mock_post]);

    $build = $block->build();

    $this->assertCount(1, $build['posts']['#items']);
    $this->assertEquals('socialfeed_twitter_post', $build['posts']['#items'][0]['#theme']);
    $this->assertSame($mock_post, $build['posts']['#items'][0]['#post']);
  }

}

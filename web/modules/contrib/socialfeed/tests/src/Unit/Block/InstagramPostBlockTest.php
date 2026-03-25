<?php

namespace Drupal\Tests\socialfeed\Unit\Block;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\socialfeed\Plugin\Block\InstagramPostBlock;
use Drupal\socialfeed\Services\InstagramApiService;
use Drupal\socialfeed\Services\InstagramPostCollector;
use Drupal\socialfeed\Services\InstagramPostCollectorFactory;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for InstagramPostBlock.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Plugin\Block\InstagramPostBlock
 */
class InstagramPostBlockTest extends UnitTestCase {

  /**
   * The Instagram factory mock.
   *
   * @var \Drupal\socialfeed\Services\InstagramPostCollectorFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $instagramFactory;

  /**
   * The Instagram collector mock.
   *
   * @var \Drupal\socialfeed\Services\InstagramPostCollector|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $instagramCollector;

  /**
   * The immutable config mock.
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
   * The editable config mock (for token refresh).
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $editableConfig;

  /**
   * The Instagram API service mock.
   *
   * @var \Drupal\socialfeed\Services\InstagramApiService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $instagramApi;

  /**
   * The current user mock.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The request mock.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $request;

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

    $this->instagramFactory = $this->createMock(InstagramPostCollectorFactory::class);
    $this->instagramCollector = $this->createMock(InstagramPostCollector::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->editableConfig = $this->createMock(Config::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->instagramApi = $this->createMock(InstagramApiService::class);
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->request = $this->createMock(Request::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->configFactory->method('get')
      ->with('socialfeed.instagram.settings')
      ->willReturn($this->config);

    $this->configFactory->method('getEditable')
      ->with('socialfeed.instagram.settings')
      ->willReturn($this->editableConfig);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);
  }

  /**
   * Creates an InstagramPostBlock instance.
   *
   * @param array $configuration
   *   The block configuration.
   *
   * @return \Drupal\socialfeed\Plugin\Block\InstagramPostBlock
   *   The block instance.
   */
  protected function createBlock(array $configuration = []): InstagramPostBlock {
    return new InstagramPostBlock(
      $configuration,
      'instagram_post_block',
      ['provider' => 'socialfeed'],
      $this->configFactory,
      $this->instagramFactory,
      $this->currentUser,
      $this->request,
      $this->instagramApi,
      $this->loggerFactory
    );
  }

  /**
   * Tests build() without override key doesn't produce warnings.
   *
   * Regression test for "Undefined array key 'override'" warning.
   *
   * @covers ::build
   */
  public function testBuildWithoutOverrideUsesGlobalConfig(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['access_token_date', NULL],
        ['access_token', 'global_token'],
        ['client_id', 'global_client'],
        ['app_secret', 'global_secret'],
        ['redirect_uri', 'http://example.com/callback'],
        ['picture_count', 6],
        ['post_link', FALSE],
        ['video_thumbnail', FALSE],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->instagramFactory->expects($this->once())
      ->method('createInstance')
      ->willReturn($this->instagramCollector);

    $this->instagramCollector->expects($this->once())
      ->method('getPosts')
      ->willReturn([]);

    $build = $block->build();

    $this->assertIsArray($build);
    $this->assertArrayHasKey('posts', $build);
  }

  /**
   * Tests shouldRefreshToken() returns FALSE when token_date is NULL.
   *
   * @covers ::shouldRefreshToken
   */
  public function testShouldNotRefreshWhenTokenDateIsNull(): void {
    $block = $this->createBlock([]);

    $method = new \ReflectionMethod($block, 'shouldRefreshToken');
    $method->setAccessible(TRUE);

    $this->assertFalse($method->invoke($block, NULL));
  }

  /**
   * Tests shouldRefreshToken() returns FALSE for recent token.
   *
   * @covers ::shouldRefreshToken
   */
  public function testShouldNotRefreshRecentToken(): void {
    $block = $this->createBlock([]);

    $method = new \ReflectionMethod($block, 'shouldRefreshToken');
    $method->setAccessible(TRUE);

    // Token created 10 days ago should NOT be refreshed.
    $ten_days_ago = time() - (10 * 24 * 60 * 60);
    $this->assertFalse($method->invoke($block, $ten_days_ago));
  }

  /**
   * Tests shouldRefreshToken() returns TRUE for old token.
   *
   * @covers ::shouldRefreshToken
   */
  public function testShouldRefreshOldToken(): void {
    $block = $this->createBlock([]);

    $method = new \ReflectionMethod($block, 'shouldRefreshToken');
    $method->setAccessible(TRUE);

    // Token created 51 days ago should be refreshed.
    $fifty_one_days_ago = time() - (51 * 24 * 60 * 60);
    $this->assertTrue($method->invoke($block, $fifty_one_days_ago));
  }

  /**
   * Tests refreshAccessToken() refreshes and saves a new token.
   *
   * @covers ::refreshAccessToken
   */
  public function testRefreshAccessTokenSuccess(): void {
    $block = $this->createBlock([]);

    $old_date = time() - (51 * 24 * 60 * 60);

    $this->config->method('get')
      ->willReturnMap([
        ['access_token_date', $old_date],
        ['access_token', 'old_token'],
        ['client_id', 'client'],
        ['app_secret', 'secret'],
        ['redirect_uri', 'http://example.com'],
        ['picture_count', 6],
        ['post_link', FALSE],
        ['video_thumbnail', FALSE],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->instagramApi->expects($this->once())
      ->method('refreshToken')
      ->with('old_token')
      ->willReturn('new_token');

    $this->editableConfig->expects($this->exactly(2))
      ->method('set')
      ->willReturnSelf();

    $this->editableConfig->expects($this->once())
      ->method('save');

    $this->instagramFactory->method('createInstance')
      ->willReturn($this->instagramCollector);

    $this->instagramCollector->method('getPosts')
      ->willReturn([]);

    $block->build();
  }

  /**
   * Tests refreshAccessToken() logs warning on failure.
   *
   * @covers ::refreshAccessToken
   */
  public function testRefreshAccessTokenFailure(): void {
    $block = $this->createBlock([]);

    $old_date = time() - (51 * 24 * 60 * 60);

    $this->config->method('get')
      ->willReturnMap([
        ['access_token_date', $old_date],
        ['access_token', 'old_token'],
        ['client_id', 'client'],
        ['app_secret', 'secret'],
        ['redirect_uri', 'http://example.com'],
        ['picture_count', 6],
        ['post_link', FALSE],
        ['video_thumbnail', FALSE],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->instagramApi->expects($this->once())
      ->method('refreshToken')
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Failed to refresh Instagram access token. Token may expire soon.');

    $this->instagramFactory->method('createInstance')
      ->willReturn($this->instagramCollector);

    $this->instagramCollector->method('getPosts')
      ->willReturn([]);

    $block->build();
  }

  /**
   * Tests build() renders video thumbnails when setting is enabled.
   *
   * @covers ::build
   */
  public function testBuildRendersVideoThumbnails(): void {
    $block = $this->createBlock([]);

    $this->config->method('get')
      ->willReturnMap([
        ['access_token_date', NULL],
        ['access_token', 'token'],
        ['client_id', 'client'],
        ['app_secret', 'secret'],
        ['redirect_uri', 'http://example.com'],
        ['picture_count', 6],
        ['post_link', TRUE],
        ['video_thumbnail', TRUE],
      ]);

    $this->config->method('getCacheTags')
      ->willReturn([]);
    $this->config->method('getCacheContexts')
      ->willReturn([]);

    $this->instagramFactory->method('createInstance')
      ->willReturn($this->instagramCollector);

    $video_post = [
      'raw' => (object) [
        'media_type' => 'VIDEO',
        'permalink' => 'https://instagram.com/p/123',
        'thumbnail_url' => 'https://example.com/thumb.jpg',
      ],
      'media_url' => 'https://example.com/video.mp4',
    ];

    $this->instagramCollector->method('getPosts')
      ->willReturn([$video_post]);

    $build = $block->build();

    $this->assertCount(1, $build['posts']['#items']);
    // Video with thumbnail enabled should render as image theme.
    $this->assertEquals('socialfeed_instagram_post_image', $build['posts']['#items'][0]['#theme']);
    // Media URL should be replaced with thumbnail.
    $this->assertEquals('https://example.com/thumb.jpg', $build['posts']['#items'][0]['#post']['media_url']);
    // Post URL should be set.
    $this->assertEquals('https://instagram.com/p/123', $build['posts']['#items'][0]['#post']['post_url']);
  }

}

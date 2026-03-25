<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\socialfeed\Services\TwitterPostCollectorFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Tests for TwitterPostCollectorFactory.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\TwitterPostCollectorFactory
 */
class TwitterPostCollectorFactoryTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The logger factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The logger mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The cache backend mock.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The Twitter post collector factory.
   *
   * @var \Drupal\socialfeed\Services\TwitterPostCollectorFactory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['consumer_key', 'default_consumer_key'],
        ['consumer_secret', 'default_consumer_secret'],
        ['access_token', 'default_access_token'],
        ['access_token_secret', 'default_token_secret'],
        ['bearer_token', 'default_bearer_token'],
        ['account_id', 'default_account_id'],
      ]);

    $this->configFactory->method('get')
      ->with('socialfeed.twitter.settings')
      ->willReturn($config);

    $this->cache = $this->createMock(CacheBackendInterface::class);

    $this->factory = new TwitterPostCollectorFactory(
      $this->configFactory,
      $this->loggerFactory,
      $this->cache
    );
  }

  /**
   * Tests createInstance with custom credentials.
   *
   * @covers ::createInstance
   * @covers ::__construct
   */
  public function testCreateInstanceWithCustomCredentials(): void {
    $instance = $this->factory->createInstance(
      'custom_key',
      'custom_secret',
      'custom_token',
      'custom_token_secret',
      'custom_bearer',
      'custom_account_id'
    );

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\TwitterPostCollector',
      $instance
    );
  }

  /**
   * Tests createInstance with empty credentials falls back to defaults.
   *
   * @covers ::createInstance
   * @covers ::__construct
   */
  public function testCreateInstanceWithEmptyCredentialsFallsBackToDefaults(): void {
    $instance = $this->factory->createInstance('', '', '', '', '', '');

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\TwitterPostCollector',
      $instance
    );
  }

  /**
   * Tests factory handles missing config gracefully.
   *
   * @covers ::__construct
   */
  public function testFactoryHandlesMissingConfig(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturn(NULL);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('socialfeed.twitter.settings')
      ->willReturn($config);

    $factory = new TwitterPostCollectorFactory(
      $configFactory,
      $this->loggerFactory,
      $this->cache
    );

    $instance = $factory->createInstance('', '', '', '', '', '');
    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\TwitterPostCollector',
      $instance
    );
  }

  /**
   * Tests createInstance returns new instance each time.
   *
   * @covers ::createInstance
   */
  public function testCreateInstanceReturnsNewInstanceEachTime(): void {
    $instance1 = $this->factory->createInstance(
      'key1',
      'secret1',
      'token1',
      'token_secret1',
      'bearer1',
      'account1'
    );
    $instance2 = $this->factory->createInstance(
      'key2',
      'secret2',
      'token2',
      'token_secret2',
      'bearer2',
      'account2'
    );

    $this->assertNotSame($instance1, $instance2);
  }

}

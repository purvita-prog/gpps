<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\Core\Config\Config;
use Drupal\socialfeed\Services\FacebookPostCollectorFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Tests for FacebookPostCollectorFactory.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\FacebookPostCollectorFactory
 */
class FacebookPostCollectorFactoryTest extends UnitTestCase {

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
   * The HTTP client mock.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The logger mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The Facebook post collector factory.
   *
   * @var \Drupal\socialfeed\Services\FacebookPostCollectorFactory
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
    $this->httpClient = $this->createMock(ClientInterface::class);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['app_id', 'default_app_id'],
        ['secret_key', 'default_secret_key'],
        ['user_token', 'default_user_token'],
        ['page_id', NULL],
        ['page_name', 'default_page_name'],
      ]);

    $this->configFactory->method('get')
      ->with('socialfeed.facebook.settings')
      ->willReturn($config);

    // FacebookPostCollector::defaultAccessToken() calls getEditable().
    $editableConfig = $this->createMock(Config::class);
    $editableConfig->method('get')
      ->willReturnMap([
        ['page_permanent_token', 'test_permanent_token'],
      ]);
    $this->configFactory->method('getEditable')
      ->with('socialfeed.facebook.settings')
      ->willReturn($editableConfig);

    $this->factory = new FacebookPostCollectorFactory(
      $this->configFactory,
      $this->loggerFactory,
      $this->httpClient
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
      'custom_app_id',
      'custom_secret',
      'custom_token',
      'custom_page'
    );

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\FacebookPostCollector',
      $instance
    );

    // The Facebook SDK (CrashReporter) registers its own error and exception
    // handlers during initialization. Restore the original handlers so PHPUnit
    // does not flag this test as risky.
    restore_error_handler();
    restore_exception_handler();
  }

  /**
   * Tests createInstance with empty credentials falls back to defaults.
   *
   * @covers ::createInstance
   * @covers ::__construct
   */
  public function testCreateInstanceWithEmptyCredentialsFallsBackToDefaults(): void {
    $instance = $this->factory->createInstance('', '', '', '');

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\FacebookPostCollector',
      $instance
    );
  }

  /**
   * Tests createInstance with partial credentials.
   *
   * @covers ::createInstance
   * @covers ::__construct
   */
  public function testCreateInstanceWithPartialCredentials(): void {
    $instance = $this->factory->createInstance(
      'custom_app',
      '',
      '',
      'custom_page'
    );

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\FacebookPostCollector',
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

    $editableConfig = $this->createMock(Config::class);
    $editableConfig->method('get')->willReturn(NULL);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('socialfeed.facebook.settings')
      ->willReturn($config);
    $configFactory->method('getEditable')
      ->with('socialfeed.facebook.settings')
      ->willReturn($editableConfig);

    $factory = new FacebookPostCollectorFactory(
      $configFactory,
      $this->loggerFactory,
      $this->httpClient
    );

    $instance = $factory->createInstance('', '', '', '');
    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\FacebookPostCollector',
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
      'app1',
      'secret1',
      'token1',
      'page1'
    );
    $instance2 = $this->factory->createInstance(
      'app2',
      'secret2',
      'token2',
      'page2'
    );

    $this->assertNotSame($instance1, $instance2);
  }

  /**
   * Tests that factory properly loads default config values.
   *
   * @covers ::__construct
   */
  public function testFactoryLoadsDefaultConfigValues(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['app_id', 'loaded_app_id'],
        ['secret_key', 'loaded_secret'],
        ['user_token', 'loaded_token'],
        ['page_id', NULL],
        ['page_name', 'loaded_page'],
      ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->expects($this->once())
      ->method('get')
      ->with('socialfeed.facebook.settings')
      ->willReturn($config);

    new FacebookPostCollectorFactory(
      $configFactory,
      $this->loggerFactory,
      $this->httpClient
    );

    $this->assertTrue(TRUE);
  }

}

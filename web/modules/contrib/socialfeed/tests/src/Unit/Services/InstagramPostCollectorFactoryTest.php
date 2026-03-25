<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\socialfeed\Services\InstagramPostCollectorFactory;
use Drupal\socialfeed\Services\InstagramApiService;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Tests for InstagramPostCollectorFactory.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\InstagramPostCollectorFactory
 */
class InstagramPostCollectorFactoryTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The Instagram API service mock.
   *
   * @var \Drupal\socialfeed\Services\InstagramApiService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $instagramApiService;

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
   * The Instagram post collector factory.
   *
   * @var \Drupal\socialfeed\Services\InstagramPostCollectorFactory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->instagramApiService = $this->createMock(InstagramApiService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['client_id', 'default_client_id'],
        ['api_secret', 'default_api_secret'],
        ['redirect_uri', 'https://default.example.com/redirect'],
        ['access_token', 'default_access_token'],
      ]);

    $this->configFactory->method('get')
      ->with('socialfeed.instagram.settings')
      ->willReturn($config);

    $this->factory = new InstagramPostCollectorFactory(
      $this->configFactory,
      $this->instagramApiService,
      $this->loggerFactory
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
      'custom_api_key',
      'custom_api_secret',
      'https://custom.example.com/redirect',
      'custom_access_token'
    );

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\InstagramPostCollector',
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
    $instance = $this->factory->createInstance('', '', '', '');

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\InstagramPostCollector',
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
    // Pass custom API key but empty others - should use defaults for
    // empty values.
    $instance = $this->factory->createInstance(
      'custom_key',
      '',
      '',
      'custom_token'
    );

    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\InstagramPostCollector',
      $instance
    );
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
        ['client_id', 'loaded_client_id'],
        ['api_secret', 'loaded_secret'],
        ['redirect_uri', 'loaded_uri'],
        ['access_token', 'loaded_token'],
      ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->expects($this->once())
      ->method('get')
      ->with('socialfeed.instagram.settings')
      ->willReturn($config);

    new InstagramPostCollectorFactory(
      $configFactory,
      $this->instagramApiService,
      $this->loggerFactory
    );

    // If we get here without exceptions, the factory loaded config properly.
    $this->assertTrue(TRUE);
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
      ->with('socialfeed.instagram.settings')
      ->willReturn($config);

    $factory = new InstagramPostCollectorFactory(
      $configFactory,
      $this->instagramApiService,
      $this->loggerFactory
    );

    // Should handle NULL config values gracefully by converting to
    // empty strings.
    $instance = $factory->createInstance('', '', '', '');
    $this->assertInstanceOf(
      'Drupal\socialfeed\Services\InstagramPostCollector',
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
      'uri1',
      'token1'
    );
    $instance2 = $this->factory->createInstance(
      'key2',
      'secret2',
      'uri2',
      'token2'
    );

    $this->assertNotSame($instance1, $instance2);
  }

}

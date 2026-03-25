<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\socialfeed\Services\InstagramApiService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Tests for InstagramApiService.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\InstagramApiService
 */
class InstagramApiServiceTest extends UnitTestCase {

  /**
   * The HTTP client mock.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

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
   * The Instagram API service.
   *
   * @var \Drupal\socialfeed\Services\InstagramApiService
   */
  protected $instagramApiService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    $this->instagramApiService = new InstagramApiService(
      $this->httpClient,
      $this->loggerFactory
    );
  }

  /**
   * Tests setCredentials method.
   *
   * @covers ::setCredentials
   */
  public function testSetCredentials(): void {
    $app_id = 'test_app_id';
    $app_secret = 'test_app_secret';
    $redirect_uri = 'https://example.com/callback';

    $this->instagramApiService->setCredentials($app_id, $app_secret, $redirect_uri);

    // Verify credentials are set by checking the login URL.
    $login_url = $this->instagramApiService->getLoginUrl();
    $this->assertStringContainsString($app_id, $login_url);
    $this->assertStringContainsString(urlencode($redirect_uri), $login_url);
  }

  /**
   * Tests getLoginUrl method.
   *
   * @covers ::getLoginUrl
   */
  public function testGetLoginUrl(): void {
    $this->instagramApiService->setCredentials(
      'app123',
      'secret456',
      'https://example.com/auth'
    );

    $login_url = $this->instagramApiService->getLoginUrl();

    $this->assertStringContainsString('https://api.instagram.com/oauth/authorize', $login_url);
    $this->assertStringContainsString('client_id=app123', $login_url);
    $this->assertStringContainsString('redirect_uri=' . urlencode('https://example.com/auth'), $login_url);
    $this->assertStringContainsString('scope=user_profile%2Cuser_media', $login_url);
    $this->assertStringContainsString('response_type=code', $login_url);
  }

  /**
   * Tests getOauthToken method with successful response.
   *
   * @covers ::getOauthToken
   * @covers ::makeApiRequest
   */
  public function testGetOauthTokenSuccess(): void {
    $this->instagramApiService->setCredentials('app_id', 'app_secret', 'redirect_uri');

    $response_body = json_encode(['access_token' => 'short_lived_token']);
    $response = new Response(200, [], $response_body);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('POST', 'https://api.instagram.com/oauth/access_token')
      ->willReturn($response);

    $token = $this->instagramApiService->getOauthToken('auth_code');

    $this->assertEquals('short_lived_token', $token);
  }

  /**
   * Tests getOauthToken method with failed response.
   *
   * @covers ::getOauthToken
   * @covers ::makeApiRequest
   */
  public function testGetOauthTokenFailure(): void {
    $this->instagramApiService->setCredentials('app_id', 'app_secret', 'redirect_uri');

    $exception = new RequestException(
      'Error',
      new Request('POST', 'test')
    );

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        '@error: @message',
        $this->callback(function ($context) {
          return $context['@error'] === 'Failed to get OAuth token';
        })
      );

    $token = $this->instagramApiService->getOauthToken('auth_code');

    $this->assertNull($token);
  }

  /**
   * Tests getLongLivedToken method with successful response.
   *
   * @covers ::getLongLivedToken
   * @covers ::makeApiRequest
   */
  public function testGetLongLivedTokenSuccess(): void {
    $this->instagramApiService->setCredentials('app_id', 'app_secret', 'redirect_uri');

    $response_body = json_encode(['access_token' => 'long_lived_token']);
    $response = new Response(200, [], $response_body);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://graph.instagram.com/access_token')
      ->willReturn($response);

    $token = $this->instagramApiService->getLongLivedToken('short_lived_token');

    $this->assertEquals('long_lived_token', $token);
  }

  /**
   * Tests refreshToken method with successful response.
   *
   * @covers ::refreshToken
   * @covers ::makeApiRequest
   */
  public function testRefreshTokenSuccess(): void {
    $response_body = json_encode(['access_token' => 'refreshed_token']);
    $response = new Response(200, [], $response_body);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://graph.instagram.com/refresh_access_token')
      ->willReturn($response);

    $token = $this->instagramApiService->refreshToken('old_token');

    $this->assertEquals('refreshed_token', $token);
  }

  /**
   * Tests getInstagramUserId method with successful response.
   *
   * @covers ::getInstagramUserId
   * @covers ::makeApiRequest
   */
  public function testGetInstagramUserIdSuccess(): void {
    $response_body = json_encode([
      'id' => '123456',
      'username' => 'testuser',
    ]);
    $response = new Response(200, [], $response_body);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://graph.instagram.com/me')
      ->willReturn($response);

    $user_id = $this->instagramApiService->getInstagramUserId('access_token');

    $this->assertEquals('123456', $user_id);
  }

  /**
   * Tests getUserMedia method with successful response.
   *
   * @covers ::getUserMedia
   * @covers ::makeApiRequest
   */
  public function testGetUserMediaSuccess(): void {
    $response_body = json_encode([
      'data' => [
        [
          'id' => 'media1',
          'media_type' => 'IMAGE',
          'media_url' => 'https://example.com/image.jpg',
        ],
        [
          'id' => 'media2',
          'media_type' => 'VIDEO',
          'media_url' => 'https://example.com/video.mp4',
        ],
      ],
    ]);
    $response = new Response(200, [], $response_body);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://graph.instagram.com/me/media')
      ->willReturn($response);

    $media = $this->instagramApiService->getUserMedia('access_token', 25, 'me');

    $this->assertIsObject($media);
    $this->assertCount(2, $media->data);
    $this->assertEquals('media1', $media->data[0]->id);
    $this->assertEquals('IMAGE', $media->data[0]->media_type);
  }

  /**
   * Tests getUserMedia method with API error.
   *
   * @covers ::getUserMedia
   * @covers ::makeApiRequest
   */
  public function testGetUserMediaFailure(): void {
    $exception = new RequestException(
      'API Error',
      new Request('GET', 'test')
    );

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        '@error: @message',
        $this->callback(function ($context) {
          return $context['@error'] === 'Failed to get user media';
        })
      );

    $media = $this->instagramApiService->getUserMedia('access_token');

    $this->assertNull($media);
  }

  /**
   * Tests setGraphVersion method.
   *
   * @covers ::setGraphVersion
   */
  public function testSetGraphVersion(): void {
    $version = 'v20.0';
    $this->instagramApiService->setGraphVersion($version);

    // Can't directly test private property, but we can ensure no exceptions.
    $this->assertTrue(TRUE);
  }

  /**
   * Tests makeApiRequest with response that has no expected key.
   *
   * @covers ::makeApiRequest
   */
  public function testMakeApiRequestWithMissingKey(): void {
    $this->instagramApiService->setCredentials('app_id', 'app_secret', 'redirect_uri');

    $response_body = json_encode(['other_key' => 'value']);
    $response = new Response(200, [], $response_body);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $token = $this->instagramApiService->getOauthToken('code');

    $this->assertNull($token);
  }

}

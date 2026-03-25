<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\socialfeed\Services\InstagramPostCollector;
use Drupal\socialfeed\Services\InstagramApiService;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Tests for InstagramPostCollector.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\InstagramPostCollector
 */
class InstagramPostCollectorTest extends UnitTestCase {

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
   * The Instagram post collector.
   *
   * @var \Drupal\socialfeed\Services\InstagramPostCollector
   */
  protected $postCollector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->instagramApiService = $this->createMock(InstagramApiService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    $this->postCollector = new InstagramPostCollector(
      'test_api_key',
      'test_api_secret',
      'https://example.com/redirect',
      'test_access_token',
      $this->instagramApiService,
      $this->loggerFactory
    );
  }

  /**
   * Tests getPosts method with successful response.
   *
   * @covers ::getPosts
   * @covers ::transformPosts
   */
  public function testGetPostsSuccess(): void {
    $mock_data = (object) [
      'data' => [
        (object) [
          'id' => 'post1',
          'media_type' => 'IMAGE',
          'media_url' => 'https://example.com/image1.jpg',
          'caption' => 'Test post 1',
        ],
        (object) [
          'id' => 'post2',
          'media_type' => 'VIDEO',
          'media_url' => 'https://example.com/video1.mp4',
          'thumbnail_url' => 'https://example.com/thumb1.jpg',
          'caption' => 'Test post 2',
        ],
      ],
    ];

    $this->instagramApiService->expects($this->once())
      ->method('getUserMedia')
      ->with('test_access_token', 10, 'me')
      ->willReturn($mock_data);

    $posts = $this->postCollector->getPosts(10);

    $this->assertCount(2, $posts);
    $this->assertEquals('post1', $posts[0]['raw']->id);
    $this->assertEquals('IMAGE', $posts[0]['type']);
    $this->assertEquals('https://example.com/image1.jpg', $posts[0]['media_url']);
    $this->assertEquals('VIDEO', $posts[1]['type']);
    $this->assertNull($posts[0]['children']);
  }

  /**
   * Tests getPosts method with NULL response from API.
   *
   * @covers ::getPosts
   */
  public function testGetPostsWithNullResponse(): void {
    $this->instagramApiService->expects($this->once())
      ->method('getUserMedia')
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Instagram API returned NULL. Check access token and credentials.');

    $posts = $this->postCollector->getPosts(10);

    $this->assertEmpty($posts);
  }

  /**
   * Tests getPosts method with response missing data field.
   *
   * @covers ::getPosts
   */
  public function testGetPostsWithMissingDataField(): void {
    $mock_response = (object) ['error' => 'Some error'];

    $this->instagramApiService->expects($this->once())
      ->method('getUserMedia')
      ->willReturn($mock_response);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Instagram API response missing data field.');

    $posts = $this->postCollector->getPosts(10);

    $this->assertEmpty($posts);
  }

  /**
   * Tests getPosts with carousel album containing children.
   *
   * @covers ::getPosts
   * @covers ::transformPosts
   */
  public function testGetPostsWithCarouselAlbum(): void {
    $mock_data = (object) [
      'data' => [
        (object) [
          'id' => 'carousel1',
          'media_type' => 'CAROUSEL_ALBUM',
          'media_url' => 'https://example.com/carousel1.jpg',
          'children' => (object) [
            'data' => [
              (object) [
                'id' => 'child1',
                'media_url' => 'https://example.com/child1.jpg',
                'media_type' => 'IMAGE',
              ],
            ],
          ],
        ],
      ],
    ];

    $this->instagramApiService->expects($this->once())
      ->method('getUserMedia')
      ->willReturn($mock_data);

    $posts = $this->postCollector->getPosts(5);

    $this->assertCount(1, $posts);
    $this->assertEquals('CAROUSEL_ALBUM', $posts[0]['type']);
    $this->assertNotNull($posts[0]['children']);
  }

  /**
   * Tests getPosts with custom user ID.
   *
   * @covers ::getPosts
   */
  public function testGetPostsWithCustomUserId(): void {
    $mock_data = (object) ['data' => []];

    $this->instagramApiService->expects($this->once())
      ->method('getUserMedia')
      ->with('test_access_token', 15, 'custom_user_id')
      ->willReturn($mock_data);

    $posts = $this->postCollector->getPosts(15, 'custom_user_id');

    $this->assertEmpty($posts);
  }

  /**
   * Tests transformPosts handles posts with missing optional fields.
   *
   * @covers ::getPosts
   * @covers ::transformPosts
   */
  public function testGetPostsWithMissingOptionalFields(): void {
    $mock_data = (object) [
      'data' => [
        (object) [
          'id' => 'minimal_post',
          // Missing media_url, media_type, children.
        ],
      ],
    ];

    $this->instagramApiService->expects($this->once())
      ->method('getUserMedia')
      ->willReturn($mock_data);

    $posts = $this->postCollector->getPosts(1);

    $this->assertCount(1, $posts);
    $this->assertNull($posts[0]['media_url']);
    $this->assertNull($posts[0]['type']);
    $this->assertNull($posts[0]['children']);
  }

}

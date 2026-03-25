<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\socialfeed\Services\TwitterPostCollector;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Noweh\TwitterApi\Client;
use Noweh\TwitterApi\Timeline;

/**
 * Tests for TwitterPostCollector.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\TwitterPostCollector
 */
class TwitterPostCollectorTest extends UnitTestCase {

  /**
   * The Twitter API v2 client mock.
   *
   * @var \Noweh\TwitterApi\Client|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $twitterClient;

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
   * The Twitter post collector.
   *
   * @var \Drupal\socialfeed\Services\TwitterPostCollector
   */
  protected $postCollector;

  /**
   * The timeline mock.
   *
   * @var \Noweh\TwitterApi\Timeline|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $timeline;

  /**
   * The cache backend mock.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->twitterClient = $this->createMock(Client::class);
    $this->timeline = $this->createMock(Timeline::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);

    $this->loggerFactory->method('get')
      ->with('socialfeed')
      ->willReturn($this->logger);

    // Cache miss by default so tests hit the API mock.
    $this->cache->method('get')->willReturn(FALSE);

    $this->postCollector = new TwitterPostCollector(
      'test_consumer_key',
      'test_consumer_secret',
      'test_access_token',
      'test_access_token_secret',
      'test_bearer_token',
      '9876543210',
      $this->loggerFactory,
      $this->cache,
      $this->twitterClient
    );
  }

  /**
   * Tests getPosts method with successful response.
   *
   * @covers ::getPosts
   */
  public function testGetPostsSuccess(): void {
    $user = new \stdClass();
    $user->id = '9876543210';
    $user->name = 'Test Account';
    $user->username = 'testuser';

    $tweet1 = new \stdClass();
    $tweet1->id = '123';
    $tweet1->text = 'Test tweet 1';
    $tweet1->created_at = '2024-12-01T14:30:00.000Z';
    $tweet1->author_id = '9876543210';

    $tweet2 = new \stdClass();
    $tweet2->id = '124';
    $tweet2->text = 'Test tweet 2';
    $tweet2->created_at = '2024-11-30T10:00:00.000Z';
    $tweet2->author_id = '9876543210';

    $includes = new \stdClass();
    $includes->users = [$user];

    $v2_response = new \stdClass();
    $v2_response->data = [$tweet1, $tweet2];
    $v2_response->includes = $includes;

    $this->twitterClient->expects($this->once())
      ->method('timeline')
      ->willReturn($this->timeline);

    $this->timeline->expects($this->once())
      ->method('getRecentTweets')
      ->with('9876543210')
      ->willReturn($this->timeline);

    $this->timeline->expects($this->once())
      ->method('performRequest')
      ->willReturn($v2_response);

    $posts = $this->postCollector->getPosts(10);

    $this->assertCount(2, $posts);
    $this->assertEquals('123', $posts[0]->id_str);
    $this->assertEquals('Test tweet 1', $posts[0]->full_text);
    $this->assertEquals('testuser', $posts[0]->user->screen_name);
  }

  /**
   * Tests getPosts method with API exception.
   *
   * @covers ::getPosts
   */
  public function testGetPostsWithException(): void {
    $this->twitterClient->expects($this->once())
      ->method('timeline')
      ->willThrowException(new \Exception('X API error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with('X API error: @error', ['@error' => 'X API error']);

    $posts = $this->postCollector->getPosts(10);

    $this->assertEmpty($posts);
  }

  /**
   * Tests getPosts method returns empty array on empty data.
   *
   * @covers ::getPosts
   */
  public function testGetPostsWithEmptyData(): void {
    $v2_response = new \stdClass();
    $v2_response->data = [];

    $this->twitterClient->expects($this->once())
      ->method('timeline')
      ->willReturn($this->timeline);

    $this->timeline->expects($this->once())
      ->method('getRecentTweets')
      ->with('9876543210')
      ->willReturn($this->timeline);

    $this->timeline->expects($this->once())
      ->method('performRequest')
      ->willReturn($v2_response);

    $posts = $this->postCollector->getPosts(10);

    $this->assertEmpty($posts);
  }

  /**
   * Tests getPosts respects count parameter.
   *
   * @covers ::getPosts
   */
  public function testGetPostsRespectsCount(): void {
    $user = new \stdClass();
    $user->id = '9876543210';
    $user->name = 'Test';
    $user->username = 'test';

    $t1 = new \stdClass();
    $t1->id = '1';
    $t1->text = 'Tweet 1';
    $t1->author_id = '9876543210';

    $t2 = new \stdClass();
    $t2->id = '2';
    $t2->text = 'Tweet 2';
    $t2->author_id = '9876543210';

    $t3 = new \stdClass();
    $t3->id = '3';
    $t3->text = 'Tweet 3';
    $t3->author_id = '9876543210';

    $includes = new \stdClass();
    $includes->users = [$user];

    $v2_response = new \stdClass();
    $v2_response->data = [$t1, $t2, $t3];
    $v2_response->includes = $includes;

    $this->twitterClient->method('timeline')->willReturn($this->timeline);
    $this->timeline->method('getRecentTweets')->willReturn($this->timeline);
    $this->timeline->method('performRequest')->willReturn($v2_response);

    $posts = $this->postCollector->getPosts(2);

    $this->assertCount(2, $posts);
  }

  /**
   * Tests that constructor accepts provided Twitter client.
   *
   * @covers ::__construct
   * @covers ::setTwitterClient
   */
  public function testConstructorAcceptsProvidedClient(): void {
    $customClient = $this->createMock(Client::class);

    $collector = new TwitterPostCollector(
      'key',
      'secret',
      'token',
      'token_secret',
      'bearer',
      'account_id',
      $this->loggerFactory,
      $this->cache,
      $customClient
    );

    $this->assertInstanceOf(TwitterPostCollector::class, $collector);
  }

}

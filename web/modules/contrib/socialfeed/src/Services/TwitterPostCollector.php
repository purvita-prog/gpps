<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Noweh\TwitterApi\Client;

/**
 * The collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollector {

  /**
   * Twitter's consumer key.
   *
   * @var string
   */
  protected string $consumerKey;

  /**
   * Twitter's consumer secret.
   *
   * @var string
   */
  protected string $consumerSecret;

  /**
   * Twitter's access token.
   *
   * @var string
   */
  protected string $accessToken;

  /**
   * Twitter's access token secret.
   *
   * @var string
   */
  protected string $accessTokenSecret;

  /**
   * X Bearer Token.
   *
   * @var string
   */
  protected string $bearerToken;

  /**
   * X Account ID.
   *
   * @var string
   */
  protected string $accountId;

  /**
   * Twitter's API v2 client.
   *
   * @var \Noweh\TwitterApi\Client|null
   */
  protected ?Client $twitter;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * TwitterPostCollector constructor.
   *
   * @param string $consumerKey
   *   Twitter's consumer key.
   * @param string $consumerSecret
   *   Twitter's consumer secret.
   * @param string $accessToken
   *   Twitter's access token.
   * @param string $accessTokenSecret
   *   Twitter's access token secret.
   * @param string $bearerToken
   *   X Bearer Token.
   * @param string $accountId
   *   X Account ID.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Noweh\TwitterApi\Client|null $twitter
   *   Twitter's API v2 Client.
   */
  public function __construct(
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret,
    string $bearerToken,
    string $accountId,
    LoggerChannelFactoryInterface $logger_factory,
    CacheBackendInterface $cache,
    ?Client $twitter = NULL,
  ) {
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->accessToken = $accessToken;
    $this->accessTokenSecret = $accessTokenSecret;
    $this->bearerToken = $bearerToken;
    $this->accountId = $accountId;
    $this->logger = $logger_factory->get('socialfeed');
    $this->cache = $cache;
    $this->twitter = $twitter;
    $this->setTwitterClient();
  }

  /**
   * Sets the Twitter client.
   */
  public function setTwitterClient(): void {
    if (NULL === $this->twitter) {
      $this->twitter = new Client([
        'account_id' => $this->accountId,
        'access_token' => $this->accessToken,
        'access_token_secret' => $this->accessTokenSecret,
        'consumer_key' => $this->consumerKey,
        'consumer_secret' => $this->consumerSecret,
        'bearer_token' => $this->bearerToken,
      ]);
    }
  }

  /**
   * Cache lifetime in seconds (1 hour).
   */
  const CACHE_LIFETIME = 3600;

  /**
   * Retrieves Tweets from the given account's timeline.
   *
   * @param int $count
   *   The number of posts to return.
   *
   * @return array
   *   An array of post objects.
   */
  public function getPosts(int $count): array {
    $cid = 'socialfeed:twitter:' . $this->accountId . ':' . $count;
    $cached = $this->cache->get($cid);
    if ($cached) {
      return $cached->data;
    }

    try {
      $result = $this->twitter
        ->timeline()
        ->getRecentTweets($this->accountId)
        ->performRequest();

      if (empty($result->data)) {
        return [];
      }

      // Build a user lookup from includes.
      $users = [];
      if (!empty($result->includes->users)) {
        foreach ($result->includes->users as $user) {
          $users[$user->id] = $user;
        }
      }

      // Normalize v2 response into v1.1-compatible objects for the theme layer.
      $posts = [];
      foreach (array_slice((array) $result->data, 0, $count) as $tweet) {
        $post = new \stdClass();
        $post->id_str = $tweet->id;
        $post->full_text = $tweet->text;
        $post->created_at = $tweet->created_at ?? '';

        // Map user data from includes.
        $post->user = new \stdClass();
        if (isset($tweet->author_id, $users[$tweet->author_id])) {
          $post->user->screen_name = $users[$tweet->author_id]->username;
          $post->user->name = $users[$tweet->author_id]->name;
        }
        else {
          $post->user->screen_name = '';
          $post->user->name = '';
        }

        // Map entities if present.
        $post->entities = new \stdClass();
        if (isset($tweet->entities->urls)) {
          $post->entities->urls = $tweet->entities->urls;
        }

        $posts[] = $post;
      }

      $this->cache->set($cid, $posts, time() + self::CACHE_LIFETIME, [
        'config:socialfeed.twitter.settings',
      ]);

      return $posts;
    }
    catch (\Exception $e) {
      $this->logger->error('X API error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}

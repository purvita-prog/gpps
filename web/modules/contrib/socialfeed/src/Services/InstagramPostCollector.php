<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * The collector class for Instagram.
 *
 * @package Drupal\socialfeed
 */
class InstagramPostCollector {

  /**
   * Instagram's application api key.
   *
   * @var string
   */
  protected string $apiKey;

  /**
   * Instagram application api secret.
   *
   * @var string
   */
  protected string $apiSecret;

  /**
   * Instagram application redirect Uri.
   *
   * @var string
   */
  protected string $redirectUri;

  /**
   * Instagram's application access token.
   *
   * @var string
   */
  protected string $accessToken;

  /**
   * Instagram API service.
   *
   * @var \Drupal\socialfeed\Services\InstagramApiService
   */
  protected InstagramApiService $instagramApi;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * InstagramPostCollector constructor.
   *
   * @param string $apiKey
   *   Instagram API key.
   * @param string $apiSecret
   *   Instagram API secret.
   * @param string $redirectUri
   *   Instagram Redirect URI.
   * @param string $accessToken
   *   Instagram Access token.
   * @param \Drupal\socialfeed\Services\InstagramApiService $instagram_api
   *   Instagram API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    string $apiKey,
    string $apiSecret,
    string $redirectUri,
    string $accessToken,
    InstagramApiService $instagram_api,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->apiKey = $apiKey;
    $this->apiSecret = $apiSecret;
    $this->redirectUri = $redirectUri;
    $this->accessToken = $accessToken;
    $this->instagramApi = $instagram_api;
    $this->logger = $logger_factory->get('socialfeed');
  }

  /**
   * Retrieves user's posts.
   *
   * @param int $numPosts
   *   Number of posts to get.
   * @param string $user_id
   *   The user id from whom to get media. Defaults to the user that the access
   *   token was created for.
   *
   * @return array
   *   An array of Instagram posts.
   */
  public function getPosts(int $numPosts, string $user_id = 'me'): array {
    $response = $this->instagramApi->getUserMedia($this->accessToken, $numPosts, $user_id);

    if ($response === NULL) {
      $this->logger->warning('Instagram API returned NULL. Check access token and credentials.');
      return [];
    }

    if (!isset($response->data)) {
      $this->logger->warning('Instagram API response missing data field.');
      return [];
    }

    return $this->transformPosts($response->data);
  }

  /**
   * Transforms raw API posts into structured array format.
   *
   * @param array $data
   *   Raw post data from Instagram API.
   *
   * @return array
   *   Transformed posts array.
   */
  protected function transformPosts(array $data): array {
    return array_map(function ($post) {
      return [
        'raw' => $post,
        'media_url' => $post->media_url ?? NULL,
        'type' => $post->media_type ?? NULL,
        'children' => $post->children ?? NULL,
      ];
    }, $data);
  }

}

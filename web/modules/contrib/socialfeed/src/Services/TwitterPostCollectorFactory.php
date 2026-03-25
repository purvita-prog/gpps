<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * The factory collector class for Twitter.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollectorFactory {

  /**
   * The default consumer key.
   *
   * @var string
   */
  protected string $defaultConsumerKey;

  /**
   * The default consumer secret.
   *
   * @var string
   */
  protected string $defaultConsumerSecret;

  /**
   * The default access token.
   *
   * @var string
   */
  protected string $defaultAccessToken;

  /**
   * The default access token secret.
   *
   * @var string
   */
  protected string $defaultAccessTokenSecret;

  /**
   * The default bearer token.
   *
   * @var string
   */
  protected string $defaultBearerToken;

  /**
   * The default account ID.
   *
   * @var string
   */
  protected string $defaultAccountId;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * TwitterPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    CacheBackendInterface $cache,
  ) {
    $config = $config_factory->get('socialfeed.twitter.settings');
    $this->defaultConsumerKey = $config->get('consumer_key') ?? '';
    $this->defaultConsumerSecret = $config->get('consumer_secret') ?? '';
    $this->defaultAccessToken = $config->get('access_token') ?? '';
    $this->defaultAccessTokenSecret = $config->get('access_token_secret') ?? '';
    $this->defaultBearerToken = $config->get('bearer_token') ?? '';
    $this->defaultAccountId = $config->get('account_id') ?? '';
    $this->loggerFactory = $logger_factory;
    $this->cache = $cache;
  }

  /**
   * Creates a pre-configured instance.
   *
   * @param string $consumerKey
   *   The consumer key.
   * @param string $consumerSecret
   *   The consumer secret.
   * @param string $accessToken
   *   The access token.
   * @param string $accessTokenSecret
   *   The access token secret.
   * @param string $bearerToken
   *   The bearer token.
   * @param string $accountId
   *   The account ID.
   *
   * @return \Drupal\socialfeed\Services\TwitterPostCollector
   *   A fully configured instance from TwitterPostCollector.
   *
   * @throws \Exception
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance(string $consumerKey, string $consumerSecret, string $accessToken, string $accessTokenSecret, string $bearerToken, string $accountId): TwitterPostCollector {
    return new TwitterPostCollector(
      $consumerKey ?: $this->defaultConsumerKey,
      $consumerSecret ?: $this->defaultConsumerSecret,
      $accessToken ?: $this->defaultAccessToken,
      $accessTokenSecret ?: $this->defaultAccessTokenSecret,
      $bearerToken ?: $this->defaultBearerToken,
      $accountId ?: $this->defaultAccountId,
      $this->loggerFactory,
      $this->cache
    );
  }

}

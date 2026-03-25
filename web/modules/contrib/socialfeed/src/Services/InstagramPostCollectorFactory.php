<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * The factory collector class for Instagram.
 *
 * @package Drupal\socialfeed
 */
class InstagramPostCollectorFactory {

  /**
   * The Instagram API service.
   *
   * @var \Drupal\socialfeed\Services\InstagramApiService
   */
  protected InstagramApiService $instagramApi;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The default Instagram application api key.
   *
   * @var string
   */
  protected string $defaultApiKey;

  /**
   * The default Instagram application api secret.
   *
   * @var string
   */
  protected string $defaultApiSecret;

  /**
   * The default Instagram redirect URI.
   *
   * @var string
   */
  protected string $defaultRedirectUri;

  /**
   * The default Instagram application access token.
   *
   * @var string
   */
  protected string $defaultAccessToken;

  /**
   * InstagramPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\socialfeed\Services\InstagramApiService $instagram_api
   *   Instagram API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    InstagramApiService $instagram_api,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $config = $configFactory->get('socialfeed.instagram.settings');
    $this->defaultApiKey = $config->get('client_id') ?? '';
    $this->defaultApiSecret = $config->get('api_secret') ?? '';
    $this->defaultRedirectUri = $config->get('redirect_uri') ?? '';
    $this->defaultAccessToken = $config->get('access_token') ?? '';
    $this->instagramApi = $instagram_api;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Creates a pre-configured instance.
   *
   * @param string $apiKey
   *   The API Key.
   * @param string $apiSecret
   *   The API Secret.
   * @param string $redirectUri
   *   The Redirect URI.
   * @param string $accessToken
   *   The Access Token.
   *
   * @return \Drupal\socialfeed\Services\InstagramPostCollector
   *   A fully configured instance from InstagramPostCollector.
   *
   * @throws \Exception
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance(string $apiKey, string $apiSecret, string $redirectUri, string $accessToken): InstagramPostCollector {
    return new InstagramPostCollector(
      $apiKey ?: $this->defaultApiKey,
      $apiSecret ?: $this->defaultApiSecret,
      $redirectUri ?: $this->defaultRedirectUri,
      $accessToken ?: $this->defaultAccessToken,
      $this->instagramApi,
      $this->loggerFactory
    );
  }

}

<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * The factory collector class for Facebook.
 *
 * @package Drupal\socialfeed
 */
class FacebookPostCollectorFactory {

  /**
   * The default Facebook App ID.
   *
   * @var string
   */
  protected string $defaultAppId;

  /**
   * The default Facebook App Secret.
   *
   * @var string
   */
  protected string $defaultAppSecret;

  /**
   * The default Facebook User Token.
   *
   * @var string
   */
  protected string $defaultUserToken;

  /**
   * The default Facebook Page Name.
   *
   * @var string
   */
  protected string $pageName;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * FacebookPostCollectorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client,
  ) {
    $config = $config_factory->get('socialfeed.facebook.settings');
    $this->defaultAppId = $config->get('app_id') ?? '';
    $this->defaultAppSecret = $config->get('secret_key') ?? '';
    $this->defaultUserToken = $config->get('user_token') ?? '';
    // Prefer numeric page_id if configured; otherwise use page_name.
    $configuredId = $config->get('page_id');
    $this->pageName = !empty($configuredId) ? (string) $configuredId : ($config->get('page_name') ?? '');
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->httpClient = $http_client;
  }

  /**
   * Creates a pre-configured instance.
   *
   * @param string $appId
   *   The App ID.
   * @param string $appSecret
   *   The App Secret.
   * @param string $userToken
   *   The User Token.
   * @param string $pageName
   *   The Page Name.
   *
   * @return \Drupal\socialfeed\Services\FacebookPostCollector
   *   A fully configured instance from FacebookPostCollector.
   *
   * @throws \Exception
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance(string $appId, string $appSecret, string $userToken, string $pageName): FacebookPostCollector {
    return new FacebookPostCollector(
      $appId ?: $this->defaultAppId,
      $appSecret ?: $this->defaultAppSecret,
      $userToken ?: $this->defaultUserToken,
      $pageName ?: $this->pageName,
      $this->loggerFactory,
      $this->httpClient,
      $this->configFactory
    );
  }

}

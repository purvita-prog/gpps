<?php

namespace Drupal\socialfeed\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\socialfeed\FacebookPageNormalizerTrait;
use FacebookAds\Api as Facebook;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\Page;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects posts from a Facebook page using the Facebook Graph API.
 *
 * @package Drupal\socialfeed
 */
class FacebookPostCollector {

  use FacebookPageNormalizerTrait;

  /**
   * Default Graph API version for requests.
   *
   * @var string
   */
  protected string $graphVersion = 'v24.0';

  /**
   * The Field names to retrieve from Facebook.
   *
   * @var array
   */
  protected array $fields = [
    'permalink_url',
    'message',
    'created_time',
    'picture',
    'status_type',
  ];

  /**
   * The Facebook's App ID.
   *
   * @var string
   */
  protected string $appId;

  /**
   * The Facebook's App Secret.
   *
   * @var string
   */
  protected string $appSecret;

  /**
   * The Facebook User Token.
   *
   * @var ?string
   */
  protected ?string $userToken;

  /**
   * The Facebook Page Name.
   *
   * @var ?string
   */
  protected ?string $pageName;

  /**
   * The Facebook Client.
   *
   * @var ?\FacebookAds\Api
   */
  protected ?Facebook $facebook;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

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
   * FacebookPostCollector constructor.
   *
   * @param string $appId
   *   The Facebook's App ID.
   * @param string $appSecret
   *   The Facebook's App Secret.
   * @param ?string $userToken
   *   The Facebook User Token.
   * @param ?string $pageName
   *   The Facebook Page Name.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param ?\FacebookAds\Api $facebook
   *   The Facebook Client.
   */
  public function __construct(
    string $appId,
    string $appSecret,
    ?string $userToken,
    ?string $pageName,
    LoggerChannelFactoryInterface $logger_factory,
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    ?Facebook $facebook = NULL,
  ) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    $this->userToken = $userToken;
    $this->pageName = $this->normalizePageInput($pageName);
    $this->logger = $logger_factory->get('socialfeed');
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->facebook = $facebook;
    $this->setFacebookClient();
  }

  /**
   * Sets the Facebook client.
   */

  /**
   * Generates the app secret proof for Graph API requests.
   *
   * @param string $accessToken
   *   The access token to sign with the app secret.
   *
   * @return string
   *   The HMAC-SHA256 signature (appsecret_proof).
   */
  protected function appSecretProof(string $accessToken): string {
    return hash_hmac('sha256', $accessToken, $this->appSecret);
  }

  /**
   * Initializes the Facebook SDK client if one was not provided.
   */
  public function setFacebookClient(): void {
    if (NULL === $this->facebook) {
      $this->facebook = Facebook::init($this->appId, $this->appSecret, $this->defaultAccessToken());
    }
  }

  /**
   * Fetches Facebook posts from a given feed.
   *
   * @param string $pageId
   *   The ID of the page to fetch results from.
   * @param string $postTypes
   *   The post types to filter for.
   * @param int $numPosts
   *   The number of posts to return.
   *
   * @return array
   *   An array of Facebook posts.
   */
  public function getPosts(string $pageId, string $postTypes, int $numPosts = 10): array {
    try {
      $posts = [];
      $postCount = 0;
      $params = [];
      $this->facebook->setLogger(new CurlLogger());

      // Resolve non-numeric page usernames to numeric IDs to avoid hitting
      // endpoints like "/{app-id}/feed" by mistake.
      if (empty($pageId)) {
        $pageId = (string) ($this->pageName ?? '');
      }
      // If numeric ID is provided, skip resolution completely.
      if (preg_match('/^\d+$/', (string) $pageId)) {
        // no-op: use as-is.
      }
      elseif ((string) $pageId === (string) $this->appId || !preg_match('/^\d+$/', (string) $pageId)) {
        $resolvedId = $this->getPageId($this->graphVersion, $this->defaultAccessToken());
        if (empty($resolvedId)) {
          // Final fallback: attempt with app token for ID resolution only.
          $resolvedId = $this->getPageId($this->graphVersion, $this->appId . '|' . $this->appSecret);
        }
        if (!empty($resolvedId)) {
          $pageId = $resolvedId;
          // Persist numeric page_id for future requests.
          try {
            $this->configFactory->getEditable('socialfeed.facebook.settings')->set('page_id', $pageId)->save();
          }
          catch (\Exception $e) {
            // Non-fatal.
          }
        }
      }

      // If still not numeric, abort to avoid SDK throwing
      // "Object ID must be integer".
      if (!preg_match('/^\d+$/', (string) $pageId)) {
        $this->logger->error(
          'Facebook page identifier "@name" could not be resolved to a numeric ID. Please enter the numeric Page ID or a Page username accessible to the configured token.',
          [
            '@name' => (string) $this->pageName,
          ]
        );
        return [];
      }

      do {
        $response = (new Page($pageId))->getFeed($this->fields, $params)->getLastResponse();
        // Ensure not caught in an infinite loop if there's no next page.
        $url = NULL;
        if ($response->getStatusCode() == Response::HTTP_OK) {
          $data = $response->getContent();
          $posts = array_merge($this->extractFacebookFeedData($postTypes, $data['data']), $posts);
          $postCount = count($posts);
          if ($postCount < $numPosts && isset($data['paging']['next'])) {
            $url = $data['paging']['next'];
          }
        }
      } while ($postCount < $numPosts && NULL != $url);
      return array_slice($posts, 0, $numPosts);
    }
    catch (\Exception $e) {
      // Fallback: try direct Graph request using resolved numeric page id
      // (if any).
      if (preg_match('/^\d+$/', (string) $pageId)) {
        $fallback = $this->fetchPostsViaGraph((string) $pageId, $this->defaultAccessToken(), $postTypes, $numPosts);
        if (!empty($fallback)) {
          return $fallback;
        }
      }
      $this->logger->error(
        'Facebook API error: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
      return [];
    }
  }

  /**
   * Fetches posts directly via the Graph API as a fallback to the SDK.
   *
   * @param string $pageId
   *   The numeric page ID to fetch posts for.
   * @param string $accessToken
   *   The access token to use for the request.
   * @param string $postTypes
   *   The post types to filter for.
   * @param int $numPosts
   *   The maximum number of posts to return.
   *
   * @return array
   *   A list of posts.
   */
  protected function fetchPostsViaGraph(string $pageId, string $accessToken, string $postTypes, int $numPosts): array {
    $fields = implode(',', $this->fields);
    $tokens = [];
    // Prefer a stored permanent token, then provided token, then app token.
    $config = $this->configFactory->get('socialfeed.facebook.settings');
    $perm = (string) ($config->get('page_permanent_token') ?? '');
    if ($perm !== '') {
      $tokens[] = $perm;
    }
    if ($accessToken !== '') {
      $tokens[] = $accessToken;
    }
    // Last resort (may not have permissions)
    $tokens[] = $this->appId . '|' . $this->appSecret;

    foreach ($tokens as $token) {
      try {
        $url = "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/posts?fields={$fields}&access_token={$token}&limit=" . max(5, $numPosts);
        $posts = [];
        $next = $url;
        do {
          $res = $this->httpClient->request('GET', $next);
          $data = json_decode($res->getBody()->getContents(), TRUE);
          $posts = array_merge($posts, $this->extractFacebookFeedData($postTypes, $data['data'] ?? []));
          $next = $data['paging']['next'] ?? NULL;
        } while (count($posts) < $numPosts && !empty($next));
        if (!empty($posts)) {
          return array_slice($posts, 0, $numPosts);
        }
      }
      catch (\Exception $e) {
        // Try next token.
        $this->logger->warning(
          'Graph fallback with current token failed: @error',
          [
            '@error' => $e->getMessage(),
          ]
        );
      }
    }

    $this->logger->error(
      'Facebook Graph fallback error: All token attempts failed for page @id.',
      [
        '@id' => $pageId,
      ]
    );
    return [];
  }

  /**
   * Filters and normalizes raw feed data from the Graph API response.
   *
   * @param string $postTypes
   *   The post type to filter by (or '1' to include all types).
   * @param array $data
   *   Raw post items from the API.
   *
   * @return array
   *   The filtered posts.
   */
  protected function extractFacebookFeedData(string $postTypes, array $data): array {
    $posts = array_map(function ($post) {
      return $post;
    }, $data);

    // Following is TRUE, when user has asked for specific post type in Facebook
    // settings form instead of all posts.
    if ($postTypes !== '1') {
      return array_filter($posts, function ($post) use ($postTypes) {
        if (!empty($post['status_type'])) {
          return $post['status_type'] === $postTypes;
        }
      });
    }
    return $posts;
  }

  /**
   * Generates the Facebook access token.
   *
   * @return string
   *   The access token.
   */
  protected function defaultAccessToken(): string {
    try {
      $config = $this->configFactory->getEditable('socialfeed.facebook.settings');
      $permanentToken = $config->get('page_permanent_token');

      if (!empty($permanentToken)) {
        return $permanentToken;
      }

      // Attempt to generate and persist a permanent page token.
      $generated = $this->generatePermanentToken($config);
      if (!empty($generated)) {
        return $generated;
      }

      // Attempt to derive a Page Access Token directly from /me/accounts when
      // long-lived token exchange failed but we still have a user token.
      if (!empty($this->userToken)) {
        $pageAccess = $this->getPageAccessTokenFromAccounts($this->graphVersion, $this->userToken);
        if (!empty($pageAccess)) {
          $config->set('page_permanent_token', $pageAccess)->save();
          return $pageAccess;
        }
        // Final fallback: return provided token so downstream errors are logged
        // but the site doesn't white-screen.
        $this->logger->warning('Using provided Facebook access token as fallback; permanent token generation failed.');
        return (string) $this->userToken;
      }

      return '';
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Facebook token generation error: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
      return '';
    }
  }

  /**
   * Try to obtain a Page Access Token from /me/accounts list.
   */
  protected function getPageAccessTokenFromAccounts(string $graphVersion, string $accessToken): ?string {
    try {
      $proof = $this->appSecretProof($accessToken);
      $url = "https://graph.facebook.com/{$graphVersion}/me/accounts?access_token={$accessToken}&appsecret_proof={$proof}";
      $response = $this->httpClient->request('GET', $url);
      $payload = json_decode($response->getBody()->getContents(), TRUE);
      $target = $this->normalizePageInput($this->pageName);
      foreach ($payload['data'] ?? [] as $acc) {
        $id = (string) ($acc['id'] ?? '');
        $name = (string) ($acc['name'] ?? '');
        $username = (string) ($acc['username'] ?? '');
        $matchesId = $target !== '' && preg_match('/^\d+$/', $target) && $id === $target;
        $matchesName = $target !== '' && (strcasecmp($name, $target) === 0 || strcasecmp($username, $target) === 0);
        if ($matchesId || $matchesName) {
          return (string) ($acc['access_token'] ?? '');
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error getting page access token from /me/accounts: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
    }
    return NULL;
  }

  /**
   * Generates a permanent Facebook page token.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   *
   * @return string
   *   The permanent access token or empty string on failure.
   */
  protected function generatePermanentToken($config): string {
    $graphVersion = $this->graphVersion;

    // Step 1: Get long-lived user token.
    $longToken = $this->getLongLivedToken($graphVersion);
    if (empty($longToken)) {
      return '';
    }

    // Step 2: Get user account ID.
    $accountId = $this->getUserAccountId($graphVersion, $longToken);
    if (empty($accountId)) {
      return '';
    }

    // Step 3: Get page ID.
    $pageId = $this->getPageId($graphVersion, $longToken);
    if (empty($pageId)) {
      return '';
    }

    $config->set('page_id', $pageId)->save();

    // Step 4: Get permanent page token.
    return $this->getPagePermanentToken($graphVersion, $accountId, $longToken, $pageId, $config);
  }

  /**
   * Gets a long-lived Facebook user token.
   *
   * @param string $graphVersion
   *   The Graph API version.
   *
   * @return string|null
   *   The long-lived token or NULL on failure.
   */
  protected function getLongLivedToken(string $graphVersion): ?string {
    try {
      $url = "https://graph.facebook.com/{$graphVersion}/oauth/access_token";
      $url .= "?grant_type=fb_exchange_token&client_id={$this->appId}";
      $url .= "&client_secret={$this->appSecret}&fb_exchange_token={$this->userToken}";

      $response = $this->httpClient->request('GET', $url);
      $data = json_decode($response->getBody()->getContents());

      $token = $data->access_token ?? NULL;
      if (empty($token)) {
        $this->logger->error('Failed to get Facebook long-lived token');
      }

      return $token;
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error getting long-lived token: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
      return NULL;
    }
  }

  /**
   * Gets the Facebook user account ID.
   *
   * @param string $graphVersion
   *   The Graph API version.
   * @param string $accessToken
   *   The access token.
   *
   * @return string|null
   *   The account ID or NULL on failure.
   */
  protected function getUserAccountId(string $graphVersion, string $accessToken): ?string {
    try {
      $proof = $this->appSecretProof($accessToken);
      $url = "https://graph.facebook.com/{$graphVersion}/me?access_token={$accessToken}&appsecret_proof={$proof}";
      $response = $this->httpClient->request('GET', $url);
      $data = json_decode($response->getBody()->getContents());

      $accountId = $data->id ?? NULL;
      if (empty($accountId)) {
        $this->logger->error('Failed to get Facebook account ID');
      }

      return $accountId;
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error getting account ID: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
      return NULL;
    }
  }

  /**
   * Gets the Facebook page ID.
   *
   * @param string $graphVersion
   *   The Graph API version.
   * @param string $accessToken
   *   The access token.
   *
   * @return string|null
   *   The page ID or NULL on failure.
   */
  protected function getPageId(string $graphVersion, string $accessToken): ?string {
    // If a numeric page id is already configured, return it.
    if (!empty($this->pageName) && preg_match('/^\d+$/', (string) $this->pageName)) {
      return (string) $this->pageName;
    }

    // Attempt 1: Direct lookup by page username/slug.
    try {
      if (!empty($this->pageName)) {
        $proof = $this->appSecretProof($accessToken);
        $url = "https://graph.facebook.com/{$graphVersion}/{$this->pageName}";
        $url .= "?fields=id&access_token={$accessToken}&appsecret_proof={$proof}";
        $response = $this->httpClient->request('GET', $url);
        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (!empty($data['id'])) {
          return (string) $data['id'];
        }
      }
    }
    catch (\Exception $e) {
      // Continue to fallback.
      $this->logger->warning(
        'Direct page ID lookup failed for "@name": @error. Trying accounts fallback.',
        [
          '@name' => (string) $this->pageName,
          '@error' => $e->getMessage(),
        ]
      );
    }

    // Attempt 2: Fallback via /me/accounts to match by name or username.
    try {
      $url = "https://graph.facebook.com/{$graphVersion}/me/accounts?access_token={$accessToken}";
      $response = $this->httpClient->request('GET', $url);
      $payload = json_decode($response->getBody()->getContents(), TRUE);
      foreach ($payload['data'] ?? [] as $acc) {
        $name = (string) ($acc['name'] ?? '');
        $username = (string) ($acc['username'] ?? '');
        $candidates = array_filter([$name, $username]);
        foreach ($candidates as $candidate) {
          if (strcasecmp($candidate, (string) $this->pageName) === 0
            || strcasecmp(str_replace(['.', '_'], ' ', $candidate), str_replace(['.', '_'], ' ', (string) $this->pageName)) === 0) {
            return (string) ($acc['id'] ?? '');
          }
        }
      }
      $this->logger->error(
        'Failed to resolve Facebook page ID for "@name" via /me/accounts fallback.',
        [
          '@name' => (string) $this->pageName,
        ]
      );
      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error getting page ID via accounts fallback: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
      return NULL;
    }
  }

  /**
   * Gets the permanent page access token.
   *
   * @param string $graphVersion
   *   The Graph API version.
   * @param string $accountId
   *   The account ID.
   * @param string $accessToken
   *   The access token.
   * @param string $pageId
   *   The page ID.
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   *
   * @return string
   *   The permanent token or empty string on failure.
   */
  protected function getPagePermanentToken(string $graphVersion, string $accountId, string $accessToken, string $pageId, $config): string {
    try {
      $proof = $this->appSecretProof($accessToken);
      $url = "https://graph.facebook.com/{$graphVersion}/{$accountId}/accounts?access_token={$accessToken}&appsecret_proof={$proof}";
      $response = $this->httpClient->request('GET', $url);
      $data = json_decode($response->getBody()->getContents());

      foreach ($data->data ?? [] as $responseData) {
        if ($responseData->id == $pageId) {
          $config->set('page_permanent_token', $responseData->access_token)->save();
          return $responseData->access_token;
        }
      }

      $this->logger->error('Failed to find page token in accounts response');
      return '';
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Error getting permanent token: @error',
        [
          '@error' => $e->getMessage(),
        ]
      );
      return '';
    }
  }

}

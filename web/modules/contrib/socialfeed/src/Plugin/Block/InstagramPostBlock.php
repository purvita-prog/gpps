<?php

namespace Drupal\socialfeed\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\socialfeed\Services\InstagramPostCollectorFactory;
use Drupal\socialfeed\Services\InstagramApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an 'Instagram' block.
 *
 * @Block(
 *  id = "instagram_post_block",
 *  admin_label = @Translation("Instagram Block"),
 * )
 */
class InstagramPostBlock extends SocialBlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface {

  /**
   * The immutable config (respects overrides).
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The config factory (for editable config in token refresh).
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Instagram Service.
   *
   * @var \Drupal\socialfeed\Services\InstagramPostCollectorFactory
   */
  protected $instagram;

  /**
   * The Instagram API service.
   *
   * @var \Drupal\socialfeed\Services\InstagramApiService
   */
  protected $instagramApi;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    InstagramPostCollectorFactory $instagram,
    AccountInterface $currentUser,
    Request $request,
    InstagramApiService $instagram_api,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get('socialfeed.instagram.settings');
    $this->instagram = $instagram;
    $this->currentUser = $currentUser;
    $this->currentRequest = $request;
    $this->instagramApi = $instagram_api;
    $this->logger = $logger_factory->get('socialfeed');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('socialfeed.instagram'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('socialfeed.instagram_api'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $this->messenger()->addWarning($this->t('By overriding the `FEED CONFIGURATION` settings here, this block won\'t receive the renewed <strong>Access Token</strong> when the current one expires in <strong>60 days</strong>, hence you have to manually add a new <strong>Access Token</strong> post expiry. <br /> Global Settings doesn\'t have this limitation so in case if you haven\'t configured them here yet, then you should configure the `FEED CONFIGURATION` at <a href="@admin">/admin/config/socialfeed/instagram</a>',
      ['@admin' => Url::fromRoute('socialfeed.instagram_settings_form')->toString()])
    );

    $form['overrides']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('Found in <a href="@link" target="_blank">Meta for Developers</a> → Your App → Settings → Basic. Leave empty to use global settings.', [
        '@link' => 'https://developers.facebook.com/apps/',
      ]),
      '#default_value' => $this->defaultSettingValue('client_id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Secret'),
      '#description' => $this->t('Found in <a href="@link" target="_blank">Meta for Developers</a> → Your App → Settings → Basic. Leave empty to use global settings.', [
        '@link' => 'https://developers.facebook.com/apps/',
      ]),
      '#default_value' => $this->defaultSettingValue('app_secret'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#description' => $this->t('The OAuth redirect URI configured in your Instagram app. Must match the URL in <a href="@link" target="_blank">Meta for Developers</a> → Your App → Settings → Basic. Leave empty to use global settings.', [
        '@link' => 'https://developers.facebook.com/apps/',
      ]),
      '#default_value' => $this->defaultSettingValue('redirect_uri'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#description' => $this->t('<strong>Warning:</strong> Block-level tokens will NOT be auto-renewed. Use global settings for automatic token renewal, or manually renew your access token every 60 days via <a href="@settings">global settings</a>.', [
        '@settings' => Url::fromRoute('socialfeed.instagram_settings_form')->toString(),
      ]),
      '#default_value' => $this->defaultSettingValue('access_token'),
      '#size' => 60,
      '#maxlength' => 300,
      '#required' => TRUE,
    ];

    $form['overrides']['picture_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Picture Count'),
      '#default_value' => $this->defaultSettingValue('picture_count'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
    ];

    $this->blockFormElementStates($form);

    $form['overrides']['post_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show post URL'),
      '#default_value' => $this->defaultSettingValue('post_link'),
    ];

    $form['overrides']['video_thumbnail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show video thumbnails instead of actual videos'),
      '#default_value' => $this->defaultSettingValue('video_thumbnail'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $items = [];

    // Refresh the long-lived Access Token.
    $this->refreshAccessToken();

    $instagram = $this->instagram->createInstance($this->getSetting('client_id'), $this->getSetting('app_secret'), $this->getSetting('redirect_uri'), $this->getSetting('access_token'));

    $posts = $instagram->getPosts(
      $this->getSetting('picture_count')
    );

    // Validating the settings.
    $post_link = $this->getSetting('post_link');
    $video_thumbnail = $this->getSetting('video_thumbnail');

    foreach ($posts as $post) {
      $theme_type = ($post['raw']->media_type == 'VIDEO') ? 'video' : ($post['raw']->media_type == 'CAROUSEL_ALBUM' ? 'carousel_album' : 'image');

      // Set the post link.
      if ($post_link) {
        $post['post_url'] = $post['raw']->permalink;
      }

      // Use video thumbnails instead of rendered videos.
      if ($video_thumbnail && $theme_type == 'video') {
        $theme_type = 'image';
        $post['media_url'] = $post['raw']->thumbnail_url;
      }

      $items[] = [
        '#theme' => 'socialfeed_instagram_post_' . $theme_type,
        '#post' => $post,
        '#cache' => [
          // Cache for 1 hour.
          'max-age' => 60 * 60,
          'cache tags' => $this->config->getCacheTags(),
          'context' => $this->config->getCacheContexts(),
        ],
      ];
    }
    $build['posts'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $build;
  }

  /**
   * Update the access token with a "long-lived" one.
   *
   * Tokens are refreshed after 50 days to ensure they stay valid
   * (tokens expire after 60 days).
   */
  protected function refreshAccessToken(): void {
    $token_date = $this->config->get('access_token_date');
    $access_token = $this->config->get('access_token');

    if (!$this->shouldRefreshToken($token_date)) {
      return;
    }

    $new_token = $this->instagramApi->refreshToken($access_token);

    if ($new_token) {
      $editable = $this->configFactory->getEditable('socialfeed.instagram.settings');
      $editable->set('access_token', $new_token);
      $editable->set('access_token_date', time());
      $editable->save();
    }
    else {
      $this->logger->warning('Failed to refresh Instagram access token. Token may expire soon.');
    }
  }

  /**
   * Check if the token should be refreshed.
   *
   * @param int|null $token_date
   *   The timestamp when the token was last updated.
   *
   * @return bool
   *   TRUE if the token should be refreshed, FALSE otherwise.
   */
  protected function shouldRefreshToken(?int $token_date): bool {
    if (empty($token_date)) {
      return FALSE;
    }

    // Refresh after 50 days (tokens expire after 60 days).
    $refresh_threshold = 50 * 24 * 60 * 60;
    return ($token_date + $refresh_threshold) <= time();
  }

}

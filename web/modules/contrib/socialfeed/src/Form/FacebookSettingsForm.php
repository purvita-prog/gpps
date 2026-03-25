<?php

namespace Drupal\socialfeed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\socialfeed\FacebookPageNormalizerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Facebook Page Feed settings.
 *
 * This form allows administrators to configure integration with Facebook
 * Pages API to display posts from a Facebook Page on the Drupal site.
 *
 * @package Drupal\socialfeed\Form
 */
class FacebookSettingsForm extends ConfigFormBase {

  use FacebookPageNormalizerTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->httpClient = $container->get('http_client');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'facebook_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.facebook.settings');
    $post_type_options = [
      'shared_story' => 'Shared story',
      'published_story' => 'Published story',
      'mobile_status_update' => 'Status',
      'added_photos' => 'Photo',
      'added_video' => 'Video',
    ];

    $form['header'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' . $this->t('<h3>Facebook Page Feed Setup</h3>
        <p>This module allows you to display posts from your Facebook Page on your Drupal site.</p>
        <h4>Requirements</h4>
        <ul>
          <li><strong>Facebook Page:</strong> You must have a Facebook Page (not a personal profile).</li>
          <li><strong>Facebook App:</strong> You need to create a Facebook App to get API credentials.</li>
          <li><strong>Page Access Token:</strong> You will need a Page Access Token to fetch posts from your page.</li>
        </ul>
        <h4>Setup Steps</h4>
        <ol>
          <li><strong>Create Facebook App:</strong> Go to <a href="@fb_dev" target="_blank">Meta for Developers</a> and create a new app or use an existing one. Select "Business" as the app type.</li>
          <li><strong>Get App ID & Secret:</strong> Navigate to Settings → Basic in your app dashboard. Copy your App ID and App Secret and paste them below.</li>
          <li><strong>Generate Page Access Token:</strong> Use the <a href="@token_tool" target="_blank">Graph API Explorer</a> to generate a Page Access Token:
            <ul>
              <li>Select your app from the "Meta App" dropdown</li>
              <li>Under "User or Page", select your page from "Page Access Tokens"</li>
              <li>Generate a token with <code>pages_read_engagement</code> and <code>pages_show_list</code> permissions</li>
              <li>For long-lived tokens (recommended), use the <a href="@token_debugger" target="_blank">Access Token Debugger</a> to extend the token expiration</li>
            </ul>
          </li>
          <li><strong>Enter Page Name:</strong> Your Facebook Page username (the part after facebook.com/ in your page URL).</li>
        </ol>
        <p><strong>Important:</strong> Page Access Tokens can expire. For production use, consider generating a long-lived token or implementing token refresh logic.</p>
        <p>For detailed information, see <a href="@pages_api" target="_blank">Facebook Pages API Documentation</a>.</p>', [
          '@fb_dev' => 'https://developers.facebook.com/apps/',
          '@token_tool' => 'https://developers.facebook.com/tools/explorer/',
          '@token_debugger' => 'https://developers.facebook.com/tools/debug/accesstoken/',
          '@pages_api' => 'https://developers.facebook.com/docs/pages-api',
        ]) . '</div>',
    ];

    $form['page_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page Name'),
      '#default_value' => $config->get('page_name'),
      '#description' => $this->t('The username or handle of your Facebook Page. For example, if your page URL is <code>https://www.facebook.com/YourPageName</code>, enter <strong>YourPageName</strong>. Find it in your page\'s "About" section or in the URL bar.'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App ID'),
      '#default_value' => $config->get('app_id'),
      '#description' => $this->t('Found in <a href="@link" target="_blank">Meta for Developers</a> → Your App → Settings → Basic. Look for "App ID" at the top of the page.', [
        '@link' => 'https://developers.facebook.com/apps/',
      ]),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook App Secret'),
      '#default_value' => $config->get('secret_key'),
      '#description' => $this->t('Found in <a href="@link" target="_blank">Meta for Developers</a> → Your App → Settings → Basic. Click "Show" next to "App Secret" to reveal it. <strong>Keep this secret and never commit it to version control.</strong>', [
        '@link' => 'https://developers.facebook.com/apps/',
      ]),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['user_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Access Token'),
      '#default_value' => $config->get('user_token'),
      '#description' => $this->t('Generate this at <a href="@explorer" target="_blank">Graph API Explorer</a>:
        <ul>
          <li>Select your app from the "Meta App" dropdown</li>
          <li>Under "User or Page", find your page in the "Page Access Tokens" section</li>
          <li>Click "Generate Access Token" and grant necessary permissions (<code>pages_read_engagement</code>, <code>pages_show_list</code>)</li>
          <li>Copy the generated token and paste it here</li>
        </ul>
        <strong>Note:</strong> Tokens expire. For long-lived tokens, use the <a href="@debugger" target="_blank">Access Token Debugger</a> to extend expiration.', [
          '@explorer' => 'https://developers.facebook.com/tools/explorer/',
          '@debugger' => 'https://developers.facebook.com/tools/debug/accesstoken/',
        ]),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['no_feeds'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Posts to Display'),
      '#default_value' => $config->get('no_feeds'),
      '#description' => $this->t('The number of Facebook posts to fetch and display (maximum 100).'),
      '#size' => 60,
      '#maxlength' => 60,
      '#max' => 100,
      '#min' => 1,
    ];
    $form['all_types'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show All Post Types'),
      '#description' => $this->t('Display all types of Facebook posts (stories, status updates, photos, videos, etc.). Uncheck to filter by a specific post type.'),
      '#default_value' => $config->get('all_types'),
    ];
    $form['post_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post Type Filter'),
      '#default_value' => $config->get('post_type'),
      '#description' => $this->t('Select which type of posts to display from your Facebook Page.'),
      '#options' => $post_type_options,
      '#empty_option' => $this->t('- Select -'),
      '#states' => [
        'visible' => [
          ':input[name="all_types"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="all_types"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['display_pic'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Post Picture'),
      '#default_value' => $config->get('display_pic'),
      '#states' => [
        'visible' => [
          ':input[name="post_type"]' => ['value' => 2],
        ],
      ],
    ];
    $form['display_video'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Post Video'),
      '#default_value' => $config->get('display_video'),
      '#states' => [
        'visible' => [
          ':input[name="post_type"]' => ['value' => 3],
        ],
      ],
    ];
    $form['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Post Text Length'),
      '#default_value' => $config->get('trim_length'),
      '#description' => $this->t('Maximum number of characters to display from post text. Leave empty to show full text.'),
      '#size' => 60,
      '#maxlength' => 60,
      '#min' => 0,
    ];
    $form['teaser_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Read More" Link Text'),
      '#default_value' => $config->get('teaser_text'),
      '#description' => $this->t('Text to display for the "read more" link when post text is trimmed. For example, "Read more" or "Continue reading".'),
      '#size' => 60,
      '#maxlength' => 60,
    ];
    $form['hashtag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Hashtags'),
      '#description' => $this->t('Show hashtags from post content.'),
      '#default_value' => $config->get('hashtag'),
    ];
    $form['time_stamp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Post Date/Time'),
      '#description' => $this->t('Display when the post was published.'),
      '#default_value' => $config->get('time_stamp'),
    ];
    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/Time Format'),
      '#default_value' => $config->get('time_format'),
      '#description' => $this->t('PHP date format string (e.g., <code>Y-m-d H:i:s</code> for "2024-03-15 14:30:00" or <code>F j, Y</code> for "March 15, 2024"). See <a href="@datetime" target="_blank">PHP date format documentation</a> for all available options.', [
        '@datetime' => 'https://www.php.net/manual/en/datetime.format.php',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['style_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Style Settings'),
      '#open' => TRUE,
    ];

    $form['style_settings']['use_facebook_style'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Default Facebook UI Style'),
      '#description' => $this->t('Check this to apply Facebook-like styling to posts (recommended). Uncheck if you want to provide your own custom CSS styling.'),
      '#default_value' => $config->get('use_facebook_style') ?? TRUE,
    ];

    $form['style_settings']['style_help'] = [
      '#type' => 'markup',
      '#markup' => '<div class="description">' . $this->t('<strong>Facebook UI Style:</strong> Posts will be styled to look like Facebook embeds with proper layout, spacing, and visual hierarchy.<br><strong>Custom Style:</strong> Minimal markup will be provided for you to style with your own CSS. Use classes like <code>.socialfeed-facebook-custom</code>, <code>.fb-message</code>, <code>.fb-pic</code>, etc.') . '</div>',
    ];

    // Secondary action: Test connection without saving all values.
    $form['actions']['test_connection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test connection'),
      '#submit' => ['::testConnection'],
      '#limit_validation_errors' => [
        ['page_name'],
        ['app_id'],
        ['secret_key'],
        ['user_token'],
      ],
      '#button_type' => 'secondary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.facebook.settings');
    $keys = [
      'page_name', 'app_id', 'secret_key', 'user_token',
      'no_feeds', 'all_types', 'post_type',
      'display_pic', 'display_video',
      'trim_length', 'teaser_text',
      'hashtag', 'time_stamp', 'time_format',
    ];
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    // use_facebook_style is nested under the style_settings wrapper.
    $style = $form_state->getValue('style_settings');
    if (is_array($style) && array_key_exists('use_facebook_style', $style)) {
      $config->set('use_facebook_style', $style['use_facebook_style']);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for Test connection.
   */
  public function testConnection(array &$form, FormStateInterface $form_state): void {
    $version = 'v24.0';
    $page_input = (string) $form_state->getValue('page_name');
    $app_id = (string) $form_state->getValue('app_id');
    $app_secret = (string) $form_state->getValue('secret_key');
    $user_token = (string) $form_state->getValue('user_token');

    $page_key = $this->normalizePageInput($page_input);

    if ($page_key === '' || $app_id === '' || $app_secret === '' || $user_token === '') {
      $this->messenger->addError($this->t('Please fill Page Name, App ID, App Secret, and Page Access Token before testing.'));
      return;
    }

    // Step 1: Try direct page id lookup with user token.
    $resolved_id = '';
    try {
      $url = "https://graph.facebook.com/{$version}/{$page_key}?fields=id&access_token={$user_token}";
      $res = $this->httpClient->request('GET', $url);
      $data = json_decode($res->getBody()->getContents(), TRUE);
      $resolved_id = (string) ($data['id'] ?? '');
    }
    catch (\Throwable $e) {
      // Continue fallback.
    }

    // Step 2: Fallback via /me/accounts if not resolved.
    $page_access_token = '';
    if ($resolved_id === '') {
      try {
        $url = "https://graph.facebook.com/{$version}/me/accounts?access_token={$user_token}";
        $res = $this->httpClient->request('GET', $url);
        $payload = json_decode($res->getBody()->getContents(), TRUE);
        foreach ($payload['data'] ?? [] as $acc) {
          $id = (string) ($acc['id'] ?? '');
          $name = (string) ($acc['name'] ?? '');
          $username = (string) ($acc['username'] ?? '');
          $matches_id = preg_match('/^\d+$/', $page_key) && $id === $page_key;
          $matches_name = strcasecmp($name, $page_key) === 0 || strcasecmp($username, $page_key) === 0;
          if ($matches_id || $matches_name) {
            $resolved_id = $id;
            $page_access_token = (string) ($acc['access_token'] ?? '');
            break;
          }
        }
      }
      catch (\Throwable $e) {
        // Continue fallback.
      }
    }

    // Step 3: Last-ditch ID lookup with app token only (no page token).
    if ($resolved_id === '') {
      try {
        $app_token = $app_id . '|' . $app_secret;
        $url = "https://graph.facebook.com/{$version}/{$page_key}?fields=id&access_token={$app_token}";
        $res = $this->httpClient->request('GET', $url);
        $data = json_decode($res->getBody()->getContents(), TRUE);
        $resolved_id = (string) ($data['id'] ?? '');
      }
      catch (\Throwable $e) {
        // Give up.
      }
    }

    if ($resolved_id === '' || !preg_match('/^\d+$/', $resolved_id)) {
      $this->messenger->addError($this->t('Could not resolve a numeric Page ID for %page. Ensure this is a Facebook Page (not a personal profile) and that your token has pages_read_engagement and pages_show_list permissions.', ['%page' => $page_input]));
      return;
    }

    // If we do not yet have a page access token, try to obtain it now via
    // /me/accounts.
    if ($page_access_token === '') {
      try {
        $url = "https://graph.facebook.com/{$version}/me/accounts?access_token={$user_token}";
        $res = $this->httpClient->request('GET', $url);
        $payload = json_decode($res->getBody()->getContents(), TRUE);
        foreach ($payload['data'] ?? [] as $acc) {
          if ((string) ($acc['id'] ?? '') === $resolved_id) {
            $page_access_token = (string) ($acc['access_token'] ?? '');
            break;
          }
        }
      }
      catch (\Throwable $e) {
        // Ignore; we will still save the ID with a warning.
      }
    }

    // Save resolved ID and page token (if found) to config.
    $config = $this->config('socialfeed.facebook.settings');
    $config->set('page_id', $resolved_id);
    if ($page_access_token !== '') {
      $config->set('page_permanent_token', $page_access_token);
    }
    $config->save();

    if ($page_access_token !== '') {
      $this->messenger->addStatus($this->t('Success. Resolved Page ID: %id and stored a Page Access Token.', ['%id' => $resolved_id]));
    }
    else {
      $this->messenger->addWarning($this->t('Resolved Page ID: %id, but a Page Access Token was not found via /me/accounts. The block may still render using your provided token, but we recommend generating a Page token.', ['%id' => $resolved_id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'socialfeed.facebook.settings',
    ];
  }

}

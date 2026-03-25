<?php

namespace Drupal\socialfeed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure X (formerly Twitter) API settings.
 *
 * This form allows administrators to configure integration with the X API v2
 * to display posts from an X account on the Drupal site.
 *
 * @package Drupal\socialfeed\Form
 */
class TwitterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.twitter.settings');

    $form['header'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' . $this->t('<h3>X (formerly Twitter) API v2 Setup</h3>
        <p>This module displays posts from an X account on your Drupal site using the X API v2.</p>
        <p><strong>⚠️ Important:</strong> X API uses pay-per-usage pricing — you purchase credits upfront and they are deducted per request. The free tier does <strong>not</strong> support reading posts; you must purchase credits to use the timeline endpoint. Review <a href="@pricing" target="_blank">X API pricing</a> before proceeding.</p>
        <h4>Requirements</h4>
        <ul>
          <li><strong>X Developer Account:</strong> You need an approved X Developer account at <a href="@portal" target="_blank">console.x.com</a>.</li>
          <li><strong>API Credits:</strong> Reading posts requires purchased credits. The free tier only allows posting, not reading.</li>
          <li><strong>Bearer Token:</strong> Required for v2 API authentication.</li>
          <li><strong>Account ID:</strong> The numeric user ID of the X account whose posts you want to display.</li>
        </ul>
        <h4>Setup Steps</h4>
        <ol>
          <li><strong>Create Developer Account:</strong> Go to the <a href="@portal" target="_blank">X Developer Console</a> and sign up for a developer account if you don\'t have one.</li>
          <li><strong>Create Project and App:</strong> In the Developer Console, create a new Project, then create an App within that project.</li>
          <li><strong>Configure App Settings:</strong> In your app settings, select "Web App, Automated App or Bot" and enable "OAuth 1.0a" under User authentication settings.</li>
          <li><strong>Get API Keys:</strong> Navigate to your app\'s "Keys and tokens" tab to find:
            <ul>
              <li>API Key (Consumer Key)</li>
              <li>API Key Secret (Consumer Secret)</li>
              <li>Bearer Token</li>
            </ul>
          </li>
          <li><strong>Generate Access Tokens:</strong> In the same "Keys and tokens" tab, generate:
            <ul>
              <li>Access Token</li>
              <li>Access Token Secret</li>
            </ul>
          </li>
          <li><strong>Purchase Credits:</strong> Buy API credits at <a href="@pricing" target="_blank">developer.x.com</a> to enable reading posts.</li>
          <li><strong>Find your Account ID:</strong> Your numeric X user ID can be found using a service like <a href="@tweeterid" target="_blank">tweeterid.com</a> or the X API users/me endpoint.</li>
        </ol>
        <p>For detailed information, see the <a href="@docs" target="_blank">X API v2 Documentation</a>.</p>', [
          '@portal' => 'https://console.x.com',
          '@docs' => 'https://docs.x.com/x-api',
          '@pricing' => 'https://developer.x.com/#pricing',
          '@tweeterid' => 'https://tweeterid.com',
        ]) . '</div>',
    ];
    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key (Consumer Key)'),
      '#default_value' => $config->get('consumer_key'),
      '#description' => $this->t('Found in the <a href="@portal" target="_blank">X Developer Portal</a> → Your Project → Your App → Keys and tokens tab.', [
        '@portal' => 'https://console.x.com',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key Secret (Consumer Secret)'),
      '#default_value' => $config->get('consumer_secret'),
      '#description' => $this->t('Found in the <a href="@portal" target="_blank">X Developer Portal</a> → Your Project → Your App → Keys and tokens tab. <strong>Keep this secret and never commit it to version control.</strong>', [
        '@portal' => 'https://console.x.com',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $config->get('access_token'),
      '#description' => $this->t('Generated in the <a href="@portal" target="_blank">X Developer Portal</a> → Your Project → Your App → Keys and tokens tab.', [
        '@portal' => 'https://console.x.com',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token Secret'),
      '#default_value' => $config->get('access_token_secret'),
      '#description' => $this->t('Generated alongside the Access Token in the <a href="@portal" target="_blank">X Developer Portal</a>. <strong>Keep this secret and never commit it to version control.</strong>', [
        '@portal' => 'https://console.x.com',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['bearer_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bearer Token'),
      '#default_value' => $config->get('bearer_token'),
      '#description' => $this->t('Required for X API v2 authentication. Found in the <a href="@portal" target="_blank">X Developer Portal</a> → Your Project → Your App → Keys and tokens tab.', [
        '@portal' => 'https://console.x.com',
      ]),
      '#size' => 60,
      '#maxlength' => 200,
      '#required' => TRUE,
    ];
    $form['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#default_value' => $config->get('account_id'),
      '#description' => $this->t('The numeric user ID of the X account whose posts you want to display. You can find your numeric ID using <a href="@tweeterid" target="_blank">tweeterid.com</a> or the X API users/me endpoint.', [
        '@tweeterid' => 'https://tweeterid.com',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['tweets_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Posts to Display'),
      '#default_value' => $config->get('tweets_count'),
      '#description' => $this->t('The number of posts to fetch and display from the timeline. Note: API rate limits may apply depending on your API tier.'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
    ];
    // @todo Move these to the block form; Update theme implementation.
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
    $form['time_ago'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Relative Dates'),
      '#description' => $this->t('Display dates in relative style (e.g., "2h ago", "3d ago") instead of absolute dates.'),
      '#default_value' => $config->get('time_ago'),
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/Time Format'),
      '#default_value' => $config->get('time_format'),
      '#description' => $this->t('PHP date format string (e.g., <code>Y-m-d H:i:s</code> for "2024-03-15 14:30:00" or <code>F j, Y</code> for "March 15, 2024"). Only used when relative dates are disabled. See <a href="@datetime" target="_blank">PHP date format documentation</a> for all available options.', [
        '@datetime' => 'https://www.php.net/manual/en/datetime.format.php',
      ]),
      '#size' => 60,
      '#maxlength' => 100,
      '#states' => [
        'visible' => [
          ':input[name="time_stamp"]' => ['checked' => TRUE],
          ':input[name="time_ago"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Post Text Length'),
      '#default_value' => $config->get('trim_length'),
      '#description' => $this->t('Maximum number of characters to display from post text (posts can be up to 280 characters). Leave empty or set to 0 to show full text.'),
      '#size' => 60,
      '#maxlength' => 280,
      '#min' => 0,
      '#max' => 280,
    ];
    $form['teaser_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Read More" Link Text'),
      '#default_value' => $config->get('teaser_text'),
      '#description' => $this->t('Text to display for the "read more" link when post text is trimmed. For example, "Read more" or "View full post".'),
      '#size' => 60,
      '#maxlength' => 60,
    ];

    $form['style_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Style Settings'),
      '#open' => TRUE,
    ];
    $form['style_settings']['use_twitter_style'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Default X UI Style'),
      '#description' => $this->t('Check to apply an X-like layout and styles. Uncheck to output minimal markup you can style yourself.'),
      '#default_value' => $config->get('use_twitter_style') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('socialfeed.twitter.settings');
    $keys = [
      'consumer_key', 'consumer_secret',
      'access_token', 'access_token_secret',
      'bearer_token', 'account_id',
      'tweets_count', 'hashtag', 'time_stamp', 'time_format',
      'time_ago', 'trim_length', 'teaser_text',
    ];
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    // use_twitter_style is nested under the style_settings wrapper.
    $style = $form_state->getValue('style_settings');
    if (is_array($style) && array_key_exists('use_twitter_style', $style)) {
      $config->set('use_twitter_style', $style['use_twitter_style']);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'socialfeed.twitter.settings',
    ];
  }

}

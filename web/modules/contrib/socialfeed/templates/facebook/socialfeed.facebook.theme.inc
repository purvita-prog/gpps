<?php

/**
 * @file
 * Provides preprocess functions for Socialfeed Facebook templates.
 */

use Carbon\Carbon;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Preprocess for socialfeed_facebook_post.
 */
function template_preprocess_socialfeed_facebook_post(array &$variables): void {
  $facebook_settings = \Drupal::config('socialfeed.facebook.settings');
  $use_facebook_hashtag = (bool) $facebook_settings->get('hashtag');
  $should_display_time = (bool) $facebook_settings->get('time_stamp');
  $teaser_text = $facebook_settings->get('teaser_text');
  $use_facebook_style = $facebook_settings->get('use_facebook_style') ?? TRUE;

  $post = &$variables['post'];

  // Expose the raw permalink URL for use in template link wrappers (e.g.
  // wrapping images). The post value is later replaced with a rendered Link.
  $variables['permalink_url'] = $post['permalink_url'] ?? NULL;

  // Provide a plain-text alt for post images, derived from the raw message
  // before it is converted to a render array.
  $raw_message = $post['message'] ?? '';
  $variables['image_alt'] = $raw_message !== '' ? mb_substr(strip_tags($raw_message), 0, 120) : (string) new TranslatableMarkup('Facebook post');

  // Pass style setting and attach CSS if enabled.
  $variables['use_facebook_style'] = $use_facebook_style;
  if ($use_facebook_style) {
    $variables['#attached']['library'][] = 'socialfeed/facebook_style';
  }

  if (isset($post['message'])) {
    $trim = (int) $facebook_settings->get('trim_length');
    if ($trim > 0) {
      $post['message'] = mb_substr($post['message'], 0, $trim);
    }
  }

  if (!empty($post['permalink_url'])) {
    $post['permalink_url'] = Link::fromTextAndUrl(
      new TranslatableMarkup('@teaser_text', [
        '@teaser_text' => !empty($teaser_text) ? $teaser_text : $post['permalink_url'],
      ]),
      Url::fromUri(
        $post['permalink_url'],
        [
          'attributes' => [
            'target' => '_blank',
            'rel' => 'noopener',
          ],
        ]
      )
    )->toString();
  }

  if ($use_facebook_hashtag) {
    $post['message'] = preg_replace_callback(
      '/#(\\w+)/',
      static function ($hash) {
        return Link::fromTextAndUrl(
          $hash[0],
          Url::fromUri(
            '//facebook.com/hashtag/' . $hash[1],
            [
              'attributes' => [
                'target' => '_blank',
                'rel' => 'noopener',
              ],
            ]
          )
        )->toString();
      },
      !empty($post['message']) ? $post['message'] : ''
    );
  }

  if ($should_display_time && !empty($post['created_time'])) {
    $variables['should_display_time'] = TRUE;
    $timestamp = strtotime($post['created_time']);
    if ($timestamp) {
      $formatted_date = Carbon::createFromTimestamp($timestamp);
      $post['created_time'] = $formatted_date->format($facebook_settings->get('time_format'));
    }
  }

  if (isset($post['message'])) {
    $post['message'] = ['#markup' => $post['message']];
  }
}

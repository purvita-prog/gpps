<?php

/**
 * @file
 * Provides preprocess functions for Socialfeed Twitter templates.
 */

use Carbon\Carbon;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Preprocess for socialfeed_twitter_post.
 */
function template_preprocess_socialfeed_twitter_post(array &$variables): void {
  if (empty($variables['post']) || !is_object($variables['post'])) {
    unset($variables['post']);
    return;
  }

  $twitter_settings = \Drupal::config('socialfeed.twitter.settings');
  $display_time = (bool) $twitter_settings->get('time_stamp');
  $display_date_twitter_style = (bool) $twitter_settings->get('time_ago');
  $twitter_hash_tag = (bool) $twitter_settings->get('hashtag');
  $time_format = $twitter_settings->get('time_format');
  $trim_length = (int) $twitter_settings->get('trim_length');
  $teaser_text = $twitter_settings->get('teaser_text');

  // Pass style setting and attach CSS if enabled.
  $variables['use_twitter_style'] = $twitter_settings->get('use_twitter_style') ?? TRUE;
  if ($variables['use_twitter_style']) {
    $variables['#attached']['library'][] = 'socialfeed/twitter_style';
  }

  $post = &$variables['post'];
  $post->username = $post->user->screen_name;
  $post->full_username = '//x.com/' . $post->username;
  $post->text = $post->full_text;

  preg_match_all('#\\bhttps?://[^\\s()<>]+(?:\\([\\w\\d]+\\)|([^[:punct:]\\s]|/))#', $post->text, $extra_links);
  foreach ($extra_links[0] as $extra_link) {
    $post->text = str_replace(
      $extra_link,
      Link::fromTextAndUrl(
        $extra_link,
        Url::fromUri(
          $extra_link,
          [
            'attributes' => [
              'target' => '_blank',
              'rel' => 'noopener',
            ],
          ]
        )
      )->toString(),
      $post->text
    );
  }

  $tweet = '';
  if (isset($post->text)) {
    $tweet = mb_substr(rtrim($post->text), 0, $trim_length);
  }

  $tweet_url = NULL;
  if (!empty($teaser_text)) {
    // Use the first expanded URL from entities (v2 format), or fall back to
    // the tweet permalink.
    $link_target = NULL;
    if (isset($post->entities->urls[0]->expanded_url)) {
      $link_target = $post->entities->urls[0]->expanded_url;
    }
    elseif (!empty($post->username) && !empty($post->id_str)) {
      $link_target = '//x.com/' . $post->username . '/status/' . $post->id_str;
    }
    if ($link_target) {
      $tweet_url = Link::fromTextAndUrl(
        $teaser_text,
        Url::fromUri(
          $link_target,
          [
            'attributes' => [
              'target' => '_blank',
              'rel' => 'noopener',
            ],
          ]
        )
      )->toString();
    }
  }

  if ($display_time) {
    $formatted_twitter_date = Carbon::parse($post->created_at);
    if ($display_date_twitter_style) {
      $post->twitter_date = socialfeed_time_elapsed_string($formatted_twitter_date);
    }
    else {
      $post->twitter_date = $formatted_twitter_date->format($time_format);
    }
  }

  if ($twitter_hash_tag) {
    $tweet = preg_replace_callback(
      '/#(\\w+)|@(\\w+)/u',
      static function ($hash) {
        if ($hash[0][0] === '#') {
          return Link::fromTextAndUrl(
            $hash[0],
            Url::fromUri(
              '//x.com/hashtag/' . $hash[1],
              [
                'attributes' => [
                  'target' => '_blank',
                  'rel' => 'noopener',
                ],
              ]
            )
          )->toString();
        }
        if ($hash[0][0] === '@') {
          return Link::fromTextAndUrl(
            $hash[0],
            Url::fromUri(
              '//x.com/' . $hash[2],
              [
                'attributes' => [
                  'target' => '_blank',
                  'rel' => 'noopener',
                ],
              ]
            )
          )->toString();
        }
      },
      $tweet
    );
  }
  $variables['tweet'] = ['#markup' => $tweet];
  $variables['tweet_url'] = $tweet_url;
}

/**
 * Relative time helper.
 */
function socialfeed_time_elapsed_string($datetime) {
  return Carbon::instance($datetime)->diffForHumans();
}

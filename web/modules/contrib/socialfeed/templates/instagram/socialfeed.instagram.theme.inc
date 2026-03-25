<?php

/**
 * @file
 * Provides preprocess functions for Socialfeed Instagram templates.
 */

/**
 * Common preprocess for all Instagram post types.
 *
 * Sets the use_instagram_style variable and attaches the CSS library.
 */
function _socialfeed_instagram_preprocess(array &$variables): void {
  $config = \Drupal::config('socialfeed.instagram.settings');
  $variables['use_instagram_style'] = $config->get('use_instagram_style') ?? TRUE;
  if ($variables['use_instagram_style']) {
    $variables['#attached']['library'][] = 'socialfeed/instagram_style';
  }
}

/**
 * Preprocess for socialfeed_instagram_post_image.
 */
function template_preprocess_socialfeed_instagram_post_image(array &$variables): void {
  _socialfeed_instagram_preprocess($variables);
}

/**
 * Preprocess for socialfeed_instagram_post_video.
 */
function template_preprocess_socialfeed_instagram_post_video(array &$variables): void {
  _socialfeed_instagram_preprocess($variables);
}

/**
 * Preprocess for socialfeed_instagram_post_carousel_album.
 */
function template_preprocess_socialfeed_instagram_post_carousel_album(array &$variables): void {
  _socialfeed_instagram_preprocess($variables);
}

<?php

/**
 * @file
 * Post-update functions for the socialfeed module.
 */

/**
 * Add bearer_token and account_id required by the X API v2 upgrade.
 *
 * Existing sites won't have these keys; fresh installs get them from
 * config/install/socialfeed.twitter.settings.yml.
 */
function socialfeed_post_update_add_twitter_v2_config(): void {
  $config = \Drupal::configFactory()->getEditable('socialfeed.twitter.settings');
  if ($config->get('bearer_token') === NULL) {
    $config->set('bearer_token', '');
  }
  if ($config->get('account_id') === NULL) {
    $config->set('account_id', '');
  }
  $config->save();
}

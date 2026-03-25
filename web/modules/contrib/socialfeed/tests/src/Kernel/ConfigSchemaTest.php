<?php

namespace Drupal\Tests\socialfeed\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests config schema and default values for socialfeed.
 *
 * @group socialfeed
 */
class ConfigSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['socialfeed'];

  /**
   * Tests that module installs and default config is valid against schema.
   */
  public function testDefaultConfigIsValid(): void {
    $this->installConfig(['socialfeed']);

    // Facebook defaults.
    $fb = $this->config('socialfeed.facebook.settings');
    $this->assertEquals(10, $fb->get('no_feeds'));
    $this->assertTrue($fb->get('all_types'));
    $this->assertEquals('d-M-Y', $fb->get('time_format'));
    $this->assertSame('', $fb->get('page_name'));

    // Twitter defaults.
    $tw = $this->config('socialfeed.twitter.settings');
    $this->assertEquals(3, $tw->get('tweets_count'));
    $this->assertTrue($tw->get('hashtag'));
    $this->assertTrue($tw->get('time_stamp'));
    $this->assertEquals(280, $tw->get('trim_length'));

    // Instagram defaults.
    $ig = $this->config('socialfeed.instagram.settings');
    $this->assertEquals(3, $ig->get('picture_count'));
    $this->assertFalse($ig->get('video_thumbnail'));
    $this->assertTrue($ig->get('post_link'));
  }

  /**
   * Tests that config can be saved and read back correctly.
   */
  public function testConfigSaveAndLoad(): void {
    $this->installConfig(['socialfeed']);

    // Save Facebook config.
    $config = $this->config('socialfeed.facebook.settings');
    $config->set('page_name', 'TestPage');
    $config->set('app_id', 'test_app_123');
    $config->set('no_feeds', 25);
    $config->set('use_facebook_style', TRUE);
    $config->save();

    // Re-read from storage.
    $reloaded = $this->config('socialfeed.facebook.settings');
    $this->assertEquals('TestPage', $reloaded->get('page_name'));
    $this->assertEquals('test_app_123', $reloaded->get('app_id'));
    $this->assertEquals(25, $reloaded->get('no_feeds'));
    $this->assertTrue($reloaded->get('use_facebook_style'));
  }

  /**
   * Tests Instagram access_token_date stores integer (timestamp) correctly.
   */
  public function testInstagramAccessTokenDateIsInteger(): void {
    $this->installConfig(['socialfeed']);

    $config = $this->config('socialfeed.instagram.settings');
    $timestamp = time();
    $config->set('access_token_date', $timestamp);
    $config->save();

    $reloaded = $this->config('socialfeed.instagram.settings');
    $this->assertSame($timestamp, $reloaded->get('access_token_date'));
  }

  /**
   * Tests Instagram video_thumbnail stores boolean correctly.
   */
  public function testInstagramVideoThumbnailIsBoolean(): void {
    $this->installConfig(['socialfeed']);

    $config = $this->config('socialfeed.instagram.settings');
    $config->set('video_thumbnail', TRUE);
    $config->save();

    $reloaded = $this->config('socialfeed.instagram.settings');
    $this->assertTrue($reloaded->get('video_thumbnail'));
  }

}

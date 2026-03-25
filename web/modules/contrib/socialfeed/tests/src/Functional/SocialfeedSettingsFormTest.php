<?php

namespace Drupal\Tests\socialfeed\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the socialfeed settings forms.
 *
 * @group socialfeed
 */
class SocialfeedSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['socialfeed', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer socialfeed.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user without socialfeed permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer socialfeed',
      'administer blocks',
    ]);
    $this->regularUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests access control on settings forms.
   */
  public function testSettingsFormAccess(): void {
    // Anonymous user should be denied.
    $this->drupalGet('admin/config/socialfeed/facebook');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/socialfeed/twitter');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/socialfeed/instagram');
    $this->assertSession()->statusCodeEquals(403);

    // Regular user should be denied.
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('admin/config/socialfeed/facebook');
    $this->assertSession()->statusCodeEquals(403);

    // Admin user should have access.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/socialfeed/facebook');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/socialfeed/twitter');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/socialfeed/instagram');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests Facebook settings form submission.
   */
  public function testFacebookSettingsForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/socialfeed/facebook');

    $this->assertSession()->fieldExists('page_name');
    $this->assertSession()->fieldExists('app_id');
    $this->assertSession()->fieldExists('secret_key');
    $this->assertSession()->fieldExists('user_token');
    $this->assertSession()->fieldExists('no_feeds');

    $this->submitForm([
      'page_name' => 'TestPage',
      'app_id' => 'test_app_id',
      'secret_key' => 'test_secret',
      'user_token' => 'test_token',
      'no_feeds' => 15,
      'trim_length' => 200,
      'teaser_text' => 'See more',
    ], 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = $this->config('socialfeed.facebook.settings');
    $this->assertEquals('TestPage', $config->get('page_name'));
    $this->assertEquals('test_app_id', $config->get('app_id'));
    $this->assertEquals(15, $config->get('no_feeds'));
    $this->assertEquals(200, $config->get('trim_length'));
  }

  /**
   * Tests Twitter settings form submission.
   */
  public function testTwitterSettingsForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/socialfeed/twitter');

    $this->assertSession()->fieldExists('consumer_key');
    $this->assertSession()->fieldExists('consumer_secret');
    $this->assertSession()->fieldExists('access_token');
    $this->assertSession()->fieldExists('access_token_secret');
    $this->assertSession()->fieldExists('bearer_token');
    $this->assertSession()->fieldExists('account_id');

    $this->submitForm([
      'consumer_key' => 'ck_test',
      'consumer_secret' => 'cs_test',
      'access_token' => 'at_test',
      'access_token_secret' => 'ats_test',
      'bearer_token' => 'bt_test',
      'account_id' => '123456789',
      'tweets_count' => 5,
      'trim_length' => 140,
      'teaser_text' => 'More',
    ], 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = $this->config('socialfeed.twitter.settings');
    $this->assertEquals('ck_test', $config->get('consumer_key'));
    $this->assertEquals(5, $config->get('tweets_count'));
    $this->assertEquals(140, $config->get('trim_length'));
  }

  /**
   * Tests Instagram settings form submission.
   */
  public function testInstagramSettingsForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/socialfeed/instagram');

    $this->assertSession()->fieldExists('client_id');
    $this->assertSession()->fieldExists('app_secret');
    $this->assertSession()->fieldExists('picture_count');

    $this->submitForm([
      'client_id' => 'ig_client_test',
      'app_secret' => 'ig_secret_test',
      'picture_count' => 9,
      'video_thumbnail' => TRUE,
      'post_link' => FALSE,
    ], 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $config = $this->config('socialfeed.instagram.settings');
    $this->assertEquals('ig_client_test', $config->get('client_id'));
    $this->assertEquals(9, $config->get('picture_count'));
  }

  /**
   * Tests the socialfeed configuration landing page.
   */
  public function testConfigurationLandingPage(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/services/socialfeed');
    $this->assertSession()->statusCodeEquals(200);
  }

}

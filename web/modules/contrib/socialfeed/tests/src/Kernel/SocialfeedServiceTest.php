<?php

namespace Drupal\Tests\socialfeed\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\socialfeed\Services\FacebookPostCollectorFactory;
use Drupal\socialfeed\Services\InstagramApiService;
use Drupal\socialfeed\Services\InstagramPostCollectorFactory;
use Drupal\socialfeed\Services\TwitterPostCollectorFactory;

/**
 * Tests that socialfeed services are properly registered.
 *
 * @group socialfeed
 */
class SocialfeedServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['socialfeed'];

  /**
   * Tests that all services are available from the container.
   */
  public function testServicesExist(): void {
    $this->assertInstanceOf(
      FacebookPostCollectorFactory::class,
      $this->container->get('socialfeed.facebook')
    );
    $this->assertInstanceOf(
      TwitterPostCollectorFactory::class,
      $this->container->get('socialfeed.twitter')
    );
    $this->assertInstanceOf(
      InstagramPostCollectorFactory::class,
      $this->container->get('socialfeed.instagram')
    );
    $this->assertInstanceOf(
      InstagramApiService::class,
      $this->container->get('socialfeed.instagram_api')
    );
  }

}

<?php

namespace Drupal\navigation_plus\Hook;

use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\navigation_plus\NavigationPlusUi;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for navigation_plus.
 */
class NavigationPlusHooks {

  use StringTranslationTrait;

  public function __construct(
    protected NavigationPlusUi $ui,
  ) {
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top', order: Order::Last)]
  public function pageTop(array &$page_top): void {
    $this->ui->buildPageTop($page_top);
  }
}

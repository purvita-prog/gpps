<?php

declare(strict_types=1);

namespace Drupal\lb_plus\Plugin\Tool;

use Drupal\lb_plus\LbPlusToolTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\navigation_plus\Attribute\Tool;
use Drupal\navigation_plus\ToolPluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the tool.
 */
#[Tool(
  id: 'configure',
  label: new TranslatableMarkup('Configure'),
  hot_key: 'o',
  weight: 120,
)]
final class Configure extends ToolPluginBase {

  use LbPlusToolTrait;

  /**
   * {@inheritdoc}
   */
  public function getIconsPath(): array {
    $path = $this->extensionList->getPath('lb_plus');
    return [
      'pack_id' => 'lb_plus',
      'icon_id' => 'gear',
      'mouse_icon' => "url('/$path/assets/gear-mouse.svg') 3 3",
      'tool_indicator_icons' => [
        'section' => "/$path/assets/gear-white.svg",
        'block' => "/$path/assets/gear-bold-blue.svg",
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addAttachments(array &$attachments): void {
    $attachments['library'][] = 'lb_plus/configure';
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $this->lbPlusToolApplies($entity);
  }


}

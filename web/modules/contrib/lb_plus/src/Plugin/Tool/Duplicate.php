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
  id: 'duplicate',
  label: new TranslatableMarkup('Duplicate'),
  hot_key: 'd',
  weight: 140,
)]
final class Duplicate extends ToolPluginBase {

  use LbPlusToolTrait;

  /**
   * {@inheritdoc}
   */
  public function getIconsPath(): array {
    $path = $this->extensionList->getPath('lb_plus');
    return [
      'pack_id' => 'lb_plus',
      'icon_id' => 'duplicate',
      'mouse_icon' => "url('/$path/assets/duplicate-mouse.svg') 3 3",
      'tool_indicator_icons' => [
        'block' => "/$path/assets/duplicate-bold-blue.svg",
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addAttachments(array &$attachments): void {
    $attachments['library'][] = 'lb_plus/duplicate';
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $this->lbPlusToolApplies($entity);
  }


}

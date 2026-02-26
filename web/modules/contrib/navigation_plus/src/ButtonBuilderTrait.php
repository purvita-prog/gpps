<?php

declare(strict_types=1);

namespace Drupal\navigation_plus;

use Drupal\Core\Url;

/**
 * Provides a helper method for building top bar buttons with icon support.
 */
trait ButtonBuilderTrait {

  /**
   * Build a top bar button with icon support.
   *
   * @param string $id
   *   The button ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $label
   *   The button label.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $title
   *   The button title (tooltip).
   * @param string $pack_id
   *   The icon pack ID.
   * @param string $icon_id
   *   The icon ID.
   * @param \Drupal\Core\Url|array|null $url_or_attributes
   *   Optional URL for link buttons, or extra attributes for container buttons.
   *
   * @return array
   *   A render array for the button.
   */
  protected function buildTopBarButton(string $id, $label, $title, string $pack_id, string $icon_id, $url_or_attributes = NULL): array {
    $is_link = $url_or_attributes instanceof Url;

    $button = [
      'wrapper' => [
        '#type' => $is_link ? 'link' : 'container',
        '#attributes' => [
          'id' => $id,
          'title' => $title,
          'data-icon-text' => substr((string) $label, 0, 2),
          'class' => ['toolbar-button', 'toolbar-button--collapsible'],
        ],
        'label' => [
          '#type' => 'inline_template',
          '#template' => "{{ icon(pack_id, icon_id, { class: 'toolbar-button__icon', size: 20 }) }}<span class='toolbar-button__label'>{{label}}</span>",
          '#context' => [
            'id' => $id,
            'label' => $label,
            'title' => $title,
            'pack_id' => $pack_id,
            'icon_id' => $icon_id,
          ],
        ],
      ],
    ];

    // Add URL for link buttons.
    if ($is_link) {
      $button['wrapper']['#url'] = $url_or_attributes;
      $button['wrapper']['#title'] = $button['wrapper']['label'];
      unset($button['wrapper']['label']);
    }
    // Add extra attributes for container buttons.
    elseif (is_array($url_or_attributes)) {
      $button['wrapper']['#attributes'] = array_merge($button['wrapper']['#attributes'], $url_or_attributes);
    }

    return $button;
  }

}

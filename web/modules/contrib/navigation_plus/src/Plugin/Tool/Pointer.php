<?php

declare(strict_types=1);

namespace Drupal\navigation_plus\Plugin\Tool;

use Drupal\user\Entity\User;
use Drupal\navigation_plus\Attribute\Tool;
use Drupal\navigation_plus\ToolPluginBase;
use Drupal\navigation_plus\ToolPluginManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the tool.
 */
#[Tool(
  id: 'pointer',
  label: new TranslatableMarkup('Preview'),
  hot_key: 'p',
  weight: 0,
)]
final class Pointer extends ToolPluginBase {

  use StringTranslationTrait;

  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ModuleExtensionList $extensionList, protected ToolPluginManager $toolManager, protected AccountProxyInterface $account) {
    parent::__construct($container, $configuration, $plugin_id, $plugin_definition, $extensionList);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module'),
      $container->get('plugin.manager.tools'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIconsPath(): array {
    $path = $this->extensionList->getPath('navigation_plus');
    return [
      'pack_id' => 'navigation_plus',
      'icon_id' => 'cursor',
      'mouse_icon' => "url('/$path/assets/pointer-mouse.svg') 3 3",
    ];
  }

}

<?php
/**
 * @file
 * Contains \Drupal\guifi\Controller\GuifiZoneController.
 */
namespace Drupal\guifi\Controller;
class GuifiZoneController {
  public function list() {
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello, World!'),
    );
  }
}

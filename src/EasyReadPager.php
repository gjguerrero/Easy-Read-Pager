<?php

namespace Drupal\easy_read_pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides utility functions for the EasyReadPager module.
 *
 * This class contains methods to enhance the usability and accessibility of paginated text fields,
 * supporting a more inclusive user experience.
 */
class AccessibilityHelper {

  /**
   * Integrates default configuration settings for pagination.
   *
   * @param array $settings The current settings array.
   * @return array Modified settings with defaults applied.
   */
  public static function integrateDefaultConfig($settings = []) {
    return [
      'pageIndexName' => 'page',
      'navigatePreviousNext' => true,
      'navigateFirstLast' => true,
      'navigateByNumbers' => true,
      'maxPagesToShow' => 5,
      'showPageSummary' => false,
    ] + $settings;
  }

  /**
   * Enhances form elements with pagination settings.
   *
   * @param array $form The form to be altered.
   * @param FormStateInterface $form_state The current state of the form.
   * @param object $instance The instance containing specific settings.
   * @param array $elements Additional elements to merge into the form.
   * @return array The modified form elements.
   */
  public static function enhanceFormWithSettings(array $form, FormStateInterface $form_state, $instance, $elements = []) {
    $elements['showPageSummary'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Display page summary'),
      '#description' => new TranslatableMarkup('Show a summary like "Page X of N".'),
      '#default_value' => $instance->getSetting('showPageSummary'),
    ];
    $elements['pageIndexName'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Index field name'),
      '#default_value' => $instance->getSetting('pageIndexName'),
      '#required' => TRUE,
    ];
    $elements['navigatePreviousNext'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Display Previous & Next'),
      '#default_value' => $instance->getSetting('navigatePreviousNext'),
    ];
    $elements['navigateFirstLast'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Display First & Last'),
      '#default_value' => $instance->getSetting('navigateFirstLast'),
    ];
    $elements['navigateByNumbers'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Display numeric page navigation'),
      '#default_value' => $instance->getSetting('navigateByNumbers'),
    ];
    $elements['maxPagesToShow'] = [
      '#type' => 'number',
      '#title' => new TranslatableMarkup('Maximum number of pages to display'),
      '#default_value' => $instance->getSetting('maxPagesToShow'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][navigateByNumbers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * Creates a summary of settings for display.
   *
   * @param array $summary The existing summary array.
   * @param object $instance The instance containing specific settings.
   * @return array The modified summary.
   */
  public static function createSettingsSummary($summary, $instance) {
    $maxPages = $instance->getSetting('maxPagesToShow');
    $summary[] = new TranslatableMarkup("Pagination settings (Max pages: @max)", ['@max' => $maxPages]);
    return $summary;
  }

  /**
   * Constructs the pagination view elements.
   *
   * @param int $totalItems The total number of items to paginate.
   * @param object $instance The instance containing specific settings.
   * @param array $fields The fields to include in the pagination.
   * @param array $settings Custom settings for the pagination.
   * @return array The modified elements including pagination controls.
   */
  public static function constructPaginationView($totalItems, $instance, $fields, $settings = []) {
    if ($totalItems < 2) {
      return $fields;
    }

    $currentIndex = (int) ($_GET[$instance->getSetting('pageIndexName')] ?? 0);
    $paginationElements = self::buildPaginationControls($totalItems, $currentIndex, $instance, $settings);

    return [
      '#theme' => 'easy_read_pager',
      '#index' => $currentIndex,
      '#items' => $paginationElements,
      '#settings' => $settings,
      'fields' => $fields,
      '#cache' => ['max-age' => 0], // Handle cache appropriately
    ];
  }

/**
 * Builds pagination controls based on settings and total items.
 *
 * @param int $totalItems Total items to paginate over.
 * @param int $currentIndex The current page index.
 * @param object $instance Instance with pagination settings.
 * @param array $settings Pagination settings.
 * @return array An array of pagination controls.
 */
private static function buildPaginationControls($totalItems, $currentIndex, $instance, $settings) {
  $items = [];
  $url = Url::fromRoute('<current>');

  // Calculate the previous and next page indexes
  $prevIndex = max($currentIndex - 1, 0);
  $nextIndex = min($currentIndex + 1, $totalItems - 1);

  // Determine visibility settings
  $showFirstLast = $settings['navigateFirstLast'] ?? $instance->getSetting('navigateFirstLast');
  $showPrevNext = $settings['navigatePreviousNext'] ?? $instance->getSetting('navigatePreviousNext');
  $showNumbered = $settings['navigateByNumbers'] ?? $instance->getSetting('navigateByNumbers');

  // First page link
  if ($showFirstLast && $currentIndex > 0) {
    $items[] = [
      'text' => new TranslatableMarkup('First'),
      'href' => $url->setRouteParameter($settings['pageIndexName'], 0)->toString(),
      'attributes' => ['aria-label' => new TranslatableMarkup('Go to the first page')],
    ];
  }

  // Previous page link
  if ($showPrevNext && $currentIndex > 0) {
    $items[] = [
      'text' => new TranslatableMarkup('Previous'),
      'href' => $url->setRouteParameter($settings['pageIndexName'], $prevIndex)->toString(),
      'attributes' => ['aria-label' => new TranslatableMarkup('Go to the previous page')],
    ];
  }

  // Numbered page links
  if ($showNumbered) {
    for ($i = 0; $i < $totalItems; $i++) {
      $items[] = [
        'text' => $i + 1,
        'href' => $url->setRouteParameter($settings['pageIndexName'], $i)->toString(),
        'attributes' => $i == $currentIndex ? ['class' => ['active'], 'aria-current' => 'page'] : [],
      ];
    }
  }

  // Next page link
  if ($showPrevNext && $currentIndex < $totalItems - 1) {
    $items[] = [
      'text' => new TranslatableMarkup('Next'),
      'href' => $url->setRouteParameter($settings['pageIndexName'], $nextIndex)->toString(),
      'attributes' => ['aria-label' => new TranslatableMarkup('Go to the next page')],
    ];
  }

  // Last page link
  if ($showFirstLast && $currentIndex < $totalItems - 1) {
    $items[] = [
      'text' => new TranslatableMarkup('Last'),
      'href' => $url->setRouteParameter($settings['pageIndexName'], $totalItems - 1)->toString(),
      'attributes' => ['aria-label' => new TranslatableMarkup('Go to the last page')],
    ];
  }

  return $items;
}
}

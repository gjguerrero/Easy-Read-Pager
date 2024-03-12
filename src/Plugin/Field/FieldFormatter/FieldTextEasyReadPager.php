<?php

namespace Drupal\easy_read_pager\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\easy_read_pager\AccessibilityHelper;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a formatter for text fields that supports easy reading with pagination.
 *
 * This formatter enhances text fields by adding pagination functionality, 
 * making content more accessible, especially for users needing smaller chunks of text.
 *
 * @FieldFormatter(
 *   id = "text_easy_read_pager",
 *   label = @Translation("Text with Easy Read Pagination"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class EasyReadTextFormatter extends TextDefaultFormatter {

  /**
   * Merges default pagination settings with formatter settings.
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return AccessibilityHelper::integrateDefaultConfig($settings);
  }

  /**
   * Enhances the formatter's settings form with pagination options.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return AccessibilityHelper::enhanceFormWithSettings($form, $form_state, $this, $elements);
  }

  /**
   * Summarizes the formatter's settings for the UI.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return AccessibilityHelper::createSettingsSummary($summary, $this);
  }

  /**
   * Renders the field items with pagination.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    // Adjust pagination view integration as necessary for your implementation.
    return AccessibilityHelper::constructPaginationView(count($items), $this, $elements);
  }

  /**
   * Generates the view elements for individual field items with accessible pagination.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $pageIndex = $this->getSetting('pageIndexName');
    $currentPage = (int) ($_GET[$pageIndex] ?? 0);

    if (!isset($items[$currentPage])) {
      // Consider handling not found situations with appropriate user feedback.
      // throw new NotFoundHttpException();
      return $elements;
    }
    
    $item = $items[$currentPage];

    // Adds the processed text element with necessary information for rendering.
    $elements[$currentPage] = [
      '#type' => 'processed_text',
      '#text' => $item->value,
      '#format' => $item->format,
      '#langcode' => $item->getLangcode(),
    ];

    return $elements;
  }

}

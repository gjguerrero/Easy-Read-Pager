<?php

namespace Drupal\easy_read_pager\Plugin\Field\FieldFormatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\easy_read_pager\AccessibilityHelper;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Provides a base class for easy read pager field formatters.
 * 
 * This abstract class extends the default text field formatter with functionality
 * to add pagination to text fields, improving accessibility for users by breaking 
 * text into manageable chunks.
 */
abstract class EasyReadPagerBaseFormatter extends TextDefaultFormatter {

  /**
   * Enhances default settings with easy read pager-specific options.
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return AccessibilityHelper::integrateDefaultConfig($settings);
  }

  /**
   * Builds the settings form with additional easy read pager configuration options.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return AccessibilityHelper::enhanceFormWithSettings($form, $form_state, $this, $elements);
  }

  /**
   * Summarizes the current settings for the easy read pager formatter.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return AccessibilityHelper::createSettingsSummary($summary, $this);
  }

  /**
   * Renders the field items with pagination support.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    return AccessibilityHelper::constructPaginationView(count($items), $this, $elements);
  }

  /**
   * Generates view elements for individual field items, applying pagination.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $pageIndexName = $this->getSetting('pageIndexName');
    $currentPageIndex = (int) ($_GET[$pageIndexName] ?? 0);

    // Ensure the current page item exists; otherwise, handle the not found exception.
    if (!isset($items[$currentPageIndex])) {
      throw new NotFoundHttpException();
    }

    return $elements;
  }

}

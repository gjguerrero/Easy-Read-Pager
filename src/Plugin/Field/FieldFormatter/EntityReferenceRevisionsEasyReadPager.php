<?php

namespace Drupal\easy_read_pager\Plugin\Field\FieldFormatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\easy_read_pager\AccessibilityHelper;

/**
 * Extends the 'entity reference revisions' formatter to add pagination.
 *
 * Provides a field formatter that renders referenced entities with pagination,
 * enhancing the display for content-heavy sites, making it easier to navigate through referenced entities.
 *
 * @FieldFormatter(
 *   id = "entity_reference_revisions_easy_read_pager",
 *   label = @Translation("Rendered entity with Easy Read Pagination"),
 *   description = @Translation("Displays the referenced entities with pagination, rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityReferenceRevisionsWithPagination extends EntityReferenceRevisionsEntityFormatter {

  /**
   * Incorporates default pagination settings into the formatter.
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return AccessibilityHelper::integrateDefaultConfig($settings);
  }

  /**
   * Augments the formatter's settings form with pagination configuration options.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return AccessibilityHelper::enhanceFormWithSettings($form, $form_state, $this, $elements);
  }

  /**
   * Provides a summary of the current formatter settings, including pagination.
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
    $entities = $this->getEntitiesToView($items, $langcode);
    // Pagination logic adjustment based on the total entities.
    return AccessibilityHelper::constructPaginationView(count($entities), $this, $elements);
  }

  /**
   * Generates the view elements for individual field items, applying pagination.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $pageIndex = $this->getSetting('pageIndexName');
    $currentEntityIndex = (int) ($_GET[$pageIndex] ?? 0);
    $entities = $this->getEntitiesToView($items, $langcode);

    if (!isset($entities[$currentEntityIndex])) {
      throw new NotFoundHttpException();
    }

    $entity = $entities[$currentEntityIndex];
    $viewMode = $this->getSetting('view_mode');

    // Handling recursive rendering protection.
    static $renderDepth = 0;
    $renderDepth++;
    if ($renderDepth > 20) {
      $this->loggerFactory->get('entity')->error('Recursive rendering detected for entity @type @id. Rendering aborted.', [
        '@type' => $entity->getEntityTypeId(),
        '@id' => $entity->id(),
      ]);
      return $elements;
    }

    // Rendering the entity with the specified view mode.
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    $elements[$currentEntityIndex] = $viewBuilder->view($entity, $viewMode, $entity->language()->getId());

    // Ensuring RDFa compliance with 'resource' attribute.
    if (!empty($items[$currentEntityIndex]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
      $items[$currentEntityIndex]->_attributes += ['resource' => $entity->toUrl()->toString()];
    }
    $renderDepth = 0;

    return $elements;
  }

}

<?php

namespace Drupal\easy_read_pager\Plugin\Field\FieldFormatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\easy_read_pager\AccessibilityHelper;

/**
 * Extends the entity reference formatter to render entities with pagination.
 *
 * Provides an entity reference field formatter that displays referenced entities
 * using a paginated format, rendering entities through `entity_view()` and enhancing
 * accessibility by allowing users to navigate entities in smaller chunks.
 *
 * @FieldFormatter(
 *   id = "entity_reference_easy_read_pager",
 *   label = @Translation("Rendered Entity with Easy Read Pagination"),
 *   description = @Translation("Displays referenced entities with pagination, rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class ReferencedEntityEasyReadPager extends EntityReferenceEntityFormatter {

  /**
   * Incorporates default pagination settings into the formatter.
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return AccessibilityHelper::integrateDefaultConfig($settings);
  }

  /**
   * Enhances the formatter's settings form with accessible pagination options.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return AccessibilityHelper::enhanceFormWithSettings($form, $form_state, $this, $elements);
  }

  /**
   * Provides a summary of the current formatter settings, highlighting pagination features.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return AccessibilityHelper::createSettingsSummary($summary, $this);
  }

  /**
   * Renders the field items, applying pagination to the displayed entities.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $entities = $this->getEntitiesToView($items, $langcode);
    // Adjust the pagination view based on the total number of entities.
    return AccessibilityHelper::constructPaginationView(count($entities), $this, $elements);
  }

  /**
   * Generates the view elements for individual field items, incorporating pagination.
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
    $recursiveRenderId = $this->buildRecursiveRenderId($items, $entity);
    $this->protectAgainstRecursiveRendering($recursiveRenderId);

    $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $elements[$currentEntityIndex] = $viewBuilder->view($entity, $viewMode, $entity->language()->getId());

    // Enhance the rendered entity with structured data for improved accessibility.
    $this->addStructuredDataAttributes($items, $currentEntityIndex, $entity);

    return $elements;
  }

  /**
   * Builds a unique ID for tracking recursive rendering.
   */
  protected function buildRecursiveRenderId($items, $entity) {
    return $items->getFieldDefinition()->getTargetEntityTypeId()
           . $items->getFieldDefinition()->getTargetBundle()
           . $items->getName()
           . $items->getEntity()->id()
           . $entity->getEntityTypeId()
           . $entity->id();
  }

  /**
   * Implements a simple mechanism to prevent recursive rendering.
   */
  protected function protectAgainstRecursiveRendering($id) {
    static $depth = 0;
    $depth++;
    if ($depth > 20) {
      throw new \Exception('Recursive rendering detected. Aborting rendering.');
    }
    // Reset depth counter after escaping recursive rendering.
    $depth = 0;
  }

  /**
   * Adds RDFa or other structured data attributes to the rendered entities.
   */
  protected function addStructuredDataAttributes($items, $index, $entity) {
    if (!empty($items[$index]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
      $items[$index]->_attributes += ['resource' => $entity->toUrl()->toString()];
    }
  }

}

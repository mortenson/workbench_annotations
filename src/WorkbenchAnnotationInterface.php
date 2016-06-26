<?php

namespace Drupal\workbench_annotation;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a workbench_annotation entity.
 */
interface WorkbenchAnnotationInterface extends ContentEntityInterface {

  /**
   * Determines if the annotation has a parent.
   *
   * @return bool
   *   TRUE if the Annotation has a parent, FALSE otherwise.
   */
  public function hasParent();

  /**
   * Gets the parent annotation, if there is one.
   *
   * @return \Drupal\workbench_annotation\Entity\WorkbenchAnnotation|null
   *   The parent Annotation, or NULL if this is not a reply.
   */
  public function getParent();

  /**
   * Gets the author that created this annotation.
   *
   * @return \Drupal\user\Entity\User|null
   *   The author of this Annotation, or NULL if there is no author.
   */
  public function getAuthor();

  /**
   * Gets the author's name.
   *
   * @return string|null
   *   The author's label, or NULL if there is no author.
   */
  public function getAuthorName();

  /**
   * Gets a full URL to the image that represents the Author.
   *
   * @return string
   *   A full URL that can be directly used in an <img> tag.
   */
  public function getAuthorImageUrl();

  /**
   * Gets the Entity that this annotation is related to.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the annotated Entity, or NULL if one cannot be found.
   */
  public function getAnnotatedEntity();

  /**
   * Sets the Entity that this annotation is related to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An Entity to reference.
   */
  public function setAnnotatedEntity($entity);

  /**
   * Gets the Entity Type ID for the annotated Entity.
   *
   * @return string
   *   The Entity Type ID.
   */
  public function getAnnotatedEntityTypeId();

  /**
   * Gets the Entity ID for the annotated Entity.
   *
   * @return integer
   *   The Entity ID.
   */
  public function getAnnotatedEntityId();

  /**
   * Sets which Entity this annotation is related to.
   *
   * @param string $entity_type_id
   *   An Entity Type ID.
   * @param int $entity_id
   *   An Entity ID.
   */
  public function setAnnotatedEntityById($entity_type_id, $entity_id);

  /**
   * Gets the created timestamp for this annotation.
   *
   * @return integer
   *   The created timestamp.
   */
  public function getCreatedTime();

  /**
   * Gets the quoted text for this annotation.
   *
   * @return string
   *   The text that was selected when the annotation was created.
   */
  public function getQuote();

  /**
   * Sets the quoted text for this annotation.
   *
   * @param string $quote
   *   The text that was selected when the annotation was created.
   */
  public function setQuote($quote);

  /**
   * Gets the text content for this annotation.
   *
   * @return string
   *   The text content of this annotation.
   */
  public function getText();

  /**
   * Sets the text content for this annotation.
   *
   * @param string $text
   *   The text content of this annotation.
   */
  public function setText($text);

  /**
   * Gets the ranges for this Annotation.
   *
   * @return array
   *   An array that represents an AnnotatorJS selection.
   */
  public function getRanges();

  /**
   * Sets the ranges for this annotation.
   *
   * @param array $ranges
   *   An array that represents an AnnotatorJS selection.
   */
  public function setRanges($ranges);

  /**
   * Gets the severity Entity associated with this annotation.
   *
   * @return \Drupal\workbench_annotation\Entity\WorkbenchAnnotationSeverity|null
   *   The WorkbenchAnnotationSeverity Entity, or NULL if there is none.
   */
  public function getSeverity();

  /**
   * Gets the severity ID for this Annotation.
   *
   * @return string
   *   The severity ID for this Annotation.
   */
  public function getSeverityId();

  /**
   * Sets the severity ID for this Annotation.
   *
   * @param string $id
   *   The severity ID for this Annotation.
   */
  public function setSeverityId($id);

}

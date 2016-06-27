<?php

namespace Drupal\workbench_annotation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\workbench_annotation\Entity\WorkbenchAnnotation;
use Drupal\workbench_annotation\WorkbenchAnnotationInterface;
use Drupal\workbench_moderation\ModerationInformation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Endpoints for the Workbench Annotation module.
 */
class WorkbenchAnnotationController extends ControllerBase {

  /**
   * The ModerationInformation service.
   *
   * @var \Drupal\workbench_moderation\ModerationInformation
   */
  protected $moderation_information;

  /**
   * Constructs a WorkbenchAnnotationController.
   *
   * @param \Drupal\workbench_moderation\ModerationInformation $moderation_information
   *   The ModerationInformation service.
   */
  public function __construct(ModerationInformation $moderation_information) {
    $this->moderation_information = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workbench_moderation.moderation_information')
    );
  }

  /**
   * Attempts to parse a request's content as JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   The parsed JSON as an associative array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if the request does not contain valid JSON.
   */
  protected function getJSON(Request $request) {
    $content = $request->getContent();
    if (!empty($content)) {
      if (!$data = json_decode($content, TRUE)) {
        throw new BadRequestHttpException('Unable to decode JSON.');
      }
    }
    else {
      throw new BadRequestHttpException('Request content is empty.');
    }

    return $data;
  }

  /**
   * Create (POST) endpoint for AnnotatorJS.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing valid AnnotatorJS data.
   */
  public function createAnnotation(Request $request) {
    $data = $this->getJSON($request);
    /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotation $annotation */
    $annotation = WorkbenchAnnotation::create();
    $annotation->setRanges($data['ranges']);
    $annotation->setAnnotatedEntityById($data['entity_type'], $data['entity_id']);
    $annotation->setQuote($data['quote']);
    $annotation->setText($data['text']);
    $annotation->setSeverityId($data['severity']);
    $annotation->save();
    $return = $this->getAnnotatorJSData($annotation);
    return new JsonResponse($return);
  }

  /**
   * Read (GET) endpoint for AnnotatorJS.
   *
   * @param \Drupal\workbench_annotation\WorkbenchAnnotationInterface $workbench_annotation
   *   The requested annotation.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing valid AnnotatorJS data.
   */
  public function readAnnotation(WorkbenchAnnotationInterface $workbench_annotation) {
    $data = $this->getAnnotatorJSData($workbench_annotation);
    return new JsonResponse($data);
  }

  /**
   * Update (PUT) endpoint for AnnotatorJS.
   *
   * @param \Drupal\workbench_annotation\WorkbenchAnnotationInterface $workbench_annotation
   *   The requested annotation.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing valid AnnotatorJS data.
   */
  public function updateAnnotation(WorkbenchAnnotationInterface $workbench_annotation, Request $request) {
    $data = $this->getJSON($request);
    $workbench_annotation->setText($data['text']);
    $workbench_annotation->setSeverityId($data['severity']);
    $workbench_annotation->save();
    $data = $this->getAnnotatorJSData($workbench_annotation);
    return new JsonResponse($data);
  }

  /**
   * Delete (DELETE) endpoint for AnnotatorJS.
   *
   * @param \Drupal\workbench_annotation\WorkbenchAnnotationInterface $workbench_annotation
   *   The requested annotation.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   An empty JSON response. Could be anything returning a 200 response.
   */
  public function deleteAnnotation(WorkbenchAnnotationInterface $workbench_annotation) {
    $workbench_annotation->delete();
    return new JsonResponse();
  }

  /**
   * Search endpoint for AnnotatorJS.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response that's consumable by AnnotatorJS.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if the query contains invalid keys for entity_type or entity_id.
   */
  public function searchAnnotations(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $entity_id = $request->query->get('entity_id');
    if (!is_string($entity_type) || !is_numeric($entity_id)) {
      throw new BadRequestHttpException('Invalid GET parameters sent.');
    }

    $rows = [];
    $result = \Drupal::entityQuery('workbench_annotation')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->execute();
    $annotations = WorkbenchAnnotation::loadMultiple($result);
    /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotation $annotation */
    foreach ($annotations as $annotation) {
      if ($annotation->access('view')) {
        $rows[] = $this->getAnnotatorJSData($annotation);
      }
    }
    return new JsonResponse([
      'rows' => $rows,
      'total' => count($rows)
    ]);
  }

  /**
   * Parses a given annotation into data that's consumable by AnnotatorJS.
   *
   * @param \Drupal\workbench_annotation\WorkbenchAnnotationInterface $workbench_annotation
   *   The requested annotation.
   *
   * @return array
   *   An associative array containing keys that AnnotatorJS can parse.
   */
  protected function getAnnotatorJSData(WorkbenchAnnotationInterface $workbench_annotation) {
    $created = date('F jS, Y', $workbench_annotation->get('created')->getString());

    $data = [
      'id' => $workbench_annotation->id(),
      'created' => $created,
      'quote' => $workbench_annotation->getQuote(),
      'ranges' => $workbench_annotation->getRanges(),
      'text' => $workbench_annotation->getText(),
      // These variables are unique to the Workbench Annotation module, and
      // aren't used by AnnotatorJS. "from_drupal" is used to prevent errors
      // when viewing annotations created locally or from other sources.
      'from_drupal' => TRUE,
      'author_image' => $workbench_annotation->getAuthorImageUrl(),
      'author_name' => $workbench_annotation->getAuthorName(),
      'severity' => $workbench_annotation->getSeverityId(),
      'access' => [
        'update' => $workbench_annotation->access('update'),
        'delete' => $workbench_annotation->access('delete'),
      ]
    ];

    return $data;
  }

}

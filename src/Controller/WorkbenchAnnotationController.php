<?php

namespace Drupal\workbench_annotation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\workbench_annotation\Entity\WorkbenchAnnotation;
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

    if (isset($data['severity']) && $data['severity'] == 'default') {
      unset($data['severity']);
    }

    return $data;
  }

  public function createAnnotation(Request $request) {
    $data = $this->getJSON($request);
    /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotation $annotation */
    $annotation = WorkbenchAnnotation::create($data);
    $annotation->save();
    $data = $this->getAnnotatorJSData($annotation);
    return new JsonResponse($data);
  }

  public function readAnnotation(WorkbenchAnnotation $workbench_annotation) {
    $data = $this->getAnnotatorJSData($workbench_annotation);
    return new JsonResponse($data);
  }

  public function updateAnnotation(WorkbenchAnnotation $workbench_annotation, Request $request) {
    $data = $this->getJSON($request);
    $workbench_annotation->set('text', $data['text']);
    $workbench_annotation->set('severity', $data['severity']);
    $workbench_annotation->save();
    $data = $this->getAnnotatorJSData($workbench_annotation);
    return new JsonResponse($data);
  }

  public function deleteAnnotation(WorkbenchAnnotation $workbench_annotation) {
    $workbench_annotation->delete();
    return new JsonResponse();
  }

  public function searchAnnotations(Request $request) {
    $rows = [];
    $result = \Drupal::entityQuery('workbench_annotation')
      ->condition('entity_type', $request->get('entity_type'))
      ->condition('entity_id', $request->get('entity_id'))
      ->execute();
    $annotations = WorkbenchAnnotation::loadMultiple($result);
    /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotation $annotation */
    foreach ($annotations as $annotation) {
      $rows[] = $this->getAnnotatorJSData($annotation);
    }
    return new JsonResponse([
      'rows' => $rows,
      'total' => count($rows)
    ]);
  }

  protected function getAnnotatorJSData(WorkbenchAnnotation $workbench_annotation) {
    /** @var \Drupal\user\Entity\User $author */
    $author = $workbench_annotation->get('author')->referencedEntities()[0];
    /** @var \Drupal\Core\File\FileSystem $file_system */
    if ($author->hasField('user_picture')) {
      /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $user_picture */
      $user_picture = $author->get('user_picture')->get(0);
      if ($user_picture) {
        $image_path = $user_picture->entity->getFileUri();
      }
    }

    if (!isset($image_path)) {
      $image_path = drupal_get_path('module', 'workbench_annotation') . '/images/account.svg';
    }

    $image_src = file_create_url($image_path);
    $created = date('F jS, Y', $workbench_annotation->get('created')->getString());
    $data = [
      'id' => $workbench_annotation->id(),
      'author_image' => $image_src,
      'author_name' => $author->getDisplayName(),
      'created' => $created,
      'quote' => $workbench_annotation->get('quote')->getString(),
      'ranges' => $workbench_annotation->get('ranges')->getValue(),
      'text' => $workbench_annotation->get('text')->getString(),
      'severity' => $workbench_annotation->get('severity')->getString()
    ];
    foreach ($data as $key => $value) {
      if (is_string($data[$key])) {
        $data[$key] = filter_var($value, FILTER_SANITIZE_STRING);
      }
    }

    return $data;
  }

}

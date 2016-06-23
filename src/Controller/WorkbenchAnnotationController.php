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

  public function createAnnotation(Request $request) {
    $content = $request->getContent();
    if (!empty($content)) {
      if (!$data = json_decode($content, true)) {
        throw new BadRequestHttpException('Unable to decode JSON.');
      }

      $annotation = WorkbenchAnnotation::create($data);
      $annotation->save();
      $data['id'] = $annotation->id();
      return new JsonResponse($data);
    }
    else {
      throw new BadRequestHttpException('Request content is empty.');
    }
  }

  public function updateAnnotation($annotation) {
    return new JsonResponse();
  }

  public function deleteAnnotation($annotation) {
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
      /** @var \Drupal\user\Entity\User $author */
      $author = $annotation->get('author')->referencedEntities()[0];
      /** @var \Drupal\Core\File\FileSystem $file_system */
      if ($author->hasField('user_picture')) {
        /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $user_picture */
        $user_picture = $author->get('user_picture')->get(0);
        $image_path = $user_picture->entity->getFileUri();
      }
      else {
        $image_path = drupal_get_path('module', 'workbench_annotation') . '/images/account.svg';
      }
      $image_src = file_create_url($image_path);
      $created = date('F jS, Y', $annotation->get('created')->getString());
      $data = [
        'id' => $annotation->id(),
        'author_image' => $image_src,
        'author_name' => $author->getDisplayName(),
        'created' => $created,
        'quote' => $annotation->get('quote')->getString(),
        'ranges' => $annotation->get('ranges')->getValue(),
        'text' => $annotation->get('text')->getString()
      ];
      foreach ($data as $key => $value) {
        if (is_string($data[$key])) {
          $data[$key] = filter_var($value, FILTER_SANITIZE_STRING);
        }
      }
      $rows[] = $data;
    }
    return new JsonResponse([
      'rows' => $rows,
      'total' => count($rows)
    ]);
  }

}

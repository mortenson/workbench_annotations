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

  public function searchAnnotations() {
    $data = [];
    $annotations = WorkbenchAnnotation::loadMultiple();
    /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotation $annotation */
    foreach ($annotations as $annotation) {
      $data[] = [
        'id' => $annotation->id(),
        'quote' => $annotation->get('quote')->getString(),
        'ranges' => $annotation->get('ranges')->getValue(),
        'text' => $annotation->get('text')->getString()
      ];
    }
    return new JsonResponse($data);
  }

}

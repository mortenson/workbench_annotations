/**
 * @file
 * Contains all Workbench Annotation behaviors.
 */

(function ($) {

  'use strict';

  Annotator.Plugin.WorkbenchAnnotation = function (element, options) {
    this.entity_type = options.entity_type;
    this.entity_id = options.entity_id;
  };

  jQuery.extend(Annotator.Plugin.WorkbenchAnnotation.prototype, new Annotator.Plugin(), {
    events: {},
    options: {},
    pluginInit: function () {
      var self = this;
      this.annotator
        .subscribe('beforeAnnotationCreated', function (annotation) {
          annotation.entity_type = self.entity_type;
          annotation.entity_id = self.entity_id;
        });
    }
  });

  /**
   * Contains all Workbench Annotation behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.workbench_annotation = {
    attach: function (context, settings) {
      $('[data-workbench-annotation-entity-id]').once('workbench-annotation').each(function() {
        var entity = $(this).data('workbench-annotation-entity-id').split('/');
        var options = {
          entity_type: entity[0],
          entity_id: entity[1]
        };
        $(this).annotator()
          .annotator('addPlugin', 'Store', {
            prefix: '/admin/workbench_annotation'
          })
          .annotator('addPlugin', 'WorkbenchAnnotation', options);
      });
    }
  };

}(jQuery));

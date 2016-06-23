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

  $.extend(Annotator.Plugin.WorkbenchAnnotation.prototype, new Annotator.Plugin(), {
    events: {},
    options: {},
    pluginInit: function () {
      var self = this;
      this.annotator
        .subscribe('beforeAnnotationCreated', function (annotation) {
          annotation.entity_type = self.entity_type;
          annotation.entity_id = self.entity_id;
        })
        .subscribe('annotationViewerTextField', function (field, annotation) {
          var quote = $(field).html();
          $(field).html('');
          $(field).addClass('workbench-annotator-annotation');
          $(field).append(
            '<div class="author-info">' +
            '  <img class="author-image" src="' + annotation.author_image + '" width="50" height="50"/>' +
            '  <span class="author-name">' + annotation.author_name + '</span>' +
            '</div>'
          );
          $(field).append(
            '<div class="annotation-info">' +
            '  <p class="created-date">Posted on ' + annotation.created + '</p>' +
            '  <p class="annotator-quote">' + quote + '</p>' +
            '</div>'
          );
        })
        .subscribe('annotationEditorShown', function (editor, annotation) {
          var $element = editor.element;
          $element.once('workbench-annotator-form-extras').each(function () {
            var $select = $('<select>').addClass('workbench-annotator-severity');
            for (var type in drupalSettings.workbench_annotation.severities) {
              var label = drupalSettings.workbench_annotation.severities[type];
              $select.append('<option value="' + type + '">' + label + '</option>');
            }
            $element.find('.annotator-controls').prepend($select);
          });
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
            prefix: '/admin/workbench_annotation',
            loadFromSearch: {
              'entity_type':  entity[0],
              'entity_id': entity[1]
            }
          })
          .annotator('addPlugin', 'WorkbenchAnnotation', options);
      });
    }
  };

}(jQuery));

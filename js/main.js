/**
 * @file
 * Contains all Workbench Annotation behaviors.
 */

(function ($) {

  'use strict';

  Annotator.Plugin.WorkbenchAnnotation = function (element, options) {
    this.entity_type = options.entity_type;
    this.entity_id = options.entity_id
    this.severities = options.severities;
    this.author_template = _.template(
      '<div class="author-info">' +
      '  <img class="author-image" src="<%- author_image %>" width="50" height="50"/>' +
      '  <span class="author-name"><%- author_name %></span>' +
      '</div>'
    );
    this.annotation_template = _.template(
      '<div class="annotation-info">' +
      '  <p class="created-date">Posted on <%- created %></p>' +
      '  <p class="annotator-text"><%- text %></p>' +
      '</div>'
    );
    this.severity_template = _.template(
      '<select class="workbench-annotator-severity">' +
      '  <% for(var i in severities) { %>' +
      '  <option value="<%- i %>"><%- severities[i].label %></option>' +
      '  <% } %>' +
      '</select>'
    );
  };

  $.extend(Annotator.Plugin.WorkbenchAnnotation.prototype, new Annotator.Plugin(), {
    events: {},
    options: {},
    pluginInit: function () {
      var self = this;
      this.annotator
        .subscribe('beforeAnnotationCreated', function (annotation) {
          annotation.severity = self.severity;
          annotation.entity_type = self.entity_type;
          annotation.entity_id = self.entity_id;
        })
        .subscribe('annotationViewerTextField', function (field, annotation) {
          var $field = $(field);
          var $widget = $field.closest('.annotator-widget');
          $field.html('').addClass('workbench-annotator-annotation');
          $widget.removeClass(function (index, css) {
            return (css.match (/(^|\s)severity-\S+/g) || []).join(' ');
          });
          if (annotation.severity) {
            $widget.addClass('severity-' + annotation.severity);
          }
          $field.append(self.author_template(annotation));
          $field.append(self.annotation_template(annotation));
        })
        .subscribe('annotationEditorShown', function (editor, annotation) {
          var $element = editor.element;
          $element.once('workbench-annotator-form-extras').each(function () {
            var $select = $(self.severity_template({severities: self.severities}));
            $element.find('.annotator-controls').prepend($select);
            if (annotation.severity) {
              $select.val(annotation.severity).change();
            }
          });
        })
        .subscribe('annotationEditorSubmit', function (editor, annotation) {
          var $element = editor.element;
          annotation.severity = $element.find('.workbench-annotator-severity').val();
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
          entity_id: entity[1],
          severities: settings.workbench_annotation.severities
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

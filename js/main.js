/**
 * @file
 * Contains all Workbench Annotation behaviors.
 */

(function ($) {

  'use strict';

  Annotator.Plugin.WorkbenchAnnotation = function (element, options) {
    this.annotator = options.annotator;
    this.entity_type = options.entity_type;
    this.entity_id = options.entity_id;
    this.severities = options.severities;
    this.annotation_template = _.template(
      '<div class="author-info">' +
      '  <img class="author-image" src="<%- author_image %>" width="50" height="50"/>' +
      '  <span class="author-name"><%- author_name %></span>' +
      '</div>' +
      '<div class="annotation-info">' +
      '  <p class="created-date">' + Drupal.t('Posted on') + ' <%- created %></p>' +
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
    this.outdated_annotations = [];

    if (!options.can_create) {
      $(element).addClass('workbench-annotation-hide-adder');
    }
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
          if (annotation.from_drupal) {
            // Theme the annotation with our Underscore template.
            var $field = $(field);
            $field.html(self.annotation_template(annotation));

            // Set a class based on the severity to support custom styles.
            var $widget = $field.closest('.annotator-widget');
            $widget.removeClass(function (index, css) {
              return (css.match(/(^|\s)severity-\S+/g) || []).join(' ');
            });
            if (annotation.severity) {
              $widget.addClass('severity-' + annotation.severity);
            }
            $widget.addClass('workbench-annotator-annotation');

            // Based on the current user's access, hide certain actions.
            var $controls = $field.closest('.annotator-controls');
            $controls.find('.annotator-edit').toggle(annotation.access.update);
            $controls.find('.annotator-delete').toggle(annotation.access.delete);
          }
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
        })
        .subscribe('rangeNormalizeFail', function (annotation, range, error) {
          self.outdated_annotations.push(annotation);
        })
        .subscribe('annotationDeleted', function (deleted) {
          var index = self.outdated_annotations.findIndex(function (annotation) {
            return annotation.id == deleted.id;
          });
          if (index !== -1) {
            self.outdated_annotations.splice(index, 1);
          }
          if (self.outdated_annotations.length == 0) {
            $(this).find('.workbench-annotator-outdated').remove();
          }
        })
        .subscribe('annotationsLoaded', function () {
          if (self.outdated_annotations.length) {
            var text = Drupal.t('Hover to view outdated annotations.');
            $(this).once('workbench-annotator-outdated').each(function () {
              var $element = $('<p class="workbench-annotator-outdated"><span>' + text + '</span></p>');
              var $wrapper = $(this).find('.annotator-wrapper');
              $wrapper.prepend($element);
              $element.mouseover(function (e) {
                if (self.annotator.viewer.isShown()) {
                  self.annotator.viewer.hide();
                }
                self.annotator.clearViewerHideTimer();
                self.annotator.showViewer(self.outdated_annotations, Annotator.Util.mousePosition(e, $wrapper[0]));
              });
              $element.mouseout(function (e) {
                self.annotator.startViewerHideTimer();
              });
            });
          }
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
        var annotator = $(this).annotator();
        $(this).annotator('addPlugin', 'Store', {
          prefix: '/admin/workbench_annotation',
          loadFromSearch: {
            'entity_type':  entity[0],
            'entity_id': entity[1]
          }
        });
        var options = {
          annotator: annotator,
          entity_type: entity[0],
          entity_id: entity[1],
          severities: settings.workbench_annotation.severities,
          can_create: settings.workbench_annotation.can_create
        };
        $(this).annotator('addPlugin', 'WorkbenchAnnotation', options);
      });
    }
  };

}(jQuery));

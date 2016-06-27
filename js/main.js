/**
 * @file
 * Contains all Workbench Annotation behaviors.
 */

(function ($) {

  'use strict';

  // Defines a custom AnnotatorJS plugin which handles all of our Workbench
  // integrations.
  Annotator.Plugin.WorkbenchAnnotation = function (element, options) {
    // Store a reference to the Annotator object for later use.
    this.annotator = options.annotator;
    // For convenience keep the Entity information around so that we can add it
    // to new Annotations.
    this.entity_type = options.entity_type;
    this.entity_id = options.entity_id;
    // Store a local cache of Workbench Severity Entity information for use in
    // forms and styling.
    this.severities = options.severities;
    // A list of outdated annotations - i.e. annotations that refer to text that
    // no longer exists or XPaths that are no longer executable.
    this.outdated_annotations = [];
    // Override the default AnnotatorJS template with our own.
    this.annotation_template = _.template(
      '<div class="author-info">' +
      '  <img class="author-image" src="<%- author_image %>" width="50" height="50" />' +
      '  <span class="author-name"><%- author_name %></span>' +
      '</div>' +
      '<div class="annotation-info">' +
      '  <p class="created-date">' + Drupal.t('Posted on') + ' <%- created %></p>' +
      '  <p class="annotator-text"><%- text %></p>' +
      '</div>'
    );
    // Template for the form element used to select severities.
    this.severity_template = _.template(
      '<select class="workbench-annotator-severity">' +
      '  <% for(var i in severities) { %>' +
      '  <option value="<%- i %>"><%- severities[i].label %></option>' +
      '  <% } %>' +
      '</select>'
    );
    // If the current user can't create Annotations, hide the add button.
    if (!options.can_create) {
      $(element).addClass('workbench-annotation-hide-adder');
    }
  };

  // Extend the base Plugin implementation and subscribe to required events.
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
          // Append our custom <select> element.
          editor.element.once('workbench-annotator-form-extras').each(function () {
            var $select = $(self.severity_template({severities: self.severities}));
            editor.element.find('.annotator-controls').prepend($select);
          });

          // Set the default value to match the current Annotation.
          if (annotation.severity) {
            editor.element.find('.workbench-annotator-severity').val(annotation.severity).change();
          }
        })
        .subscribe('annotationEditorSubmit', function (editor, annotation) {
          // Set the severity of the annotation based on our custom form field.
          annotation.severity = editor.element.find('.workbench-annotator-severity').val();
        })
        .subscribe('rangeNormalizeFail', function (annotation, range, error) {
          // When an annotation cannot be displayed, this event is called. By
          // keeping track of these outdated annotations, we still allow editors
          // to resolve or edit them.
          self.outdated_annotations.push(annotation);
        })
        .subscribe('annotationDeleted', function (deleted) {
          // When an annotation is removed, we need to remove it from our array
          // of outdated annotations.
          var index = self.outdated_annotations.findIndex(function (annotation) {
            return annotation.id == deleted.id;
          });
          if (index !== -1) {
            self.outdated_annotations.splice(index, 1);
          }
          if (self.outdated_annotations.length === 0) {
            $(this).find('.workbench-annotator-outdated').remove();
          }
        })
        .subscribe('annotationsLoaded', function () {
          // Set up a custom annotation which contains references to all
          // outdated annotations. Without this, annotations would grow stale
          // and could never be deleted.
          if (self.outdated_annotations.length) {
            var text = Drupal.t('Hover to view outdated annotations.');
            $(this).once('workbench-annotator-outdated').each(function () {
              var $element = $('<p class="workbench-annotator-outdated"><span>' + text + '</span></p>');
              var $wrapper = $(this).find('.annotator-wrapper');
              $wrapper.prepend($element);
              // These events match the AnnotatorJS callbacks.
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
      // For each on-screen moderated Entity, set up an AnnotatorJS instance.
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

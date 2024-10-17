(function (wp) {
    var el = wp.element.createElement,
      registerBlockType = wp.blocks.registerBlockType,
      ServerSideRender = wp.serverSideRender,
      TextControl = wp.components.TextControl,
      SelectControl = wp.components.SelectControl,
      InspectorControls = wp.editor.InspectorControls,
      withSelect = wp.data.withSelect;
  
    var TagCloudEdit = withSelect(function (select, props) {
      var currentPostId = select('core/editor').getCurrentPostId();
  
      return {
        post_id: currentPostId,
      };
    })(function (props) {
      return [
        el(ServerSideRender, {
          key: 'serverSideRender',
          block: 'taxopress/tag-clouds',
          attributes: props.attributes,
        }),
        el(
          InspectorControls,
          { key: 'inspectorControls' },
          el(SelectControl, {
            id: 'stb-tag-clouds-select',
            className: 'stb-block-tag-clouds-post',
            label: ST_TAG_CLOUDS.select_label,
            options: ST_TAG_CLOUDS.options,
            onChange: function (value) {
              props.setAttributes({
                tagcloud_id: value,
                post_id: props.post_id,
              });
            },
            value: props.attributes.tagcloud_id,
          })
        ),
      ];
    });
  
    registerBlockType('taxopress/tag-clouds', {
      title: ST_TAG_CLOUDS.panel_title,
      icon: 'tag',
      category: 'widgets',
      edit: TagCloudEdit,
  
      save: function () {
        return null;
      },
    });
  })(window.wp);
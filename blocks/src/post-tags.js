(function (wp) {
    var el = wp.element.createElement,
      registerBlockType = wp.blocks.registerBlockType,
      ServerSideRender = wp.serverSideRender,
      TextControl = wp.components.TextControl,
      SelectControl = wp.components.SelectControl,
      InspectorControls = wp.editor.InspectorControls,
      withSelect = wp.data.withSelect;
  
    var PostTagsEdit = withSelect(function (select, props) {
      var currentPostId = select('core/editor').getCurrentPostId();
  
      return {
        post_id: currentPostId,
      };
    })(function (props) {
      return [
        el(ServerSideRender, {
          key: 'serverSideRender',
          block: 'taxopress/post-tags',
          attributes: props.attributes,
        }),
        el(
          InspectorControls,
          { key: 'inspectorControls' },
          el(SelectControl, {
            id: 'stb-post-tags-select',
            className: 'stb-block-post-tags',
            label: ST_POST_TAGS.select_label,
            options: ST_POST_TAGS.options,
            onChange: function (value) {
              props.setAttributes({
                posttags_id: value,
                post_id: props.post_id,
              });
            },
            value: props.attributes.posttags_id,
          })
        ),
      ];
    });
  
    registerBlockType('taxopress/post-tags', {
      title: ST_POST_TAGS.panel_title,
      icon: 'admin-post',
      category: 'widgets',
      edit: PostTagsEdit,
  
      save: function () {
        return null;
      },
    });
  })(window.wp);
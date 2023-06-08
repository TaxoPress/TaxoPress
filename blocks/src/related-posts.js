(function (wp) {
  var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    ServerSideRender = wp.serverSideRender,
    TextControl = wp.components.TextControl,
    SelectControl = wp.components.SelectControl,
    InspectorControls = wp.editor.InspectorControls,
    withSelect = wp.data.withSelect;

  var RelatedPostsEdit = withSelect(function (select, props) {
    var currentPostId = select('core/editor').getCurrentPostId();

    return {
      post_id: currentPostId,
    };
  })(function (props) {
    return [
      el(ServerSideRender, {
        key: 'serverSideRender',
        block: 'taxopress/related-posts',
        attributes: props.attributes,
      }),
      el(
        InspectorControls,
        { key: 'inspectorControls' },
        el(SelectControl, {
          id: 'stb-related-post-select',
          className: 'stb-block-related-post',
          label: ST_RELATED_POST.select_label,
          help: ST_RELATED_POST.select_desc,
          options: ST_RELATED_POST.options,
          onChange: function (value) {
            props.setAttributes({
              relatedpost_id: value,
              post_id: props.post_id,
            });
          },
          value: props.attributes.relatedpost_id,
        })
      ),
    ];
  });

  registerBlockType('taxopress/related-posts', {
    title: ST_RELATED_POST.panel_title,
    icon: 'tag',
    category: 'widgets',
    edit: RelatedPostsEdit,

    save: function () {
      return null;
    },
  });
})(window.wp);
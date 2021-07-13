(function (wp) {
  var el = wp.element.createElement,
  registerBlockType = wp.blocks.registerBlockType,
  ServerSideRender = wp.components.ServerSideRender,
  TextControl = wp.components.TextControl,
  SelectControl = wp.components.SelectControl,
  InspectorControls = wp.editor.InspectorControls

registerBlockType('taxopress/related-posts', {
  title: ST_RELATED_POST.panel_title,
  icon: 'tag',
  category: 'widgets',
  edit: function(props) {
    props.setAttributes({post_id: wp.data.select('core/editor').getCurrentPostId()})
    return [
      el(ServerSideRender, {
        block: 'taxopress/related-posts',
        attributes: props.attributes,
      }),
      el(InspectorControls, {},
        el(SelectControl,
          {
            id: 'stb-related-post-select',
            className: 'stb-block-related-post',
            label: ST_RELATED_POST.select_label,
            help: ST_RELATED_POST.select_desc,
            options: ST_RELATED_POST.options,
            onChange: (value) => {
              props.setAttributes({relatedpost_id: value}),
                props.setAttributes({post_id: wp.data.select('core/editor').getCurrentPostId()})
            },
            value: props.attributes.relatedpost_id
          }
        ),
      ),


    ]
  },

  save: function() {
    return null
  },
})

} )(
	window.wp
);
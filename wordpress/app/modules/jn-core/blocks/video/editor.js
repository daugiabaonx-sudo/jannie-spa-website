( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'jn/video', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var context = props.context || {};

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Video', initialOpen: true },
						el( TextControl, {
							label: 'YouTube URL (empty = use the testimonial video)',
							value: attributes.url,
							onChange: function ( value ) {
								setAttributes( { url: value } );
							}
						} ),
						el( TextControl, {
							label: 'Name in the link text (empty = testimonial name)',
							value: attributes.label,
							onChange: function ( value ) {
								setAttributes( { label: value } );
							}
						} )
					)
				),
				el( ServerSideRender, {
					block: 'jn/video',
					attributes: attributes,
					urlQueryArgs: context.postId ? { post_id: context.postId } : {}
				} )
			);
		}
	} );
}( window.wp ) );

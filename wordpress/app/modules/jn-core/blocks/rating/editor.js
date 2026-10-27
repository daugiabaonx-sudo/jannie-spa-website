( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'jn/rating', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'jn-rating-editor-wrap'
			} );

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Star Rating Settings', initialOpen: true },
						el( RangeControl, {
							label: 'Rating Override (0 = use Testimonial meta)',
							value: attributes.rating || 0,
							min: 0,
							max: 5,
							step: 1,
							onChange: function ( nextVal ) {
								setAttributes( { rating: Number( nextVal ) || 0 } );
							}
						} ),
						el( RangeControl, {
							label: 'Star Size (px)',
							value: attributes.size || 18,
							min: 12,
							max: 32,
							step: 2,
							onChange: function ( nextSize ) {
								setAttributes( { size: Number( nextSize ) || 18 } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'jn/rating',
						attributes: attributes
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp );

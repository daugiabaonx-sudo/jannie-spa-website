( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'jn/product-stage', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var bottles = ( attributes.bottles || [] ).slice( 0, 3 );
			while ( bottles.length < 3 ) {
				bottles.push( '' );
			}

			function bottleField( index ) {
				return el( TextControl, {
					key: 'bottle-' + index,
					label: 'Bottle ' + ( index + 1 ) + ' name',
					value: bottles[ index ],
					onChange: function ( value ) {
						var next = bottles.slice();
						next[ index ] = value;
						setAttributes( { bottles: next } );
					}
				} );
			}

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Illustration', initialOpen: true },
						el( TextControl, {
							label: 'Description for screen readers',
							value: attributes.label,
							onChange: function ( value ) {
								setAttributes( { label: value } );
							}
						} ),
						el( TextControl, {
							label: 'Handwritten note (English)',
							value: attributes.note,
							onChange: function ( value ) {
								setAttributes( { note: value } );
							}
						} ),
						el( TextControl, {
							label: 'Label brand line',
							value: attributes.brand,
							onChange: function ( value ) {
								setAttributes( { brand: value } );
							}
						} ),
						el( TextControl, {
							label: 'Label small line',
							value: attributes.tag,
							onChange: function ( value ) {
								setAttributes( { tag: value } );
							}
						} ),
						bottleField( 0 ),
						bottleField( 1 ),
						bottleField( 2 )
					)
				),
				el( ServerSideRender, { block: 'jn/product-stage', attributes: attributes } )
			);
		}
	} );
}( window.wp ) );

( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextareaControl = wp.components.TextareaControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'jn/marquee', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Marquee', initialOpen: true },
						el( TextareaControl, {
							label: 'Phrases (one per line)',
							value: ( attributes.items || [] ).join( '\n' ),
							onChange: function ( value ) {
								setAttributes( {
									items: value.split( '\n' ).map( function ( line ) {
										return line.trim();
									} ).filter( Boolean )
								} );
							}
						} ),
						el( TextControl, {
							label: 'Language of the phrases',
							help: 'Language code, e.g. en or vi.',
							value: attributes.lang,
							onChange: function ( value ) {
								setAttributes( { lang: value } );
							}
						} )
					)
				),
				el( ServerSideRender, { block: 'jn/marquee', attributes: attributes } )
			);
		}
	} );
}( window.wp ) );

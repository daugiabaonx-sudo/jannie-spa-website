( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	function textField( attributes, setAttributes, key, label ) {
		return el( TextControl, {
			key: key,
			label: label,
			value: attributes[ key ],
			onChange: function ( value ) {
				var next = {};
				next[ key ] = value;
				setAttributes( next );
			}
		} );
	}

	wp.blocks.registerBlockType( 'jn/product-actions', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var context = props.context || {};
			var isContact = 'contact' === attributes.mode;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Buttons', initialOpen: true },
						el( SelectControl, {
							label: 'Mode',
							value: attributes.mode,
							options: [
								{ label: 'Product card (inside a product collection)', value: 'product' },
								{ label: 'General contact button', value: 'contact' }
							],
							onChange: function ( value ) {
								setAttributes( { mode: value } );
							}
						} ),
						isContact
							? textField( attributes, setAttributes, 'contactLabel', 'Button label' )
							: [
								textField( attributes, setAttributes, 'buyLabel', 'Label when the product has a price' ),
								textField( attributes, setAttributes, 'viewLabel', 'Label when the product has no price' )
							],
						el( 'p', null, 'The consult button reads "Tư vấn qua Zalo" when the jn_contact_zalo_url option holds a zalo.me link, otherwise "Nhận tư vấn".' )
					)
				),
				el( ServerSideRender, {
					block: 'jn/product-actions',
					attributes: attributes,
					urlQueryArgs: ! isContact && context.postId ? { post_id: context.postId } : {}
				} )
			);
		}
	} );
}( window.wp ) );

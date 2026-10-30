( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	var TEXT_FIELDS = [
		{ key: 'captionKicker', label: 'Caption kicker' },
		{ key: 'captionTitle', label: 'Caption title' },
		{ key: 'polaroidLabel', label: 'Polaroid label' },
		{ key: 'labelLang', label: 'Polaroid label language', help: 'Language code of the polaroid label, e.g. en or vi.' },
		{ key: 'toggleLabel', label: 'Swap description (screen readers)', help: 'Read after the polaroid label, e.g. "Đổi ảnh chân dung".' },
		{ key: 'sideText', label: 'Vertical side text' }
	];

	function imagePicker( label, attribute, attributes, setAttributes ) {
		return el(
			MediaUploadCheck,
			null,
			el( MediaUpload, {
				allowedTypes: [ 'image' ],
				value: attributes[ attribute ] || 0,
				onSelect: function ( media ) {
					var next = {};
					next[ attribute ] = media && media.id ? media.id : 0;
					setAttributes( next );
				},
				render: function ( picker ) {
					return el(
						'div',
						{ style: { marginBottom: '12px' } },
						el( 'p', null, label + ( attributes[ attribute ] ? ' (#' + attributes[ attribute ] + ')' : ' (default)' ) ),
						el( Button, { variant: 'secondary', onClick: picker.open }, 'Choose image' ),
						attributes[ attribute ]
							? el(
								Button,
								{
									variant: 'link',
									isDestructive: true,
									onClick: function () {
										var next = {};
										next[ attribute ] = 0;
										setAttributes( next );
									}
								},
								'Use default'
							)
							: null
					);
				}
			} )
		);
	}

	registerBlockType( 'jn/hero-portrait', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Images', initialOpen: true },
						imagePicker( 'Main portrait (LCP)', 'primaryId', attributes, setAttributes ),
						imagePicker( 'Swap photo (polaroid)', 'secondaryId', attributes, setAttributes )
					),
					el(
						PanelBody,
						{ title: 'Text', initialOpen: false },
						TEXT_FIELDS.map( function ( field ) {
							return el( TextControl, {
								key: field.key,
								label: field.label,
								help: field.help,
								value: attributes[ field.key ] || '',
								onChange: function ( value ) {
									var next = {};
									next[ field.key ] = value;
									setAttributes( next );
								}
							} );
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'jn/hero-portrait',
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

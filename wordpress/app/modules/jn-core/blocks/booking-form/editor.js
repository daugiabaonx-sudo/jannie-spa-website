( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'jn/booking-form', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'jn-booking-form-editor-wrap'
			} );

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Booking Form Settings', initialOpen: true },
						el( SelectControl, {
							label: 'Default Selected Package',
							value: attributes.defaultPackage || '60-phut',
							options: [
								{ label: 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)', value: '60-phut' },
								{ label: 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)', value: '30-phut' },
								{ label: 'Gói Master Mentorship (3 Tháng)', value: 'mentorship-3-thang' }
							],
							onChange: function ( nextPkg ) {
								setAttributes( { defaultPackage: nextPkg } );
							}
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'jn/booking-form',
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

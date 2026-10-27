( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	var ICON_OPTIONS = [
		{ label: 'check_circle (Check Circle)', value: 'check_circle' },
		{ label: 'verified (Verified Badge)', value: 'verified' },
		{ label: 'star (Filled Star)', value: 'star' },
		{ label: 'arrow_forward (Arrow Forward)', value: 'arrow_forward' },
		{ label: 'north_east (North East Arrow)', value: 'north_east' },
		{ label: 'south (South Arrow)', value: 'south' },
		{ label: 'expand_more (Chevron Down)', value: 'expand_more' },
		{ label: 'person (Account / Person)', value: 'person' },
		{ label: 'all_inclusive (Infinity)', value: 'all_inclusive' },
		{ label: 'architecture (Architecture / Pillar 1)', value: 'architecture' },
		{ label: 'query_stats (Query Stats / Pillar 2)', value: 'query_stats' },
		{ label: 'fingerprint (Fingerprint / Pillar 3)', value: 'fingerprint' },
		{ label: 'format_quote (Quote Mark)', value: 'format_quote' },
		{ label: 'award_star (Award Star)', value: 'award_star' },
		{ label: 'spa (Spa Lotus)', value: 'spa' },
		{ label: 'send (Send)', value: 'send' },
		{ label: 'location_on (Location Pin)', value: 'location_on' },
		{ label: 'mail (Mail Envelope)', value: 'mail' },
		{ label: 'schedule (Clock / Schedule)', value: 'schedule' },
		{ label: 'sparkline (Decorative Sparkline Chart)', value: 'sparkline' }
	];

	registerBlockType( 'jn/icon', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'jn-icon-wrap'
			} );

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Icon Settings', initialOpen: true },
						el( SelectControl, {
							label: 'Icon Name',
							value: attributes.name || 'check_circle',
							options: ICON_OPTIONS,
							onChange: function ( nextName ) {
								setAttributes( { name: nextName } );
							}
						} ),
						el( RangeControl, {
							label: 'Size (px)',
							value: attributes.size || 20,
							min: 12,
							max: 64,
							step: 2,
							onChange: function ( nextSize ) {
								setAttributes( { size: Number( nextSize ) || 20 } );
							}
						} ),
						el( TextControl, {
							label: 'Accessible Label (optional)',
							help: 'Leave empty for decorative icons (aria-hidden="true").',
							value: attributes.label || '',
							onChange: function ( nextLabel ) {
								setAttributes( { label: nextLabel } );
							}
						} )
					)
				),
				el(
					'span',
					blockProps,
					el( ServerSideRender, {
						block: 'jn/icon',
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

<?php
/**
 * Token-based Block-Style Variations for the Jannie child theme.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all design-system block-style variations.
 *
 * @return void
 */
function jannie_register_block_styles() {
	$variations = array(
		'core/button'    => array(
			array(
				'name'       => 'primary-pill',
				'label'      => 'Primary Pill (Ink)',
				'is_default' => true,
			),
			array(
				'name'  => 'soft-pill',
				'label' => 'Soft Pill (Beige)',
			),
			array(
				'name'  => 'accent-pill',
				'label' => 'Accent Pill (Champagne)',
			),
			array(
				'name'  => 'outline',
				'label' => 'Outline Pill',
			),
			array(
				'name'  => 'text-link',
				'label' => 'Editorial Text Link',
			),
		),
		'core/group'     => array(
			array(
				'name'  => 'card',
				'label' => 'Card (Elevated White)',
			),
			array(
				'name'  => 'card-dark',
				'label' => 'Card Dark (Featured Tier)',
			),
			array(
				'name'  => 'panel-beige',
				'label' => 'Panel Beige',
			),
			array(
				'name'  => 'panel-cream',
				'label' => 'Panel Cream',
			),
			array(
				'name'  => 'metrics-bar',
				'label' => 'Metrics Bar (3-Column)',
			),
			array(
				'name'  => 'glass-caption',
				'label' => 'Glass Caption (Blurred Overlay)',
			),
		),
		'core/paragraph' => array(
			array(
				'name'  => 'eyebrow',
				'label' => 'Eyebrow (Label SM)',
			),
			array(
				'name'  => 'eyebrow-dot',
				'label' => 'Eyebrow + Dot & Line',
			),
			array(
				'name'  => 'eyebrow-line',
				'label' => 'Eyebrow + Line',
			),
		),
		'core/list'      => array(
			array(
				'name'  => 'check',
				'label' => 'Check Circle List',
			),
			array(
				'name'  => 'verified',
				'label' => 'Verified Badge List',
			),
		),
		'core/image'     => array(
			array(
				'name'  => 'portrait-4-5',
				'label' => 'Portrait 4:5',
			),
			array(
				'name'  => 'portrait-3-4',
				'label' => 'Portrait 3:4',
			),
		),
	);

	foreach ( $variations as $block_name => $styles ) {
		foreach ( $styles as $style_args ) {
			register_block_style( $block_name, $style_args );
		}
	}
}
add_action( 'init', 'jannie_register_block_styles' );

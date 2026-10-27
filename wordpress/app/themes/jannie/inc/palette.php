<?php
/**
 * Jannie Color Palette & Editor Color Picker integration with Blocksy.
 *
 * Places the 8 core brand colours in Blocksy's Global Color Palette and
 * exposes every design-system colour token (including §4 WCAG AA text
 * variants) in the Gutenberg colour picker exactly once with clear names.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the 8-slot Blocksy Global Color Palette (`color1`..`color8`).
 *
 * @return array<string, array<string, string>>
 */
function jannie_get_global_color_palette() {
	return array(
		'color1' => array(
			'color' => '#1F1E1D',
			'title' => 'Ink Charcoal (Primary)',
		),
		'color2' => array(
			'color' => '#735944',
			'title' => 'Warm Bronze (Secondary Hover)',
		),
		'color3' => array(
			'color' => '#1B1C1A',
			'title' => 'On Surface (Body Text)',
		),
		'color4' => array(
			'color' => '#050504',
			'title' => 'Deep Primary (Icon Badge)',
		),
		'color5' => array(
			'color' => '#E5DFD7',
			'title' => 'Wireframe Border',
		),
		'color6' => array(
			'color' => '#F3EFEA',
			'title' => 'Surface Beige',
		),
		'color7' => array(
			'color' => '#FAF8F5',
			'title' => 'Surface Cream',
		),
		'color8' => array(
			'color' => '#FFFFFF',
			'title' => 'Surface Pure',
		),
	);
}

/**
 * Ensure Blocksy's `colorPalette` theme_mod always resolves to the 8 brand slots.
 *
 * @param mixed $value Current theme_mod value.
 * @return array<string, array<string, string>>
 */
function jannie_filter_blocksy_color_palette_mod( $value ) {
	$defaults = jannie_get_global_color_palette();
	if ( ! is_array( $value ) || empty( $value['color1']['color'] ) ) {
		return $defaults;
	}

	foreach ( $defaults as $slot => $slot_data ) {
		if ( ! isset( $value[ $slot ]['color'] ) ) {
			$value[ $slot ] = $slot_data;
		} else {
			$value[ $slot ]['title'] = $slot_data['title'];
		}
	}

	return $value;
}
add_filter( 'theme_mod_colorPalette', 'jannie_filter_blocksy_color_palette_mod' );

/**
 * Flush Blocksy's dynamic styles transient if it was cached with stock `#2872fa` colors.
 *
 * @return void
 */
function jannie_maybe_flush_blocksy_dynamic_css_transient() {
	$cached = get_transient( 'blocksy_dynamic_styles_descriptor' );
	if ( is_array( $cached ) && isset( $cached['styles']['desktop'] ) && false !== strpos( $cached['styles']['desktop'], '#2872fa' ) ) {
		delete_transient( 'blocksy_dynamic_styles_descriptor' );
		if ( function_exists( 'blocksy_manager' ) && isset( blocksy_manager()->db ) ) {
			blocksy_manager()->db->wipe_cache();
		}
	}
}
add_action( 'init', 'jannie_maybe_flush_blocksy_dynamic_css_transient', 5 );

/**
 * Provide the complete, deduplicated colour token list to the block editor picker.
 *
 * Every colour token (including §4 WCAG AA text variants) appears exactly once
 * with a clear name and a deterministic slug.
 *
 * @return array<int, array<string, string>>
 */
function jannie_filter_editor_color_palette() {
	return array(
		// 1–8: Blocksy Global Color Palette slots.
		array(
			'name'  => 'Ink Charcoal (Primary)',
			'slug'  => 'palette-color-1',
			'color' => '#1F1E1D',
		),
		array(
			'name'  => 'Warm Bronze (Secondary Hover)',
			'slug'  => 'palette-color-2',
			'color' => '#735944',
		),
		array(
			'name'  => 'On Surface (Body Text)',
			'slug'  => 'palette-color-3',
			'color' => '#1B1C1A',
		),
		array(
			'name'  => 'Deep Primary (Icon Badge)',
			'slug'  => 'palette-color-4',
			'color' => '#050504',
		),
		array(
			'name'  => 'Wireframe Border',
			'slug'  => 'palette-color-5',
			'color' => '#E5DFD7',
		),
		array(
			'name'  => 'Surface Beige',
			'slug'  => 'palette-color-6',
			'color' => '#F3EFEA',
		),
		array(
			'name'  => 'Surface Cream',
			'slug'  => 'palette-color-7',
			'color' => '#FAF8F5',
		),
		array(
			'name'  => 'Surface Pure',
			'slug'  => 'palette-color-8',
			'color' => '#FFFFFF',
		),
		// 9–17: Design System tokens & §4 WCAG AA text variants.
		array(
			'name'  => 'Surface Canvas',
			'slug'  => 'surface',
			'color' => '#FBF9F6',
		),
		array(
			'name'  => 'Champagne Accent (Bg / Dark Surface)',
			'slug'  => 'champagne-accent',
			'color' => '#AC8E76',
		),
		array(
			'name'  => 'Champagne Text Small (§4 AA < 24px)',
			'slug'  => 'champagne-text-sm',
			'color' => '#7E6754',
		),
		array(
			'name'  => 'Champagne Text Large (§4 AA >= 24px)',
			'slug'  => 'champagne-text-lg',
			'color' => '#A0846E',
		),
		array(
			'name'  => 'Muted Stone Text (§4 AA)',
			'slug'  => 'muted-stone',
			'color' => '#716A61',
		),
		array(
			'name'  => 'On Surface Variant (Navigation)',
			'slug'  => 'on-surface-variant',
			'color' => '#4A4640',
		),
		array(
			'name'  => 'Wireframe Guide (Muted on Dark)',
			'slug'  => 'wireframe-guide',
			'color' => '#D1C7BD',
		),
		array(
			'name'  => 'Error',
			'slug'  => 'error',
			'color' => '#BA1A1A',
		),
		array(
			'name'  => 'On Error Container',
			'slug'  => 'on-error-container',
			'color' => '#93000A',
		),
	);
}
add_filter( 'blocksy:editor-color-palette', 'jannie_filter_editor_color_palette', 20 );

/**
 * Remove Blocksy's stock rainbow gradient presets from the block editor.
 *
 * @return array<empty>
 */
function jannie_disable_editor_gradients() {
	return array();
}
add_filter( 'blocksy:editor-gradient-presets', 'jannie_disable_editor_gradients', 20 );

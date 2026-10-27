<?php
/**
 * Inline SVG Icon Sprite & Renderer (19 Material Symbols Outlined + Sparkline).
 *
 * All 19 Material Symbols Outlined paths are licensed under Apache-2.0.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Icon_Sprite
 */
class JN_Icon_Sprite {

	/**
	 * Return map of allowed icon names to SVG path definitions.
	 *
	 * Standard Material Symbols Outlined use viewBox="0 -960 960 960".
	 * The decorative "sparkline" uses viewBox="0 0 100 30".
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_icons() {
		return array(
			'star'          => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m233-120 65-281L80-590l288-25 112-265 112 265 288 25-218 189 65 281-247-149-247 149Z"/>',
				),
			),
			'check_circle'  => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>',
				),
			),
			'verified'      => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m344-60-76-128-144-32 14-148-98-112 98-112-14-148 144-32 76-128 136 58 136-58 76 128 144 32-14 148 98 112-98 112 14 148-144 32-76 128-136-58-136 58Zm34-102 102-44 104 44 56-96 110-26-10-112 74-84-74-86 10-112-110-24-58-96-102 44-104-44-56 96-110 24 10 112-74 86 74 84-10 114 110 24 58 96Zm102-318Zm-42 142 226-226-56-58-170 170-86-84-56 56 142 142Z"/>',
				),
			),
			'arrow_forward' => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M647-440H160v-80h487L423-744l57-56 320 320-320 320-57-56 224-224Z"/>',
				),
			),
			'expand_more'   => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>',
				),
			),
			'person'        => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/>',
				),
			),
			'north_east'    => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m216-160-56-56 464-464H360v-80h400v400h-80v-264L216-160Z"/>',
				),
			),
			'south'         => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M480-80 200-360l56-56 184 183v-647h80v647l184-184 56 57L480-80Z"/>',
				),
			),
			'all_inclusive' => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M220-260q-92 0-156-64T0-480q0-92 64-156t156-64q37 0 71 13t61 37l68 62-60 54-62-56q-16-14-36-22t-42-8q-58 0-99 41t-41 99q0 58 41 99t99 41q22 0 42-8t36-22l310-280q27-24 61-37t71-13q92 0 156 64t64 156q0 92-64 156t-156 64q-37 0-71-13t-61-37l-68-62 60-54 62 56q16 14 36 22t42 8q58 0 99-41t41-99q0-58-41-99t-99-41q-22 0-42 8t-36 22L352-310q-27 24-61 37t-71 13Z"/>',
				),
			),
			'architecture'  => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m276-224-56-56 116-116-28-28 116-116 56 56 56-56 56 56-116 116-28-28-116 116 44 44-100 12Zm408 0-100-12 44-44-40-40 56-56 40 40 56-56 40 40-96 128ZM440-694v-106h80v106q51 14 85.5 56T640-540q0 66-47 113t-113 47q-66 0-113-47t-47-113q0-56 34.5-98t85.5-56Zm40 234q33 0 56.5-23.5T560-540q0-33-23.5-56.5T480-620q-33 0-56.5 23.5T400-540q0 33 23.5 56.5T480-460Zm0-80Z"/>',
				),
			),
			'query_stats'   => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m105-399-65-47 200-320 120 140 160-260 109 163q-23 1-43.5 5.5T545-703l-22-33-152 247-121-141-145 231ZM863-40 738-165q-20 14-44.5 21t-50.5 7q-75 0-127.5-52.5T463-317q0-75 52.5-127.5T643-497q75 0 127.5 52.5T823-317q0 26-7 50.5T795-221L920-97l-57 57ZM643-217q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Zm89-320q-19-8-39.5-13t-42.5-6l205-324 65 47-188 296Z"/>',
				),
			),
			'fingerprint'   => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M481-781q106 0 200 45.5T838-604q7 9 4.5 16t-8.5 12q-6 5-14 4.5t-14-8.5q-55-78-141.5-119.5T481-741q-97 0-182 41.5T158-580q-6 9-14 10t-14-4q-7-5-8.5-12.5T126-602q62-85 155.5-132T481-781Zm0 94q135 0 232 90t97 223q0 50-35.5 83.5T688-257q-51 0-87.5-33.5T564-374q0-33-24.5-55.5T481-452q-34 0-58.5 22.5T398-374q0 97 57.5 162T604-121q9 3 12 10t1 15q-2 7-8 12t-15 3q-104-26-170-103.5T358-374q0-50 36-84t87-34q51 0 87 34t36 84q0 33 25 55.5t59 22.5q34 0 58-22.5t24-55.5q0-116-85-195t-204-79q-118 0-203 79t-85 194q0 24 4.5 60t21.5 84q3 9-.5 16T208-205q-8 3-15.5-.5T182-217q-15-39-21.5-77.5T154-374q0-133 96.5-223T481-687Zm0-192q64 0 125 15.5T724-819q9 5 10.5 12t-1.5 14q-3 7-10 11t-17-1q-53-27-109.5-41.5T481-839q-58 0-114 13.5T260-783q-8 5-16 2.5T232-791q-4-8-2-14.5t10-11.5q56-30 117-46t124-16Zm0 289q93 0 160 62.5T708-374q0 9-5.5 14.5T688-354q-8 0-14-5.5t-6-14.5q0-75-55.5-125.5T481-550q-76 0-130.5 50.5T296-374q0 81 28 137.5T406-123q6 6 6 14t-6 14q-6 6-14 6t-14-6q-59-62-90.5-126.5T256-374q0-91 66-153.5T481-590Zm-1 196q9 0 14.5 6t5.5 14q0 75 54 123t126 48q6 0 17-1t23-3q9-2 15.5 2.5T744-191q2 8-3 14t-13 8q-18 5-31.5 5.5t-16.5.5q-89 0-154.5-60T460-374q0-8 5.5-14t14.5-6Z"/>',
				),
			),
			'format_quote'  => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m228-240 92-160q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 23-5.5 42.5T458-480L320-240h-92Zm360 0 92-160q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 23-5.5 42.5T818-480L680-240h-92ZM320-500q25 0 42.5-17.5T380-560q0-25-17.5-42.5T320-620q-25 0-42.5 17.5T260-560q0 25 17.5 42.5T320-500Zm360 0q25 0 42.5-17.5T740-560q0-25-17.5-42.5T680-620q-25 0-42.5 17.5T620-560q0 25 17.5 42.5T680-500Zm0-60Zm-360 0Z"/>',
				),
			),
			'award_star'    => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m363-310 117-71 117 71-31-133 104-90-137-11-53-126-53 126-137 11 104 90-31 133ZM480-28 346-160H160v-186L28-480l132-134v-186h186l134-132 134 132h186v186l132 134-132 134v186H614L480-28Zm0-112 100-100h140v-140l100-100-100-100v-140H580L480-820 380-720H240v140L140-480l100 100v140h140l100 100Zm0-340Z"/>',
				),
			),
			'spa'           => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M480-120q-129 0-228.5-65.5T122-365q-19-45 6.5-85t73.5-40q23 0 44.5 7.5T288-460q11-94 64-175.5T480-760q75 43 128 124.5T672-460q20-15 41.5-22.5T758-490q48 0 73.5 40t6.5 85q-30 114-129.5 179.5T480-120Zm0-80q97 0 176-47t108-133q3-8-1-14.5t-12-6.5q-27 0-53 16t-48 42l-62-48q2-21 1-42t-5-42q-12-62-45-117.5T480-684q-55 37-88 92.5T347-474q-4 21-5 42t1 42l-62 48q-22-26-48-42t-53-16q-8 0-12 6.5t-1 14.5q29 86 108 133t176 47Zm0-280Z"/>',
				),
			),
			'send'          => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M120-160v-640l760 320-760 320Zm80-120 474-200-474-200v140l240 60-240 60v140Zm0 0v-400 400Z"/>',
				),
			),
			'location_on'   => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M480-480q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Zm0 294q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z"/>',
				),
			),
			'mail'          => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200ZM160-640v-80 480-400Z"/>',
				),
			),
			'schedule'      => array(
				'viewBox' => '0 -960 960 960',
				'paths'   => array(
					'<path d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/>',
				),
			),
			'sparkline'     => array(
				'viewBox'    => '0 0 100 30',
				'is_graphic' => true,
				'paths'      => array(
					'<path d="M0 25 L20 22 L40 18 L60 12 L80 14 L100 4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>',
					'<path d="M0 25 L20 22 L40 18 L60 12 L80 14 L100 4 V30 H0 Z" fill="currentColor" fill-opacity="0.12"/>',
				),
			),
		);
	}

	/**
	 * Render an inline SVG icon by name.
	 *
	 * @param array<string, mixed> $args {
	 *     Icon rendering arguments.
	 *
	 *     @type string $name  Icon slug. Default 'check_circle'.
	 *     @type int    $size  Icon height in pixels. Default 20.
	 *     @type string $label Accessible label. Empty string renders aria-hidden="true".
	 *     @type string $class Additional CSS classes.
	 * }
	 * @return string Inline SVG markup.
	 */
	public static function render( $args = array() ) {
		$defaults = array(
			'name'  => 'check_circle',
			'size'  => 20,
			'label' => '',
			'class' => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$icons = self::get_icons();
		$name  = sanitize_key( (string) $args['name'] );

		if ( ! isset( $icons[ $name ] ) ) {
			$name = 'check_circle';
		}

		$icon_data = $icons[ $name ];
		$size      = max( 8, min( 256, absint( $args['size'] ) ) );
		if ( 0 === $size ) {
			$size = 20;
		}

		$is_graphic = ! empty( $icon_data['is_graphic'] );
		$width      = $is_graphic ? (int) round( $size * 3 ) : $size;
		$height     = $size;
		$fill       = $is_graphic ? 'none' : 'currentColor';
		$label      = trim( sanitize_text_field( (string) $args['label'] ) );

		$classes = array(
			'jn-icon',
			'jn-icon--' . $name,
		);
		if ( ! empty( $args['class'] ) ) {
			$classes[] = sanitize_text_field( (string) $args['class'] );
		}

		$a11y_attrs = 'aria-hidden="true" focusable="false"';
		if ( '' !== $label ) {
			$a11y_attrs = sprintf( 'role="img" aria-label="%s"', esc_attr( $label ) );
		}

		return sprintf(
			'<svg class="%1$s" width="%2$d" height="%3$d" viewBox="%4$s" fill="%5$s" xmlns="http://www.w3.org/2000/svg" %6$s>%7$s</svg>',
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			$width,
			$height,
			esc_attr( $icon_data['viewBox'] ),
			esc_attr( $fill ),
			$a11y_attrs,
			implode( '', $icon_data['paths'] )
		);
	}
}

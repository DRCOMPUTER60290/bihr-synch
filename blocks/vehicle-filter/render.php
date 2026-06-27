<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$atts = array(
    'title'      => $attributes['title'] ?? 'Trouvez vos pièces',
    'show_button' => $attributes['showButton'] ?? true ? 'yes' : 'no',
);

echo do_shortcode( '[bihr_vehicle_filter title="' . esc_attr( $atts['title'] ) . '" show_button="' . esc_attr( $atts['show_button'] ) . '"]' );

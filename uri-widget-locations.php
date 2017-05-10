<?php
/*
Plugin Name: URI Widget Locations
Plugin URI: http://www.uri.edu
Description: Control which pages widgets appear on
Version: 1.0
Author: John Pennypacker
Author URI: 
*/

// Block direct requests
if ( !defined('ABSPATH') )
	die('-1');
	

/**
 * Creates a text area to accept which URLs to show a given widget
 */
function uri_widget_locations_add_urls( $widget, $return, $instance ) {
 
		// Display the description option.
		$urls = isset( $instance['uri2017-urls'] ) ? $instance['uri2017-urls'] : '';
		?>
		<p>
			<label for="<?php echo $widget->get_field_id('uri2017-urls'); ?>">
			<?php _e( 'URLs to display this widget:', 'uri_2017' ); ?>
			</label>
			<textarea class="widefat" rows="6" cols="20" id="<?php echo $widget->get_field_id('uri2017-urls'); ?>" name="<?php echo $widget->get_field_name('uri2017-urls'); ?>"><?php echo esc_html( $urls ); ?></textarea><span class="description">Add URLs (one per line) where you'd like this widget to appear.  Use * as wildcards, and &lt;front&gt; for the homepage.  e.g. /people/*<br>To exclude the widget from certain URLs, begin the pattern with !. e.g. !/people/page/*</span>
		</p>
		<?php

}
add_filter('in_widget_form', 'uri_widget_locations_add_urls', 10, 3 );


/**
 * Handles submissions of new URLs
 */
function uri_widget_locations_update_urls( $instance, $new_instance ) {
	if ( !empty( $new_instance['uri2017-urls'] ) ) {
		$new_instance['uri2017-urls'] = esc_html( $new_instance['uri2017-urls'] );
	}
	return $new_instance;
}
add_filter( 'widget_update_callback', 'uri_widget_locations_update_urls', 10, 2 );


/**
 * A test function to see if a widget is visible.  Loads widget, then checks its URL
 * @param str $option the internal name of the widget
 * @param int $number the numeric key of the widget
 * @return bool
 */
function uri_widget_locations_widget_is_visible( $option, $number ) {
	if ( empty ( $option ) || empty ( $number ) ) {
		return FALSE;
	}
	if ( substr ( $option, 0, 7 ) != 'widget_' ) {
		$option = 'widget_' . $option;
	}
	$widgets = get_option( $option );
	$widget = $widgets[$number];
	
// 	echo 'Option: <pre>', print_r($option, TRUE), '</pre>';
// 	echo 'Number: <pre>', print_r($number, TRUE), '</pre>';
//	echo 'Widget: <pre>', print_r($widget, TRUE), '</pre>';

	return uri_widget_locations_match_url($widget['uri2017-urls'] );
}


/**
 * A filter designed to remove widgets not designated for the current URL
 * @param arr
 * @return arr
 */
function uri_widget_locations_filter_sidebar_widgets( $sidebars_widgets ) {
	static $widgets;

	// do not interfere with the administrative tools
	if( is_admin() ) {
		return $sidebars_widgets;
	}
	if(is_array($widgets)) { // we've already gone over this array on this request, return results
		return $widgets;
	}
	
	// fresh request, init widgets
	$widgets = $sidebars_widgets;
	
	// iterate over widgets, hide those that do not display on this URL
	foreach($widgets as $sidebar_key => $sidebar) {
		if(is_array($sidebar)) {
			foreach($sidebar as $key => $widget) {
				// why not use list()?  because it's different in php 5 and php 7
				$bits = explode('-', $widget);
				$option = $bits[0];
				$number = $bits[1];
				$show = uri_widget_locations_widget_is_visible( $option, $number );
				if( $show ) {
				} else {
					unset ( $widgets[$sidebar_key][$key] );
				}
			}
		}
	}

	return $widgets;

}
add_filter( 'sidebars_widgets', 'uri_widget_locations_filter_sidebar_widgets', 11 );


/**
 * Gets the current WP path as known by Apache, not WordPress.
 * @param bool $strip is a switch to strip slashes from the end of the URL
 * it does this so that paths like "who" and "who/*" can be differentiated
 * otherwise, there's no way to single out "who"
 * @return str
 */
function uri_widget_locations_get_current_path($strip=TRUE) {

	
	if ( strpos($_SERVER['HTTP_REFERER'], 'wp-admin/customize.php') === FALSE ) {
		$current_path = trim($_SERVER['REQUEST_URI']);
	} else {
		// when the Customizer is being used, we need to use the referrer 
		// because the Request URI is a different endpoint.
		$url = parse_url( $_SERVER['HTTP_REFERER'] );
		$q = trim( urldecode ( $url['query'] ) );
		$q = str_replace( 'url=', '', $q );
		$url = parse_url ( $q );
		$current_path = $url['path'];
	}

	// remove the query string when it isn't a preview
	if(!isset($_GET['preview']) && strpos($current_path, '?') !== FALSE) {
		$bits = explode('?', $current_path);
		$current_path = $bits[0];
	}

	$base_bits = parse_url( site_url() );	
	if ( strpos ( $current_path, $base_bits['path'] ) === 0 ) {
		$current_path = substr( $current_path, strlen( $base_bits['path'] ) );
	}
	if($strip === TRUE) {
		$current_path = rtrim($current_path, '/');
	}
	
	return $current_path;
}


function uri_widget_locations_match_url( $urls, $debug = FALSE ) {
	$show = FALSE;	
	$current_path = uri_widget_locations_get_current_path();
	$paths = explode("\n", $urls);
	
	$negatives = array();
	
	foreach($paths as $p) {
		if ( strpos ($p, '!') === 0 ) {
			$negatives[] = $p;
		}
		if($current_path == '' && $p == '&lt;front&gt;') {
			//echo $p . ' matched &lt;front&gt;';
			return TRUE;
		}
		$pattern = '/^' . str_replace(array('*', '/'), array('.*', '\/'), trim($p)) . '$/';
		if(preg_match($pattern, $current_path) == 1) {
			$is_match = 'matched';
			$show = TRUE;
		} else {
			$is_match = 'did not match';
		}
		if ( $debug === TRUE ) {
			echo '<p>"<code>' . $pattern . '</code>" ' . $is_match . ' "<code>' . $current_path . '</code>"</p>';
		}
	}
	if ( $show === TRUE && count( $negatives ) > 0) {
		// there's a negative selector, see if we need to turn the true to a false
		foreach ( $negatives as $p) {
			$p = ltrim( trim( $p ), '!' );
			$pattern = '/^' . str_replace( array('*', '/'), array('.*', '\/'), $p ) . '$/';
			if ( preg_match( $pattern, $current_path ) == 1) {
				$show = FALSE;
			}

		}
	}
	
	
	return $show;
}

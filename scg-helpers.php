<?php
/**
 * Helpers.
 */

if ( ! function_exists( 'scg_post' ) ) {
	/**
	 * Return url parameter corresponding to ajax input parameter.
	 *
	 * @param        $key
	 * @param string $prefix
	 * @param bool   $default
	 * @param bool   $value
	 * @param bool   $htmlspecialchars
	 *
	 * @return string
	 */
	function scg_post( $key, $prefix = '', $default = false, $value = false, $htmlspecialchars = true ) {
		$result = false;
		if ( array_key_exists( $key, $_POST ) ) {
			$result = ( $htmlspecialchars ? htmlspecialchars( $_POST[ $key ] ) : $_POST[ $key ] );
			if ( $result && $value )
				$result = $value;
		}
		if ( $result )
			return $prefix . $result;
		elseif ( $default )
			return $prefix . $default;
		return '';
	}
}

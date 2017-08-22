<?php
/**
 * Functions for getting and setting the plugin's registration status.
 *
 * @package   SocialWarfare\Functions
 * @copyright Copyright (c) 2017, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 */

/**
 * Check to see if the plugin has been registered once per page load.
 *
 * @since  2.1.0
 * @param  string $domain The current site's domain.
 * @param  string $context The context where the key will be used.
 * @return string A registration key based on the site's domain.
 */
function swp_get_registration_key( $domain, $context = 'api' ) {
	$key = md5( $domain );

	if ( 'db' === $context ) {
		$key = md5( $key );
	}

	return $key;
}

/**
 * Check to see if the plugin has been registered once per page load.
 * Once per week, we'll ping our server to ask if the license key is still valid.
 *
 * @since  unknown
 * @return bool True if the plugin is registered, false otherwise.
 */
function is_swp_registered($timeline = false) {

	// Check if we have a constant so we don't recheck every time the function is called
	if( defined('IS_SWP_REGISTERED') ){
		return IS_SWP_REGISTERED;
	}

	// Get the plugin options from the database
	$options = get_option( 'socialWarfareOptions' );
	$is_registered = false;

	// Get the timestamps setup for comparison to see if a week has passed since our last check
	$current_time = time();
	if(!isset($options['pro_license_key_timestamp'])):
		$timestamp = 0;
	else:
		$timestamp = $options['pro_license_key_timestamp'];
	endif;
	$time_to_recheck = $timestamp + 604800;

	// If they have a key and a week hasn't passed since the last check, just return true...the plugin is registered.
	if( !empty($options['pro_license_key']) && $current_time < $time_to_recheck ) {

		$is_registered = true;

	// If a week has indeed passed since the last check, ping our API to check the validity of the license key
	} elseif( !empty($options['pro_license_key']) ){

		// Setup the API parameters
		$store_url = 'https://warfareplugins.com';
		$license = $options['pro_license_key'];

		$url ='https://warfareplugins.com/?edd_action=check_license&item_id=63157&license='.$license.'&url='.swp_get_site_url();
		$response = swp_file_get_contents_curl( $url );

		if( false != $response ) {

			// Parse the response into an object
			$license_data = json_decode( $response );

			// If the license was valid
			if( isset($license_data->license) && 'valid' == $license_data->license ) {
				$options['pro_license_key_timestamp'] = $current_time;
				update_option( 'socialWarfareOptions' , $options );
				$is_registered = true;

			// If the license was invalid
			} elseif( isset($license_data->license) && 'invalid' == $license_data->license) {
				$is_registered = false;
				$options['pro_license_key'] = '';
				$options['pro_license_key_timestamp'] = $current_time;
				update_option( 'socialWarfareOptions' , $options );

			// If the property is some other status, just go with it.
			} else {
				$options['pro_license_key_timestamp'] = $current_time;
				update_option( 'socialWarfareOptions' , $options );
				$is_registered = true;
			}

		// If we recieved no response from the server, we'll just check again next week
		} else {
			$options['pro_license_key_timestamp'] = $current_time;
			update_option( 'socialWarfareOptions' , $options );
			$is_registered = true;
		}
	}

	// Add this to a constant so we don't recheck every time this function is called
	define('IS_SWP_REGISTERED' , $is_registered );

	// Return the registration value true/false
	return $is_registered;
}

/**
 * Attempt to register the plugin.
 *
 * @since  2.1.0
 * @since  2.3.0 Hooked registration into the new EDD Software Licensing API
 * @param  none
 * @return JSON Encoded Array (Echoed) - The Response from the EDD API
 *
 */
add_action( 'wp_ajax_swp_register_plugin', 'swp_register_plugin' );
function swp_register_plugin() {

	// Check to ensure that license key was passed into the function
	if(!empty($_POST['pro_license_key'])) {

		// Grab the license key so we can use it below
		$license = $_POST['pro_license_key'];

		$url ='https://warfareplugins.com/?edd_action=activate_license&item_id=63157&license='.$license.'&url='.swp_get_site_url();
		$response = swp_file_get_contents_curl( $url );

		if(false != $response){

			// Parse the response into an object
			$license_data = json_decode( $response );

			// If the license is valid store it in the database
			if( isset($license_data->license) && 'valid' == $license_data->license ) {

				$current_time = time();
				$options = get_option( 'socialWarfareOptions' );
				$options['pro_license_key'] = $license;
				$options['pro_license_key_timestamp'] = $current_time;
				update_option( 'socialWarfareOptions' , $options );

				echo json_encode($license_data);
				wp_die();

			// If the license is not valid
		} elseif( isset($license_data->license) &&  'invalid' == $license_data->license ) {
				echo json_encode($license_data);
				wp_die();

			// If some other status was returned
			} else {
				$license_data['success'] == false;
				$license_data['data'] == 'Invaid response from the registration server.';
				echo json_encode($license_data);
				wp_die();
			}

		// If we didn't get a response from the registration server
		} else {
			$license_data['success'] == false;
			$license_data['data'] == 'Failed to connect to registration server.';
			echo json_encode($license_data);
			wp_die();
		}
	}

	wp_die();

}

/**
 * Attempt to unregister the plugin.
 *
 * @since  2.1.0
 * @since  2.3.0 Hooked into the EDD Software Licensing API
 * @param  none
 * @return JSON Encoded Array (Echoed) - The Response from the EDD API
 */
add_action( 'wp_ajax_swp_unregister_plugin', 'swp_unregister_plugin' );
function swp_unregister_plugin() {

	$options = get_option( 'socialWarfareOptions' );

	// Check to ensure that license key was passed into the function
	if(empty($options['pro_license_key'])) {
		echo 'success';
	} else {

		// Grab the license key so we can use it below
		$license = $options['pro_license_key'];

		// Setup the API URL and send the HTTP request via our in house cURL function
		$url ='https://warfareplugins.com/?edd_action=deactivate_license&item_id=63157&license='.$license.'&url='.swp_get_site_url();
		$response = swp_file_get_contents_curl( $url );

		// Parse the response into an object
		$license_data = json_decode( $response );

		// If the deactivation was valid update the database
		if( isset($license_data->license) && $license_data->license == 'valid' ) {

			$options = get_option( 'socialWarfareOptions' );
			$options['pro_license_key'] = '';
			update_option( 'socialWarfareOptions' , $options );
			echo json_encode($license_data);
			wp_die();

		// If the API request didn't work, just deactivate locally anyways
		} else {

			$options = get_option( 'socialWarfareOptions' );
			$options['pro_license_key'] = '';
			update_option( 'socialWarfareOptions' , $options );
			echo json_encode($license_data);
			wp_die();
		}
	}

	wp_die();
}

/**
 * Registration debugging
 */
add_action('init' , 'swp_debug_registration');
function swp_debug_registration() {
	if ( true === _swp_is_debug('registration') ) {
		swp_check_registration_status();
		echo 'Debugging Registration Complete';
	}
}

/**
 * Check if the site is registered at our server.
 *
 * @since  unknown
 * @global $swp_user_options
 * @return bool
 */
function swp_check_registration_status() {
	global $swp_user_options;

	$options = $swp_user_options;

	// Bail early if no premium code exists.
	if ( empty( $options['premiumCode'] ) ) {
		return false;
	}

	$domain = swp_get_site_url();
	$email = $options['emailAddress'];
	$key = swp_get_registration_key( $domain, 'db' );

	// If the codes don't match the domain, migrate it.
	if ( isset( $options['premiumCode'] ) && $key !== $options['premiumCode'] ) {

		swp_migrate_registration();
		return true;

	} else {

		$args = array(
			'activity'         => 'check_registration',
			'emailAddress'     => $email,
			'domain'           => $domain,
			'registrationCode' => swp_get_registration_key( $domain ),
		);

		$response = swp_get_registration_api( $args, false );
		$status = is_swp_registered();

		// If the response is negative, unregister the plugin....
		if ( ! $response || 'false' === $response ) {
			if ( swp_register_plugin( $email, $domain ) ) {
				$status = true;
			} else {
				swp_unregister_plugin( $email, $options['premiumCode'] );
				$status = false;
			}
		}
	}

	return $status;
}

add_action( 'admin_init', 'swp_delete_cron_jobs' );
/**
 * Clear out any leftover cron jobs from previous plugin versions.
 *
 * @since  2.1.0
 * @return void
 */
function swp_delete_cron_jobs() {
	if ( wp_get_schedule( 'swp_check_registration_event' ) ) {
		wp_clear_scheduled_hook( 'swp_check_registration_event' );
	}
}

add_action( 'admin_init', 'swp_check_license' );
/**
 * Check to see if the license is valid once every month.
 *
 * @since  2.1.0
 * @return void
 */
function swp_check_license() {

	// Get the options and create our timestamp variables
	$options = get_option( 'socialWarfareOptions' );
	$expiration = 30 * 24 * 60 * 60;
	$now = time();



	// If the timestamp exists, and if it's more than 30 days old, check the license.
	if(isset($options['registration_timestamp']) && $now > ( $options['registration_timestamp'] + $expiration ) ):

		// Check the registration status
		swp_check_registration_status();

		// Update the timestamp
		swp_update_option( 'registration_timestamp', $now );

	// If the timestamp does not exist, create it so we can compare it later.
	elseif( !isset( $options['registration_timestamp'] ) ) :

		// Create a timestamp
		swp_update_option( 'registration_timestamp', $now );

	endif;

}

// add_action( 'admin_head-toplevel_page_social-warfare', 'swp_migrate_registration' );
/**
 * Attempt to migrate registration to a new domain when the sites domain changes.
 *
 * @since  2.1.0
 * @global $swp_user_options
 * @return void
 */
function swp_migrate_registration() {
	global $swp_user_options;

	$options = $swp_user_options;

	// Bail if we don't have the data we need to continue.
	if ( empty( $options['premiumCode'] ) || empty( $options['emailAddress'] ) ) {
		return;
	}

	$url   = swp_get_site_url();
	$email = $options['emailAddress'];
	$code  = $options['premiumCode'];

	// Unregister and re-register if our current key doesn't match the database.
	if ( swp_get_registration_key( $url, 'db' ) !== $code ) {
		// swp_unregister_plugin( $email, $code );
		swp_register_plugin( $email, $url );
	}
}

add_action( 'wp_ajax_swp_ajax_passthrough', 'swp_ajax_passthrough' );
/**
 * Pass ajax responses to a remote HTTP request.
 *
 * @since  2.0.0
 * @return void
 */
function swp_ajax_passthrough() {
	if ( ! check_ajax_referer( 'swp_plugin_registration', 'security', false ) ) {
		wp_send_json_error( esc_html__( 'Security failed.', 'social-warfare' ) );
		die;
	}

	$data = wp_unslash( $_POST ); // Input var okay.

	if ( ! isset( $data['activity'], $data['email'] ) ) {
		wp_send_json_error( esc_html__( 'Required fields missing.', 'social-warfare' ) );
		die;
	}

	if ( 'register' === $data['activity'] ) {
		$response = swp_register_plugin( $data['email'], swp_get_site_url() );

		if ( ! $response ) {
			wp_send_json_error( esc_html__( 'Plugin could not be registered.', 'social-warfare' ) );
			die;
		}

		$response['message'] = esc_html__( 'Plugin successfully registered!', 'social-warfare' );
	}

	if ( 'unregister' === $data['activity'] && isset( $data['key'] ) ) {
		$response = swp_unregister_plugin( $data['email'], $data['key'] );

		if ( ! $response ) {
			wp_send_json_error( esc_html__( 'Plugin could not be unregistered.', 'social-warfare' ) );
			die;
		}

		$response['message'] = esc_html__( 'Plugin successfully unregistered!', 'social-warfare' );
	}

	wp_send_json_success( $response );

	die;
}

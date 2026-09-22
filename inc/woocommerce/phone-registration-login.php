<?php
/**
 * Mobile Phone & Email Registration and Login
 *
 * Allows customers to register and log in using either a Mobile Phone Number
 * or an Email Address, and set their own custom password directly.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if the provided input resembles a phone number rather than an email.
 *
 * @param string $input Raw user input.
 * @return bool True if phone number, false otherwise.
 */
function agro_aura_is_phone_input( $input ) {
	$input = trim( (string) $input );

	// If it contains '@', it is an email address.
	if ( false !== strpos( $input, '@' ) ) {
		return false;
	}

	$digits = preg_replace( '/[^0-9]/', '', $input );
	$len    = strlen( $digits );

	return ( $len >= 10 && $len <= 15 );
}

/**
 * Normalize phone number to standard digits format (Indian standard 10 digits).
 *
 * @param string $input Raw phone string.
 * @return string Clean digits string.
 */
function agro_aura_normalize_phone( $input ) {
	$digits = preg_replace( '/[^0-9]/', '', (string) $input );

	// If 11 digits starting with 0 (e.g. 09876543210), strip leading 0.
	if ( 11 === strlen( $digits ) && '0' === $digits[0] ) {
		$digits = substr( $digits, 1 );
	}

	// If 12 digits starting with 91 (India country code), strip 91 for 10-digit consistency.
	if ( 12 === strlen( $digits ) && '91' === substr( $digits, 0, 2 ) ) {
		$digits = substr( $digits, 2 );
	}

	return $digits;
}

/**
 * Find a WordPress user by mobile phone number.
 * Checks user_login, billing_phone meta, and phone_number meta.
 *
 * @param string $phone Phone number.
 * @return WP_User|null
 */
function agro_aura_find_user_by_phone( $phone ) {
	$clean = agro_aura_normalize_phone( $phone );
	if ( empty( $clean ) ) {
		return null;
	}

	// 1. Check user_login by normalized phone.
	$user = get_user_by( 'login', $clean );
	if ( $user ) {
		return $user;
	}

	// Also check raw digits if different from normalized.
	$raw_clean = preg_replace( '/[^0-9]/', '', $phone );
	if ( $raw_clean !== $clean ) {
		$user_raw = get_user_by( 'login', $raw_clean );
		if ( $user_raw ) {
			return $user_raw;
		}
	}

	// 2. Check billing_phone meta.
	$users_by_meta = get_users(
		array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'billing_phone',
					'value'   => $clean,
					'compare' => '=',
				),
				array(
					'key'     => 'billing_phone',
					'value'   => $raw_clean,
					'compare' => '=',
				),
			),
			'number'     => 1,
			'fields'     => 'all',
		)
	);
	if ( ! empty( $users_by_meta ) ) {
		return $users_by_meta[0];
	}

	// 3. Check custom phone_number meta.
	$users_by_phone_meta = get_users(
		array(
			'meta_key'   => 'phone_number',
			'meta_value' => $clean,
			'number'     => 1,
			'fields'     => 'all',
		)
	);
	if ( ! empty( $users_by_phone_meta ) ) {
		return $users_by_phone_meta[0];
	}

	return null;
}

/**
 * Intercept registration form submission early.
 * Priority 5 on 'wp_loaded' runs before WC_Form_Handler::process_registration (priority 20).
 */
function agro_aura_handle_phone_registration() {
	if ( ! isset( $_POST['register'] ) || ! isset( $_POST['email'] ) ) {
		return;
	}

	$nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? wp_unslash( $_POST['woocommerce-register-nonce'] ) : ''; // phpcs:ignore
	if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
		return;
	}

	$raw_input = trim( wp_unslash( $_POST['email'] ) );
	$password  = isset( $_POST['password'] ) ? trim( wp_unslash( $_POST['password'] ) ) : '';

	// 1. Validate password
	if ( empty( $password ) ) {
		wc_add_notice( __( 'Please create a password for your account.', 'storefront-child' ), 'error' );
		unset( $_POST['register'] );
		return;
	}

	if ( strlen( $password ) < 6 ) {
		wc_add_notice( __( 'Password must be at least 6 characters long.', 'storefront-child' ), 'error' );
		unset( $_POST['register'] );
		return;
	}

	// 2. Validate identifier (Email or Phone)
	if ( empty( $raw_input ) ) {
		return; // Let WooCommerce trigger empty email notice.
	}

	// Case A: Email registration
	if ( is_email( $raw_input ) ) {
		if ( email_exists( $raw_input ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: login link */
					__( 'An account is already registered with this email address. <a href="%s" class="showlogin">Please log in.</a>', 'storefront-child' ),
					esc_url( wc_get_page_permalink( 'myaccount' ) )
				),
				'error'
			);
			unset( $_POST['register'] );
			return;
		}

		$GLOBALS['agro_aura_chosen_password'] = $password;
		return;
	}

	// Case B: Mobile Phone registration
	if ( agro_aura_is_phone_input( $raw_input ) ) {
		$phone = agro_aura_normalize_phone( $raw_input );

		if ( strlen( $phone ) < 10 ) {
			wc_add_notice( __( 'Please enter a valid 10-digit mobile number or email address.', 'storefront-child' ), 'error' );
			unset( $_POST['register'] );
			return;
		}

		// Check if user with this phone already exists.
		$existing_user = agro_aura_find_user_by_phone( $phone );
		if ( $existing_user ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: login link */
					__( 'An account is already registered with this mobile number. <a href="%s" class="showlogin">Please log in.</a>', 'storefront-child' ),
					esc_url( wc_get_page_permalink( 'myaccount' ) )
				),
				'error'
			);
			unset( $_POST['register'] );
			return;
		}

		// Generate synthetic email for WordPress requirements.
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $domain ) ) {
			$domain = 'agro-aura-staging.local';
		}
		$synthetic_email = $phone . '@' . $domain;

		if ( email_exists( $synthetic_email ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: login link */
					__( 'An account is already registered with this mobile number. <a href="%s" class="showlogin">Please log in.</a>', 'storefront-child' ),
					esc_url( wc_get_page_permalink( 'myaccount' ) )
				),
				'error'
			);
			unset( $_POST['register'] );
			return;
		}

		// Replace $_POST['email'] with synthetic email and set username to phone.
		$_POST['email']    = $synthetic_email;
		$_POST['username'] = $phone;

		$GLOBALS['agro_aura_phone_registration'] = array(
			'phone'           => $phone,
			'synthetic_email' => $synthetic_email,
			'password'        => $password,
		);
		$GLOBALS['agro_aura_chosen_password'] = $password;
	} else {
		// Neither valid email nor valid phone.
		wc_add_notice( __( 'Please provide a valid 10-digit mobile number or a valid email address.', 'storefront-child' ), 'error' );
		unset( $_POST['register'] );
		return;
	}
}
add_action( 'wp_loaded', 'agro_aura_handle_phone_registration', 5 );

/**
 * Ensure the customer's self-chosen password and username are respected,
 * even when woocommerce_registration_generate_password option is enabled.
 *
 * @param array $customer_data Data passed to wc_create_new_customer.
 * @return array
 */
function agro_aura_filter_new_customer_data( $customer_data ) {
	// Respect customer's chosen password.
	if ( ! empty( $GLOBALS['agro_aura_chosen_password'] ) ) {
		$customer_data['user_pass'] = $GLOBALS['agro_aura_chosen_password'];
	} elseif ( ! empty( $_POST['password'] ) ) {
		$customer_data['user_pass'] = wp_unslash( $_POST['password'] );
	}

	// For phone registration, enforce user_login and synthetic email.
	if ( ! empty( $GLOBALS['agro_aura_phone_registration'] ) ) {
		$customer_data['user_login'] = $GLOBALS['agro_aura_phone_registration']['phone'];
		$customer_data['user_email'] = $GLOBALS['agro_aura_phone_registration']['synthetic_email'];
	}

	return $customer_data;
}
add_filter( 'woocommerce_new_customer_data', 'agro_aura_filter_new_customer_data', 10, 1 );

/**
 * Save phone number to user meta and billing details upon registration.
 *
 * @param int   $customer_id       Created user ID.
 * @param array $new_customer_data User data.
 * @param bool  $password_generated Whether password was auto-generated.
 */
function agro_aura_on_phone_customer_created( $customer_id, $new_customer_data, $password_generated ) {
	if ( ! empty( $GLOBALS['agro_aura_phone_registration'] ) ) {
		$phone = $GLOBALS['agro_aura_phone_registration']['phone'];

		// Clear temporary synthetic email so user_email is completely blank in database.
		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_email' => '' ),
			array( 'ID' => $customer_id )
		);
		clean_user_cache( $customer_id );

		update_user_meta( $customer_id, 'billing_phone', $phone );
		update_user_meta( $customer_id, 'phone_number', $phone );
		update_user_meta( $customer_id, 'is_phone_registered', 1 );

		// Set default display name to phone number.
		wp_update_user(
			array(
				'ID'           => $customer_id,
				'display_name' => $phone,
			)
		);
	}
}
add_action( 'woocommerce_created_customer', 'agro_aura_on_phone_customer_created', 10, 3 );

/**
 * Suppress WooCommerce new customer account notification email for synthetic phone emails.
 *
 * @param bool             $enabled  Whether email is enabled.
 * @param WP_User|WC_Order $customer Customer object or Order.
 * @param WC_Email         $email    Email object.
 * @return bool
 */
function agro_aura_suppress_phone_reg_email( $enabled, $customer, $email ) {
	if ( ! empty( $GLOBALS['agro_aura_phone_registration'] ) ) {
		return false;
	}

	if ( $customer instanceof WP_User && get_user_meta( $customer->ID, 'is_phone_registered', true ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'woocommerce_email_enabled_customer_new_account', 'agro_aura_suppress_phone_reg_email', 10, 3 );

/**
 * Allow login using Mobile Phone Number in addition to standard username and email.
 *
 * @param WP_User|WP_Error|null $user     Authenticated user or error.
 * @param string                $username Username, email, or phone.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function agro_aura_authenticate_by_phone( $user, $username, $password ) {
	if ( $user instanceof WP_User ) {
		return $user; // Already authenticated.
	}

	if ( empty( $username ) || empty( $password ) ) {
		return $user;
	}

	$clean_phone = preg_replace( '/[^0-9]/', '', $username );

	// If input has 10 to 15 digits (phone format):
	if ( strlen( $clean_phone ) >= 10 && strlen( $clean_phone ) <= 15 ) {
		$found_user = agro_aura_find_user_by_phone( $username );
		if ( $found_user ) {
			return wp_authenticate_username_password( null, $found_user->user_login, $password );
		}
	}

	// Fallback to normal WordPress/WooCommerce authentication (username or email).
	return $user;
}
add_filter( 'authenticate', 'agro_aura_authenticate_by_phone', 20, 3 );

/**
 * Add Phone Number column to WordPress Admin Users table.
 */
function agro_aura_add_phone_column_to_users_admin( $columns ) {
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'email' === $key ) {
			$new_columns['billing_phone_col'] = __( 'Phone Number', 'storefront-child' );
		}
	}
	return $new_columns;
}
add_filter( 'manage_users_columns', 'agro_aura_add_phone_column_to_users_admin' );

/**
 * Display Phone Number in WordPress Admin Users table.
 */
function agro_aura_display_phone_column_in_users_admin( $value, $column_name, $user_id ) {
	if ( 'billing_phone_col' === $column_name ) {
		$phone = get_user_meta( $user_id, 'billing_phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $user_id, 'phone_number', true );
		}
		if ( empty( $phone ) ) {
			$u = get_userdata( $user_id );
			if ( $u && agro_aura_is_phone_input( $u->user_login ) ) {
				$phone = $u->user_login;
			}
		}
		return ! empty( $phone ) ? '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>' : '—';
	}
	return $value;
}
add_filter( 'manage_users_custom_column', 'agro_aura_display_phone_column_in_users_admin', 10, 3 );

/**
 * Allow phone registered users to have empty email when edited in WP Admin profile.
 */
function agro_aura_allow_empty_email_in_profile( $errors, $update, $user ) {
	if ( $user && get_user_meta( $user->ID, 'is_phone_registered', true ) ) {
		if ( empty( $_POST['email'] ) ) {
			$errors->remove( 'empty_email' );
		}
	}
}
add_action( 'user_profile_update_errors', 'agro_aura_allow_empty_email_in_profile', 10, 3 );

/**
 * Allow phone-registered users to leave email empty in Account Details if they don't have one.
 */
function agro_aura_allow_empty_email_in_account_details( $errors ) {
	$user_id = get_current_user_id();
	if ( $user_id && get_user_meta( $user_id, 'is_phone_registered', true ) ) {
		if ( empty( $_POST['account_email'] ) ) {
			$errors->remove( 'account_email' );
		}
	}
}
add_action( 'woocommerce_save_account_details_errors', 'agro_aura_allow_empty_email_in_account_details', 20, 1 );

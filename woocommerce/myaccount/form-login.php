<?php
/**
 * Login & Registration Form Override
 *
 * Supports toggle between Login and Registration forms with user-chosen password
 * and Mobile Phone Number or Email Address.
 *
 * @package Storefront_Child
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' );

$show_register_initial = isset( $_POST['register'] ) || ( isset( $_GET['action'] ) && 'register' === $_GET['action'] );
?>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

<div class="u-columns col2-set auth-toggle-layout" id="customer_login">

	<div class="u-column1 col-1" id="agro_aura_login_box" <?php echo $show_register_initial ? 'style="display: none;"' : ''; ?>>

<?php endif; ?>

		<h2><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username"><?php esc_html_e( 'Mobile number or Email address', 'storefront-child' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="<?php esc_attr_e( 'Enter mobile number or email', 'storefront-child' ); ?>" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="<?php esc_attr_e( 'Enter your password', 'storefront-child' ); ?>" required aria-required="true" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row rememberme-lostpassword">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
				</label>
			</p>

			<p class="form-row">
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
			</p>
			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>

		<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
			<div class="agro-aura-auth-switch">
				<p>
					<?php esc_html_e( "Don't have an account?", 'storefront-child' ); ?>
					<a href="#register" class="agro-switch-to-register"><?php esc_html_e( 'Create New Account', 'storefront-child' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

	</div>

	<div class="u-column2 col-2" id="agro_aura_register_box" <?php echo ! $show_register_initial ? 'style="display: none;"' : ''; ?>>

		<h2><?php esc_html_e( 'Create Account', 'storefront-child' ); ?></h2>

		<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
				</p>

			<?php endif; ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email"><?php esc_html_e( 'Mobile number or Email address', 'storefront-child' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="username" placeholder="<?php esc_attr_e( 'Enter 10-digit mobile number or email', 'storefront-child' ); ?>" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
			</p>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_password"><?php esc_html_e( 'Create Password', 'storefront-child' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Create a password (at least 6 characters)', 'storefront-child' ); ?>" required aria-required="true" />
			</p>

			<?php do_action( 'woocommerce_register_form' ); ?>

			<p class="woocommerce-form-row form-row">
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Create Account', 'storefront-child' ); ?></button>
			</p>

			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>

		<div class="agro-aura-auth-switch">
			<p>
				<?php esc_html_e( 'Already have an account?', 'storefront-child' ); ?>
				<a href="#login" class="agro-switch-to-login"><?php esc_html_e( 'Log In', 'storefront-child' ); ?></a>
			</p>
		</div>

	</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var loginBox = document.getElementById('agro_aura_login_box');
	var registerBox = document.getElementById('agro_aura_register_box');
	if (!loginBox || !registerBox) return;

	function showRegisterForm() {
		loginBox.style.display = 'none';
		registerBox.style.display = 'block';
	}

	function showLoginForm() {
		registerBox.style.display = 'none';
		loginBox.style.display = 'block';
	}

	document.addEventListener('click', function(e) {
		var regTrigger = e.target.closest('.agro-switch-to-register');
		if (regTrigger) {
			e.preventDefault();
			if (history.pushState) {
				history.pushState(null, null, '#register');
			}
			showRegisterForm();
			return;
		}

		var loginTrigger = e.target.closest('.agro-switch-to-login, .showlogin');
		if (loginTrigger) {
			e.preventDefault();
			if (history.pushState) {
				history.pushState(null, null, '#login');
			}
			showLoginForm();
			return;
		}
	});

	if (window.location.hash === '#register') {
		showRegisterForm();
	}

	window.addEventListener('hashchange', function() {
		if (window.location.hash === '#register') {
			showRegisterForm();
		} else {
			showLoginForm();
		}
	});
});
</script>

<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>

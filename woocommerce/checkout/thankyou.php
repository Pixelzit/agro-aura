<?php
/**
 * Thankyou page
 *
 * Overridden in storefront-child for Agro Aura modern e-commerce experience.
 *
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order agro-order-received-container">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="agro-thankyou-status-card agro-status-failed">
				<div class="agro-status-icon failed">
					<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
				</div>
				<h2 class="agro-status-title"><?php esc_html_e( 'Order Payment Failed', 'storefront-child' ); ?></h2>
				<p class="agro-status-desc"><?php esc_html_e( 'Unfortunately your order could not be processed as the transaction was declined. Please attempt payment again or choose another payment method.', 'storefront-child' ); ?></p>
				<div class="agro-status-actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="agro-btn agro-btn-primary"><?php esc_html_e( 'Retry Payment', 'storefront-child' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="agro-btn agro-btn-secondary"><?php esc_html_e( 'Go to My Account', 'storefront-child' ); ?></a>
					<?php endif; ?>
				</div>
			</div>

		<?php else : ?>

			<?php
			$customer_name = $order->get_billing_first_name();
			if ( empty( $customer_name ) ) {
				$customer_name = $order->get_formatted_billing_full_name();
			}
			$customer_name = trim( $customer_name );
			?>

			<!-- HERO CELEBRATION CARD -->
			<div class="agro-thankyou-hero-card">
				<div class="agro-thankyou-badge-wrap">
					<div class="agro-thankyou-badge">
						<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
						<span><?php esc_html_e( 'Order Confirmed', 'storefront-child' ); ?></span>
					</div>
				</div>

				<div class="agro-thankyou-icon-check">
					<div class="agro-pulse-ring"></div>
					<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="20 6 9 17 4 12"></polyline>
					</svg>
				</div>

				<h1 class="agro-thankyou-title">
					<?php if ( ! empty( $customer_name ) ) : ?>
						<?php echo sprintf( esc_html__( 'Thank You, %s!', 'storefront-child' ), esc_html( $customer_name ) ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Thank You for Your Order!', 'storefront-child' ); ?>
					<?php endif; ?>
				</h1>

				<p class="agro-thankyou-subtitle">
					<?php
					$order_number_html = '<strong>#' . esc_html( $order->get_order_number() ) . '</strong>';
					echo sprintf(
						/* translators: %s: Order number */
						__( 'Your order %s has been placed successfully and is being prepared.', 'storefront-child' ),
						$order_number_html
					);
					?>
				</p>

				<?php if ( $order->get_billing_email() ) : ?>
					<p class="agro-thankyou-email-note">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
						<span>
							<?php
							echo sprintf(
								/* translators: %s: Billing email */
								esc_html__( 'Confirmation & delivery updates sent to %s', 'storefront-child' ),
								'<strong>' . esc_html( $order->get_billing_email() ) . '</strong>'
							);
							?>
						</span>
					</p>
				<?php endif; ?>

				<!-- ORDER STATUS STEPPER -->
				<div class="agro-order-stepper">
					<div class="agro-step is-active is-completed">
						<div class="step-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
						</div>
						<span class="step-label"><?php esc_html_e( 'Placed', 'storefront-child' ); ?></span>
					</div>
					<div class="agro-step-connector is-active"></div>
					<div class="agro-step is-active is-completed">
						<div class="step-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
						</div>
						<span class="step-label"><?php esc_html_e( 'Confirmed', 'storefront-child' ); ?></span>
					</div>
					<div class="agro-step-connector is-current"></div>
					<div class="agro-step is-current">
						<div class="step-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
						</div>
						<span class="step-label"><?php esc_html_e( 'Preparing', 'storefront-child' ); ?></span>
					</div>
					<div class="agro-step-connector"></div>
					<div class="agro-step">
						<div class="step-icon">
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
						</div>
						<span class="step-label"><?php esc_html_e( 'Delivered', 'storefront-child' ); ?></span>
					</div>
				</div>
			</div>

			<!-- ORDER OVERVIEW KEY METRICS -->
			<div class="agro-thankyou-overview-cards">
				<div class="overview-card">
					<div class="card-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					</div>
					<div class="card-content">
						<span class="card-label"><?php esc_html_e( 'Order Number', 'storefront-child' ); ?></span>
						<strong class="card-val">#<?php echo esc_html( $order->get_order_number() ); ?></strong>
					</div>
				</div>

				<div class="overview-card">
					<div class="card-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
					</div>
					<div class="card-content">
						<span class="card-label"><?php esc_html_e( 'Date Placed', 'storefront-child' ); ?></span>
						<strong class="card-val"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'F j, Y' ) ); ?></strong>
					</div>
				</div>

				<div class="overview-card">
					<div class="card-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
					</div>
					<div class="card-content">
						<span class="card-label"><?php esc_html_e( 'Payment Method', 'storefront-child' ); ?></span>
						<strong class="card-val"><?php echo wp_kses_post( $order->get_payment_method_title() ?: __( 'Cash on delivery', 'storefront-child' ) ); ?></strong>
					</div>
				</div>

				<div class="overview-card is-total">
					<div class="card-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
					</div>
					<div class="card-content">
						<span class="card-label"><?php esc_html_e( 'Total Amount', 'storefront-child' ); ?></span>
						<strong class="card-val highlight"><?php echo $order->get_formatted_order_total(); ?></strong>
					</div>
				</div>
			</div>

			<!-- ACTION BUTTONS -->
			<div class="agro-thankyou-actions-bar">
				<a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="agro-action-btn agro-btn-primary">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
					<span><?php esc_html_e( 'Continue Shopping', 'storefront-child' ); ?></span>
				</a>

				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="agro-action-btn agro-btn-secondary">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
						<span><?php esc_html_e( 'View My Orders', 'storefront-child' ); ?></span>
					</a>
				<?php endif; ?>

				<button type="button" onclick="window.print()" class="agro-action-btn agro-btn-outline">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
					<span><?php esc_html_e( 'Print Receipt', 'storefront-child' ); ?></span>
				</button>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

		<!-- SUPPORT REASSURANCE CARD -->
		<div class="agro-thankyou-support-card">
			<div class="support-content">
				<div class="support-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
				</div>
				<div class="support-text">
					<h4><?php esc_html_e( 'Need help with your order?', 'storefront-child' ); ?></h4>
					<p><?php esc_html_e( 'Our dedicated support team is here to assist you with delivery status, cancellations, or returns.', 'storefront-child' ); ?></p>
				</div>
			</div>
			<div class="support-contacts">
				<a href="tel:+919876543210" class="support-link phone">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
					<span>+91 98765 43210</span>
				</a>
				<a href="mailto:support@agroaura.com" class="support-link email">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
					<span>support@agroaura.com</span>
				</a>
			</div>
		</div>

	<?php else : ?>

		<div class="agro-thankyou-status-card">
			<div class="agro-thankyou-icon-check">
				<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
			</div>
			<h2 class="agro-status-title"><?php esc_html_e( 'Order Received', 'storefront-child' ); ?></h2>
			<p class="agro-status-desc"><?php esc_html_e( 'Thank you. Your order has been received.', 'storefront-child' ); ?></p>
			<div class="agro-status-actions">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="agro-btn agro-btn-primary"><?php esc_html_e( 'Browse Products', 'storefront-child' ); ?></a>
			</div>
		</div>

	<?php endif; ?>

</div>

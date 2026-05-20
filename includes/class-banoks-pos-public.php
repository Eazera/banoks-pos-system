<?php
/**
 * Public online ordering shortcodes.
 *
 * @package Banoks_POS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Banoks_POS_Public {

    const COOKIE_NAME = 'banoks_customer_session';

    /**
     * Repository.
     *
     * @var Banoks_POS_Repository
     */
    private $repository;

    /**
     * Initialize public hooks.
     */
    public function __construct() {
        $this->repository = new Banoks_POS_Repository();

        add_action( 'init', array( $this, 'handle_public_forms' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'banoks_customer_auth', array( $this, 'render_customer_auth' ) );
        add_shortcode( 'banoks_online_menu', array( $this, 'render_online_menu' ) );
        add_shortcode( 'banoks_checkout', array( $this, 'render_checkout' ) );
        add_shortcode( 'banoks_cart_button', array( $this, 'render_cart_button' ) );
        add_shortcode( 'banoks_my_orders', array( $this, 'render_my_orders' ) );
    }

    /**
     * Enqueue public CSS/JS only when needed.
     */
    public function enqueue_assets() {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_post();
        if ( ! $this->page_has_banoks_shortcode( $post ) ) {
            return;
        }

        $css = BANOKS_POS_PATH . 'public/css/banoks-online-ordering.css';
        $js  = BANOKS_POS_PATH . 'public/js/banoks-online-ordering.js';

        wp_enqueue_style(
            'banoks-online-poppins',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;400;500;600;700&display=swap',
            array(),
            null
        );

        wp_enqueue_style(
            'banoks-online-ordering',
            BANOKS_POS_URL . 'public/css/banoks-online-ordering.css',
            array( 'banoks-online-poppins' ),
            file_exists( $css ) ? filemtime( $css ) : BANOKS_POS_VERSION
        );

        wp_enqueue_script(
            'banoks-online-ordering',
            BANOKS_POS_URL . 'public/js/banoks-online-ordering.js',
            array(),
            file_exists( $js ) ? filemtime( $js ) : BANOKS_POS_VERSION,
            true
        );
    }

    /**
     * Check normal content and common builder data for Banoks public shortcodes.
     *
     * Elementor stores shortcode widgets in post meta, so checking only
     * post_content can miss assets on the published page.
     *
     * @param WP_Post|null $post Current post.
     * @return bool
     */
    private function page_has_banoks_shortcode( $post ) {
        if ( ! $post ) {
            return false;
        }

        if ( false !== strpos( (string) $post->post_content, '[banoks_' ) ) {
            return true;
        }

        $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
        if ( is_string( $elementor_data ) && false !== strpos( $elementor_data, '[banoks_' ) ) {
            return true;
        }

        $elementor_data = get_post_meta( $post->ID, '_elementor_page_settings', true );
        if ( is_string( $elementor_data ) && false !== strpos( $elementor_data, '[banoks_' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Process register/login/logout/order form submissions.
     */
    public function handle_public_forms() {
        if ( empty( $_POST['banoks_public_action'] ) ) {
            return;
        }

        $action = sanitize_key( wp_unslash( $_POST['banoks_public_action'] ) );

        if ( 'register' === $action ) {
            $this->handle_register();
        } elseif ( 'login' === $action ) {
            $this->handle_login();
        } elseif ( 'logout' === $action ) {
            check_admin_referer( 'banoks_customer_logout' );
            $this->clear_customer_cookie();
            $this->redirect_with_notice( 'Logged out successfully.', false );
        } elseif ( 'place_order' === $action ) {
            $this->handle_place_order();
        }
    }

    /**
     * Render customer register/login block.
     *
     * @return string
     */
    public function render_customer_auth() {
        $customer = $this->get_current_customer();

        ob_start();
        $this->render_notice();
        ?>
        <div class="banoks-online-shell">
            <?php if ( $customer ) : ?>
                <div class="banoks-online-panel">
                    <h2>Welcome, <?php echo esc_html( $customer->full_name ); ?></h2>
                    <p class="banoks-muted">Customer ID: <?php echo esc_html( $customer->customer_id ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'banoks_customer_logout' ); ?>
                        <input type="hidden" name="banoks_public_action" value="logout">
                        <button type="submit">Log Out</button>
                    </form>
                </div>
            <?php else : ?>
                <div class="banoks-online-auth-grid">
                    <form method="post" class="banoks-online-panel">
                        <?php wp_nonce_field( 'banoks_customer_register' ); ?>
                        <input type="hidden" name="banoks_public_action" value="register">
                        <h2>Create Account</h2>
                        <label>Full Name <input type="text" name="full_name" required></label>
                        <label>Contact Number <input type="text" name="phone" required></label>
                        <label>Email <input type="email" name="email"></label>
                        <label>Address <textarea name="address" rows="3" required></textarea></label>
                        <label>Password <input type="password" name="password" minlength="6" required></label>
                        <button type="submit">Register</button>
                    </form>

                    <form method="post" class="banoks-online-panel">
                        <?php wp_nonce_field( 'banoks_customer_login' ); ?>
                        <input type="hidden" name="banoks_public_action" value="login">
                        <h2>Login</h2>
                        <label>Email or Phone <input type="text" name="identifier" required></label>
                        <label>Password <input type="password" name="password" required></label>
                        <button type="submit">Login</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the public menu product grid.
     *
     * @return string
     */
    public function render_online_menu() {
        global $wpdb;

        $products       = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_items WHERE COALESCE(is_active, 1) = 1 ORDER BY category ASC, product_name ASC" );
        $addon_rows     = $wpdb->get_results(
            "SELECT a.product_id, p.product_id AS addon_product_id, p.product_name, p.current_price, p.product_image_id
             FROM {$wpdb->prefix}banoks_product_addons a
             INNER JOIN {$wpdb->prefix}banoks_items p ON a.addon_product_id = p.product_id
             WHERE COALESCE(p.is_active, 1) = 1
             AND COALESCE(p.is_available, 1) = 1
             ORDER BY a.sort_order ASC, p.product_name ASC"
        );
        $addon_map      = array();
        foreach ( $addon_rows as $addon ) {
            $product_id = absint( $addon->product_id );
            if ( ! isset( $addon_map[ $product_id ] ) ) {
                $addon_map[ $product_id ] = array();
            }
            $addon_image_url = ! empty( $addon->product_image_id ) ? wp_get_attachment_image_url( absint( $addon->product_image_id ), 'thumbnail' ) : '';
            $addon_map[ $product_id ][] = array(
                'id'    => absint( $addon->addon_product_id ),
                'name'  => $addon->product_name,
                'price' => floatval( $addon->current_price ),
                'image' => $addon_image_url ? $addon_image_url : '',
            );
        }
        $menu_categories = array();
        foreach ( $products as $product ) {
            $category     = ! empty( $product->category ) ? $product->category : 'General';
            $category_key = sanitize_title( $category );
            if ( ! isset( $menu_categories[ $category_key ] ) ) {
                $menu_categories[ $category_key ] = $category;
            }
        }

        ob_start();
        $this->render_notice();
        ?>
        <div class="banoks-online-shell">
            <div class="banoks-online-menu" data-banoks-menu>
                    <section class="banoks-online-panel banoks-menu-panel">
                        <h2 class="banoks-menu-title">Menu</h2>
                        <?php if ( empty( $products ) ) : ?>
                            <p class="banoks-muted">No menu items available yet.</p>
                        <?php else : ?>
                            <div class="banoks-menu-category-filter" aria-label="Menu categories">
                                <button type="button" class="banoks-menu-category-btn is-active" data-category-filter="all">Popular</button>
                                <?php foreach ( $menu_categories as $category_key => $category_label ) : ?>
                                    <?php if ( 'popular' === strtolower( $category_label ) ) : ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <button type="button" class="banoks-menu-category-btn" data-category-filter="<?php echo esc_attr( $category_key ); ?>"><?php echo esc_html( $category_label ); ?></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="banoks-menu-grid">
                                <?php foreach ( $products as $product ) : ?>
                                    <?php
                                    $available = ! isset( $product->is_available ) || intval( $product->is_available );
                                    $image_url  = ! empty( $product->product_image_id ) ? wp_get_attachment_image_url( absint( $product->product_image_id ), 'medium' ) : '';
                                    $category   = ! empty( $product->category ) ? $product->category : 'General';
                                    $description = ! empty( $product->product_description ) ? $product->product_description : 'No description available.';
                                    ?>
                                    <div class="banoks-menu-item<?php echo $available ? '' : ' is-disabled'; ?>" data-price="<?php echo esc_attr( $product->current_price ); ?>" data-category="<?php echo esc_attr( sanitize_title( $category ) ); ?>">
                                        <div class="banoks-menu-image">
                                            <?php if ( $image_url ) : ?>
                                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->product_name ); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="banoks-menu-copy">
                                            <strong><?php echo esc_html( $product->product_name ); ?></strong>
                                            <p class="banoks-menu-description"><?php echo esc_html( $description ); ?></p>
                                        </div>
                                        <div class="banoks-menu-row">
                                            <span>₱<?php echo esc_html( number_format( floatval( $product->current_price ), 2 ) ); ?></span>
                                            <?php if ( $available ) : ?>
                                                <button type="button" class="banoks-add-cart-btn"
                                                    data-product-id="<?php echo esc_attr( $product->product_id ); ?>"
                                                    data-product-name="<?php echo esc_attr( $product->product_name ); ?>"
                                                    data-product-price="<?php echo esc_attr( $product->current_price ); ?>"
                                                    data-product-image="<?php echo esc_url( $image_url ); ?>">Add to Cart</button>
                                            <?php else : ?>
                                                <em>Out of Stock</em>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

            </div>

                <div class="banoks-cart-modal" id="banoks-cart-modal" aria-hidden="true">
                    <div class="banoks-cart-dialog" role="dialog" aria-modal="true" aria-labelledby="banoks-cart-modal-title">
                        <button type="button" class="banoks-cart-modal-close" aria-label="Close">&times;</button>
                        <div class="banoks-cart-modal-product">
                            <div class="banoks-cart-modal-image" id="banoks-cart-modal-image"></div>
                            <div>
                                <h3 id="banoks-cart-modal-title">Add to Cart</h3>
                                <p id="banoks-cart-modal-price"></p>
                            </div>
                        </div>
                        <div class="banoks-cart-quantity-control">
                            <span>Quantity</span>
                            <div>
                                <button type="button" class="banoks-cart-qty-btn" data-qty-action="minus">-</button>
                                <input type="number" id="banoks-cart-modal-qty" value="1" min="1" step="1">
                                <button type="button" class="banoks-cart-qty-btn" data-qty-action="plus">+</button>
                            </div>
                        </div>
                        <div class="banoks-cart-addons">
                            <h4>Add-ons</h4>
                            <div id="banoks-cart-addon-list"></div>
                        </div>
                        <button type="button" class="banoks-cart-confirm-btn" id="banoks-cart-confirm">Add to Cart</button>
                    </div>
                </div>
        </div>
        <script>
            window.banoksOnlineAddons = <?php echo wp_json_encode( $addon_map ); ?>;
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render checkout form as a separate shortcode.
     *
     * @return string
     */
    public function render_checkout() {
        $customer       = $this->get_current_customer();
        $delivery_areas = $this->repository->get_delivery_areas();

        ob_start();
        $this->render_notice();
        ?>
        <div class="banoks-online-shell">
            <form method="post" enctype="multipart/form-data" class="banoks-online-checkout-form" data-can-order="<?php echo $customer ? '1' : '0'; ?>">
                <?php wp_nonce_field( 'banoks_customer_place_order' ); ?>
                <input type="hidden" name="banoks_public_action" value="place_order">
                <div class="banoks-checkout-layout">
                    <section class="banoks-online-panel banoks-checkout-details-panel">
                        <h2>Checkout</h2>
                        <?php if ( ! $customer ) : ?>
                            <p class="banoks-warning">Please register or login before placing an order.</p>
                        <?php else : ?>
                            <p class="banoks-muted">Ordering as <?php echo esc_html( $customer->full_name ); ?></p>
                        <?php endif; ?>

                        <label>Order Type
                            <select name="fulfillment_type" id="banoks-fulfillment-type" required>
                                <option value="delivery">Delivery</option>
                                <option value="pickup">Pickup</option>
                            </select>
                        </label>

                        <div class="banoks-delivery-checkout-fields">
                            <label>Delivery Area
                                <select name="delivery_area_id" id="banoks-delivery-area" required>
                                    <option value="" data-fee="0">Select area</option>
                                    <?php foreach ( $delivery_areas as $area ) : ?>
                                        <option value="<?php echo esc_attr( $area->id ); ?>" data-fee="<?php echo esc_attr( $area->delivery_fee ); ?>" <?php disabled( ! intval( $area->is_deliverable ) ); ?>>
                                            <?php echo esc_html( $area->area_name . ' - ' . html_entity_decode( '&#8369;', ENT_QUOTES, 'UTF-8' ) . number_format( floatval( $area->delivery_fee ), 2 ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>Specific Address
                                <textarea name="delivery_address" rows="3" required><?php echo $customer ? esc_textarea( $customer->address ) : ''; ?></textarea>
                            </label>
                        </div>

                        <label>Payment Method
                            <select name="payment_method" id="banoks-payment-method" required>
                                <option value="cod" data-fulfillment="delivery">COD</option>
                                <option value="pay_at_pickup" data-fulfillment="pickup">Pay at Pickup</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </label>

                        <div class="banoks-gcash-fields">
                            <p class="banoks-muted">GCash payment will be manually verified by cashier.</p>
                            <label>Screenshot <input type="file" name="payment_screenshot" accept="image/*" class="banoks-gcash-required"></label>
                        </div>

                        <label>Order Notes
                            <textarea name="notes" rows="3"></textarea>
                        </label>
                    </section>

                    <aside class="banoks-online-panel banoks-checkout-panel">
                        <h2>Your Cart</h2>
                        <div class="banoks-checkout-cart-list" id="banoks-checkout-cart-list">
                            <p class="banoks-muted">Your cart is empty.</p>
                        </div>
                        <div class="banoks-checkout-hidden-items" id="banoks-checkout-hidden-items"></div>
                        <div class="banoks-cart-summary">
                            <div><span>Subtotal</span><strong id="banoks-subtotal">&#8369;0.00</strong></div>
                            <div><span>Delivery Fee</span><strong id="banoks-delivery-fee">&#8369;0.00</strong></div>
                            <div><span>Total</span><strong id="banoks-total">&#8369;0.00</strong></div>
                        </div>
                        <button type="submit" <?php disabled( ! $customer ); ?>>Place Order</button>
                    </aside>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a cart icon shortcode with a live item count badge.
     *
     * @return string
     */
    public function render_cart_button() {
        ob_start();
        ?>
        <span class="banoks-cart-button-shortcode">
            <style>
                .banoks-cart-button-shortcode{all:initial;display:inline-block!important;width:48px!important;height:48px!important;line-height:1!important;font-family:"Poppins",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important}
                .banoks-cart-button-shortcode .banoks-floating-cart-button{all:unset;box-sizing:border-box!important;position:relative!important;width:48px!important;height:48px!important;min-width:48px!important;min-height:48px!important;max-width:48px!important;max-height:48px!important;border:0!important;outline:0!important;border-radius:4px!important;background:#000!important;box-shadow:none!important;cursor:pointer!important;display:inline-grid!important;place-items:center!important;padding:0!important;margin:0!important;appearance:none!important;-webkit-appearance:none!important;text-decoration:none!important;vertical-align:middle!important}
                .banoks-cart-button-shortcode .banoks-floating-cart-button:hover,.banoks-cart-button-shortcode .banoks-floating-cart-button:focus{background:#000!important;box-shadow:none!important}
                .banoks-cart-button-shortcode .banoks-floating-cart-button svg{display:block!important;width:30px!important;height:30px!important;fill:#fff!important;stroke:none!important;overflow:visible!important;margin:0!important}
                .banoks-cart-button-shortcode .banoks-floating-cart-button svg path{fill:#fff!important;stroke:none!important}
                .banoks-cart-button-shortcode .banoks-cart-count{box-sizing:border-box!important;position:absolute!important;top:6px!important;right:6px!important;min-width:16px!important;width:auto!important;height:16px!important;padding:0 4px!important;border:0!important;border-radius:999px!important;background:#ef1010!important;color:#fff!important;display:grid!important;place-items:center!important;font-size:9px!important;font-weight:600!important;line-height:1!important;letter-spacing:0!important;text-align:center!important;text-decoration:none!important}
            </style>
            <button type="button" class="banoks-floating-cart-button" aria-label="Cart">
                <svg aria-hidden="true" viewBox="0 0 360 360" focusable="false">
                    <path d="M113,78.81c-9.38,0-14.22-8.14-14.11-14.64.13-7.83,5.92-14.13,14.54-14.13l208.21-.09c22.57,0,42.84,19.6,37.5,42.91l-29.01,126.66c-3.6,15.73-17.35,28.58-34.03,28.58l-182.75-.04c-15.84,0-29.84-12.67-33.3-27.78L42.01,54.38,7.91,38.21C.52,34.71-1.92,25.74,1.56,19.08c3.54-6.79,11.55-10.01,19.06-6.5l30.15,14.08c9.92,4.64,17.4,13.32,19.94,24.41l36.92,161.6c.88,3.85,4.51,7.15,8.68,7.15l176.97.02c3.94,0,8.12-2.95,8.98-6.66l28.78-124.3c1.18-5.09-2.73-10-8.07-10l-209.97-.06Z"/>
                    <path d="M178.24,312.54c0,20.06-16.22,36.33-36.23,36.33s-36.23-16.27-36.23-36.33,16.22-36.33,36.23-36.33,36.23,16.27,36.23,36.33ZM155.25,312.53c0-7.34-5.94-13.3-13.26-13.3s-13.26,5.95-13.26,13.3,5.94,13.3,13.26,13.3,13.26-5.95,13.26-13.3Z"/>
                    <path d="M303.8,312.55c0,20.06-16.22,36.33-36.22,36.33s-36.22-16.26-36.22-36.33,16.22-36.33,36.22-36.33,36.22,16.26,36.22,36.33ZM280.85,312.52c0-7.34-5.93-13.29-13.25-13.29s-13.25,5.95-13.25,13.29,5.93,13.29,13.25,13.29,13.25-5.95,13.25-13.29Z"/>
                </svg>
                <span class="banoks-cart-count">0</span>
            </button>
        </span>
        <?php
        return ob_get_clean();
    }

    /**
     * Render logged-in customer's orders.
     *
     * @return string
     */
    public function render_my_orders() {
        $customer = $this->get_current_customer();

        ob_start();
        $this->render_notice();
        ?>
        <div class="banoks-online-shell">
            <div class="banoks-online-panel">
                <h2>My Orders</h2>
                <?php if ( ! $customer ) : ?>
                    <p class="banoks-warning">Please login to view your orders.</p>
                <?php else : ?>
                    <?php $orders = $this->repository->get_customer_online_orders( $customer->id ); ?>
                    <?php if ( empty( $orders ) ) : ?>
                        <p class="banoks-muted">You do not have online orders yet.</p>
                    <?php else : ?>
                        <div class="banoks-order-list">
                            <?php foreach ( $orders as $order ) : ?>
                                <?php
                                $status_label      = ucwords( str_replace( '_', ' ', $order->order_status ) );
                                $fulfillment_label = ! empty( $order->fulfillment_type ) && 'pickup' === $order->fulfillment_type ? 'Pickup' : 'Delivery';
                                ?>
                                <div class="banoks-order-card">
                                    <div>
                                        <strong><?php echo esc_html( $order->online_order_id ); ?></strong>
                                        <span><?php echo esc_html( wp_date( 'M d, Y g:i A', strtotime( $order->created_at ) ) ); ?></span>
                                        <span><?php echo esc_html( $fulfillment_label ); ?></span>
                                    </div>
                                    <div>
                                        <span class="banoks-status"><?php echo esc_html( $status_label ); ?></span>
                                        <strong>₱<?php echo esc_html( number_format( floatval( $order->total_amount ), 2 ) ); ?></strong>
                                    </div>
                                    <?php if ( 'delivery' === $order->fulfillment_type && 'delivering' === $order->order_status && ( $order->driver_name || $order->driver_contact ) ) : ?>
                                        <p>Driver: <?php echo esc_html( trim( $order->driver_name . ' ' . $order->driver_contact ) ); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_register() {
        check_admin_referer( 'banoks_customer_register' );

        $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        if ( strlen( $password ) < 6 ) {
            $this->redirect_with_notice( 'Password must be at least 6 characters.', true );
        }

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

        if ( ( '' !== $email && $this->repository->get_customer_by_identifier( $email ) ) || $this->repository->get_customer_by_identifier( $phone ) ) {
            $this->redirect_with_notice( 'An account with that email or phone already exists.', true );
        }

        $customer = $this->repository->create_customer(
            array(
                'full_name' => isset( $_POST['full_name'] ) ? wp_unslash( $_POST['full_name'] ) : '',
                'phone'     => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
                'email'     => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
                'address'   => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '',
                'password'  => $password,
            )
        );

        if ( is_array( $customer ) && isset( $customer['error'] ) ) {
            $this->redirect_with_notice( $customer['error'], true );
        }

        $this->set_customer_cookie( $customer );
        $this->redirect_with_notice( 'Account created successfully.', false );
    }

    private function handle_login() {
        check_admin_referer( 'banoks_customer_login' );

        $identifier = isset( $_POST['identifier'] ) ? wp_unslash( $_POST['identifier'] ) : '';
        $password   = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $customer   = $this->repository->get_customer_by_identifier( $identifier );

        if ( ! $customer || empty( $customer->password_hash ) || ! wp_check_password( $password, $customer->password_hash ) ) {
            $this->redirect_with_notice( 'Invalid login details.', true );
        }

        $this->set_customer_cookie( $customer );
        $this->redirect_with_notice( 'Logged in successfully.', false );
    }

    private function handle_place_order() {
        check_admin_referer( 'banoks_customer_place_order' );

        $customer = $this->get_current_customer();
        if ( ! $customer ) {
            $this->redirect_with_notice( 'Please login before placing an order.', true );
        }

        $payment_attachment_id  = 0;
        $payment_screenshot_url = '';
        $payment_method         = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cod';

        if ( 'gcash' === $payment_method && empty( $_FILES['payment_screenshot']['name'] ) ) {
            $this->redirect_with_notice( 'Please upload your GCash payment proof before placing the order.', true );
        }

        if ( ! empty( $_FILES['payment_screenshot']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $payment_attachment_id = media_handle_upload( 'payment_screenshot', 0 );
            if ( is_wp_error( $payment_attachment_id ) ) {
                $this->redirect_with_notice( 'GCash screenshot upload failed: ' . $payment_attachment_id->get_error_message(), true );
            }
            $payment_screenshot_url = wp_get_attachment_url( $payment_attachment_id );
        }

        $result = $this->repository->create_online_order(
            array(
                'customer_id'           => $customer->id,
                'fulfillment_type'      => isset( $_POST['fulfillment_type'] ) ? wp_unslash( $_POST['fulfillment_type'] ) : 'delivery',
                'delivery_area_id'      => isset( $_POST['delivery_area_id'] ) ? absint( $_POST['delivery_area_id'] ) : 0,
                'delivery_address'      => isset( $_POST['delivery_address'] ) ? wp_unslash( $_POST['delivery_address'] ) : '',
                'payment_method'        => $payment_method,
                'payment_attachment_id' => $payment_attachment_id,
                'payment_screenshot_url'=> $payment_screenshot_url,
                'notes'                 => isset( $_POST['notes'] ) ? wp_unslash( $_POST['notes'] ) : '',
                'items'                 => isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array(),
            )
        );

        if ( isset( $result['error'] ) ) {
            $this->redirect_with_notice( $result['error'], true );
        }

        $this->redirect_with_notice( 'Order placed successfully.', false, array( 'banoks_order_success' => '1' ) );
    }

    private function get_current_customer() {
        if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
            return null;
        }

        $parts = explode( ':', sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) );
        if ( 2 !== count( $parts ) ) {
            return null;
        }

        $customer_id = absint( $parts[0] );
        $signature   = $parts[1];
        if ( ! hash_equals( wp_hash( 'banoks_customer|' . $customer_id ), $signature ) ) {
            return null;
        }

        return $this->repository->get_customer( $customer_id );
    }

    private function set_customer_cookie( $customer ) {
        $value = intval( $customer->id ) . ':' . wp_hash( 'banoks_customer|' . intval( $customer->id ) );
        setcookie( self::COOKIE_NAME, $value, time() + WEEK_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
        $_COOKIE[ self::COOKIE_NAME ] = $value;
    }

    private function clear_customer_cookie() {
        setcookie( self::COOKIE_NAME, '', time() - HOUR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
        unset( $_COOKIE[ self::COOKIE_NAME ] );
    }

    private function redirect_with_notice( $message, $is_error, $extra_args = array() ) {
        $url = remove_query_arg( array( 'banoks_notice', 'banoks_error', 'banoks_order_success' ), wp_get_referer() ?: home_url( '/' ) );
        $url = add_query_arg(
            array_merge(
                $extra_args,
                array(
                $is_error ? 'banoks_error' : 'banoks_notice' => rawurlencode( $message ),
                )
            ),
            $url
        );
        wp_safe_redirect( $url );
        exit;
    }

    private function render_notice() {
        if ( isset( $_GET['banoks_error'] ) ) {
            echo '<div class="banoks-notice is-error">' . esc_html( sanitize_text_field( wp_unslash( $_GET['banoks_error'] ) ) ) . '</div>';
        } elseif ( isset( $_GET['banoks_notice'] ) ) {
            echo '<div class="banoks-notice is-success">' . esc_html( sanitize_text_field( wp_unslash( $_GET['banoks_notice'] ) ) ) . '</div>';
        }
    }
}

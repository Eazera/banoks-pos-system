<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://banoks.com
 * @since      1.0.0
 * @package    Banoks_POS
 * @subpackage Banoks_POS/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Banoks_POS
 * @subpackage Banoks_POS/admin
 * @author     Christian Fulache
 */
class Banoks_POS_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
        if ( ! $this->is_banoks_pos_screen() ) {
            return;
        }

        wp_enqueue_style( 'dashicons' );

        $admin_css = BANOKS_POS_PATH . 'admin/css/banoks-pos-admin.css';

		wp_enqueue_style(
            $this->plugin_name,
            BANOKS_POS_URL . 'admin/css/banoks-pos-admin.css',
            array(),
            file_exists( $admin_css ) ? filemtime( $admin_css ) : $this->version,
            'all'
        );

        if ( current_user_can( 'banoks_use_pos' ) && ! current_user_can( 'manage_options' ) ) {
            wp_add_inline_style(
                $this->plugin_name,
                '
                html.wp-toolbar {
                    padding-top: 0 !important;
                }

                #wpadminbar,
                #adminmenumain,
                #screen-meta-links,
                #wpfooter {
                    display: none !important;
                }

                #wpcontent,
                #wpfooter {
                    margin-left: 0 !important;
                }

                #wpbody-content {
                    padding-bottom: 0 !important;
                }

                .auto-fold #wpcontent {
                    margin-left: 0 !important;
                }
                '
            );
        }
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        if ( ! $this->is_banoks_pos_screen() ) {
            return;
        }

        if (
            $this->is_banoks_pos_screen( 'banoks-pos-reports' )
            && isset( $_GET['report_action'] )
            && 'export_pdf' === sanitize_key( wp_unslash( $_GET['report_action'] ) )
        ) {
            check_admin_referer( 'banoks_export_report_pdf' );
            $start_date = $this->get_request_date( 'start_date', wp_date( 'Y-m-01' ) );
            $end_date   = $this->get_request_date( 'end_date', wp_date( 'Y-m-d' ) );
            $branch_key = isset( $_GET['branch_key'] ) ? sanitize_key( wp_unslash( $_GET['branch_key'] ) ) : Banoks_POS_Repository::STOCK_LOCATION_MANUKAN;
            $this->export_report_pdf( $start_date, $end_date, $branch_key );
        }

        if ( $this->is_banoks_pos_screen( 'banoks-pos-products' ) ) {
            wp_enqueue_media();
        }

        $admin_js = BANOKS_POS_PATH . 'admin/js/banoks-pos-admin.js';
        $deps     = array( 'jquery' );

        if ( $this->is_banoks_pos_screen( 'banoks-pos-reports' ) ) {
            wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true );
            $deps[] = 'chart-js';
        }

		wp_enqueue_script(
            $this->plugin_name,
            BANOKS_POS_URL . 'admin/js/banoks-pos-admin.js',
            $deps,
            file_exists( $admin_js ) ? filemtime( $admin_js ) : $this->version,
            true
        );
	}

    /**
     * Check whether the current admin page belongs to Banoks POS.
     *
     * @since    1.0.1
     * @param    string $page Optional exact page slug.
     * @return   bool
     */
    private function is_banoks_pos_screen( $page = '' ) {
        $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

        if ( '' !== $page ) {
            return $current_page === $page;
        }

        return 0 === strpos( $current_page, 'banoks-pos' );
    }

    /**
     * Return a request date only when it is a valid Y-m-d value.
     *
     * @since    1.0.10
     * @param    string $key Request key.
     * @param    string $default Default date.
     * @return   string
     */
    private function get_request_date( $key, $default ) {
        if ( empty( $_GET[ $key ] ) ) {
            return $default;
        }

        $date = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return $default;
        }

        $parts = explode( '-', $date );
        if ( ! checkdate( intval( $parts[1] ), intval( $parts[2] ), intval( $parts[0] ) ) ) {
            return $default;
        }

        return $date;
    }

    /**
     * Return a request value only when it appears in an allowed list.
     *
     * @since    1.0.10
     * @param    string $key Request key.
     * @param    array  $allowed Allowed values.
     * @param    string $default Default value.
     * @return   string
     */
    private function get_request_choice( $key, $allowed, $default ) {
        if ( empty( $_GET[ $key ] ) ) {
            return $default;
        }

        $value = sanitize_key( wp_unslash( $_GET[ $key ] ) );
        return in_array( $value, $allowed, true ) ? $value : $default;
    }

    /**
     * Run additive schema updates for existing installs.
     *
     * @since    1.0.7
     */
    public function maybe_run_migrations() {
        if ( current_user_can( 'banoks_use_pos' ) && class_exists( 'Banoks_DB' ) ) {
            Banoks_DB::create_tables();
        }
    }

    /**
     * Ensure the products table has the current columns.
     *
     * @since    1.0.4
     */
    private function maybe_update_products_schema() {
        if ( class_exists( 'Banoks_DB' ) ) {
            Banoks_DB::create_tables();
        }
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Banoks POS', // Page title
            'Banoks POS', // Menu title
            'banoks_use_pos', // Capability
            $this->plugin_name, // Menu slug
            array( $this, 'display_plugin_setup_page' ), // Callback function
            'dashicons-cart', // Icon
            30 // Position
        );

        add_submenu_page(
            $this->plugin_name,
            'Walk-in Orders',
            'Walk-in Orders',
            'banoks_use_pos',
            $this->plugin_name . '-pos',
            array( $this, 'display_pos_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Owner Dashboard',
            'Owner Dashboard',
            'manage_options',
            $this->plugin_name . '-owner-dashboard',
            array( $this, 'display_owner_dashboard_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Product Management',
            'Product Management',
            'manage_options',
            $this->plugin_name . '-products',
            array( $this, 'display_products_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Online Orders',
            'Online Orders',
            'banoks_use_pos',
            $this->plugin_name . '-online-orders',
            array( $this, 'display_online_orders_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Delivery Areas',
            'Delivery Areas',
            'manage_options',
            $this->plugin_name . '-delivery-areas',
            array( $this, 'display_delivery_areas_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Stock Management',
            'Stock Management',
            'manage_options',
            $this->plugin_name . '-stock-management',
            array( $this, 'display_stock_management_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Business Reports',
            'Reports',
            'manage_options',
            $this->plugin_name . '-reports',
            array( $this, 'display_reports_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Finance',
            'Finance',
            'manage_options',
            $this->plugin_name . '-cash-management',
            array( $this, 'display_cash_management_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            'Requests',
            'Requests',
            'banoks_use_pos',
            $this->plugin_name . '-expenses',
            array( $this, 'display_expenses_page' )
        );
    }

    /**
     * Return cash source options for expense and stock purchase records.
     *
     * @since    1.0.13
     * @return   array
     */
    private function get_cash_source_options() {
        return array(
            'store_cash'    => 'Manukan Store Balance (Today\'s Sales)',
            'cash_on_hand'  => 'Cash on Hand',
            'gcash_balance' => 'GCash Balance',
            'bank_balance'  => 'Bank Balance',
        );
    }

    private function get_stock_location_options() {
        return array(
            Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION => 'Production Stock',
            Banoks_POS_Repository::STOCK_LOCATION_MANUKAN    => 'Manukan Branch',
        );
    }

    private function sanitize_stock_location_key( $location_key ) {
        $repository = new Banoks_POS_Repository();
        return $repository->sanitize_stock_location_key( $location_key );
    }

    /**
     * Return a valid cash source key.
     *
     * @since    1.0.13
     * @param    string $source Requested source.
     * @return   string
     */
    private function sanitize_cash_source( $source ) {
        $source = sanitize_key( $source );
        return isset( $this->get_cash_source_options()[ $source ] ) ? $source : 'store_cash';
    }

    /**
     * Return inventory units supported by stock management.
     *
     * @since    1.0.11
     * @return   array
     */
    private function get_stock_unit_options() {
        return array(
            'pcs'      => 'Pieces',
            'servings' => 'Servings',
            'sticks'   => 'Sticks',
            'bottles'  => 'Bottles',
            'packs'    => 'Packs',
            'kg'       => 'Kilograms',
            'g'        => 'Grams',
            'liters'   => 'Liters',
            'ml'       => 'Milliliters',
        );
    }

    /**
     * Render the POS ordering interface.
     * Render the Shared Header for Dashboard and POS.
     *
     * @since    1.0.0
     */
    public function display_admin_header() {
        global $wpdb;
        $today = current_time( 'Y-m-d' );
        $walkin_sales = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(grand_total) FROM {$wpdb->prefix}banoks_orders WHERE date = %s AND status = 'completed'", $today ) ) ?: 0;
        $online_sales = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total_amount) FROM {$wpdb->prefix}banoks_online_orders WHERE DATE(created_at) = %s AND order_status = 'completed'", $today ) ) ?: 0;
        $sales = floatval( $walkin_sales ) + floatval( $online_sales );
        $total_expenses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$wpdb->prefix}banoks_expenses WHERE date = %s AND cash_source = 'store_cash'", $today ) ) ?: 0;
        $total_expenses += $this->get_stock_cash_expenses_for_period( $today, $today, 'store_cash' );
        $sales = $sales - $total_expenses;
        
        $current_user = wp_get_current_user();
        $cashier_name = !empty($current_user->display_name) ? $current_user->display_name : $current_user->user_login;

        $show_nav      = true;
        $dashboard_url = admin_url( 'admin.php?page=banoks-pos&view=pending' );

        include BANOKS_POS_PATH . 'templates/parts/admin-header.php';
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_setup_page() {
        global $wpdb;
        $today = current_time( 'Y-m-d' );
        
        // Stat cards show all-time totals.
        $walkin_sales = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(grand_total) FROM {$wpdb->prefix}banoks_orders WHERE date = %s AND status = 'completed'", $today ) ) ?: 0;
        $online_sales = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total_amount) FROM {$wpdb->prefix}banoks_online_orders WHERE DATE(created_at) = %s AND order_status = 'completed'", $today ) ) ?: 0;
        $total_sales = floatval( $walkin_sales );
        $cash_expenses = $wpdb->get_var($wpdb->prepare("SELECT SUM(amount)FROM {$wpdb->prefix}banoks_expenses WHERE date = %s AND cash_source = 'store_cash'",$today ));
        $stock_cash_expenses = $this->get_stock_cash_expenses_for_period( $today, $today, 'store_cash' );
        $total_expenses = floatval( $cash_expenses ) + floatval( $stock_cash_expenses );
        $final_sale = $total_sales - $total_expenses;
        $today_walkin_sales = floatval( $walkin_sales );
        $today_online_sales = floatval( $online_sales );
        $repository = new Banoks_POS_Repository();
        $critical_inventory_alerts = $repository->get_inventory_stock_alerts( 5 );

        // Date for list filtering
        $active_date = $this->get_request_date( 'date', $today );

        // View Type (pending/history)
        $view = $this->get_request_choice( 'view', array( 'pending', 'history' ), 'pending' );
        $status_filter = $this->get_request_choice( 'status', array( 'all', 'pending', 'preparing', 'completed', 'cancelled' ), 'all' );
        $search_query = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $has_date_param = isset( $_GET['date'] );

        $where = array();
        
        // Smart Search Filter - IF SEARCHING BY ID, IGNORE ALL OTHER FILTERS
        if ( ! empty( $search_query ) ) {
            $numeric_id = preg_replace('/[^0-9]/', '', $search_query);
            if ( ! empty( $numeric_id ) ) {
                $where[] = $wpdb->prepare("order_id = %d", intval($numeric_id));
            }
        }

        // Only apply Date/Status/View filters if NOT searching by a specific ID
        if ( empty( $where ) ) {
            // Base view filter
            if ( 'history' === $view ) {
                if ( 'all' === $status_filter ) {
                    $where[] = "status IN ('completed', 'cancelled')";
                } else {
                    $where[] = $wpdb->prepare("status = %s", $status_filter);
                }
            } else {
                $where[] = "status IN ('pending', 'preparing')";
            }

            // Date filter (only if not overridden by All History)
            if ( $has_date_param ) {
                $where[] = $wpdb->prepare("date = %s", $active_date);
            }
        }

        $where_clause = "WHERE " . implode(' AND ', $where);
        $orders = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_orders $where_clause ORDER BY entry_timestamp DESC LIMIT 100" );

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-admin-display.php';
    }

    public function display_owner_dashboard_page() {
        global $wpdb;

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to access this page.', 'banoks-pos-system' ) );
        }

        $this->display_admin_header();
        $this->maybe_update_products_schema();

        $message = '';
        $error   = '';

        if ( isset( $_POST['banoks_owner_request_action'] ) ) {
            check_admin_referer( 'banoks_owner_request_action' );
            $result = $this->handle_owner_request_decision();
            if ( isset( $result['error'] ) ) {
                $error = $result['error'];
            } else {
                $message = $result['message'];
            }
        }

        $pending_request_count = $this->get_owner_request_count( 'pending' );
        $recent_requests  = $this->get_requests_for_owner( 'all' );
        $owner_product_branches = $this->get_active_branches();
        $owner_cards      = array(
            array( 'label' => 'Product Management', 'url' => '#banoks-owner-product-branch-modal', 'desc' => 'Choose a branch before managing product stock.', 'modal' => 'banoks-owner-product-branch-modal' ),
            array( 'label' => 'Stock Management', 'url' => admin_url( 'admin.php?page=banoks-pos-stock-management' ), 'desc' => 'Manage Production and Manukan Branch stock.' ),
            array( 'label' => 'Requests', 'url' => admin_url( 'admin.php?page=banoks-pos-expenses' ), 'desc' => 'Review worker requests and expense history.', 'badge' => $pending_request_count ),
            array( 'label' => 'Finance', 'url' => admin_url( 'admin.php?page=banoks-pos-cash-management' ), 'desc' => 'Track GCash, bank, and claimed store sales.' ),
            array( 'label' => 'Reports', 'url' => admin_url( 'admin.php?page=banoks-pos-reports' ), 'desc' => 'Review sales, expenses, and transactions.' ),
            array( 'label' => 'Delivery Areas', 'url' => admin_url( 'admin.php?page=banoks-pos-delivery-areas' ), 'desc' => 'Manage online delivery areas and fees.' ),
        );

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-owner-dashboard-display.php';
    }

    /**
     * Render the POS ordering interface.
     *
     * @since    1.0.0
     */
    public function display_pos_page() {
        $repository = new Banoks_POS_Repository();
        $renderer   = new Banoks_POS_Renderer();
        $data       = $repository->get_pos_data(
            array(
                'active_date' => $this->get_request_date( 'date', current_time( 'Y-m-d' ) ),
            )
        );

        $data['show_header']   = true;
        $data['show_nav']      = true;
        $data['dashboard_url'] = admin_url( 'admin.php?page=banoks-pos&view=pending' );
        $data['is_shortcode']  = false;

        echo $renderer->render( 'pos', $data );
    }

    /**
     * Render the products management page and handle CRUD logic.
     *
     * @since    1.0.0
     */
    public function display_products_page() {
        global $wpdb;
        $this->display_admin_header();
        $this->maybe_update_products_schema();
        $table_name = $wpdb->prefix . 'banoks_items';
        $repository = new Banoks_POS_Repository();
        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
        $message = '';
        $active_branches = $this->get_active_branches();
        $selected_branch_key = isset( $_GET['branch_key'] ) ? sanitize_key( wp_unslash( $_GET['branch_key'] ) ) : '';
        $selected_branch_name = '';

        foreach ( $active_branches as $branch ) {
            $branch_key = sanitize_key( $branch->branch_key );
            if ( '' === $selected_branch_key ) {
                $selected_branch_key = $branch_key;
            }

            if ( $selected_branch_key === $branch_key ) {
                $selected_branch_name = $branch->branch_name;
            }
        }

        if ( '' === $selected_branch_name ) {
            $selected_branch_key  = Banoks_POS_Repository::STOCK_LOCATION_MANUKAN;
            $selected_branch_name = 'Manukan Branch';
        }

        // Handle Deletion
        if ( 'delete' === $action && isset( $_GET['id'] ) ) {
            $product_id = absint( $_GET['id'] );
            check_admin_referer( 'delete_product_' . $product_id );
            $wpdb->update(
                $table_name,
                array(
                    'is_active'    => 0,
                    'is_available' => 0,
                ),
                array( 'product_id' => $product_id ),
                array( '%d', '%d' ),
                array( '%d' )
            );
            $message = 'Product deactivated successfully.';
            $action = 'list';
        }

        // Handle Form Submission (Add/Edit)
        if ( isset( $_POST['banoks_pos_save_product'] ) ) {
            check_admin_referer( 'banoks_pos_product_action' );

            $data = array(
                'product_name'     => isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '',
                'product_description' => isset( $_POST['product_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['product_description'] ) ) : '',
                'category'         => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'General',
                'current_price'    => isset( $_POST['current_price'] ) ? floatval( wp_unslash( $_POST['current_price'] ) ) : 0,
                'product_image_id' => isset( $_POST['product_image_id'] ) ? absint( $_POST['product_image_id'] ) : 0,
                'is_available'     => ! empty( $_POST['is_available'] ) ? 1 : 0,
                'is_active'        => ! empty( $_POST['is_active'] ) ? 1 : 0,
            );
            if ( '' === trim( $data['category'] ) ) {
                $data['category'] = 'General';
            }

            if ( ! empty( $_POST['product_id'] ) ) {
                $product_id = absint( $_POST['product_id'] );
                $result = $wpdb->update(
                    $table_name,
                    $data,
                    array( 'product_id' => $product_id ),
                    array( '%s', '%s', '%s', '%f', '%d', '%d', '%d' ),
                    array( '%d' )
                );
                if ( false === $result ) {
                    $message = 'Error: Could not update product. ' . $wpdb->last_error;
                } else {
                    $this->save_product_recipe_rows( $product_id );
                    $this->save_product_addon_rows( $product_id );
                    $message = 'Product updated successfully.';
                    $action = 'list';
                }
            } else {
                $result = $wpdb->insert(
                    $table_name,
                    $data,
                    array( '%s', '%s', '%s', '%f', '%d', '%d', '%d' )
                );
                if ( false === $result ) {
                    $message = 'Error: Could not add product. ' . $wpdb->last_error;
                } else {
                    $product_id = $wpdb->insert_id;
                    $this->save_product_recipe_rows( $product_id );
                    $this->save_product_addon_rows( $product_id );
                    $message = 'Product added successfully.';
                    $action = 'list';
                }
            }
        }

        // Routing
        if ( 'add' === $action || 'edit' === $action ) {
            $product = null;
            if ( 'edit' === $action && isset( $_GET['id'] ) ) {
                $product = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE product_id = %d", absint( $_GET['id'] ) ) );
            }
            $existing_categories = $wpdb->get_col( "SELECT DISTINCT category FROM $table_name WHERE category != '' ORDER BY category ASC" );
            $inventory_items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_inventory_items WHERE is_active = 1 ORDER BY item_name ASC" );
            $addon_products = $wpdb->get_results( "SELECT product_id, product_name, current_price FROM $table_name WHERE COALESCE(is_active, 1) = 1 ORDER BY product_name ASC" );
            $selected_addon_ids = ( 'edit' === $action && ! empty( $product ) ) ? array_map( 'absint', $wpdb->get_col( $wpdb->prepare( "SELECT addon_product_id FROM {$wpdb->prefix}banoks_product_addons WHERE product_id = %d ORDER BY sort_order ASC, id ASC", absint( $product->product_id ) ) ) ) : array();
            $product_recipes = ( 'edit' === $action && ! empty( $product ) ) ? $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}banoks_product_recipes WHERE product_id = %d ORDER BY id ASC", absint( $product->product_id ) ) ) : array();
            include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-product-form.php';
        } else {
            $products = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY product_id DESC" );
            $product_ids = wp_list_pluck( $products, 'product_id' );
            $recipe_statuses = $repository->get_product_recipe_statuses( $product_ids, $selected_branch_key );
        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-products-list.php';
        }
    }

    /**
     * Render the backend-first online orders page.
     *
     * @since    1.0.7
     */
    public function display_online_orders_page() {
        global $wpdb;

        $this->display_admin_header();
        $this->maybe_update_products_schema();

        $repository = new Banoks_POS_Repository();
        $message    = '';
        $error      = '';

        if ( isset( $_POST['banoks_pos_update_online_order_status'] ) ) {
            check_admin_referer( 'banoks_pos_online_status_action' );

            $result = $repository->update_online_order_status(
                isset( $_POST['online_order_id'] ) ? absint( $_POST['online_order_id'] ) : 0,
                isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '',
                array(
                    'driver_name'    => isset( $_POST['driver_name'] ) ? wp_unslash( $_POST['driver_name'] ) : '',
                    'driver_contact' => isset( $_POST['driver_contact'] ) ? wp_unslash( $_POST['driver_contact'] ) : '',
                    'note'           => isset( $_POST['status_note'] ) ? wp_unslash( $_POST['status_note'] ) : '',
                )
            );

            if ( isset( $result['error'] ) ) {
                $error = $result['error'];
            } else {
                $message = 'Online order status updated successfully.';
            }
        }

        if ( isset( $_POST['banoks_pos_update_payment_proof'] ) ) {
            check_admin_referer( 'banoks_pos_payment_proof_action' );

            $result = $repository->update_payment_proof_status(
                isset( $_POST['payment_proof_id'] ) ? absint( $_POST['payment_proof_id'] ) : 0,
                isset( $_POST['payment_proof_status'] ) ? sanitize_key( wp_unslash( $_POST['payment_proof_status'] ) ) : '',
                isset( $_POST['payment_rejection_reason'] ) ? wp_unslash( $_POST['payment_rejection_reason'] ) : ''
            );

            if ( isset( $result['error'] ) ) {
                $error = $result['error'];
            } else {
                $message = 'Payment proof updated successfully.';
            }
        }

        $today = current_time( 'Y-m-d' );
        $online_sales = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_amount) FROM {$wpdb->prefix}banoks_online_orders WHERE DATE(created_at) = %s AND order_status = 'completed'",
                $today
            )
        ) ?: 0;
        $cash_expenses = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}banoks_expenses WHERE date = %s AND cash_source = 'store_cash'",
                $today
            )
        ) ?: 0;
        $stock_cash_expenses = $this->get_stock_cash_expenses_for_period( $today, $today, 'store_cash' );
        $total_sales         = floatval( $online_sales );
        $total_expenses      = floatval( $cash_expenses ) + floatval( $stock_cash_expenses );
        $final_sale          = $total_sales - $total_expenses;

        $online_orders  = $repository->get_recent_online_orders();
        $online_related = $repository->get_online_order_related_data( $online_orders );

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-online-orders-display.php';
    }

    /**
     * Save product recipe rows from the product form.
     *
     * @since    1.0.11
     * @param    int $product_id Product ID.
     */
    private function save_product_recipe_rows( $product_id ) {
        global $wpdb;

        $product_id = absint( $product_id );
        if ( ! $product_id ) {
            return;
        }

        $inventory_ids = isset( $_POST['recipe_inventory_item_id'] ) && is_array( $_POST['recipe_inventory_item_id'] ) ? wp_unslash( $_POST['recipe_inventory_item_id'] ) : array();
        $quantities    = isset( $_POST['recipe_quantity_used'] ) && is_array( $_POST['recipe_quantity_used'] ) ? wp_unslash( $_POST['recipe_quantity_used'] ) : array();
        $conditions    = isset( $_POST['recipe_applies_to'] ) && is_array( $_POST['recipe_applies_to'] ) ? wp_unslash( $_POST['recipe_applies_to'] ) : array();
        $recipe_rows   = array();
        $allowed_conditions = array( 'all', 'walk_in', 'online', 'delivery', 'pickup' );

        foreach ( $inventory_ids as $index => $inventory_id ) {
            $inventory_id  = absint( $inventory_id );
            $quantity_used = isset( $quantities[ $index ] ) ? max( 0, floatval( $quantities[ $index ] ) ) : 0;
            $applies_to    = isset( $conditions[ $index ] ) ? sanitize_key( $conditions[ $index ] ) : 'all';

            if ( ! $inventory_id || $quantity_used <= 0 ) {
                continue;
            }

            if ( ! in_array( $applies_to, $allowed_conditions, true ) ) {
                $applies_to = 'all';
            }

            $recipe_key = $inventory_id . '|' . $applies_to;
            if ( ! isset( $recipe_rows[ $recipe_key ] ) ) {
                $recipe_rows[ $recipe_key ] = array(
                    'inventory_id'   => $inventory_id,
                    'quantity_used'  => 0,
                    'applies_to'     => $applies_to,
                );
            }

            $recipe_rows[ $recipe_key ]['quantity_used'] += $quantity_used;
        }

        $wpdb->delete(
            $wpdb->prefix . 'banoks_product_recipes',
            array( 'product_id' => $product_id ),
            array( '%d' )
        );

        foreach ( $recipe_rows as $recipe_row ) {
            $wpdb->insert(
                $wpdb->prefix . 'banoks_product_recipes',
                array(
                    'product_id'        => $product_id,
                    'inventory_item_id' => absint( $recipe_row['inventory_id'] ),
                    'quantity_used'     => floatval( $recipe_row['quantity_used'] ),
                    'applies_to'        => $recipe_row['applies_to'],
                    'created_at'        => current_time( 'mysql' ),
                    'updated_at'        => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%f', '%s', '%s', '%s' )
            );
        }
    }

    /**
     * Save product-specific addon product rows from the product form.
     *
     * @since    1.0.14
     * @param    int $product_id Product ID.
     */
    private function save_product_addon_rows( $product_id ) {
        global $wpdb;

        $product_id = absint( $product_id );
        if ( ! $product_id ) {
            return;
        }

        $addon_ids = isset( $_POST['addon_product_ids'] ) && is_array( $_POST['addon_product_ids'] ) ? wp_unslash( $_POST['addon_product_ids'] ) : array();
        $addon_ids = array_values( array_unique( array_filter( array_map( 'absint', $addon_ids ) ) ) );

        $wpdb->delete(
            $wpdb->prefix . 'banoks_product_addons',
            array( 'product_id' => $product_id ),
            array( '%d' )
        );

        $sort_order = 0;
        foreach ( $addon_ids as $addon_id ) {
            if ( $addon_id === $product_id ) {
                continue;
            }

            $wpdb->insert(
                $wpdb->prefix . 'banoks_product_addons',
                array(
                    'product_id'       => $product_id,
                    'addon_product_id' => $addon_id,
                    'sort_order'       => $sort_order,
                    'created_at'       => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%d', '%s' )
            );
            $sort_order++;
        }
    }

    /**
     * Render stock management for ingredients and kitchen supplies.
     *
     * @since    1.0.11
     */
    public function display_stock_management_page() {
        global $wpdb;

        $this->display_admin_header();
        $this->maybe_update_products_schema();

        $items_table      = $wpdb->prefix . 'banoks_inventory_items';
        $balances_table   = $wpdb->prefix . 'banoks_inventory_balances';
        $movements_table  = $wpdb->prefix . 'banoks_inventory_movements';
        $repository       = new Banoks_POS_Repository();
        $message          = '';
        $message_type     = 'updated';
        $unit_options     = $this->get_stock_unit_options();
        $stock_locations  = $this->get_stock_location_options();
        $action           = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
        $movement_options = array(
            'stock_in'          => 'Stock In',
            'transfer_in'       => 'Branch Stock Added',
            'transfer_out'      => 'Production Stock Transferred',
            'recipe_usage'      => 'Product Usage',
            'recipe_restore'    => 'Cancelled Order Stock Return',
            'manual_adjustment' => 'Manual Adjustment',
            'usage'             => 'Legacy Usage',
            'waste'             => 'Legacy Waste',
            'correction'        => 'Legacy Correction',
        );
        $movement_filter_options = array(
            'stock_in'          => $movement_options['stock_in'],
            'transfer_in'       => $movement_options['transfer_in'],
            'transfer_out'      => $movement_options['transfer_out'],
            'recipe_usage'      => $movement_options['recipe_usage'],
            'recipe_restore'    => $movement_options['recipe_restore'],
            'manual_adjustment' => $movement_options['manual_adjustment'],
        );
        $cash_source_options = $this->get_cash_source_options();
        if ( 'deactivate' === $action && isset( $_GET['id'] ) ) {
            $item_id = absint( $_GET['id'] );
            check_admin_referer( 'banoks_deactivate_inventory_' . $item_id );
            $wpdb->update(
                $items_table,
                array(
                    'is_active'  => 0,
                    'updated_at' => current_time( 'mysql' ),
                ),
                array( 'id' => $item_id ),
                array( '%d', '%s' ),
                array( '%d' )
            );
            $message = 'Inventory item deactivated.';
            $action  = 'list';
        }

        if ( isset( $_POST['banoks_save_inventory_item'] ) ) {
            check_admin_referer( 'banoks_inventory_item_action' );

            $item_id = isset( $_POST['inventory_item_id'] ) ? absint( $_POST['inventory_item_id'] ) : 0;
            $unit    = isset( $_POST['unit'] ) ? sanitize_key( wp_unslash( $_POST['unit'] ) ) : 'pcs';
            if ( ! isset( $unit_options[ $unit ] ) ) {
                $unit = 'pcs';
            }

            $data = array(
                'item_name'           => isset( $_POST['item_name'] ) ? sanitize_text_field( wp_unslash( $_POST['item_name'] ) ) : '',
                'category'            => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'Ingredients',
                'unit'                => $unit,
                'unit_cost'           => isset( $_POST['unit_cost'] ) ? max( 0, floatval( wp_unslash( $_POST['unit_cost'] ) ) ) : 0,
                'low_stock_threshold' => isset( $_POST['low_stock_threshold'] ) ? max( 0, floatval( wp_unslash( $_POST['low_stock_threshold'] ) ) ) : 0,
                'is_active'           => ! empty( $_POST['is_active'] ) ? 1 : 0,
                'updated_at'          => current_time( 'mysql' ),
            );

            if ( '' === $data['item_name'] ) {
                $message      = 'Please enter an inventory item name.';
                $message_type = 'error';
            }

            if ( '' === trim( $data['category'] ) ) {
                $data['category'] = 'Ingredients';
            }

            if ( 'error' !== $message_type ) {
                if ( $item_id ) {
                    $updated = $wpdb->update(
                        $items_table,
                        $data,
                        array( 'id' => $item_id ),
                        array( '%s', '%s', '%s', '%f', '%f', '%d', '%s' ),
                        array( '%d' )
                    );

                    if ( false === $updated ) {
                        $message      = 'Could not update inventory item.';
                        $message_type = 'error';
                    } else {
                        $message = 'Inventory item updated.';
                        $action  = 'list';
                    }
                } else {
                    $data['created_at'] = current_time( 'mysql' );
                    $data['current_stock'] = 0;
                    $inserted = $wpdb->insert(
                        $items_table,
                        $data,
                        array( '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%f' )
                    );

                    if ( false === $inserted ) {
                        $message      = 'Could not add inventory item.';
                        $message_type = 'error';
                    } else {
                        $item_id = $wpdb->insert_id;
                        $this->set_inventory_location_stock( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION, 0 );
                        $this->set_inventory_location_stock( $item_id, Banoks_POS_Repository::STOCK_LOCATION_MANUKAN, 0 );
                        $message = 'Inventory item added.';
                        $action  = 'list';
                    }
                }
            }
        }

        if ( isset( $_POST['banoks_adjust_inventory_stock'] ) ) {
            check_admin_referer( 'banoks_inventory_adjust_action' );

            $item_id       = isset( $_POST['inventory_item_id'] ) ? absint( $_POST['inventory_item_id'] ) : 0;
            $raw_location_key = isset( $_POST['movement_location_key'] ) ? sanitize_key( wp_unslash( $_POST['movement_location_key'] ) ) : '';
            $movement_type = isset( $_POST['movement_type'] ) ? sanitize_key( wp_unslash( $_POST['movement_type'] ) ) : 'stock_in';
            $quantity      = isset( $_POST['quantity'] ) ? max( 0, floatval( wp_unslash( $_POST['quantity'] ) ) ) : 0;
            $posted_unit_cost = isset( $_POST['movement_unit_cost'] ) ? max( 0, floatval( wp_unslash( $_POST['movement_unit_cost'] ) ) ) : 0;
            $unit_cost     = $posted_unit_cost;
            $note          = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
            $affects_cash_balance = ! empty( $_POST['affects_cash_balance'] ) ? 1 : 0;
            $cash_source   = isset( $_POST['movement_cash_source'] ) ? $this->sanitize_cash_source( wp_unslash( $_POST['movement_cash_source'] ) ) : 'store_cash';
            $stock_movement_action = isset( $_POST['stock_movement_action'] ) ? sanitize_key( wp_unslash( $_POST['stock_movement_action'] ) ) : '';
            $location_key  = 'add_branch_stock' === $stock_movement_action ? $this->sanitize_stock_location_key( $raw_location_key ) : Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION;

            $manual_movement_options = array( 'stock_in' );
            if ( ! $item_id || ! isset( $movement_options[ $movement_type ] ) || ! in_array( $movement_type, $manual_movement_options, true ) || $quantity <= 0 ) {
                $message      = 'Please choose an item, movement type, and quantity.';
                $message_type = 'error';
            } else {
                $item = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $items_table WHERE id = %d",
                        $item_id
                    )
                );

                if ( ! $item ) {
                    $message      = 'Inventory item not found.';
                    $message_type = 'error';
                } else {
                    $old_stock = $this->get_inventory_location_stock( $item_id, $location_key );
                    $new_stock = in_array( $movement_type, array( 'usage', 'waste' ), true ) ? $old_stock - $quantity : $old_stock + $quantity;
                    if ( 0 === $unit_cost ) {
                        $unit_cost = isset( $item->unit_cost ) ? floatval( $item->unit_cost ) : 0;
                    }

                    if ( 'add_branch_stock' === $stock_movement_action ) {
                        $production_stock = $this->get_inventory_location_stock( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION );
                        $branch_stock     = $this->get_inventory_location_stock( $item_id, $location_key );

                        if ( '' === $raw_location_key || ! isset( $stock_locations[ $raw_location_key ] ) || Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION === $location_key ) {
                            $message      = 'Please choose a branch for branch stock.';
                            $message_type = 'error';
                        } elseif ( $production_stock < $quantity ) {
                            $message      = 'Production stock is not enough for this branch stock transfer.';
                            $message_type = 'error';
                        } else {
                            $wpdb->query( 'START TRANSACTION' );
                            $updated_production = $this->update_inventory_location_stock_if_current( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION, $production_stock, $production_stock - $quantity );
                            $updated_branch     = $this->update_inventory_location_stock_if_current( $item_id, $location_key, $branch_stock, $branch_stock + $quantity );

                            if ( false === $updated_production || 0 === $updated_production || false === $updated_branch || 0 === $updated_branch ) {
                                $wpdb->query( 'ROLLBACK' );
                                $message      = 'Stock changed while saving. Please reload and try again.';
                                $message_type = 'error';
                            } else {
                                $transfer_note = '' !== $note ? $note : 'Branch stock added from Production Inventory.';
                                $this->record_inventory_movement( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION, $production_stock, $production_stock - $quantity, 'transfer_out', $transfer_note, $unit_cost, 0, 'store_cash' );
                                $this->record_inventory_movement( $item_id, $location_key, $branch_stock, $branch_stock + $quantity, 'transfer_in', $transfer_note, $unit_cost, 0, 'store_cash' );
                                $wpdb->query( 'COMMIT' );
                                $message = 'Branch stock added.';
                            }
                        }

                    } else {
                        $new_unit_cost = $unit_cost;
                        if ( 'stock_in' === $movement_type && Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION === $location_key && $posted_unit_cost > 0 && $old_stock > 0 && $new_stock > 0 ) {
                            $new_unit_cost = ( ( $old_stock * floatval( $item->unit_cost ) ) + ( $quantity * $posted_unit_cost ) ) / $new_stock;
                        }

                        if ( $new_stock < 0 ) {
                            $message      = 'Stock cannot go below zero.';
                            $message_type = 'error';
                        } else {
                            $wpdb->query( 'START TRANSACTION' );
                            $updated = $this->update_inventory_location_stock_if_current( $item_id, $location_key, $old_stock, $new_stock );

                            if ( in_array( $movement_type, array( 'stock_in', 'correction' ), true ) ) {
                                $wpdb->update(
                                    $items_table,
                                    array(
                                        'unit_cost'  => $new_unit_cost,
                                        'updated_at' => current_time( 'mysql' ),
                                    ),
                                    array( 'id' => $item_id ),
                                    array( '%f', '%s' ),
                                    array( '%d' )
                                );
                            }

                            if ( false === $updated || 0 === $updated ) {
                                $wpdb->query( 'ROLLBACK' );
                                $message      = 'Stock changed while saving. Please reload and try again.';
                                $message_type = 'error';
                            } else {
                                $this->record_inventory_movement( $item_id, $location_key, $old_stock, $new_stock, $movement_type, $note, $unit_cost, $affects_cash_balance, $cash_source );
                                $wpdb->query( 'COMMIT' );
                                $message = 'Inventory stock updated.';
                            }
                        }
                    }
                }
            }
        }

        $edit_item = null;
        if ( 'edit' === $action && isset( $_GET['id'] ) ) {
            $edit_item = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $items_table WHERE id = %d",
                    absint( $_GET['id'] )
                )
            );
        }

        $default_inventory_categories = array( 'Ingredients', 'Rice', 'Sauce', 'Drinks', 'Packaging', 'Supplies' );
        $saved_inventory_categories   = $wpdb->get_col( "SELECT DISTINCT category FROM $items_table WHERE category != '' ORDER BY category ASC" );
        $inventory_categories         = array();
        foreach ( array_merge( $saved_inventory_categories ? $saved_inventory_categories : array(), $default_inventory_categories ) as $category_name ) {
            $category_name = trim( sanitize_text_field( $category_name ) );
            if ( '' === $category_name ) {
                continue;
            }
            $category_key = strtolower( $category_name );
            if ( ! isset( $inventory_categories[ $category_key ] ) ) {
                $inventory_categories[ $category_key ] = $category_name;
            }
        }
        natcasesort( $inventory_categories );

        $inventory_items = $wpdb->get_results( "SELECT * FROM $items_table ORDER BY is_active DESC, item_name ASC" );
        $inventory_balances = $wpdb->get_results( "SELECT inventory_item_id, location_key, current_stock FROM $balances_table" );
        $inventory_balance_map = array();
        foreach ( $inventory_balances as $balance ) {
            $inventory_balance_map[ absint( $balance->inventory_item_id ) ][ $balance->location_key ] = floatval( $balance->current_stock );
        }
        $inventory_alerts = $repository->get_inventory_stock_alerts( 0, Banoks_POS_Repository::STOCK_LOCATION_MANUKAN );
        $stock_expenses = floatval(
            $wpdb->get_var(
                "SELECT SUM(total_cost)
                 FROM $movements_table
                 WHERE affects_cash_balance = 1
                 AND change_amount > 0"
            )
        );
        $stock_value = floatval(
            $wpdb->get_var(
                "SELECT SUM(b.current_stock * i.unit_cost)
                 FROM $balances_table b
                 INNER JOIN $items_table i ON b.inventory_item_id = i.id
                 WHERE i.is_active = 1"
            )
        );
        $item_movements = $wpdb->get_results(
            "SELECT m.*, i.item_name, i.unit
             FROM $movements_table m
             LEFT JOIN $items_table i ON m.inventory_item_id = i.id
             ORDER BY m.created_at DESC"
        );

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-stock-management-display.php';
    }

    /**
     * Record an ingredient/supply inventory movement.
     *
     * @since    1.0.11
     * @param    int    $item_id Inventory item ID.
     * @param    float  $old_stock Old stock.
     * @param    float  $new_stock New stock.
     * @param    string $movement_type Movement type.
     * @param    string $note Optional note.
     */
    private function get_inventory_location_stock( $item_id, $location_key ) {
        global $wpdb;

        $location_key = $this->sanitize_stock_location_key( $location_key );
        $stock = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT current_stock FROM {$wpdb->prefix}banoks_inventory_balances WHERE inventory_item_id = %d AND location_key = %s",
                absint( $item_id ),
                $location_key
            )
        );

        if ( null === $stock ) {
            $this->set_inventory_location_stock( $item_id, $location_key, 0 );
            return 0;
        }

        return floatval( $stock );
    }

    private function set_inventory_location_stock( $item_id, $location_key, $stock ) {
        global $wpdb;

        $location_key = $this->sanitize_stock_location_key( $location_key );
        $stock        = max( 0, floatval( $stock ) );
        $table        = $wpdb->prefix . 'banoks_inventory_balances';
        $exists       = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE inventory_item_id = %d AND location_key = %s",
                absint( $item_id ),
                $location_key
            )
        );

        if ( $exists ) {
            $result = $wpdb->update(
                $table,
                array( 'current_stock' => $stock, 'updated_at' => current_time( 'mysql' ) ),
                array( 'inventory_item_id' => absint( $item_id ), 'location_key' => $location_key ),
                array( '%f', '%s' ),
                array( '%d', '%s' )
            );
            if ( Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION === $location_key ) {
                $wpdb->update(
                    $wpdb->prefix . 'banoks_inventory_items',
                    array( 'current_stock' => $stock, 'updated_at' => current_time( 'mysql' ) ),
                    array( 'id' => absint( $item_id ) ),
                    array( '%f', '%s' ),
                    array( '%d' )
                );
            }
            return $result;
        }

        $result = $wpdb->insert(
            $table,
            array(
                'inventory_item_id' => absint( $item_id ),
                'location_key'      => $location_key,
                'current_stock'     => $stock,
                'updated_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%f', '%s' )
        );
        if ( Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION === $location_key ) {
            $wpdb->update(
                $wpdb->prefix . 'banoks_inventory_items',
                array( 'current_stock' => $stock, 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => absint( $item_id ) ),
                array( '%f', '%s' ),
                array( '%d' )
            );
        }
        return $result;
    }

    private function update_inventory_location_stock_if_current( $item_id, $location_key, $old_stock, $new_stock ) {
        global $wpdb;

        $this->ensure_inventory_location_stock_row( $item_id, $location_key );
        $result = $wpdb->update(
            $wpdb->prefix . 'banoks_inventory_balances',
            array(
                'current_stock' => max( 0, floatval( $new_stock ) ),
                'updated_at'    => current_time( 'mysql' ),
            ),
            array(
                'inventory_item_id' => absint( $item_id ),
                'location_key'      => $this->sanitize_stock_location_key( $location_key ),
                'current_stock'     => floatval( $old_stock ),
            ),
            array( '%f', '%s' ),
            array( '%d', '%s', '%f' )
        );
        if ( false !== $result && Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION === $this->sanitize_stock_location_key( $location_key ) ) {
            $wpdb->update(
                $wpdb->prefix . 'banoks_inventory_items',
                array( 'current_stock' => max( 0, floatval( $new_stock ) ), 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => absint( $item_id ) ),
                array( '%f', '%s' ),
                array( '%d' )
            );
        }
        return $result;
    }

    private function ensure_inventory_location_stock_row( $item_id, $location_key ) {
        global $wpdb;

        $location_key = $this->sanitize_stock_location_key( $location_key );
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}banoks_inventory_balances WHERE inventory_item_id = %d AND location_key = %s",
                absint( $item_id ),
                $location_key
            )
        );

        if ( $exists ) {
            return true;
        }

        return false !== $wpdb->insert(
            $wpdb->prefix . 'banoks_inventory_balances',
            array(
                'inventory_item_id' => absint( $item_id ),
                'location_key'      => $location_key,
                'current_stock'     => 0,
                'updated_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%f', '%s' )
        );
    }

    private function record_inventory_movement( $item_id, $location_key, $old_stock, $new_stock, $movement_type, $note = '', $unit_cost = 0, $affects_cash_balance = 0, $cash_source = 'store_cash' ) {
        global $wpdb;

        $location_key  = $this->sanitize_stock_location_key( $location_key );
        $movement_type = sanitize_key( $movement_type );
        $change_amount = floatval( $new_stock ) - floatval( $old_stock );
        $unit_cost     = max( 0, floatval( $unit_cost ) );
        $total_cost    = abs( $change_amount ) * $unit_cost;
        $affects_cash_balance = $affects_cash_balance && $change_amount > 0 && in_array( $movement_type, array( 'stock_in', 'correction' ), true ) ? 1 : 0;
        $cash_source = $this->sanitize_cash_source( $cash_source );

        if ( 'stock_in' === $movement_type ) {
            $item = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT item_name, unit FROM {$wpdb->prefix}banoks_inventory_items WHERE id = %d",
                    absint( $item_id )
                )
            );

            $item_name      = $item && ! empty( $item->item_name ) ? $item->item_name : 'Deleted item';
            $unit           = $item && ! empty( $item->unit ) ? $item->unit : '';
            $generated_note = sprintf( 'Stock in: %s - %s.', $item_name, $this->format_stock_quantity( abs( $change_amount ), $unit ) );
            $note           = '' !== trim( (string) $note ) ? $generated_note . ' Note: ' . $note : $generated_note;
        }

        $wpdb->insert(
            $wpdb->prefix . 'banoks_inventory_movements',
            array(
                'inventory_item_id' => absint( $item_id ),
                'location_key'      => $location_key,
                'movement_type'     => $movement_type,
                'old_stock'         => floatval( $old_stock ),
                'new_stock'         => floatval( $new_stock ),
                'change_amount'     => $change_amount,
                'unit_cost'         => $unit_cost,
                'total_cost'        => $total_cost,
                'affects_cash_balance' => $affects_cash_balance,
                'cash_source'       => $cash_source,
                'source'            => 'manual',
                'source_id'         => '',
                'updated_by'        => get_current_user_id(),
                'note'              => sanitize_textarea_field( $note ),
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    private function format_stock_quantity( $quantity, $unit = '' ) {
        $quantity = rtrim( rtrim( number_format( floatval( $quantity ), 3, '.', '' ), '0' ), '.' );
        $unit     = trim( (string) $unit );

        return '' !== $unit ? $quantity . ' ' . $unit : $quantity;
    }

    /**
     * Get stock purchases that should reduce cash balance.
     *
     * @since    1.0.12
     * @param    string $start_date Start date in Y-m-d format.
     * @param    string $end_date End date in Y-m-d format.
     * @return   float
     */
    private function get_stock_cash_expenses_for_period( $start_date, $end_date, $cash_source = '' ) {
        global $wpdb;

        $where = "affects_cash_balance = 1
                 AND change_amount > 0
                 AND movement_type = 'stock_in'
                 AND location_key = 'production'
                 AND DATE(created_at) BETWEEN %s AND %s";
        $args  = array( $start_date, $end_date );

        if ( '' !== $cash_source ) {
            $where .= ' AND cash_source = %s';
            $args[] = $this->sanitize_cash_source( $cash_source );
        }

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_cost)
                 FROM {$wpdb->prefix}banoks_inventory_movements
                 WHERE $where",
                $args
            )
        );

        return $total ? floatval( $total ) : 0;
    }

    /**
     * Get cash-affecting stock purchases as report expense rows.
     *
     * @since    1.0.12
     * @param    string $start_date Start date in Y-m-d format.
     * @param    string $end_date End date in Y-m-d format.
     * @return   array
     */
    private function get_stock_cash_expense_rows_for_period( $start_date, $end_date, $cash_source = '' ) {
        global $wpdb;

        $where = "m.affects_cash_balance = 1
                 AND m.change_amount > 0
                 AND m.movement_type = 'stock_in'
                 AND m.location_key = 'production'
                 AND DATE(m.created_at) BETWEEN %s AND %s";
        $args  = array( $start_date, $end_date );

        if ( '' !== $cash_source ) {
            $where .= ' AND m.cash_source = %s';
            $args[] = $this->sanitize_cash_source( $cash_source );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT CONCAT('Stock purchase: ', COALESCE(i.item_name, 'Deleted item')) AS description,
                        m.total_cost AS amount,
                        m.cash_source,
                        DATE(m.created_at) AS date,
                        m.created_at
                 FROM {$wpdb->prefix}banoks_inventory_movements m
                 LEFT JOIN {$wpdb->prefix}banoks_inventory_items i ON m.inventory_item_id = i.id
                 WHERE $where
                 ORDER BY m.created_at ASC",
                $args
            )
        );
    }

    /**
     * Render and manage delivery areas.
     *
     * @since    1.0.10
     */
    public function display_delivery_areas_page() {
        global $wpdb;

        $this->display_admin_header();
        $this->maybe_update_products_schema();

        $repository = new Banoks_POS_Repository();
        $message    = '';
        $error      = '';
        $table_name = $wpdb->prefix . 'banoks_delivery_areas';

        if ( isset( $_POST['banoks_pos_save_delivery_area'] ) ) {
            check_admin_referer( 'banoks_pos_delivery_area_action' );

            $area_id = isset( $_POST['area_id'] ) ? absint( $_POST['area_id'] ) : 0;
            $data = array(
                'area_name'      => isset( $_POST['area_name'] ) ? sanitize_text_field( wp_unslash( $_POST['area_name'] ) ) : '',
                'delivery_fee'   => isset( $_POST['delivery_fee'] ) ? floatval( wp_unslash( $_POST['delivery_fee'] ) ) : 0,
                'sort_order'     => isset( $_POST['sort_order'] ) ? intval( wp_unslash( $_POST['sort_order'] ) ) : 0,
                'is_deliverable' => isset( $_POST['is_deliverable'] ) ? 1 : 0,
                'updated_at'     => current_time( 'mysql' ),
            );

            if ( '' === $data['area_name'] ) {
                $error = 'Please enter a delivery area name.';
            } elseif ( $area_id ) {
                $updated = $wpdb->update(
                    $table_name,
                    $data,
                    array( 'id' => $area_id ),
                    array( '%s', '%f', '%d', '%d', '%s' ),
                    array( '%d' )
                );
                $message = false === $updated ? '' : 'Delivery area updated successfully.';
                $error   = false === $updated ? 'Could not update delivery area.' : $error;
            } else {
                $created = $repository->create_delivery_area( $data );
                $message = $created ? 'Delivery area added successfully.' : '';
                $error   = $created ? $error : 'Could not add delivery area.';
            }
        }

        $edit_area = null;
        if ( isset( $_GET['action'], $_GET['area_id'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
            $edit_area = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id = %d",
                    absint( $_GET['area_id'] )
                )
            );
        }

        $delivery_areas = $repository->get_delivery_areas();

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-delivery-areas-display.php';
    }

    /**
     * Render the Expenses page and handle expense creation.
     *
     * @since    1.0.0
     */
    public function display_expenses_page() {
        global $wpdb;

        $this->display_admin_header();
        $this->maybe_update_products_schema();

        $table_name = $wpdb->prefix . 'banoks_expenses';
        $requests_table = $wpdb->prefix . 'banoks_requests';
        $is_owner = current_user_can( 'manage_options' );
        $message = '';
        $error = '';
        $expense_form_date = current_time( 'Y-m-d' );
        $cash_source_options = $this->get_cash_source_options();
        $unit_options = $this->get_stock_unit_options();
        $inventory_items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_inventory_items WHERE is_active = 1 ORDER BY item_name ASC" );
        $expense_action = isset( $_GET['expense_action'] ) ? sanitize_key( wp_unslash( $_GET['expense_action'] ) ) : '';

        if ( $is_owner && isset( $_POST['banoks_owner_request_action'] ) ) {
            check_admin_referer( 'banoks_owner_request_action' );
            $result = $this->handle_owner_request_decision();
            if ( isset( $result['error'] ) ) {
                $error = $result['error'];
            } else {
                $message = $result['message'];
            }
        }

        if ( 'delete' === $expense_action && isset( $_GET['expense_id'] ) && $is_owner ) {
            $expense_id = absint( $_GET['expense_id'] );
            check_admin_referer( 'delete_expense_' . $expense_id );

            $deleted = $wpdb->delete( $table_name, array( 'expense_id' => $expense_id ), array( '%d' ) );

            if ( false === $deleted ) {
                $error = 'Error: Could not delete expense. ' . $wpdb->last_error;
            } else {
                $message = 'Expense deleted successfully.';
            }
        }

        if ( ! $is_owner && isset( $_POST['banoks_pos_save_expense'] ) ) {
            check_admin_referer( 'banoks_pos_expense_action' );

            $description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
            $amount      = isset( $_POST['amount'] ) ? floatval( wp_unslash( $_POST['amount'] ) ) : 0;
            $date        = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
            $date        = '' !== $date ? $date : current_time( 'Y-m-d' );
            $cash_source = isset( $_POST['cash_source'] ) ? $this->sanitize_cash_source( wp_unslash( $_POST['cash_source'] ) ) : 'store_cash';
            $request_type = isset( $_POST['request_type'] ) ? sanitize_key( wp_unslash( $_POST['request_type'] ) ) : 'expense_request';
            $inventory_item_id = isset( $_POST['inventory_item_id'] ) ? absint( $_POST['inventory_item_id'] ) : 0;
            $quantity = isset( $_POST['quantity'] ) ? max( 0, floatval( wp_unslash( $_POST['quantity'] ) ) ) : 0;
            $unit = '';
            $expense_form_date = $date;
            $is_stock_request = in_array( $request_type, array( 'stock_purchase_request', 'production_transfer_request' ), true );
            if ( 'production_transfer_request' === $request_type ) {
                $amount      = 0;
                $cash_source = 'store_cash';
            }

            if ( '' === $description ) {
                $error = 'Please enter a request description.';
            } elseif ( ! in_array( $request_type, array( 'expense_request', 'stock_purchase_request', 'production_transfer_request' ), true ) ) {
                $error = 'Please choose a valid request type.';
            } elseif ( $is_stock_request && ( ! $inventory_item_id || $quantity <= 0 ) ) {
                $error = 'Please choose an inventory item and quantity.';
            } elseif ( in_array( $request_type, array( 'expense_request', 'stock_purchase_request' ), true ) && $amount <= 0 ) {
                $error = 'Please enter a valid estimated amount.';
            } elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $error = 'Please select a valid request date.';
            } else {
                if ( $is_stock_request ) {
                    $unit = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT unit FROM {$wpdb->prefix}banoks_inventory_items WHERE id = %d AND is_active = 1",
                            $inventory_item_id
                        )
                    );
                    $unit = null === $unit ? '' : sanitize_key( $unit );

                    if ( null === $unit || '' === $unit ) {
                        $error = 'Please choose an active inventory item.';
                    }
                }

                if ( ! empty( $error ) ) {
                    $inserted = false;
                } else {
                    $inserted = $wpdb->insert(
                        $requests_table,
                        array(
                            'request_type'      => $request_type,
                            'request_status'    => 'pending',
                            'branch_key'        => 'manukan_branch',
                            'inventory_item_id' => $inventory_item_id,
                            'quantity'          => $quantity,
                            'unit'              => $unit,
                            'estimated_cost'    => $amount,
                            'expense_date'      => $date,
                            'description'       => $description,
                            'cash_source'       => $cash_source,
                            'note'              => isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '',
                            'requested_by'      => get_current_user_id(),
                            'created_at'        => current_time( 'mysql' ),
                            'updated_at'        => current_time( 'mysql' ),
                        ),
                        array( '%s', '%s', '%s', '%d', '%f', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
                    );
                }

                if ( false === $inserted ) {
                    if ( empty( $error ) ) {
                        $error = 'Error: Could not submit request. ' . $wpdb->last_error;
                    }
                } else {
                    $this->create_request_log( $wpdb->insert_id, '', 'pending', 'Request submitted.' );
                    $message = 'Request submitted for owner approval.';
                }
            }
        }

        $pending_requests = $is_owner ? $this->get_requests_for_owner( 'pending' ) : array();
        $recent_requests  = $is_owner ? $this->get_requests_for_owner( 'all' ) : array();
        $expense_filter_date = $this->get_request_date( 'expense_date', '' );
        $expenses = array();

        if ( $is_owner ) {
            if ( ! empty( $expense_filter_date ) ) {
                $expenses = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE date = %s ORDER BY created_at DESC LIMIT 100",
                        $expense_filter_date
                    )
                );
            } else {
                $expenses = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY date DESC, created_at DESC LIMIT 100" );
            }
        }

        $my_requests = array();
        if ( ! $is_owner ) {
            $my_requests = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT r.*, i.item_name
                     FROM $requests_table r
                     LEFT JOIN {$wpdb->prefix}banoks_inventory_items i ON r.inventory_item_id = i.id
                     WHERE r.requested_by = %d
                     ORDER BY r.created_at DESC
                     LIMIT 100",
                    get_current_user_id()
                )
            );
        }

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-expenses-display.php';
    }

    private function get_requests_for_owner( $status = 'pending' ) {
        global $wpdb;

        $where = '';
        $args = array();
        if ( 'all' !== $status ) {
            $where = 'WHERE r.request_status = %s';
            $args[] = sanitize_key( $status );
        }

        $sql = "SELECT r.*, i.item_name, u.display_name AS requester_name
                FROM {$wpdb->prefix}banoks_requests r
                LEFT JOIN {$wpdb->prefix}banoks_inventory_items i ON r.inventory_item_id = i.id
                LEFT JOIN {$wpdb->users} u ON r.requested_by = u.ID
                $where
                ORDER BY r.created_at DESC
                LIMIT 100";

        return $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql );
    }

    private function get_owner_request_count( $status = 'pending' ) {
        global $wpdb;

        $status = sanitize_key( $status );
        if ( '' === $status || 'all' === $status ) {
            return absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}banoks_requests" ) );
        }

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}banoks_requests WHERE request_status = %s",
                    $status
                )
            )
        );
    }

    private function create_request_log( $request_id, $old_status, $new_status, $note = '' ) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'banoks_request_logs',
            array(
                'request_id' => absint( $request_id ),
                'old_status' => sanitize_key( $old_status ),
                'new_status' => sanitize_key( $new_status ),
                'updated_by' => get_current_user_id(),
                'note'       => sanitize_textarea_field( $note ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    private function handle_owner_request_decision() {
        global $wpdb;

        if ( ! current_user_can( 'manage_options' ) ) {
            return array( 'error' => 'Only owner/admin can approve requests.' );
        }

        $request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
        $decision   = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
        $note       = isset( $_POST['decision_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['decision_note'] ) ) : '';

        if ( ! $request_id || ! in_array( $decision, array( 'approved', 'rejected' ), true ) ) {
            return array( 'error' => 'Invalid request decision.' );
        }

        if ( 'rejected' === $decision && '' === $note ) {
            return array( 'error' => 'Please enter a rejection reason.' );
        }

        $request = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_requests WHERE id = %d",
                $request_id
            )
        );

        if ( ! $request || 'pending' !== $request->request_status ) {
            return array( 'error' => 'Request is no longer pending.' );
        }

        $wpdb->query( 'START TRANSACTION' );

        if ( 'approved' === $decision ) {
            if ( 'production_transfer_request' === $request->request_type ) {
                $transfer = $this->approve_production_transfer_request( $request );
                if ( isset( $transfer['error'] ) ) {
                    $wpdb->query( 'ROLLBACK' );
                    return $transfer;
                }
            } elseif ( 'stock_purchase_request' === $request->request_type ) {
                $purchase = $this->approve_stock_purchase_request( $request );
                if ( isset( $purchase['error'] ) ) {
                    $wpdb->query( 'ROLLBACK' );
                    return $purchase;
                }
            } elseif ( 'expense_request' === $request->request_type ) {
                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'banoks_expenses',
                    array(
                        'description' => $request->description,
                        'amount'      => floatval( $request->estimated_cost ),
                        'date'        => $request->expense_date ? $request->expense_date : current_time( 'Y-m-d' ),
                        'branch_key'  => ! empty( $request->branch_key ) ? sanitize_key( $request->branch_key ) : 'manukan_branch',
                        'cash_source' => $this->sanitize_cash_source( $request->cash_source ),
                    ),
                    array( '%s', '%f', '%s', '%s', '%s' )
                );

                if ( false === $inserted ) {
                    $wpdb->query( 'ROLLBACK' );
                    return array( 'error' => 'Could not create approved expense.' );
                }
            }
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'banoks_requests',
            array(
                'request_status' => $decision,
                'decision_note'  => $note,
                'decided_by'     => get_current_user_id(),
                'decided_at'     => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ),
            array( 'id' => $request_id, 'request_status' => 'pending' ),
            array( '%s', '%s', '%d', '%s', '%s' ),
            array( '%d', '%s' )
        );

        if ( false === $updated || 0 === $updated ) {
            $wpdb->query( 'ROLLBACK' );
            return array( 'error' => 'Could not update request.' );
        }

        $this->create_request_log( $request_id, 'pending', $decision, $note );
        $wpdb->query( 'COMMIT' );

        return array( 'message' => 'Request ' . $decision . ' successfully.' );
    }

    private function approve_stock_purchase_request( $request ) {
        global $wpdb;

        $item_id        = absint( $request->inventory_item_id );
        $qty            = floatval( $request->quantity );
        $purchase_total = floatval( $request->estimated_cost );

        if ( ! $item_id || $qty <= 0 || $purchase_total <= 0 ) {
            return array( 'error' => 'Stock purchase request has invalid item, quantity, or cost.' );
        }

        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}banoks_inventory_items WHERE id = %d", $item_id ) );
        if ( ! $item ) {
            return array( 'error' => 'Inventory item not found.' );
        }

        $old_stock          = $this->get_inventory_location_stock( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION );
        $new_stock          = $old_stock + $qty;
        $purchase_unit_cost = $purchase_total / $qty;
        $old_unit_cost      = max( 0, floatval( $item->unit_cost ) );
        $new_unit_cost      = $purchase_unit_cost;

        if ( $old_stock > 0 && $new_stock > 0 ) {
            $new_unit_cost = ( ( $old_stock * $old_unit_cost ) + $purchase_total ) / $new_stock;
        }

        $updated_stock = $this->update_inventory_location_stock_if_current( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION, $old_stock, $new_stock );
        if ( false === $updated_stock || 0 === $updated_stock ) {
            return array( 'error' => 'Production stock changed while approving purchase. Please try again.' );
        }

        $updated_item = $wpdb->update(
            $wpdb->prefix . 'banoks_inventory_items',
            array(
                'unit_cost'  => $new_unit_cost,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $item_id ),
            array( '%f', '%s' ),
            array( '%d' )
        );

        if ( false === $updated_item ) {
            return array( 'error' => 'Could not update inventory unit cost.' );
        }

        $request_ref = 'REQ-' . absint( $request->id );
        $note_parts  = array( 'Approved stock purchase request ' . $request_ref . '.' );
        if ( ! empty( $request->description ) ) {
            $note_parts[] = $request->description;
        }

        $this->record_inventory_movement(
            $item_id,
            Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION,
            $old_stock,
            $new_stock,
            'stock_in',
            implode( ' ', $note_parts ),
            $purchase_unit_cost,
            1,
            $this->sanitize_cash_source( $request->cash_source )
        );

        return array( 'success' => true );
    }

    private function approve_production_transfer_request( $request ) {
        global $wpdb;

        $item_id = absint( $request->inventory_item_id );
        $qty     = floatval( $request->quantity );
        if ( ! $item_id || $qty <= 0 ) {
            return array( 'error' => 'Transfer request has invalid item or quantity.' );
        }

        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}banoks_inventory_items WHERE id = %d", $item_id ) );
        if ( ! $item ) {
            return array( 'error' => 'Inventory item not found.' );
        }

        $production_stock = $this->get_inventory_location_stock( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION );
        $branch_stock     = $this->get_inventory_location_stock( $item_id, Banoks_POS_Repository::STOCK_LOCATION_MANUKAN );

        if ( $production_stock < $qty ) {
            return array( 'error' => $item->item_name . ' has insufficient Production stock.' );
        }

        $production_new = $production_stock - $qty;
        $branch_new     = $branch_stock + $qty;

        $updated_production = $this->update_inventory_location_stock_if_current( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION, $production_stock, $production_new );
        $updated_branch     = $this->update_inventory_location_stock_if_current( $item_id, Banoks_POS_Repository::STOCK_LOCATION_MANUKAN, $branch_stock, $branch_new );

        if ( false === $updated_production || 0 === $updated_production || false === $updated_branch || 0 === $updated_branch ) {
            return array( 'error' => 'Stock changed while approving transfer. Please try again.' );
        }

        $source_id = 'REQ-' . absint( $request->id );
        $this->record_inventory_movement( $item_id, Banoks_POS_Repository::STOCK_LOCATION_PRODUCTION, $production_stock, $production_new, 'transfer_out', 'Production transfer to Manukan Branch.', floatval( $item->unit_cost ), 0, 'store_cash' );
        $this->record_inventory_movement( $item_id, Banoks_POS_Repository::STOCK_LOCATION_MANUKAN, $branch_stock, $branch_new, 'transfer_in', 'Approved transfer from Production. ' . $source_id, floatval( $item->unit_cost ), 0, 'store_cash' );

        return array( 'success' => true );
    }

    /**
     * Render admin-only finance management.
     *
     * @since    1.0.13
     */
    public function display_cash_management_page() {
        global $wpdb;

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access Finance.', 'banoks-pos' ) );
        }

        $this->display_admin_header();

        $message = '';
        $error   = '';
        $cash_date = current_time( 'Y-m-d' );
        $finance_table = $wpdb->prefix . 'banoks_finance_transactions';
        $expenses_table = $wpdb->prefix . 'banoks_expenses';

        if ( isset( $_POST['banoks_finance_claim_store_balance'] ) ) {
            check_admin_referer( 'banoks_finance_claim_store_balance' );

            $claim_date = isset( $_POST['claim_date'] ) ? sanitize_text_field( wp_unslash( $_POST['claim_date'] ) ) : $cash_date;
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $claim_date ) ) {
                $claim_date = $cash_date;
            }

            $destination = isset( $_POST['destination_account'] ) ? $this->sanitize_cash_source( wp_unslash( $_POST['destination_account'] ) ) : '';
            $claim_amount = isset( $_POST['claim_amount'] ) ? round( max( 0, floatval( wp_unslash( $_POST['claim_amount'] ) ) ), 2 ) : 0;

            $claim_branch = isset( $_POST['claim_branch_key'] ) ? sanitize_key( wp_unslash( $_POST['claim_branch_key'] ) ) : 'manukan_branch';
            $claim_type = isset( $_POST['claim_type'] ) ? sanitize_key( wp_unslash( $_POST['claim_type'] ) ) : 'cash_sales_claim';
            if ( ! in_array( $claim_type, array( 'cash_sales_claim', 'gcash_sales_claim' ), true ) ) {
                $claim_type = 'cash_sales_claim';
            }
            $claimable_breakdown = $this->get_branch_claimable_breakdown_for_date( $claim_date, $claim_branch );
            $claim_available = 'gcash_sales_claim' === $claim_type ? $claimable_breakdown['gcash_available'] : $claimable_breakdown['cash_available'];

            if ( ! in_array( $destination, array( 'cash_on_hand', 'gcash_balance', 'bank_balance' ), true ) ) {
                $error = 'Please choose Cash on Hand, GCash Balance, or Bank Balance.';
            } elseif ( 'gcash_sales_claim' === $claim_type && 'cash_on_hand' === $destination ) {
                $error = 'GCash sales cannot be claimed as Cash on Hand. Claim them to GCash Balance or Bank Balance.';
            } elseif ( $claim_amount <= 0 ) {
                $error = 'Please enter a valid amount to claim.';
            } elseif ( $claim_amount > $claim_available ) {
                $error = 'Claim amount cannot be greater than the available sales amount.';
            } else {
                $inserted = $wpdb->insert(
                    $finance_table,
                    array(
                        'transaction_type'    => $claim_type,
                        'source_account'      => 'gcash_sales_claim' === $claim_type ? 'gcash_sales' : 'store_cash',
                        'destination_account' => $destination,
                        'branch_key'          => $claim_branch,
                        'amount'              => $claim_amount,
                        'transaction_date'    => $claim_date,
                        'note'                => isset( $_POST['claim_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['claim_note'] ) ) : '',
                        'created_by'          => get_current_user_id(),
                        'created_at'          => current_time( 'mysql' ),
                    ),
                    array( '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s' )
                );

                if ( false === $inserted ) {
                    $error = 'Could not record owner claim.';
                } else {
                    $message = 'Branch sales claimed successfully.';
                    $cash_date = $claim_date;
                }
            }
        }

        if ( isset( $_POST['banoks_finance_add_balance'] ) ) {
            check_admin_referer( 'banoks_finance_add_balance' );

            $balance_date = isset( $_POST['balance_date'] ) ? sanitize_text_field( wp_unslash( $_POST['balance_date'] ) ) : $cash_date;
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $balance_date ) ) {
                $balance_date = $cash_date;
            }

            $destination_account = isset( $_POST['balance_destination_account'] ) ? $this->sanitize_cash_source( wp_unslash( $_POST['balance_destination_account'] ) ) : '';
            $balance_amount      = isset( $_POST['balance_amount'] ) ? round( max( 0, floatval( wp_unslash( $_POST['balance_amount'] ) ) ), 2 ) : 0;

            if ( ! in_array( $destination_account, array( 'cash_on_hand', 'gcash_balance', 'bank_balance' ), true ) ) {
                $error = 'Please choose where to add the owner balance.';
            } elseif ( $balance_amount <= 0 ) {
                $error = 'Please enter a valid balance amount.';
            } else {
                $inserted = $wpdb->insert(
                    $finance_table,
                    array(
                        'transaction_type'    => 'owner_capital_addition',
                        'source_account'      => 'owner_capital',
                        'destination_account' => $destination_account,
                        'branch_key'          => '',
                        'amount'              => $balance_amount,
                        'transaction_date'    => $balance_date,
                        'note'                => isset( $_POST['balance_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['balance_note'] ) ) : '',
                        'created_by'          => get_current_user_id(),
                        'created_at'          => current_time( 'mysql' ),
                    ),
                    array( '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%s' )
                );

                if ( false === $inserted ) {
                    $error = 'Could not add owner balance.';
                } else {
                    $message = 'Owner balance added successfully.';
                    $cash_date = $balance_date;
                }
            }
        }

        if ( isset( $_POST['banoks_owner_pay_bill'] ) ) {
            check_admin_referer( 'banoks_owner_pay_bill_action' );

            $bill_description = isset( $_POST['bill_description'] ) ? sanitize_text_field( wp_unslash( $_POST['bill_description'] ) ) : '';
            $bill_category    = isset( $_POST['bill_category'] ) ? sanitize_text_field( wp_unslash( $_POST['bill_category'] ) ) : 'Other';
            $bill_amount      = isset( $_POST['bill_amount'] ) ? round( max( 0, floatval( wp_unslash( $_POST['bill_amount'] ) ) ), 2 ) : 0;
            $bill_date        = isset( $_POST['bill_date'] ) ? sanitize_text_field( wp_unslash( $_POST['bill_date'] ) ) : $cash_date;
            $bill_cash_source = isset( $_POST['bill_cash_source'] ) ? $this->sanitize_cash_source( wp_unslash( $_POST['bill_cash_source'] ) ) : '';
            $bill_note        = isset( $_POST['bill_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bill_note'] ) ) : '';
            $bill_categories  = array( 'Rent', 'Utilities', 'Supplier', 'Maintenance', 'Salary', 'Other' );

            if ( '' === $bill_description ) {
                $error = 'Please enter a bill description.';
            } elseif ( $bill_amount <= 0 ) {
                $error = 'Please enter a valid bill amount.';
            } elseif ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $bill_date ) ) {
                $error = 'Please select a valid bill date.';
            } elseif ( ! in_array( $bill_cash_source, array( 'cash_on_hand', 'gcash_balance', 'bank_balance' ), true ) ) {
                $error = 'Please choose a valid payment account.';
            } else {
                if ( ! in_array( $bill_category, $bill_categories, true ) ) {
                    $bill_category = 'Other';
                }

                $expense_description = '[' . $bill_category . '] ' . $bill_description;
                if ( '' !== $bill_note ) {
                    $expense_description .= ' - ' . $bill_note;
                }

                $inserted = $wpdb->insert(
                    $expenses_table,
                    array(
                        'description' => $expense_description,
                        'amount'      => $bill_amount,
                        'date'        => $bill_date,
                        'branch_key'  => '',
                        'cash_source' => $bill_cash_source,
                    ),
                    array( '%s', '%f', '%s', '%s', '%s' )
                );

                if ( false === $inserted ) {
                    $error = 'Could not record paid bill. ' . $wpdb->last_error;
                } else {
                    $message = 'Bill paid and recorded successfully.';
                    $cash_date = $bill_date;
                }
            }
        }

        $cash_on_hand_balance = $this->get_finance_account_balance( 'cash_on_hand' );
        $gcash_balance = $this->get_finance_account_balance( 'gcash_balance' );
        $bank_balance  = $this->get_finance_account_balance( 'bank_balance' );
        $banoks_total_balance = $cash_on_hand_balance + $gcash_balance + $bank_balance;
        $finance_account_options = array(
            'cash_on_hand'  => 'Cash on Hand',
            'gcash_balance' => 'GCash Balance',
            'bank_balance'  => 'Bank Balance',
        );
        $overall_balance_transactions = $this->get_overall_balance_transactions();
        $branch_finance_groups = array();
        $branch_finance_rows = array();
        foreach ( $this->get_active_branches() as $branch ) {
            $branch_key = sanitize_key( $branch->branch_key );
            $daily_rows = $this->get_branch_daily_unclaimed_rows( $branch_key, $branch->branch_name );
            $branch_finance_groups[] = array(
                'branch_key'     => $branch_key,
                'branch_name'    => $branch->branch_name,
                'total_sales'    => $this->get_branch_total_sales( $branch_key ),
                'total_expenses' => $this->get_branch_total_expenses( $branch_key ),
                'rows'           => $daily_rows,
            );
            $branch_finance_rows = array_merge( $branch_finance_rows, $daily_rows );
        }

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-cash-management-display.php';
    }

    private function get_active_branches() {
        global $wpdb;

        $branches = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_branches WHERE is_active = 1 ORDER BY branch_name ASC" );
        if ( ! empty( $branches ) ) {
            return $branches;
        }

        return array(
            (object) array(
                'branch_key'  => 'manukan_branch',
                'branch_name' => 'Manukan Branch',
            ),
        );
    }

    private function get_branch_total_sales( $branch_key = 'manukan_branch' ) {
        $branch_key = sanitize_key( $branch_key );
        return $this->get_branch_sales_for_period( '1970-01-01', '9999-12-31', $branch_key );
    }

    private function get_branch_total_expenses( $branch_key = 'manukan_branch' ) {
        $branch_key = sanitize_key( $branch_key );
        return $this->get_branch_store_expenses_for_period( '1970-01-01', '9999-12-31', $branch_key );
    }

    private function get_branch_sales_for_date( $date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_sales_for_period( $date, $date, $branch_key );
    }

    private function get_branch_sales_for_period( $start_date, $end_date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_cash_sales_for_period( $start_date, $end_date, $branch_key ) + $this->get_branch_gcash_sales_for_period( $start_date, $end_date, $branch_key );
    }

    private function get_branch_cash_sales_for_date( $date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_cash_sales_for_period( $date, $date, $branch_key );
    }

    private function get_branch_cash_sales_for_period( $start_date, $end_date, $branch_key = 'manukan_branch' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $walkin_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );
        $online_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );

        $walkin_sales = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(grand_total)
                 FROM {$wpdb->prefix}banoks_orders
                 WHERE date BETWEEN %s AND %s
                 AND status = 'completed'
                 AND $walkin_branch_where
                 AND (received_account = 'store_cash' OR received_account IS NULL OR received_account = '')",
                $start_date,
                $end_date,
                $branch_key
            )
        ) ?: 0;
        $online_sales = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_amount)
                 FROM {$wpdb->prefix}banoks_online_orders
                 WHERE DATE(created_at) BETWEEN %s AND %s
                 AND order_status = 'completed'
                 AND $online_branch_where
                 AND LOWER(payment_method) IN ('cod', 'pay_at_pickup', 'cash')",
                $start_date,
                $end_date,
                $branch_key
            )
        ) ?: 0;

        return floatval( $walkin_sales ) + floatval( $online_sales );
    }

    private function get_branch_gcash_sales_for_date( $date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_gcash_sales_for_period( $date, $date, $branch_key );
    }

    private function get_branch_gcash_sales_for_period( $start_date, $end_date, $branch_key = 'manukan_branch' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $walkin_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );
        $online_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );

        $walkin_sales = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(grand_total)
                 FROM {$wpdb->prefix}banoks_orders
                 WHERE date BETWEEN %s AND %s
                 AND status = 'completed'
                 AND $walkin_branch_where
                 AND received_account = 'gcash_balance'",
                $start_date,
                $end_date,
                $branch_key
            )
        ) ?: 0;
        $online_sales = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_amount)
                 FROM {$wpdb->prefix}banoks_online_orders
                 WHERE DATE(created_at) BETWEEN %s AND %s
                 AND order_status = 'completed'
                 AND $online_branch_where
                 AND LOWER(payment_method) = 'gcash'",
                $start_date,
                $end_date,
                $branch_key
            )
        ) ?: 0;

        return floatval( $walkin_sales ) + floatval( $online_sales );
    }

    private function get_branch_store_expenses_for_date( $date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_store_expenses_for_period( $date, $date, $branch_key );
    }

    private function get_branch_store_expenses_for_period( $start_date, $end_date, $branch_key = 'manukan_branch' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $expense_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );

        $store_expenses = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount)
                 FROM {$wpdb->prefix}banoks_expenses
                 WHERE date BETWEEN %s AND %s
                 AND cash_source = 'store_cash'
                 AND $expense_branch_where",
                $start_date,
                $end_date,
                $branch_key
            )
        ) ?: 0;
        $store_stock_purchases = 'manukan_branch' === $branch_key ? $this->get_stock_cash_expenses_for_period( $start_date, $end_date, 'store_cash' ) : 0;

        return floatval( $store_expenses ) + floatval( $store_stock_purchases );
    }

    private function get_branch_store_balance_for_date( $date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_claimable_breakdown_for_date( $date, $branch_key )['cash_available'];
    }

    private function get_branch_gcash_claimable_for_date( $date, $branch_key = 'manukan_branch' ) {
        return $this->get_branch_claimable_breakdown_for_date( $date, $branch_key )['gcash_available'];
    }

    private function get_branch_claimable_breakdown_for_date( $date, $branch_key = 'manukan_branch' ) {
        $branch_key = sanitize_key( $branch_key );

        $cash_sales = $this->get_branch_cash_sales_for_date( $date, $branch_key );
        $gcash_sales = $this->get_branch_gcash_sales_for_date( $date, $branch_key );
        $daily_expenses = $this->get_branch_store_expenses_for_date( $date, $branch_key );
        $cash_claimed = $this->get_finance_claimed_sales_for_date( $date, $branch_key, 'cash_sales_claim' );
        $gcash_claimed = $this->get_finance_claimed_sales_for_date( $date, $branch_key, 'gcash_sales_claim' );

        $cash_expense_share = min( $cash_sales, $daily_expenses );
        $remaining_expenses = max( 0, $daily_expenses - $cash_expense_share );
        $gcash_expense_share = min( $gcash_sales, $remaining_expenses );

        $cash_available = max( 0, $cash_sales - $cash_expense_share - $cash_claimed );
        $gcash_available = max( 0, $gcash_sales - $gcash_expense_share - $gcash_claimed );

        return array(
            'cash_sales'          => $cash_sales,
            'gcash_sales'         => $gcash_sales,
            'daily_expenses'      => $daily_expenses,
            'cash_expense_share'  => $cash_expense_share,
            'gcash_expense_share' => $gcash_expense_share,
            'cash_claimed'        => $cash_claimed,
            'gcash_claimed'       => $gcash_claimed,
            'cash_available'      => $cash_available,
            'gcash_available'     => $gcash_available,
            'total_available'     => $cash_available + $gcash_available,
            'daily_sales'         => $cash_sales + $gcash_sales,
            'daily_final'         => max( 0, ( $cash_sales + $gcash_sales ) - $daily_expenses - $cash_claimed - $gcash_claimed ),
        );
    }

    private function get_branch_cash_unclaimed_balance( $branch_key = 'manukan_branch' ) {
        $branch_key = sanitize_key( $branch_key );
        $cash_sales = $this->get_branch_cash_sales_for_period( '1970-01-01', '9999-12-31', $branch_key );
        $claimed = $this->get_finance_claimed_sales_for_period( '1970-01-01', '9999-12-31', $branch_key, 'cash_sales_claim' );

        return max( 0, $cash_sales - $claimed );
    }

    private function get_branch_gcash_unclaimed_balance( $branch_key = 'manukan_branch' ) {
        $branch_key = sanitize_key( $branch_key );
        $gcash_sales = $this->get_branch_gcash_sales_for_period( '1970-01-01', '9999-12-31', $branch_key );
        $claimed = $this->get_finance_claimed_sales_for_period( '1970-01-01', '9999-12-31', $branch_key, 'gcash_sales_claim' );

        return max( 0, $gcash_sales - $claimed );
    }

    private function get_branch_daily_unclaimed_rows( $branch_key, $branch_name ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $walkin_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );
        $online_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );
        $expense_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );
        $finance_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );
        $dates = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT sale_date FROM (
                    SELECT date AS sale_date FROM {$wpdb->prefix}banoks_orders WHERE status = 'completed' AND $walkin_branch_where
                    UNION
                    SELECT DATE(created_at) AS sale_date FROM {$wpdb->prefix}banoks_online_orders WHERE order_status = 'completed' AND $online_branch_where
                    UNION
                    SELECT date AS sale_date FROM {$wpdb->prefix}banoks_expenses WHERE cash_source = 'store_cash' AND $expense_branch_where
                    UNION
                    SELECT transaction_date AS sale_date FROM {$wpdb->prefix}banoks_finance_transactions WHERE transaction_type IN ('cash_sales_claim', 'gcash_sales_claim') AND $finance_branch_where
                 ) finance_dates
                 WHERE sale_date IS NOT NULL
                 ORDER BY sale_date DESC",
                $branch_key,
                $branch_key,
                $branch_key,
                $branch_key
            )
        );

        $rows = array();
        foreach ( $dates as $date ) {
            $claimable_breakdown = $this->get_branch_claimable_breakdown_for_date( $date, $branch_key );
            $cash_unclaimed = $claimable_breakdown['cash_available'];
            $gcash_unclaimed = $claimable_breakdown['gcash_available'];
            $total_unclaimed = $cash_unclaimed + $gcash_unclaimed;

            if ( $total_unclaimed <= 0 ) {
                continue;
            }

            $rows[] = array(
                'row_key'          => sanitize_key( $branch_key . '-' . $date ),
                'branch_key'       => $branch_key,
                'branch_name'      => $branch_name,
                'claim_date'       => $date,
                'cash_unclaimed'   => $cash_unclaimed,
                'gcash_unclaimed'  => $gcash_unclaimed,
                'total_unclaimed'  => $total_unclaimed,
                'daily_sales'      => $claimable_breakdown['daily_sales'],
                'daily_expenses'   => $claimable_breakdown['daily_expenses'],
                'daily_final'      => $claimable_breakdown['daily_final'],
            );
        }

        return $rows;
    }

    private function get_branch_where_clause( $column, $branch_key ) {
        $column = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $column );
        $branch_key = sanitize_key( $branch_key );

        if ( 'manukan_branch' === $branch_key ) {
            return "($column = %s OR $column IS NULL OR $column = '')";
        }

        return "$column = %s";
    }

    private function get_finance_claimed_sales_for_date( $date, $branch_key = 'manukan_branch', $claim_type = 'cash_sales_claim' ) {
        return $this->get_finance_claimed_sales_for_period( $date, $date, $branch_key, $claim_type );
    }

    private function get_finance_claimed_sales_for_period( $start_date, $end_date, $branch_key = 'manukan_branch', $claim_type = 'cash_sales_claim' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $claim_type = sanitize_key( $claim_type );
        if ( ! in_array( $claim_type, array( 'cash_sales_claim', 'gcash_sales_claim' ), true ) ) {
            $claim_type = 'cash_sales_claim';
        }
        $finance_branch_where = $this->get_branch_where_clause( 'branch_key', $branch_key );

        $claimed = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount)
                 FROM {$wpdb->prefix}banoks_finance_transactions
                 WHERE transaction_type = %s
                 AND $finance_branch_where
                 AND transaction_date BETWEEN %s AND %s",
                $claim_type,
                $branch_key,
                $start_date,
                $end_date
            )
        );

        return $claimed ? floatval( $claimed ) : 0;
    }

    private function get_finance_account_balance( $account ) {
        global $wpdb;

        $account = $this->sanitize_cash_source( $account );
        if ( ! in_array( $account, array( 'cash_on_hand', 'gcash_balance', 'bank_balance' ), true ) ) {
            return 0;
        }

        $incoming = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount)
                 FROM {$wpdb->prefix}banoks_finance_transactions
                 WHERE destination_account = %s",
                $account
            )
        );

        $outgoing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount)
                 FROM {$wpdb->prefix}banoks_finance_transactions
                 WHERE source_account = %s",
                $account
            )
        );

        $expense_outgoing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount)
                 FROM {$wpdb->prefix}banoks_expenses
                 WHERE cash_source = %s",
                $account
            )
        );

        $stock_outgoing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_cost)
                 FROM {$wpdb->prefix}banoks_inventory_movements
                 WHERE affects_cash_balance = 1
                 AND change_amount > 0
                 AND movement_type = 'stock_in'
                 AND location_key = 'production'
                 AND cash_source = %s",
                $account
            )
        );

        return floatval( $incoming ) - floatval( $outgoing ) - floatval( $expense_outgoing ) - floatval( $stock_outgoing );
    }

    private function get_recent_finance_transactions( $limit = 20 ) {
        global $wpdb;

        $limit = max( 1, min( 100, absint( $limit ) ) );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ft.*, u.display_name AS created_by_name
                 FROM {$wpdb->prefix}banoks_finance_transactions ft
                 LEFT JOIN {$wpdb->users} u ON ft.created_by = u.ID
                 ORDER BY ft.created_at DESC, ft.id DESC
                 LIMIT %d",
                $limit
            )
        );
    }

    private function get_overall_balance_transactions( $limit = 50 ) {
        global $wpdb;

        $limit = max( 1, min( 200, absint( $limit ) ) );
        $accounts = array( 'cash_on_hand', 'gcash_balance', 'bank_balance' );
        $rows = array();

        $finance_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ft.*, u.display_name AS created_by_name, b.branch_name
                 FROM {$wpdb->prefix}banoks_finance_transactions ft
                 LEFT JOIN {$wpdb->users} u ON ft.created_by = u.ID
                 LEFT JOIN {$wpdb->prefix}banoks_branches b ON ft.branch_key = b.branch_key
                 WHERE ft.transaction_type IN ('owner_capital_addition', 'cash_sales_claim', 'gcash_sales_claim')
                 ORDER BY ft.created_at DESC, ft.id DESC
                 LIMIT %d",
                $limit
            )
        );

        foreach ( $finance_rows as $transaction ) {
            if ( ! in_array( $transaction->destination_account, $accounts, true ) ) {
                continue;
            }

            $rows[] = array(
                'timestamp'   => $transaction->created_at,
                'date'        => $transaction->transaction_date,
                'type'        => $this->format_finance_transaction_type( $transaction->transaction_type ),
                'source'      => ! empty( $transaction->branch_name ) ? $transaction->branch_name : $this->format_finance_account_label( $transaction->source_account ),
                'destination' => $this->format_finance_account_label( $transaction->destination_account ),
                'amount'      => floatval( $transaction->amount ),
                'effect'      => 'in',
                'note'        => $transaction->note,
            );
        }

        $expense_rows = $wpdb->get_results(
            "SELECT e.description, e.amount, e.cash_source, e.date, e.created_at, b.branch_name
             FROM {$wpdb->prefix}banoks_expenses e
             LEFT JOIN {$wpdb->prefix}banoks_branches b ON e.branch_key = b.branch_key
             WHERE e.cash_source IN ('cash_on_hand', 'gcash_balance', 'bank_balance')
             ORDER BY e.created_at DESC
             LIMIT $limit"
        );

        foreach ( $expense_rows as $expense ) {
            $rows[] = array(
                'timestamp'   => $expense->created_at,
                'date'        => $expense->date,
                'type'        => 'Expense',
                'source'      => ! empty( $expense->branch_name ) ? $expense->branch_name : $this->format_finance_account_label( $expense->cash_source ),
                'destination' => 'Expense',
                'amount'      => floatval( $expense->amount ),
                'effect'      => 'out',
                'note'        => $expense->description,
            );
        }

        $stock_rows = $wpdb->get_results(
            "SELECT COALESCE(i.item_name, 'Deleted item') AS item_name,
                    COALESCE(i.unit, '') AS unit,
                    COALESCE(l.location_name, m.location_key) AS location_name,
                    m.movement_type,
                    m.change_amount,
                    m.note,
                    m.total_cost AS amount,
                    m.cash_source,
                    DATE(m.created_at) AS date,
                    m.created_at
             FROM {$wpdb->prefix}banoks_inventory_movements m
             LEFT JOIN {$wpdb->prefix}banoks_inventory_items i ON m.inventory_item_id = i.id
             LEFT JOIN {$wpdb->prefix}banoks_stock_locations l ON m.location_key = l.location_key
             WHERE m.affects_cash_balance = 1
             AND m.change_amount > 0
             AND m.movement_type = 'stock_in'
             AND m.location_key = 'production'
             AND m.cash_source IN ('cash_on_hand', 'gcash_balance', 'bank_balance')
             ORDER BY m.created_at DESC
             LIMIT $limit"
        );

        foreach ( $stock_rows as $stock_row ) {
            $stock_note = 'stock_in' === $stock_row->movement_type
                ? sprintf( 'Stock in: %s - %s.', $stock_row->item_name, $this->format_stock_quantity( $stock_row->change_amount, $stock_row->unit ) )
                : ( '' !== trim( (string) $stock_row->note ) ? $stock_row->note : sprintf( 'Stock purchase: %s', $stock_row->item_name ) );

            $rows[] = array(
                'timestamp'   => $stock_row->created_at,
                'date'        => $stock_row->date,
                'type'        => 'Stock Purchase',
                'source'      => $this->format_finance_account_label( $stock_row->cash_source ),
                'destination' => $stock_row->location_name,
                'amount'      => floatval( $stock_row->amount ),
                'effect'      => 'out',
                'note'        => $stock_note,
            );
        }

        usort(
            $rows,
            function ( $a, $b ) {
                return strtotime( $b['timestamp'] ) <=> strtotime( $a['timestamp'] );
            }
        );

        return array_slice( $rows, 0, $limit );
    }

    private function format_finance_transaction_type( $type ) {
        $labels = array(
            'owner_capital_addition' => 'Owner Added Balance',
            'cash_sales_claim'       => 'Claimed Cash Sales',
            'gcash_sales_claim'      => 'Claimed GCash Sales',
        );

        return isset( $labels[ $type ] ) ? $labels[ $type ] : ucwords( str_replace( '_', ' ', (string) $type ) );
    }

    private function format_finance_account_label( $account ) {
        $labels = array(
            'owner_capital'  => 'Owner Capital',
            'store_cash'     => 'Branch Cash Sales',
            'gcash_sales'    => 'Branch GCash Sales',
            'cash_on_hand'   => 'Cash on Hand',
            'gcash_balance'  => 'GCash Balance',
            'bank_balance'   => 'Bank Balance',
        );

        return isset( $labels[ $account ] ) ? $labels[ $account ] : ucwords( str_replace( '_', ' ', (string) $account ) );
    }

    /**
     * Render the Business Reports page.
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        global $wpdb;

        // Initial Data - Current Month
        $start_date = $this->get_request_date( 'start_date', wp_date( 'Y-m-01' ) );
        $end_date = $this->get_request_date( 'end_date', wp_date( 'Y-m-d' ) );
        $transactions_start_date = $this->get_request_date( 'transactions_start_date', $start_date );
        $transactions_end_date   = $this->get_request_date( 'transactions_end_date', $end_date );
        $active_branches = $this->get_active_branches();
        $selected_branch_key = isset( $_GET['branch_key'] ) ? sanitize_key( wp_unslash( $_GET['branch_key'] ) ) : Banoks_POS_Repository::STOCK_LOCATION_MANUKAN;
        $selected_branch_name = 'Manukan Branch';
        $valid_branch_keys = array();

        foreach ( $active_branches as $branch ) {
            $branch_key = sanitize_key( $branch->branch_key );
            $valid_branch_keys[] = $branch_key;
            if ( $selected_branch_key === $branch_key ) {
                $selected_branch_name = $branch->branch_name;
            }
        }

        if ( ! in_array( $selected_branch_key, $valid_branch_keys, true ) ) {
            $selected_branch_key = Banoks_POS_Repository::STOCK_LOCATION_MANUKAN;
            foreach ( $active_branches as $branch ) {
                if ( $selected_branch_key === sanitize_key( $branch->branch_key ) ) {
                    $selected_branch_name = $branch->branch_name;
                    break;
                }
            }
        }

        $this->display_admin_header();

        $total_sales = $this->get_branch_sales_for_period( $start_date, $end_date, $selected_branch_key );
        $total_expenses = $this->get_report_expense_total_for_branch( $start_date, $end_date, $selected_branch_key );
        $net_profit = $total_sales - $total_expenses;
        $branch_expenses = $this->get_report_expense_rows_for_branch( $start_date, $end_date, $selected_branch_key );
        $top_products = $this->get_combined_top_products( $start_date, $end_date, $selected_branch_key );
        $daily_sales = $this->get_combined_daily_sales( $start_date, $end_date, $selected_branch_key );
        $report_transactions = $this->get_combined_report_transaction_table( $transactions_start_date, $transactions_end_date, $selected_branch_key );

        include_once plugin_dir_path( __FILE__ ) . 'partials/banoks-pos-reports-display.php';
    }

    /**
     * Export the selected report range as a simple PDF.
     *
     * @since    1.0.4
     * @param    string $start_date Start date in Y-m-d format.
     * @param    string $end_date End date in Y-m-d format.
     */
    private function export_report_pdf( $start_date, $end_date, $branch_key = '' ) {
        global $wpdb;

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
            $start_date = wp_date( 'Y-m-01' );
        }

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
            $end_date = wp_date( 'Y-m-d' );
        }

        $branch_key = sanitize_key( $branch_key );
        $branch_name = 'Manukan Branch';
        $active_branch_keys = array();
        foreach ( $this->get_active_branches() as $branch ) {
            $active_branch_key = sanitize_key( $branch->branch_key );
            $active_branch_keys[] = $active_branch_key;
            if ( $branch_key === $active_branch_key ) {
                $branch_name = $branch->branch_name;
            }
        }
        if ( ! in_array( $branch_key, $active_branch_keys, true ) ) {
            $branch_key = Banoks_POS_Repository::STOCK_LOCATION_MANUKAN;
            foreach ( $this->get_active_branches() as $branch ) {
                if ( $branch_key === sanitize_key( $branch->branch_key ) ) {
                    $branch_name = $branch->branch_name;
                    break;
                }
            }
        }

        $total_sales = $this->get_branch_sales_for_period( $start_date, $end_date, $branch_key );
        $total_expenses = $this->get_report_expense_total_for_branch( $start_date, $end_date, $branch_key );
        $top_products = $this->get_combined_top_products( $start_date, $end_date, $branch_key );

        $expenses          = $this->get_report_expense_rows_for_branch( $start_date, $end_date, $branch_key );
        $stock_report_rows = $this->get_branch_stock_report_rows( $start_date, $end_date, $branch_key );
        $net_profit        = floatval( $total_sales ) - floatval( $total_expenses );

        $pdf_branch_name = preg_replace( '/\s+Branch$/i', '', $branch_name );
        $lines = array(
            array( 'text' => "BANOK'S SALES REPORT", 'style' => 'title', 'align' => 'center' ),
            array( 'text' => 'Branch: ' . $pdf_branch_name ),
            array( 'text' => 'Report Period: ' . $this->format_report_date_numeric( $start_date ) . ' - ' . $this->format_report_date_numeric( $end_date ) ),
            array( 'type' => 'divider' ),
            array( 'text' => 'SUMMARY', 'style' => 'section' ),
            array( 'text' => $this->format_pdf_row( array( 'Metric', 'Amount' ), array( 42, 24 ) ), 'style' => 'table_header' ),
            array( 'text' => $this->format_pdf_row( array( 'Total Period Sales', 'PHP ' . number_format( floatval( $total_sales ), 2 ) ), array( 42, 24 ) ), 'style' => 'table_row' ),
            array( 'text' => $this->format_pdf_row( array( 'Total Period Expenses', 'PHP ' . number_format( floatval( $total_expenses ), 2 ) ), array( 42, 24 ) ), 'style' => 'table_row' ),
            array( 'text' => $this->format_pdf_row( array( 'Final Total Sales', 'PHP ' . number_format( $net_profit, 2 ) ), array( 42, 24 ) ), 'style' => 'table_row' ),
            array( 'type' => 'divider' ),
            array( 'text' => 'EXPENSES LIST', 'style' => 'section' ),
            array( 'text' => $this->format_pdf_row( array( 'Expense Name / Description', 'Amount' ), array( 60, 16 ) ), 'style' => 'table_header' ),
        );

        if ( ! empty( $expenses ) ) {
            foreach ( $expenses as $expense ) {
                $lines[] = array(
                    'text'  => $this->format_pdf_row(
                        array(
                            $expense->description,
                            'PHP ' . number_format( floatval( $expense->amount ), 2 ),
                        ),
                        array( 60, 16 )
                    ),
                    'style' => 'table_row',
                );
            }
        }

        if ( empty( $expenses ) ) {
            $lines[] = array( 'text' => 'No expenses recorded for this period.' );
        }

        $lines[] = array( 'type' => 'divider' );
        $lines[] = array( 'text' => 'STOCK', 'style' => 'section' );
        $lines[] = array( 'text' => $this->format_pdf_row( array( 'Items', 'Start', 'End' ), array( 42, 16, 16 ) ), 'style' => 'table_header' );

        if ( empty( $stock_report_rows ) ) {
            $lines[] = array( 'text' => 'No stock movement found for this branch in this period.' );
        } else {
            foreach ( $stock_report_rows as $stock_row ) {
                $lines[] = array(
                    'text'  => $this->format_pdf_row(
                        array(
                            $stock_row['item_name'],
                            $this->format_stock_quantity( $stock_row['opening_stock'], $stock_row['unit'] ),
                            $this->format_stock_quantity( $stock_row['ending_stock'], $stock_row['unit'] ),
                        ),
                        array( 42, 16, 16 )
                    ),
                    'style' => 'table_row',
                );
            }
        }

        $lines[] = array( 'type' => 'divider' );
        $lines[] = array( 'text' => 'TOP 10 BEST-SELLING PRODUCTS', 'style' => 'section' );
        $lines[] = array( 'text' => $this->format_pdf_row( array( 'Rank', 'Product Name', 'Quantity Sold', 'Total Sales' ), array( 6, 30, 12, 16 ) ), 'style' => 'table_header' );

        if ( ! empty( $top_products ) ) {
            $rank = 1;
            foreach ( $top_products as $product ) {
                $lines[] = array(
                    'text' => $this->format_pdf_row(
                        array(
                            $rank,
                            $product->product_name,
                            intval( $product->total_qty ),
                            'PHP ' . number_format( floatval( $product->total_revenue ), 2 ),
                        ),
                        array( 6, 30, 12, 16 )
                    ),
                    'style' => 'table_row',
                );
                $rank++;
            }
        } else {
            $lines[] = array( 'text' => 'No product sales found for this period.' );
        }
        $lines[] = array( 'type' => 'divider' );

        $pdf      = $this->build_simple_report_pdf( $lines );
        $filename = sanitize_file_name( $branch_name . '-sales-expense-report-' . wp_date( 'm-d-Y', strtotime( $start_date ) ) . '-to-' . wp_date( 'm-d-Y', strtotime( $end_date ) ) . '.pdf' );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $pdf ) );
        echo $pdf;
        exit;
    }

    /**
     * Get report transactions by status.
     *
     * @since    1.0.4
     * @param    string $status Order status.
     * @param    string $start_date Start date.
     * @param    string $end_date End date.
     * @return   array
     */
    private function get_report_transactions( $status, $start_date, $end_date ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM (
                    SELECT CONCAT('BNK-', LPAD(o.order_id, 6, '0')) AS receipt_no,
                        o.entry_timestamp AS entry_timestamp,
                        o.date AS date,
                        o.grand_total AS grand_total,
                        o.status AS status,
                        'Walk-in' AS source,
                        GROUP_CONCAT(CONCAT(p.product_name, ' x ', oi.qty) ORDER BY p.product_name SEPARATOR ', ') AS items
                    FROM {$wpdb->prefix}banoks_orders o
                    LEFT JOIN {$wpdb->prefix}banoks_order_items oi ON o.order_id = oi.order_id
                    LEFT JOIN {$wpdb->prefix}banoks_items p ON oi.product_id = p.product_id
                    WHERE o.date BETWEEN %s AND %s AND o.status = %s
                    GROUP BY o.order_id
                    UNION ALL
                    SELECT oo.online_order_id AS receipt_no,
                        oo.created_at AS entry_timestamp,
                        DATE(oo.created_at) AS date,
                        oo.total_amount AS grand_total,
                        oo.order_status AS status,
                        'Online' AS source,
                        GROUP_CONCAT(CONCAT(oi.product_name, ' x ', oi.quantity) ORDER BY oi.product_name SEPARATOR ', ') AS items
                    FROM {$wpdb->prefix}banoks_online_orders oo
                    LEFT JOIN {$wpdb->prefix}banoks_online_order_items oi ON oo.id = oi.online_order_id
                    WHERE DATE(oo.created_at) BETWEEN %s AND %s AND oo.order_status = %s
                    GROUP BY oo.id
                 ) report_orders
                 ORDER BY date ASC, entry_timestamp ASC",
                $start_date,
                $end_date,
                $status,
                $start_date,
                $end_date,
                $status
            )
        );
    }

    /**
     * Get combined walk-in and online top products.
     *
     * @since    1.0.9
     * @param    string $start_date Start date.
     * @param    string $end_date End date.
     * @return   array
     */
    private function get_report_expense_total_for_branch( $start_date, $end_date, $branch_key ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $expenses = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount)
                 FROM {$wpdb->prefix}banoks_expenses
                 WHERE branch_key = %s
                 AND date BETWEEN %s AND %s",
                $branch_key,
                $start_date,
                $end_date
            )
        );

        return floatval( $expenses ) + $this->get_report_stock_expenses_for_branch( $start_date, $end_date, $branch_key );
    }

    private function get_report_expense_rows_for_branch( $start_date, $end_date, $branch_key ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT description, amount, date, created_at
                 FROM {$wpdb->prefix}banoks_expenses
                 WHERE branch_key = %s
                 AND date BETWEEN %s AND %s
                 ORDER BY date ASC, created_at ASC",
                $branch_key,
                $start_date,
                $end_date
            )
        );

        $rows = array_merge( $expenses ?: array(), $this->get_report_stock_expense_rows_for_branch( $start_date, $end_date, $branch_key ) );

        usort(
            $rows,
            function ( $a, $b ) {
                $first_date  = ! empty( $a->created_at ) ? $a->created_at : $a->date;
                $second_date = ! empty( $b->created_at ) ? $b->created_at : $b->date;
                return strtotime( $first_date ) <=> strtotime( $second_date );
            }
        );

        return $rows;
    }

    private function get_report_stock_expenses_for_branch( $start_date, $end_date, $branch_key ) {
        $branch_key = sanitize_key( $branch_key );
        if ( Banoks_POS_Repository::STOCK_LOCATION_MANUKAN !== $branch_key ) {
            return 0;
        }

        return $this->get_stock_cash_expenses_for_period( $start_date, $end_date, 'store_cash' );
    }

    private function get_report_stock_expense_rows_for_branch( $start_date, $end_date, $branch_key ) {
        $branch_key = sanitize_key( $branch_key );
        if ( Banoks_POS_Repository::STOCK_LOCATION_MANUKAN !== $branch_key ) {
            return array();
        }

        return $this->get_stock_cash_expense_rows_for_period( $start_date, $end_date, 'store_cash' );
    }

    private function get_branch_stock_report_rows( $start_date, $end_date, $branch_key ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $period_start = $start_date . ' 00:00:00';
        $period_end   = $end_date . ' 23:59:59';

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT i.id, i.item_name, i.unit
                 FROM {$wpdb->prefix}banoks_inventory_movements m
                 JOIN {$wpdb->prefix}banoks_inventory_items i ON m.inventory_item_id = i.id
                 WHERE m.location_key = %s
                 AND m.created_at <= %s
                 ORDER BY i.item_name ASC",
                $branch_key,
                $period_end
            )
        );

        $rows = array();
        foreach ( $items as $item ) {
            $opening = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT new_stock
                     FROM {$wpdb->prefix}banoks_inventory_movements
                     WHERE inventory_item_id = %d
                     AND location_key = %s
                     AND created_at < %s
                     ORDER BY created_at DESC, id DESC
                     LIMIT 1",
                    absint( $item->id ),
                    $branch_key,
                    $period_start
                )
            );

            if ( null === $opening ) {
                $first_period_movement = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT old_stock, new_stock, change_amount
                         FROM {$wpdb->prefix}banoks_inventory_movements
                         WHERE inventory_item_id = %d
                         AND location_key = %s
                         AND created_at BETWEEN %s AND %s
                         ORDER BY created_at ASC, id ASC
                         LIMIT 1",
                        absint( $item->id ),
                        $branch_key,
                        $period_start,
                        $period_end
                    )
                );

                if ( $first_period_movement ) {
                    $opening = floatval( $first_period_movement->old_stock );
                    if ( $opening <= 0 && floatval( $first_period_movement->change_amount ) > 0 ) {
                        $opening = floatval( $first_period_movement->new_stock );
                    }
                }
            }

            if ( null === $opening ) {
                $opening = $this->get_inventory_location_stock( absint( $item->id ), $branch_key );
            }

            $ending = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT new_stock
                     FROM {$wpdb->prefix}banoks_inventory_movements
                     WHERE inventory_item_id = %d
                     AND location_key = %s
                     AND created_at <= %s
                     ORDER BY created_at DESC, id DESC
                     LIMIT 1",
                    absint( $item->id ),
                    $branch_key,
                    $period_end
                )
            );

            if ( null === $ending ) {
                $ending = $opening;
            }

            $period_moves = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        SUM(CASE WHEN change_amount > 0 THEN change_amount ELSE 0 END) AS stock_in,
                        SUM(CASE WHEN change_amount < 0 THEN ABS(change_amount) ELSE 0 END) AS stock_out,
                        COUNT(*) AS movement_count,
                        SUM(CASE WHEN old_stock > 0 AND new_stock <= 0 THEN 1 ELSE 0 END) AS sold_out_count,
                        SUM(CASE WHEN old_stock <= 0 AND change_amount > 0 THEN 1 ELSE 0 END) AS restock_count
                     FROM {$wpdb->prefix}banoks_inventory_movements
                     WHERE inventory_item_id = %d
                     AND location_key = %s
                     AND created_at BETWEEN %s AND %s",
                    absint( $item->id ),
                    $branch_key,
                    $period_start,
                    $period_end
                )
            );

            $movement_count = $period_moves ? intval( $period_moves->movement_count ) : 0;
            if ( 0 === $movement_count && floatval( $opening ) === floatval( $ending ) ) {
                continue;
            }

            $activity_note = $movement_count . ' movement' . ( 1 === $movement_count ? '' : 's' );
            if ( $period_moves && intval( $period_moves->sold_out_count ) > 0 && intval( $period_moves->restock_count ) > 0 ) {
                $activity_note = 'Sold out then restocked; ' . $activity_note;
            } elseif ( $period_moves && intval( $period_moves->sold_out_count ) > 0 ) {
                $activity_note = 'Sold out during period; ' . $activity_note;
            } elseif ( $period_moves && intval( $period_moves->restock_count ) > 0 ) {
                $activity_note = 'Restocked during period; ' . $activity_note;
            }

            $rows[] = array(
                'item_name'      => $item->item_name,
                'unit'           => $item->unit,
                'opening_stock'  => floatval( $opening ),
                'stock_in'       => $period_moves ? floatval( $period_moves->stock_in ) : 0,
                'stock_out'      => $period_moves ? floatval( $period_moves->stock_out ) : 0,
                'ending_stock'   => floatval( $ending ),
                'activity_note'  => $activity_note,
            );
        }

        return $rows;
    }

    private function get_combined_top_products( $start_date, $end_date, $branch_key = '' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_name, SUM(total_qty) AS total_qty, SUM(total_revenue) AS total_revenue
                 FROM (
                    SELECT p.product_name, SUM(oi.qty) AS total_qty, SUM(oi.sub_total) AS total_revenue
                    FROM {$wpdb->prefix}banoks_order_items oi
                    JOIN {$wpdb->prefix}banoks_items p ON oi.product_id = p.product_id
                    JOIN {$wpdb->prefix}banoks_orders o ON oi.order_id = o.order_id
                    WHERE o.date BETWEEN %s AND %s AND o.status = 'completed' AND o.branch_key = %s
                    GROUP BY p.product_name
                    UNION ALL
                    SELECT oi.product_name, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
                    FROM {$wpdb->prefix}banoks_online_order_items oi
                    JOIN {$wpdb->prefix}banoks_online_orders o ON oi.online_order_id = o.id
                    WHERE DATE(o.created_at) BETWEEN %s AND %s AND o.order_status = 'completed' AND o.branch_key = %s
                    GROUP BY oi.product_name
                 ) product_sales
                 GROUP BY product_name
                 ORDER BY total_qty DESC
                 LIMIT 10",
                $start_date,
                $end_date,
                $branch_key,
                $start_date,
                $end_date,
                $branch_key
            )
        );
    }

    /**
     * Get combined walk-in and online daily sales.
     *
     * @since    1.0.9
     * @param    string $start_date Start date.
     * @param    string $end_date End date.
     * @return   array
     */
    private function get_combined_daily_sales( $start_date, $end_date, $branch_key = '' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT date, SUM(total) AS total
                 FROM (
                    SELECT date, SUM(grand_total) AS total
                    FROM {$wpdb->prefix}banoks_orders
                    WHERE date BETWEEN %s AND %s AND status = 'completed' AND branch_key = %s
                    GROUP BY date
                    UNION ALL
                    SELECT DATE(created_at) AS date, SUM(total_amount) AS total
                    FROM {$wpdb->prefix}banoks_online_orders
                    WHERE DATE(created_at) BETWEEN %s AND %s AND order_status = 'completed' AND branch_key = %s
                    GROUP BY DATE(created_at)
                 ) daily
                 GROUP BY date
                 ORDER BY date ASC",
                $start_date,
                $end_date,
                $branch_key,
                $start_date,
                $end_date,
                $branch_key
            )
        );
    }

    /**
     * Get combined transaction rows for the report table.
     *
     * @since    1.0.10
     * @param    string $start_date Start date.
     * @param    string $end_date End date.
     * @return   array
     */
    private function get_combined_report_transaction_table( $start_date, $end_date, $branch_key = '' ) {
        global $wpdb;

        $branch_key = sanitize_key( $branch_key );
        $walkin_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT CONCAT('BNK-', LPAD(o.order_id, 6, '0')) AS order_id,
                    o.entry_timestamp AS transaction_date,
                    'Walk-in' AS order_type,
                    o.grand_total AS total_amount,
                    o.status AS status,
                    GROUP_CONCAT(CONCAT_WS('::', p.product_name, oi.qty, oi.unit_price_at_sale, oi.sub_total) ORDER BY p.product_name SEPARATOR '||') AS item_rows
                 FROM {$wpdb->prefix}banoks_orders o
                 LEFT JOIN {$wpdb->prefix}banoks_order_items oi ON o.order_id = oi.order_id
                 LEFT JOIN {$wpdb->prefix}banoks_items p ON oi.product_id = p.product_id
                 WHERE o.date BETWEEN %s AND %s AND o.status IN ('completed', 'cancelled') AND o.branch_key = %s
                 GROUP BY o.order_id",
                $start_date,
                $end_date,
                $branch_key
            )
        );

        $online_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT oo.online_order_id AS order_id,
                    oo.created_at AS transaction_date,
                    'Online' AS order_type,
                    oo.total_amount AS total_amount,
                    oo.order_status AS status,
                    GROUP_CONCAT(CONCAT_WS('::', oi.product_name, oi.quantity, oi.price, oi.subtotal) ORDER BY oi.product_name SEPARATOR '||') AS item_rows
                 FROM {$wpdb->prefix}banoks_online_orders oo
                 LEFT JOIN {$wpdb->prefix}banoks_online_order_items oi ON oo.id = oi.online_order_id
                 WHERE DATE(oo.created_at) BETWEEN %s AND %s AND oo.order_status IN ('completed', 'cancelled') AND oo.branch_key = %s
                 GROUP BY oo.id",
                $start_date,
                $end_date,
                $branch_key
            )
        );

        $transactions = array_merge( $walkin_rows ?: array(), $online_rows ?: array() );

        foreach ( $transactions as $transaction ) {
            $transaction->items_detail = $this->parse_report_transaction_items( $transaction->item_rows );
        }

        usort(
            $transactions,
            function ( $a, $b ) {
                return strtotime( $b->transaction_date ) <=> strtotime( $a->transaction_date );
            }
        );

        return $transactions;
    }

    /**
     * Parse transaction item rows for the clickable report modal.
     *
     * @since    1.0.10
     * @param    string $item_rows Concatenated item data.
     * @return   array
     */
    private function parse_report_transaction_items( $item_rows ) {
        $items = array();

        if ( empty( $item_rows ) ) {
            return $items;
        }

        foreach ( explode( '||', $item_rows ) as $item_row ) {
            $parts = explode( '::', $item_row );
            if ( count( $parts ) < 4 ) {
                continue;
            }

            $items[] = array(
                'name'     => sanitize_text_field( $parts[0] ),
                'quantity' => intval( $parts[1] ),
                'price'    => floatval( $parts[2] ),
                'subtotal' => floatval( $parts[3] ),
            );
        }

        return $items;
    }

    /**
     * Format transactions for the PDF.
     *
     * @since    1.0.4
     * @param    array  $transactions Transactions.
     * @param    string $status Status label.
     * @return   array
     */
    private function format_report_transaction_lines( $transactions, $status ) {
        $headers      = array( 'Receipt No.', 'Date/Time', 'Items', 'Total Amount' );
        $widths       = array( 12, 18, 36, 16 );
        $lines        = array(
            array( 'text' => $this->format_pdf_row( $headers, $widths ), 'style' => 'table_header' ),
        );

        if ( empty( $transactions ) ) {
            $lines[] = array( 'text' => 'No ' . $status . ' transactions found for this period.' );
            return $lines;
        }

        foreach ( $transactions as $transaction ) {
            $row = array(
                $transaction->receipt_no . ' ' . $transaction->source,
                wp_date( 'm/d/Y g:i A', strtotime( $transaction->entry_timestamp ) ),
                ! empty( $transaction->items ) ? $transaction->items : 'No items',
                'PHP ' . number_format( floatval( $transaction->grand_total ), 2 ),
            );

            $lines[] = array(
                'text'  => $this->format_pdf_row( $row, $widths ),
                'style' => 'table_row',
            );
        }

        return $lines;
    }

    /**
     * Build a basic text PDF without external dependencies.
     *
     * @since    1.0.4
     * @param    array $lines Report lines.
     * @return   string
     */
    private function build_simple_report_pdf( $lines ) {
        $pages      = array();
        $page_lines = array();
        $y          = 790;

        foreach ( $lines as $line ) {
            if ( isset( $line['type'] ) && 'divider' === $line['type'] ) {
                if ( $y < 52 ) {
                    $pages[]    = $page_lines;
                    $page_lines = array();
                    $y          = 790;
                }

                $page_lines[] = array(
                    'type'  => 'divider',
                    'style' => 'divider',
                    'x'     => 42,
                    'y'     => $y,
                );
                $y -= 18;
                continue;
            }

            $style = isset( $line['style'] ) ? $line['style'] : 'normal';
            $text  = isset( $line['text'] ) ? $line['text'] : '';
            $align = isset( $line['align'] ) ? $line['align'] : 'left';
            $parts = $this->wrap_pdf_text( $text, in_array( $style, array( 'title', 'heading', 'section' ), true ) ? 78 : 96 );

            foreach ( $parts as $part ) {
                if ( $y < 52 ) {
                    $pages[]    = $page_lines;
                    $page_lines = array();
                    $y          = 790;
                }

                $page_lines[] = array(
                    'text'  => $part,
                    'style' => $style,
                    'align' => $align,
                    'x'     => 42,
                    'y'     => $y,
                );

                $y -= in_array( $style, array( 'title', 'heading', 'section' ), true ) ? 22 : 17;
            }
        }

        if ( ! empty( $page_lines ) ) {
            $pages[] = $page_lines;
        }

        if ( empty( $pages ) ) {
            $pages[] = array(
                array(
                    'text'  => 'No report data available.',
                    'style' => 'normal',
                    'x'     => 42,
                    'y'     => 790,
                ),
            );
        }

        $objects  = array();
        $catalog  = 1;
        $pages_id = 2;
        $font_id  = 3;
        $mono_font_id = 4;
        $next_id  = 5;
        $page_ids = array();

        foreach ( $pages as $page ) {
            $page_id    = $next_id++;
            $content_id = $next_id++;
            $page_ids[] = $page_id;

            $stream = '';
            foreach ( $page as $entry ) {
                if ( isset( $entry['type'] ) && 'divider' === $entry['type'] ) {
                    $stream .= "[] 0 d\n";
                    $stream .= "0.7 w\n";
                    $stream .= "[4 4] 0 d\n";
                    $stream .= '42 ' . intval( $entry['y'] ) . " m\n";
                    $stream .= '553 ' . intval( $entry['y'] ) . " l\n";
                    $stream .= "S\n";
                    $stream .= "[] 0 d\n";
                    continue;
                }

                $font = 'F1';
                $size = 'normal' === $entry['style'] ? 11 : 12;
                if ( 'table_header' === $entry['style'] ) {
                    $size = 10;
                    $font = 'F2';
                } elseif ( 'table_row' === $entry['style'] ) {
                    $size = 10;
                    $font = 'F2';
                }
                if ( 'title' === $entry['style'] ) {
                    $size = 18;
                } elseif ( 'heading' === $entry['style'] || 'section' === $entry['style'] ) {
                    $size = 15;
                }
                $x = intval( $entry['x'] );
                if ( isset( $entry['align'] ) && 'center' === $entry['align'] ) {
                    $text_width_factor = 'title' === $entry['style'] ? 0.56 : 0.50;
                    $x = max( 42, intval( ( 595 - ( strlen( $entry['text'] ) * $size * $text_width_factor ) ) / 2 ) );
                }
                $stream .= "BT\n";
                $stream .= '/' . $font . ' ' . $size . " Tf\n";
                $stream .= '1 0 0 1 ' . $x . ' ' . intval( $entry['y'] ) . " Tm\n";
                $stream .= '(' . $this->escape_pdf_text( $entry['text'] ) . ") Tj\n";
                $stream .= "ET\n";
            }

            $objects[ $content_id ] = "<< /Length " . strlen( $stream ) . " >>\nstream\n" . $stream . "endstream";
            $objects[ $page_id ]    = "<< /Type /Page /Parent {$pages_id} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$font_id} 0 R /F2 {$mono_font_id} 0 R >> >> /Contents {$content_id} 0 R >>";
        }

        $objects[ $catalog ] = "<< /Type /Catalog /Pages {$pages_id} 0 R >>";
        $kids = array();
        foreach ( $page_ids as $page_id ) {
            $kids[] = $page_id . ' 0 R';
        }
        $objects[ $pages_id ] = '<< /Type /Pages /Kids [ ' . implode( ' ', $kids ) . ' ] /Count ' . count( $page_ids ) . ' >>';
        $objects[ $font_id ]  = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[ $mono_font_id ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>';

        ksort( $objects );
        $pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = array( 0 );

        foreach ( $objects as $id => $object ) {
            $offsets[ $id ] = strlen( $pdf );
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen( $pdf );
        $pdf .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ( $i = 1; $i <= count( $objects ); $i++ ) {
            $pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
        }

        $pdf .= "trailer\n<< /Size " . ( count( $objects ) + 1 ) . " /Root {$catalog} 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /**
     * Wrap PDF text into fixed-width rows.
     *
     * @since    1.0.4
     * @param    string $text Text.
     * @param    int    $width Width.
     * @return   array
     */
    private function wrap_pdf_text( $text, $width ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );

        if ( '' === $text ) {
            return array( '' );
        }

        return explode( "\n", wordwrap( $text, $width, "\n", true ) );
    }

    /**
     * Escape PDF text.
     *
     * @since    1.0.4
     * @param    string $text Text.
     * @return   string
     */
    private function escape_pdf_text( $text ) {
        $text = preg_replace( '/[^\x09\x0A\x0D\x20-\x7E]/', '', (string) $text );
        return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
    }

    /**
     * Format a fixed-width PDF table row.
     *
     * @since    1.0.4
     * @param    array $cells Cell values.
     * @param    array $widths Cell widths.
     * @return   string
     */
    private function format_pdf_row( $cells, $widths ) {
        $parts = array();

        foreach ( $cells as $index => $cell ) {
            $width = isset( $widths[ $index ] ) ? intval( $widths[ $index ] ) : 12;
            $cell  = preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $cell ) );
            $cell  = strlen( $cell ) > $width ? substr( $cell, 0, max( 0, $width - 3 ) ) . '...' : $cell;
            $parts[] = str_pad( $cell, $width );
        }

        return implode( ' | ', $parts );
    }

    /**
     * Format report dates.
     *
     * @since    1.0.4
     * @param    string $date Date.
     * @return   string
     */
    private function format_report_date( $date ) {
        return wp_date( 'M d, Y', strtotime( $date ) );
    }

    /**
     * Format report dates numerically for the PDF header.
     *
     * @since    1.0.4
     * @param    string $date Date.
     * @return   string
     */
    private function format_report_date_numeric( $date ) {
        return wp_date( 'm/d/Y', strtotime( $date ) );
    }

}

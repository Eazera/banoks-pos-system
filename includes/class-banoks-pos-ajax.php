<?php
/**
 * Handle AJAX requests for the POS system
 *
 * @link       https://banoks.com
 * @since      1.0.0
 * @package    Banoks_POS
 * @subpackage Banoks_POS/includes
 * @author     Christian Fulache
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Banoks_POS_Ajax {

    /**
     * Initialize the class and register AJAX actions.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_action( 'wp_ajax_banoks_pos_place_order', array( $this, 'handle_place_order' ) );
        add_action( 'wp_ajax_banoks_pos_update_order_status', array( $this, 'handle_update_order_status' ) );
        add_action( 'wp_ajax_banoks_pos_walk_in_order_count', array( $this, 'handle_walk_in_order_count' ) );
        add_action( 'wp_ajax_banoks_pos_online_order_count', array( $this, 'handle_online_order_count' ) );
        add_action( 'wp_ajax_banoks_pos_online_order_notifications', array( $this, 'handle_online_order_notifications' ) );
        add_action( 'wp_ajax_banoks_pos_update_online_order_status', array( $this, 'handle_update_online_order_status' ) );
        add_action( 'wp_ajax_banoks_pos_update_payment_proof', array( $this, 'handle_update_payment_proof' ) );
    }

    /**
     * Return active walk-in order count for navigation badges.
     *
     * @since    1.0.13
     */
    public function handle_walk_in_order_count() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $repository = new Banoks_POS_Repository();

        wp_send_json_success(
            array(
                'count' => $repository->count_active_walk_in_orders(),
            )
        );
    }

    /**
     * Return online order count for POS notification polling.
     *
     * @since    1.0.9
     */
    public function handle_online_order_count() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $repository = new Banoks_POS_Repository();

        wp_send_json_success(
            array(
                'count' => $repository->count_pending_online_orders(),
            )
        );
    }

    /**
     * Return online order summaries for kiosk notifications.
     *
     * @since    1.0.9
     */
    public function handle_online_order_notifications() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $repository = new Banoks_POS_Repository();
        $orders     = array();

        foreach ( $repository->get_online_order_notifications() as $order ) {
            $orders[] = array(
                'id'               => absint( $order->id ),
                'online_order_id'  => $order->online_order_id,
                'customer_name'    => $order->customer_name,
                'total_amount'     => number_format( floatval( $order->total_amount ), 2 ),
                'fulfillment_type' => 'pickup' === $order->fulfillment_type ? 'Pickup' : 'Delivery',
                'order_status'     => ucwords( str_replace( '_', ' ', $order->order_status ) ),
                'created_at'       => wp_date( 'M d, Y g:i A', strtotime( $order->created_at ) ),
            );
        }

        wp_send_json_success(
            array(
                'count'  => count( $orders ),
                'orders' => $orders,
            )
        );
    }

    /**
     * Handle online order status updates from the admin cards.
     *
     * @since    1.0.9
     */
    public function handle_update_online_order_status() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $repository = new Banoks_POS_Repository();
        $result     = $repository->update_online_order_status(
            isset( $_POST['online_order_id'] ) ? absint( $_POST['online_order_id'] ) : 0,
            isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '',
            array(
                'driver_name'    => isset( $_POST['driver_name'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_name'] ) ) : '',
                'driver_contact' => isset( $_POST['driver_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_contact'] ) ) : '',
                'note'           => isset( $_POST['status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['status_note'] ) ) : '',
            )
        );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( array( 'message' => $result['error'] ) );
        }

        wp_send_json_success( array( 'message' => 'Online order status updated successfully.' ) );
    }

    /**
     * Handle GCash payment proof updates from the online order modal.
     *
     * @since    1.0.9
     */
    public function handle_update_payment_proof() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        $repository = new Banoks_POS_Repository();
        $result     = $repository->update_payment_proof_status(
            isset( $_POST['payment_proof_id'] ) ? absint( $_POST['payment_proof_id'] ) : 0,
            isset( $_POST['payment_proof_status'] ) ? sanitize_key( wp_unslash( $_POST['payment_proof_status'] ) ) : '',
            isset( $_POST['payment_rejection_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['payment_rejection_reason'] ) ) : ''
        );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( array( 'message' => $result['error'] ) );
        }

        wp_send_json_success( array( 'message' => 'Payment proof updated successfully.' ) );
    }

    /**
     * Handle updating an order status (Complete/Cancel).
     *
     * @since    1.0.0
     */
    public function handle_update_order_status() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        global $wpdb;
        $orders_table = $wpdb->prefix . 'banoks_orders';
        $order_id     = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $status       = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

        if ( ! $order_id || ! in_array( $status, array( 'preparing', 'completed', 'cancelled' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid order update.' ) );
        }

        $current_status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM {$orders_table} WHERE order_id = %d",
                $order_id
            )
        );

        if ( null === $current_status ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $allowed = array(
            'pending'   => array( 'preparing', 'cancelled' ),
            'preparing' => array( 'completed', 'cancelled' ),
        );

        if ( empty( $allowed[ $current_status ] ) || ! in_array( $status, $allowed[ $current_status ], true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid status movement.' ) );
        }

        $restores_stock = 'cancelled' === $status && 'preparing' === $current_status;
        $uses_stock_transaction = 'preparing' === $status || $restores_stock;
        if ( $uses_stock_transaction ) {
            $wpdb->query( 'START TRANSACTION' );
        }

        $repository = new Banoks_POS_Repository();
        if ( 'preparing' === $status ) {
            $order_items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT product_id, qty FROM {$wpdb->prefix}banoks_order_items WHERE order_id = %d",
                    $order_id
                )
            );
            $stock_result = $repository->deduct_stock_for_items( $order_items, 'walk_in', 'POS-' . $order_id, 'walk_in' );
            if ( isset( $stock_result['error'] ) ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( array( 'message' => $stock_result['error'] ) );
            }
        }

        if ( $restores_stock ) {
            $stock_result = $repository->restore_stock_for_source( 'walk_in', 'POS-' . $order_id );
            if ( isset( $stock_result['error'] ) ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( array( 'message' => $stock_result['error'] ) );
            }
        }

        $updated = $wpdb->update(
            $orders_table,
            array( 'status' => $status ),
            array( 'order_id' => $order_id, 'status' => $current_status ),
            array( '%s' ),
            array( '%d', '%s' )
        );

        if ( false === $updated || 0 === $updated ) {
            if ( $uses_stock_transaction ) {
                $wpdb->query( 'ROLLBACK' );
            }
            wp_send_json_error( array( 'message' => 'Failed to update order status.' ) );
        }

        if ( $uses_stock_transaction ) {
            $wpdb->query( 'COMMIT' );
        }

        wp_send_json_success( array( 'message' => 'Order status updated to ' . $status ) );
    }

    /**
     * Handle the AJAX request to place an order.
     *
     * @since    1.0.0
     */
    public function handle_place_order() {
        check_ajax_referer( 'banoks_pos_order_nonce', 'nonce' );

        if ( ! current_user_can( 'banoks_use_pos' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
        }

        global $wpdb;
        $items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();

        if ( empty( $items ) ) {
            wp_send_json_error( array( 'message' => 'Cart is empty.' ) );
        }

        $order_date = ! empty( $_POST['order_date'] ) ? sanitize_text_field( wp_unslash( $_POST['order_date'] ) ) : current_time( 'Y-m-d' );

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $order_date ) ) {
            wp_send_json_error( array( 'message' => 'Invalid order date.' ) );
        }

        $payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'cash';
        if ( ! in_array( $payment_method, array( 'cash', 'gcash' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid payment method.' ) );
        }
        $received_account = 'gcash' === $payment_method ? 'gcash_balance' : 'store_cash';

        $cart_quantities = array();

        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $product_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            $quantity   = isset( $item['qty'] ) ? absint( $item['qty'] ) : 0;

            if ( ! $product_id || ! $quantity ) {
                continue;
            }

            if ( ! isset( $cart_quantities[ $product_id ] ) ) {
                $cart_quantities[ $product_id ] = 0;
            }

            $cart_quantities[ $product_id ] += $quantity;
        }

        if ( empty( $cart_quantities ) ) {
            wp_send_json_error( array( 'message' => 'No valid order items were submitted.' ) );
        }

        $product_ids      = array_keys( $cart_quantities );
        $placeholders     = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );
        $products_table   = $wpdb->prefix . 'banoks_items';
        $orders_table     = $wpdb->prefix . 'banoks_orders';
        $order_items_table = $wpdb->prefix . 'banoks_order_items';
        $products         = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id, product_name, current_price, COALESCE(is_active, 1) AS is_active, COALESCE(is_available, 1) AS is_available
                 FROM {$products_table}
                 WHERE product_id IN ({$placeholders})",
                $product_ids
            ),
            OBJECT_K
        );

        if ( ! is_array( $products ) ) {
            wp_send_json_error( array( 'message' => 'Failed to validate order products.' ) );
        }

        if ( count( $products ) !== count( $product_ids ) ) {
            wp_send_json_error( array( 'message' => 'One or more products are no longer available.' ) );
        }

        $validated_items = array();
        $grand_total     = 0;

        foreach ( $cart_quantities as $product_id => $quantity ) {
            $product = isset( $products[ $product_id ] ) ? $products[ $product_id ] : null;

            if ( ! $product || ! intval( $product->is_active ) || ! intval( $product->is_available ) ) {
                wp_send_json_error( array( 'message' => 'One or more products are no longer available.' ) );
            }

            $unit_price = floatval( $product->current_price );

            if ( $unit_price < 0 ) {
                wp_send_json_error( array( 'message' => 'One or more products have an invalid price.' ) );
            }

            $sub_total         = $unit_price * $quantity;
            $grand_total      += $sub_total;
            $validated_items[] = array(
                'product_id' => $product_id,
                'quantity'   => $quantity,
                'unit_price' => $unit_price,
                'sub_total'  => $sub_total,
            );
        }

        $repository = new Banoks_POS_Repository();
        $recipe_result = $repository->validate_recipe_inventory_for_items( $validated_items, 'walk_in' );
        if ( isset( $recipe_result['error'] ) ) {
            wp_send_json_error( array( 'message' => $recipe_result['error'] ) );
        }

        $current_user = wp_get_current_user();
        $cashier_name = ! empty( $current_user->user_login ) ? $current_user->user_login : 'unknown';

        $order_inserted = $wpdb->insert(
            $orders_table,
            array(
                'created_by'      => $cashier_name,
                'branch_key'      => 'manukan_branch',
                'entry_timestamp' => current_time( 'mysql' ),
                'date'            => $order_date,
                'grand_total'     => $grand_total,
                'payment_method'  => $payment_method,
                'received_account'=> $received_account,
                'status'          => 'pending',
            ),
            array( '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );

        if ( ! $order_inserted ) {
            wp_send_json_error( array( 'message' => 'Failed to save order.' ) );
        }

        $order_id = $wpdb->insert_id;
        $inserted_items    = 0;

        foreach ( $validated_items as $item ) {
            $inserted = $wpdb->insert(
                $order_items_table,
                array(
                    'order_id'           => $order_id,
                    'product_id'         => $item['product_id'],
                    'qty'                => $item['quantity'],
                    'unit_price_at_sale' => $item['unit_price'],
                    'sub_total'          => $item['sub_total'],
                ),
                array( '%d', '%d', '%d', '%f', '%f' )
            );

            if ( false !== $inserted ) {
                $inserted_items++;
            }
        }

        if ( count( $validated_items ) !== $inserted_items ) {
            $wpdb->delete( $order_items_table, array( 'order_id' => $order_id ), array( '%d' ) );
            $wpdb->delete( $orders_table, array( 'order_id' => $order_id ), array( '%d' ) );
            wp_send_json_error( array( 'message' => 'Failed to save all order items.' ) );
        }

        wp_send_json_success( array( 
            'message'  => 'Order placed successfully!',
            'order_id' => $order_id,
            'total'    => $grand_total,
        ) );
    }
}

<?php
/**
 * Shared data access for Banoks POS.
 *
 * @link       https://banoks.com
 * @since      1.0.0
 * @package    Banoks_POS
 * @subpackage Banoks_POS/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Banoks_POS_Repository {

    const STOCK_LOCATION_PRODUCTION = 'production';
    const STOCK_LOCATION_MANUKAN    = 'manukan_branch';

    const ONLINE_STATUS_PENDING    = 'pending';
    const ONLINE_STATUS_VERIFYING  = 'verifying';
    const ONLINE_STATUS_PREPARING  = 'preparing';
    const ONLINE_STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    const ONLINE_STATUS_DELIVERING = 'delivering';
    const ONLINE_STATUS_COMPLETED  = 'completed';
    const ONLINE_STATUS_CANCELLED  = 'cancelled';
    const ONLINE_STATUS_REJECTED   = 'rejected';

    /**
     * Get products for the POS grid.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_products() {
        global $wpdb;

        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_items WHERE COALESCE(is_active, 1) = 1 AND COALESCE(is_available, 1) = 1 ORDER BY product_name ASC" );
    }

    /**
     * Get unique product categories.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_categories() {
        global $wpdb;

        return $wpdb->get_col( "SELECT DISTINCT category FROM {$wpdb->prefix}banoks_items WHERE category != '' ORDER BY category ASC" );
    }

    /**
     * Deduct recipe inventory for a set of order items.
     *
     * @since    1.0.10
     * @param    array  $items Items with product_id and quantity.
     * @param    string $source Source type.
     * @param    string $source_id Source/order ID.
     * @return   array
     */
    public function deduct_stock_for_items( $items, $source, $source_id = '', $recipe_context = '' ) {
        $quantities = $this->normalize_order_item_quantities( $items );
        $recipe_context = $recipe_context ? sanitize_key( $recipe_context ) : $this->get_recipe_context_from_source( $source );

        $recipe_result = $this->deduct_recipe_inventory_for_items( $quantities, $source, $source_id, $recipe_context, self::STOCK_LOCATION_MANUKAN );
        if ( isset( $recipe_result['error'] ) ) {
            return $recipe_result;
        }

        return array( 'success' => true );
    }

    /**
     * Restore ingredient stock that was previously deducted for an order source.
     *
     * @since    1.0.12
     * @param    string $source Source type used when stock was deducted.
     * @param    string $source_id Source/order ID used when stock was deducted.
     * @return   array
     */
    public function restore_stock_for_source( $source, $source_id ) {
        global $wpdb;

        $source    = sanitize_key( $source );
        $source_id = sanitize_text_field( $source_id );

        if ( '' === $source || '' === $source_id ) {
            return array( 'error' => 'Missing stock restoration source.' );
        }

        $movements = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.inventory_item_id, m.location_key, m.change_amount, m.unit_cost, i.item_name
                 FROM {$wpdb->prefix}banoks_inventory_movements m
                 LEFT JOIN {$wpdb->prefix}banoks_inventory_items i ON m.inventory_item_id = i.id
                 WHERE m.source = %s
                 AND m.source_id = %s
                 AND m.movement_type = 'recipe_usage'
                 AND m.change_amount < 0
                 ORDER BY m.id ASC",
                $source,
                $source_id
            )
        );

        if ( empty( $movements ) ) {
            return array( 'success' => true );
        }

        foreach ( $movements as $movement ) {
            $location_key = $this->sanitize_stock_location_key( $movement->location_key );
            $old_stock = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT current_stock
                     FROM {$wpdb->prefix}banoks_inventory_balances
                     WHERE inventory_item_id = %d
                     AND location_key = %s",
                    absint( $movement->inventory_item_id ),
                    $location_key
                )
            );

            if ( null === $old_stock ) {
                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'banoks_inventory_balances',
                    array(
                        'inventory_item_id' => absint( $movement->inventory_item_id ),
                        'location_key'      => $location_key,
                        'current_stock'     => 0,
                        'updated_at'        => current_time( 'mysql' ),
                    ),
                    array( '%d', '%s', '%f', '%s' )
                );

                if ( false === $inserted ) {
                    return array( 'error' => 'Could not restore ingredient stock. Please try again.' );
                }

                $old_stock = 0;
            }

            $restore_quantity = abs( floatval( $movement->change_amount ) );
            $new_stock        = floatval( $old_stock ) + $restore_quantity;
            $updated          = $wpdb->update(
                $wpdb->prefix . 'banoks_inventory_balances',
                array(
                    'current_stock' => $new_stock,
                    'updated_at'    => current_time( 'mysql' ),
                ),
                array(
                    'inventory_item_id' => absint( $movement->inventory_item_id ),
                    'location_key'      => $location_key,
                    'current_stock'     => floatval( $old_stock ),
                ),
                array( '%f', '%s' ),
                array( '%d', '%s', '%f' )
            );

            if ( false === $updated || 0 === $updated ) {
                return array( 'error' => 'Could not restore ingredient stock. Please try again.' );
            }

            $this->create_inventory_movement(
                $movement->inventory_item_id,
                $location_key,
                $old_stock,
                $new_stock,
                'recipe_restore',
                $source,
                $source_id,
                'Ingredient stock restored from cancelled order.',
                floatval( $movement->unit_cost )
            );
        }

        return array( 'success' => true );
    }

    /**
     * Normalize order item quantities by product.
     *
     * @since    1.0.11
     * @param    array $items Order items.
     * @return   array
     */
    private function normalize_order_item_quantities( $items ) {
        $quantities = array();

        foreach ( $items as $item ) {
            if ( is_object( $item ) ) {
                $product_id = isset( $item->product_id ) ? absint( $item->product_id ) : 0;
                $quantity   = isset( $item->quantity ) ? absint( $item->quantity ) : ( isset( $item->qty ) ? absint( $item->qty ) : 0 );
            } else {
                $product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
                $quantity   = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : ( isset( $item['qty'] ) ? absint( $item['qty'] ) : 0 );
            }

            if ( ! $product_id || ! $quantity ) {
                continue;
            }

            if ( ! isset( $quantities[ $product_id ] ) ) {
                $quantities[ $product_id ] = 0;
            }
            $quantities[ $product_id ] += $quantity;
        }

        return $quantities;
    }

    /**
     * Build ingredient requirements from product recipes.
     *
     * @since    1.0.11
     * @param    array $product_quantities Product quantities.
     * @param    string $recipe_context Recipe context.
     * @return   array
     */
    private function get_recipe_inventory_requirements( $product_quantities, $recipe_context = 'all', $location_key = self::STOCK_LOCATION_MANUKAN ) {
        global $wpdb;

        $allowed_conditions = $this->get_recipe_conditions_for_context( $recipe_context );
        $location_key       = $this->sanitize_stock_location_key( $location_key );
        $requirements = array();
        foreach ( $product_quantities as $product_id => $product_quantity ) {
            $recipes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT r.inventory_item_id, r.quantity_used, COALESCE(r.applies_to, 'all') AS applies_to, i.item_name, i.unit, COALESCE(b.current_stock, 0) AS current_stock, i.unit_cost, i.is_active
                     FROM {$wpdb->prefix}banoks_product_recipes r
                     INNER JOIN {$wpdb->prefix}banoks_inventory_items i ON r.inventory_item_id = i.id
                     LEFT JOIN {$wpdb->prefix}banoks_inventory_balances b ON b.inventory_item_id = i.id AND b.location_key = %s
                     WHERE r.product_id = %d",
                    $location_key,
                    $product_id
                )
            );

            foreach ( $recipes as $recipe ) {
                if ( ! in_array( sanitize_key( $recipe->applies_to ), $allowed_conditions, true ) ) {
                    continue;
                }

                $inventory_item_id = absint( $recipe->inventory_item_id );
                $needed            = floatval( $recipe->quantity_used ) * intval( $product_quantity );

                if ( $needed <= 0 ) {
                    continue;
                }

                if ( ! isset( $requirements[ $inventory_item_id ] ) ) {
                    $requirements[ $inventory_item_id ] = array(
                        'inventory_item_id' => $inventory_item_id,
                        'item_name'         => $recipe->item_name,
                        'unit'              => $recipe->unit,
                        'current_stock'     => floatval( $recipe->current_stock ),
                        'unit_cost'         => floatval( $recipe->unit_cost ),
                        'is_active'         => intval( $recipe->is_active ),
                        'quantity_needed'   => 0,
                    );
                }

                $requirements[ $inventory_item_id ]['quantity_needed'] += $needed;
            }
        }

        return $requirements;
    }

    /**
     * Validate recipe inventory availability for order items.
     *
     * @since    1.0.11
     * @param    array $items Order items.
     * @param    string $recipe_context Recipe context.
     * @return   array
     */
    public function validate_recipe_inventory_for_items( $items, $recipe_context = 'all', $location_key = self::STOCK_LOCATION_MANUKAN ) {
        $requirements = $this->get_recipe_inventory_requirements( $this->normalize_order_item_quantities( $items ), $recipe_context, $location_key );

        foreach ( $requirements as $requirement ) {
            if ( ! $requirement['is_active'] ) {
                return array( 'error' => $requirement['item_name'] . ' is inactive in Stock Management.' );
            }

            if ( $requirement['current_stock'] < $requirement['quantity_needed'] ) {
                return array(
                    'error' => $requirement['item_name'] . ' has only ' . $this->format_inventory_quantity( $requirement['current_stock'] ) . ' ' . $requirement['unit'] . ' available, but needs ' . $this->format_inventory_quantity( $requirement['quantity_needed'] ) . ' ' . $requirement['unit'] . '.',
                );
            }
        }

        return array( 'success' => true );
    }

    /**
     * Get active inventory items that need attention.
     *
     * @since    1.0.12
     * @param    int $limit Maximum alerts to return. Use 0 for no limit.
     * @return   array
     */
    public function get_inventory_stock_alerts( $limit = 0, $location_key = self::STOCK_LOCATION_MANUKAN ) {
        global $wpdb;

        $location_key = $this->sanitize_stock_location_key( $location_key );
        $sql = $wpdb->prepare(
            "SELECT i.*, COALESCE(b.current_stock, 0) AS current_stock
                FROM {$wpdb->prefix}banoks_inventory_items i
                LEFT JOIN {$wpdb->prefix}banoks_inventory_balances b ON b.inventory_item_id = i.id AND b.location_key = %s
                WHERE i.is_active = 1
                AND (
                    COALESCE(b.current_stock, 0) <= 0
                    OR (
                        i.low_stock_threshold > 0
                        AND COALESCE(b.current_stock, 0) <= i.low_stock_threshold
                    )
                )
                ORDER BY
                    CASE WHEN COALESCE(b.current_stock, 0) <= 0 THEN 0 ELSE 1 END ASC,
                    i.item_name ASC",
            $location_key
        );

        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d', absint( $limit ) );
        }

        $alerts = $wpdb->get_results( $sql );
        foreach ( $alerts as $alert ) {
            $alert->alert_type      = floatval( $alert->current_stock ) <= 0 ? 'out' : 'low';
            $alert->formatted_stock = $this->format_inventory_quantity( $alert->current_stock ) . ' ' . $alert->unit;
            $alert->formatted_low   = $this->format_inventory_quantity( $alert->low_stock_threshold ) . ' ' . $alert->unit;
        }

        return $alerts ? $alerts : array();
    }

    /**
     * Get recipe coverage and one-serving readiness for products.
     *
     * @since    1.0.12
     * @param    array $product_ids Product IDs.
     * @return   array
     */
    public function get_product_recipe_statuses( $product_ids, $location_key = self::STOCK_LOCATION_MANUKAN ) {
        global $wpdb;

        $product_ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
        if ( empty( $product_ids ) ) {
            return array();
        }

        $statuses = array();
        foreach ( $product_ids as $product_id ) {
            $statuses[ $product_id ] = array(
                'has_recipe'      => false,
                'can_prepare'     => true,
                'available_stock' => null,
                'warnings'        => array(),
            );
        }

        $location_key  = $this->sanitize_stock_location_key( $location_key );
        $placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );
        $recipe_rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.product_id, r.quantity_used, COALESCE(r.applies_to, 'all') AS applies_to, i.item_name, i.unit, COALESCE(b.current_stock, 0) AS current_stock, i.is_active
                 FROM {$wpdb->prefix}banoks_product_recipes r
                 LEFT JOIN {$wpdb->prefix}banoks_inventory_items i ON r.inventory_item_id = i.id
                 LEFT JOIN {$wpdb->prefix}banoks_inventory_balances b ON b.inventory_item_id = i.id AND b.location_key = %s
                 WHERE r.product_id IN ({$placeholders})
                 ORDER BY r.product_id ASC, r.id ASC",
                array_merge( array( $location_key ), $product_ids )
            )
        );

        foreach ( $recipe_rows as $recipe ) {
            $product_id = absint( $recipe->product_id );
            if ( ! isset( $statuses[ $product_id ] ) ) {
                continue;
            }

            $statuses[ $product_id ]['has_recipe'] = true;
            $quantity_needed = floatval( $recipe->quantity_used );
            $current_stock   = null === $recipe->current_stock ? 0 : floatval( $recipe->current_stock );
            $item_name       = ! empty( $recipe->item_name ) ? $recipe->item_name : 'Deleted ingredient';
            $unit            = ! empty( $recipe->unit ) ? $recipe->unit : '';
            $condition_label = $this->get_recipe_condition_label( $recipe->applies_to );

            if ( empty( $recipe->is_active ) ) {
                $statuses[ $product_id ]['can_prepare'] = false;
                $statuses[ $product_id ]['warnings'][]  = $item_name . ' is inactive for ' . $condition_label . '.';
                $statuses[ $product_id ]['available_stock'] = 0;
                continue;
            }

            if ( $quantity_needed > 0 ) {
                $available_stock = floor( $current_stock / $quantity_needed );
                if ( null === $statuses[ $product_id ]['available_stock'] || $available_stock < $statuses[ $product_id ]['available_stock'] ) {
                    $statuses[ $product_id ]['available_stock'] = max( 0, intval( $available_stock ) );
                }
            }

            if ( $quantity_needed > 0 && $current_stock < $quantity_needed ) {
                $statuses[ $product_id ]['can_prepare'] = false;
                $statuses[ $product_id ]['warnings'][]  = $condition_label . ': ' . $item_name . ' needs ' . $this->format_inventory_quantity( $quantity_needed ) . ' ' . $unit . ', has ' . $this->format_inventory_quantity( $current_stock ) . ' ' . $unit . '.';
            }
        }

        return $statuses;
    }

    /**
     * Get recipe condition keys for an order context.
     *
     * @since    1.0.12
     * @param    string $recipe_context Context key.
     * @return   array
     */
    private function get_recipe_conditions_for_context( $recipe_context ) {
        $recipe_context = sanitize_key( $recipe_context );

        if ( 'walk_in' === $recipe_context ) {
            return array( 'all', 'walk_in' );
        }

        if ( 'delivery' === $recipe_context ) {
            return array( 'all', 'online', 'delivery' );
        }

        if ( 'pickup' === $recipe_context ) {
            return array( 'all', 'online', 'pickup' );
        }

        if ( 'online' === $recipe_context ) {
            return array( 'all', 'online' );
        }

        return array( 'all' );
    }

    /**
     * Infer recipe context from an order source.
     *
     * @since    1.0.12
     * @param    string $source Source key.
     * @return   string
     */
    private function get_recipe_context_from_source( $source ) {
        $source = sanitize_key( $source );

        if ( 'walk_in' === $source ) {
            return 'walk_in';
        }

        if ( 'online_delivery' === $source ) {
            return 'delivery';
        }

        if ( 'online_pickup' === $source ) {
            return 'pickup';
        }

        if ( 'online' === $source ) {
            return 'online';
        }

        return 'all';
    }

    /**
     * Get a human label for a recipe condition.
     *
     * @since    1.0.12
     * @param    string $condition Condition key.
     * @return   string
     */
    private function get_recipe_condition_label( $condition ) {
        $labels = array(
            'all'      => 'All orders',
            'walk_in'  => 'Walk-in only',
            'online'   => 'Online only',
            'delivery' => 'Delivery only',
            'pickup'   => 'Pickup only',
        );

        $condition = sanitize_key( $condition );
        return isset( $labels[ $condition ] ) ? $labels[ $condition ] : $labels['all'];
    }

    /**
     * Deduct inventory ingredients based on product recipes.
     *
     * @since    1.0.11
     * @param    array  $product_quantities Product quantities.
     * @param    string $source Source type.
     * @param    string $source_id Source/order ID.
     * @return   array
     */
    private function deduct_recipe_inventory_for_items( $product_quantities, $source, $source_id = '', $recipe_context = 'all', $location_key = self::STOCK_LOCATION_MANUKAN ) {
        global $wpdb;

        $location_key  = $this->sanitize_stock_location_key( $location_key );
        $requirements = $this->get_recipe_inventory_requirements( $product_quantities, $recipe_context, $location_key );

        foreach ( $requirements as $requirement ) {
            if ( ! $requirement['is_active'] ) {
                return array( 'error' => $requirement['item_name'] . ' is inactive in Stock Management.' );
            }

            if ( $requirement['current_stock'] < $requirement['quantity_needed'] ) {
                return array( 'error' => $requirement['item_name'] . ' does not have enough ingredient stock.' );
            }
        }

        foreach ( $requirements as $requirement ) {
            $old_stock = floatval( $requirement['current_stock'] );
            $new_stock = max( 0, $old_stock - floatval( $requirement['quantity_needed'] ) );
            $updated   = $wpdb->update(
                $wpdb->prefix . 'banoks_inventory_balances',
                array(
                    'current_stock' => $new_stock,
                    'updated_at'    => current_time( 'mysql' ),
                ),
                array(
                    'inventory_item_id' => $requirement['inventory_item_id'],
                    'location_key'       => $location_key,
                    'current_stock' => $old_stock,
                ),
                array( '%f', '%s' ),
                array( '%d', '%s', '%f' )
            );

            if ( false === $updated || 0 === $updated ) {
                return array( 'error' => 'Could not deduct ingredient stock. Please try again.' );
            }

            $this->create_inventory_movement(
                $requirement['inventory_item_id'],
                $location_key,
                $old_stock,
                $new_stock,
                'recipe_usage',
                $source,
                $source_id,
                'Ingredient stock deducted from order.',
                floatval( $requirement['unit_cost'] )
            );
        }

        return array( 'success' => true );
    }

    /**
     * Record an inventory movement.
     *
     * @since    1.0.11
     * @param    int    $inventory_item_id Inventory item ID.
     * @param    float  $old_stock Old stock.
     * @param    float  $new_stock New stock.
     * @param    string $movement_type Movement type.
     * @param    string $source Source.
     * @param    string $source_id Source ID.
     * @param    string $note Note.
     * @return   bool
     */
    public function create_inventory_movement( $inventory_item_id, $location_key, $old_stock, $new_stock, $movement_type, $source = 'manual', $source_id = '', $note = '', $unit_cost = 0, $affects_cash_balance = 0, $cash_source = 'store_cash' ) {
        global $wpdb;

        $location_key  = $this->sanitize_stock_location_key( $location_key );
        $change_amount = floatval( $new_stock ) - floatval( $old_stock );
        $unit_cost     = max( 0, floatval( $unit_cost ) );
        $total_cost    = abs( $change_amount ) * $unit_cost;
        $affects_cash_balance = $affects_cash_balance && $change_amount > 0 && in_array( sanitize_key( $movement_type ), array( 'stock_in', 'correction' ), true ) ? 1 : 0;
        $cash_source = sanitize_key( $cash_source );
        $cash_source = '' !== $cash_source ? $cash_source : 'store_cash';

        return false !== $wpdb->insert(
            $wpdb->prefix . 'banoks_inventory_movements',
            array(
                'inventory_item_id' => absint( $inventory_item_id ),
                'location_key'      => $location_key,
                'movement_type'     => sanitize_key( $movement_type ),
                'old_stock'         => floatval( $old_stock ),
                'new_stock'         => floatval( $new_stock ),
                'change_amount'     => $change_amount,
                'unit_cost'         => $unit_cost,
                'total_cost'        => $total_cost,
                'affects_cash_balance' => $affects_cash_balance,
                'cash_source'       => $cash_source,
                'source'            => sanitize_key( $source ),
                'source_id'         => sanitize_text_field( $source_id ),
                'updated_by'        => get_current_user_id(),
                'note'              => sanitize_textarea_field( $note ),
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    public function sanitize_stock_location_key( $location_key ) {
        $location_key = sanitize_key( $location_key );
        return in_array( $location_key, array( self::STOCK_LOCATION_PRODUCTION, self::STOCK_LOCATION_MANUKAN ), true ) ? $location_key : self::STOCK_LOCATION_MANUKAN;
    }

    /**
     * Format inventory quantity for messages.
     *
     * @since    1.0.11
     * @param    float $quantity Quantity.
     * @return   string
     */
    private function format_inventory_quantity( $quantity ) {
        return rtrim( rtrim( number_format( floatval( $quantity ), 3, '.', '' ), '0' ), '.' );
    }

    /**
     * Get the next visible order number.
     *
     * @since    1.0.0
     * @return   int
     */
    public function get_next_order_id() {
        global $wpdb;

        $last_id = $wpdb->get_var( "SELECT MAX(order_id) FROM {$wpdb->prefix}banoks_orders" );

        return $last_id ? intval( $last_id ) + 1 : 1;
    }

    /**
     * Get completed sales total for a date.
     *
     * @since    1.0.0
     * @param    string $date Date in Y-m-d format.
     * @return   float
     */
    public function get_sales_for_date( $date ) {
        global $wpdb;

        $sales = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(grand_total) FROM {$wpdb->prefix}banoks_orders WHERE date = %s AND status = 'completed'",
                $date
            )
        );

        return $sales ? floatval( $sales ) : 0;
    }

    /**
     * Build the data needed by the POS interface.
     *
     * @since    1.0.0
     * @param    array $args Optional display arguments.
     * @return   array
     */
    public function get_pos_data( $args = array() ) {
        $active_date = isset( $args['active_date'] ) ? sanitize_text_field( $args['active_date'] ) : current_time( 'Y-m-d' );
        $today       = current_time( 'Y-m-d' );
        $current_user = wp_get_current_user();

        return array(
            'products'      => $this->get_products(),
            'categories'    => $this->get_categories(),
            'active_date'   => $active_date,
            'next_id'       => $this->get_next_order_id(),
            'sales'         => $this->get_sales_for_date( $today ),
            'cashier_name'  => ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login,
        );
    }

    /**
     * Get the accepted online order statuses.
     *
     * @since    1.0.7
     * @return   array
     */
    public function get_online_order_statuses() {
        return array(
            self::ONLINE_STATUS_PENDING,
            self::ONLINE_STATUS_PREPARING,
            self::ONLINE_STATUS_READY_FOR_PICKUP,
            self::ONLINE_STATUS_DELIVERING,
            self::ONLINE_STATUS_COMPLETED,
            self::ONLINE_STATUS_CANCELLED,
            self::ONLINE_STATUS_REJECTED,
        );
    }

    /**
     * Generate the next public customer ID.
     *
     * @since    1.0.7
     * @return   string
     */
    public function generate_customer_public_id() {
        global $wpdb;

        $last_id = $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}banoks_customers" );

        return sprintf( 'USER-%06d', $last_id ? intval( $last_id ) + 1 : 1 );
    }

    /**
     * Generate the next public online order ID for today.
     *
     * @since    1.0.7
     * @return   string
     */
    public function generate_online_order_public_id() {
        global $wpdb;

        $date       = current_time( 'Ymd' );
        $prefix     = 'ONL-' . $date . '-';
        $like       = $wpdb->esc_like( $prefix ) . '%';
        $last_order = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT online_order_id FROM {$wpdb->prefix}banoks_online_orders WHERE online_order_id LIKE %s ORDER BY id DESC LIMIT 1",
                $like
            )
        );

        $next = 1;
        if ( $last_order && preg_match( '/-(\d+)$/', $last_order, $matches ) ) {
            $next = intval( $matches[1] ) + 1;
        }

        return $prefix . sprintf( '%04d', $next );
    }

    /**
     * Create a customer record for testing online orders.
     *
     * @since    1.0.7
     * @param    array $data Customer data.
     * @return   array
     */
    public function create_customer( $data ) {
        global $wpdb;

        $full_name = isset( $data['full_name'] ) ? sanitize_text_field( $data['full_name'] ) : '';
        $phone     = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
        $email     = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $address   = isset( $data['address'] ) ? sanitize_textarea_field( $data['address'] ) : '';
        $password  = isset( $data['password'] ) ? (string) $data['password'] : '';

        if ( '' === $full_name || '' === $phone ) {
            return array( 'error' => 'Customer name and contact number are required.' );
        }

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'banoks_customers',
            array(
                'customer_id'      => $this->generate_customer_public_id(),
                'full_name'        => $full_name,
                'phone'            => $phone,
                'email'            => $email,
                'password_hash'    => '' !== $password ? wp_hash_password( $password ) : '',
                'address'          => $address,
                'delivery_area_id' => isset( $data['delivery_area_id'] ) ? absint( $data['delivery_area_id'] ) : 0,
                'created_at'       => current_time( 'mysql' ),
                'updated_at'       => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return array( 'error' => 'Could not create customer.' );
        }

        return $this->get_customer( $wpdb->insert_id );
    }

    /**
     * Get a customer by email or phone.
     *
     * @since    1.0.8
     * @param    string $identifier Email address or phone number.
     * @return   object|null
     */
    public function get_customer_by_identifier( $identifier ) {
        global $wpdb;

        $identifier = sanitize_text_field( $identifier );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_customers WHERE email = %s OR phone = %s LIMIT 1",
                $identifier,
                $identifier
            )
        );
    }

    /**
     * Get an online order with its items.
     *
     * @since    1.0.8
     * @param    int $order_id Online order internal ID.
     * @return   array|null
     */
    public function get_online_order_with_items( $order_id ) {
        global $wpdb;

        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_orders WHERE id = %d",
                absint( $order_id )
            )
        );

        if ( ! $order ) {
            return null;
        }

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_order_items WHERE online_order_id = %d ORDER BY id ASC",
                absint( $order_id )
            )
        );

        return array(
            'order' => $order,
            'items' => $items,
        );
    }

    /**
     * Get online orders for a customer.
     *
     * @since    1.0.8
     * @param    int $customer_id Customer internal ID.
     * @return   array
     */
    public function get_customer_online_orders( $customer_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_orders WHERE customer_id = %d ORDER BY created_at DESC LIMIT 50",
                absint( $customer_id )
            )
        );
    }

    /**
     * Get a customer by internal ID.
     *
     * @since    1.0.7
     * @param    int $customer_id Internal customer ID.
     * @return   object|null
     */
    public function get_customer( $customer_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_customers WHERE id = %d",
                absint( $customer_id )
            )
        );
    }

    /**
     * Create a delivery area.
     *
     * @since    1.0.7
     * @param    array $data Delivery area data.
     * @return   bool
     */
    public function create_delivery_area( $data ) {
        global $wpdb;

        $area_name = isset( $data['area_name'] ) ? sanitize_text_field( $data['area_name'] ) : '';

        if ( '' === $area_name ) {
            return false;
        }

        return false !== $wpdb->insert(
            $wpdb->prefix . 'banoks_delivery_areas',
            array(
                'area_name'      => $area_name,
                'is_deliverable' => ! empty( $data['is_deliverable'] ) ? 1 : 0,
                'delivery_fee'   => isset( $data['delivery_fee'] ) ? floatval( $data['delivery_fee'] ) : 0,
                'sort_order'     => isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : 0,
                'created_at'     => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%f', '%d', '%s', '%s' )
        );
    }

    /**
     * Get delivery areas.
     *
     * @since    1.0.7
     * @return   array
     */
    public function get_delivery_areas() {
        global $wpdb;

        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_delivery_areas ORDER BY sort_order ASC, area_name ASC" );
    }

    /**
     * Get recent online orders.
     *
     * @since    1.0.7
     * @return   array
     */
    public function get_recent_online_orders() {
        global $wpdb;

        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}banoks_online_orders ORDER BY created_at DESC LIMIT 100" );
    }

    /**
     * Count online orders that need cashier attention.
     *
     * @since    1.0.9
     * @return   int
     */
    public function count_pending_online_orders() {
        global $wpdb;

        return intval(
            $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}banoks_online_orders WHERE order_status IN ('pending', 'verifying')"
            )
        );
    }

    /**
     * Count walk-in orders that still need cashier attention.
     *
     * @since    1.0.13
     * @return   int
     */
    public function count_active_walk_in_orders() {
        global $wpdb;

        return intval(
            $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}banoks_orders WHERE status IN ('pending', 'preparing')"
            )
        );
    }

    /**
     * Get online orders that need cashier notification.
     *
     * @since    1.0.9
     * @return   array
     */
    public function get_online_order_notifications() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, online_order_id, customer_name, total_amount, fulfillment_type, order_status, created_at
             FROM {$wpdb->prefix}banoks_online_orders
             WHERE order_status IN ('pending', 'verifying')
             ORDER BY created_at DESC
             LIMIT 20"
        );
    }

    /**
     * Get related rows for many online orders.
     *
     * @since    1.0.9
     * @param    array $orders Orders.
     * @return   array
     */
    public function get_online_order_related_data( $orders ) {
        global $wpdb;

        $ids = array();
        foreach ( $orders as $order ) {
            $ids[] = absint( $order->id );
        }

        if ( empty( $ids ) ) {
            return array(
                'items'          => array(),
                'proofs'         => array(),
                'logs'           => array(),
                'stock_warnings' => array(),
            );
        }

        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
        $items_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_order_items WHERE online_order_id IN ({$placeholders}) ORDER BY id ASC",
                $ids
            )
        );
        $proof_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_payment_proofs WHERE online_order_id IN ({$placeholders}) ORDER BY id DESC",
                $ids
            )
        );
        $log_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_order_status_logs WHERE online_order_id IN ({$placeholders}) ORDER BY created_at ASC",
                $ids
            )
        );

        return array(
            'items'          => $this->group_rows_by_order_id( $items_rows ),
            'proofs'         => $this->group_rows_by_order_id( $proof_rows ),
            'logs'           => $this->group_rows_by_order_id( $log_rows ),
            'stock_warnings' => $this->get_online_order_stock_warnings( $orders, $items_rows ),
        );
    }

    /**
     * Build stock warnings for online orders before they move to preparing.
     *
     * @since    1.0.12
     * @param    array $orders Online orders.
     * @param    array $items_rows Online order item rows.
     * @return   array
     */
    private function get_online_order_stock_warnings( $orders, $items_rows ) {
        global $wpdb;

        $warnings       = array();
        $orders_by_id   = array();
        $items_by_order = array();
        $product_ids    = array();

        foreach ( $orders as $order ) {
            $orders_by_id[ absint( $order->id ) ] = $order;
        }

        foreach ( $items_rows as $item ) {
            $order_id = absint( $item->online_order_id );
            if ( ! isset( $items_by_order[ $order_id ] ) ) {
                $items_by_order[ $order_id ] = array();
            }
            $items_by_order[ $order_id ][] = $item;
            $product_ids[] = absint( $item->product_id );
        }

        $product_ids = array_values( array_unique( array_filter( $product_ids ) ) );
        $products    = array();
        if ( ! empty( $product_ids ) ) {
            $placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );
            $products     = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT product_id, product_name
                     FROM {$wpdb->prefix}banoks_items
                     WHERE product_id IN ({$placeholders})",
                    $product_ids
                ),
                OBJECT_K
            );
        }

        foreach ( $items_by_order as $order_id => $items ) {
            $order = isset( $orders_by_id[ $order_id ] ) ? $orders_by_id[ $order_id ] : null;
            if ( ! $order || ! in_array( $order->order_status, array( self::ONLINE_STATUS_PENDING, self::ONLINE_STATUS_VERIFYING ), true ) ) {
                continue;
            }

            $order_warnings = array();
            $quantities     = $this->normalize_order_item_quantities( $items );

            foreach ( $quantities as $product_id => $quantity ) {
                if ( empty( $products[ $product_id ] ) ) {
                    $order_warnings[] = 'A product in this order no longer exists.';
                    continue;
                }

            }

            $recipe_context = 'pickup' === $order->fulfillment_type ? 'pickup' : 'delivery';
            $recipe_result  = $this->validate_recipe_inventory_for_items( $items, $recipe_context );
            if ( isset( $recipe_result['error'] ) ) {
                $order_warnings[] = $recipe_result['error'];
            }

            if ( ! empty( $order_warnings ) ) {
                $warnings[ $order_id ] = array_values( array_unique( $order_warnings ) );
            }
        }

        return $warnings;
    }

    /**
     * Update online order status with transition validation.
     *
     * @since    1.0.9
     * @param    int    $order_id Online order internal ID.
     * @param    string $new_status New status.
     * @param    array  $data Extra data.
     * @return   array
     */
    public function update_online_order_status( $order_id, $new_status, $data = array() ) {
        global $wpdb;

        $new_status = sanitize_key( $new_status );
        $order      = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_orders WHERE id = %d",
                absint( $order_id )
            )
        );

        if ( ! $order ) {
            return array( 'error' => 'Online order not found.' );
        }

        $fulfillment_type = ! empty( $order->fulfillment_type ) ? sanitize_key( $order->fulfillment_type ) : 'delivery';
        $allowed = array(
            self::ONLINE_STATUS_PENDING   => array( self::ONLINE_STATUS_PREPARING, self::ONLINE_STATUS_CANCELLED ),
            self::ONLINE_STATUS_VERIFYING => array( self::ONLINE_STATUS_PREPARING, self::ONLINE_STATUS_CANCELLED ),
        );

        if ( 'pickup' === $fulfillment_type ) {
            $allowed[ self::ONLINE_STATUS_PREPARING ] = array( self::ONLINE_STATUS_READY_FOR_PICKUP, self::ONLINE_STATUS_CANCELLED );
            $allowed[ self::ONLINE_STATUS_READY_FOR_PICKUP ] = array( self::ONLINE_STATUS_COMPLETED );
        } else {
            $allowed[ self::ONLINE_STATUS_PREPARING ] = array( self::ONLINE_STATUS_DELIVERING, self::ONLINE_STATUS_CANCELLED );
            $allowed[ self::ONLINE_STATUS_DELIVERING ] = array( self::ONLINE_STATUS_COMPLETED );
        }

        if ( empty( $allowed[ $order->order_status ] ) || ! in_array( $new_status, $allowed[ $order->order_status ], true ) ) {
            return array( 'error' => 'Invalid status movement.' );
        }

        if ( self::ONLINE_STATUS_CANCELLED === $new_status ) {
            $reason = isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '';
            if ( '' === $reason ) {
                return array( 'error' => 'Please enter a cancellation reason.' );
            }
        }

        if ( 'gcash' === $order->payment_method && self::ONLINE_STATUS_PREPARING === $new_status && 'paid' !== $order->payment_status ) {
            return array( 'error' => 'Please verify the GCash payment proof before preparing this order.' );
        }

        $restores_stock = self::ONLINE_STATUS_CANCELLED === $new_status && self::ONLINE_STATUS_PREPARING === $order->order_status;
        $uses_stock_transaction = self::ONLINE_STATUS_PREPARING === $new_status || $restores_stock;
        if ( $uses_stock_transaction ) {
            $wpdb->query( 'START TRANSACTION' );
        }

        if ( self::ONLINE_STATUS_PREPARING === $new_status ) {
            $order_items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT product_id, quantity FROM {$wpdb->prefix}banoks_online_order_items WHERE online_order_id = %d",
                    absint( $order_id )
                )
            );
            $recipe_context = 'pickup' === $fulfillment_type ? 'pickup' : 'delivery';
            $stock_source   = 'pickup' === $fulfillment_type ? 'online_pickup' : 'online_delivery';
            $stock_result = $this->deduct_stock_for_items( $order_items, $stock_source, $order->online_order_id, $recipe_context );
            if ( isset( $stock_result['error'] ) ) {
                $wpdb->query( 'ROLLBACK' );
                return $stock_result;
            }
        }

        if ( $restores_stock ) {
            $stock_source = 'pickup' === $fulfillment_type ? 'online_pickup' : 'online_delivery';
            $stock_result = $this->restore_stock_for_source( $stock_source, $order->online_order_id );
            if ( isset( $stock_result['error'] ) ) {
                $wpdb->query( 'ROLLBACK' );
                return $stock_result;
            }
        }

        $update = array(
            'order_status' => $new_status,
            'updated_at'   => current_time( 'mysql' ),
        );

        if ( self::ONLINE_STATUS_DELIVERING === $new_status ) {
            $driver_name    = isset( $data['driver_name'] ) ? sanitize_text_field( $data['driver_name'] ) : '';
            $driver_contact = isset( $data['driver_contact'] ) ? sanitize_text_field( $data['driver_contact'] ) : '';

            if ( '' === $driver_name || '' === $driver_contact ) {
                return array( 'error' => 'Driver name and contact are required before delivering.' );
            }

            $update['driver_name']    = $driver_name;
            $update['driver_contact'] = $driver_contact;
        }

        if ( self::ONLINE_STATUS_COMPLETED === $new_status ) {
            $update['completed_at'] = current_time( 'mysql' );
        }

        if ( self::ONLINE_STATUS_CANCELLED === $new_status ) {
            $update['cancelled_at'] = current_time( 'mysql' );
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'banoks_online_orders',
            $update,
            array( 'id' => absint( $order_id ), 'order_status' => $order->order_status )
        );

        if ( false === $updated || 0 === $updated ) {
            if ( $uses_stock_transaction ) {
                $wpdb->query( 'ROLLBACK' );
            }
            return array( 'error' => 'Could not update online order status.' );
        }

        $this->create_online_order_status_log(
            $order_id,
            $order->order_status,
            $new_status,
            isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : ''
        );

        if ( $uses_stock_transaction ) {
            $wpdb->query( 'COMMIT' );
        }

        return array( 'success' => true );
    }

    /**
     * Update GCash payment proof status.
     *
     * @since    1.0.9
     * @param    int    $proof_id Proof ID.
     * @param    string $status New payment proof status.
     * @param    string $reason Rejection reason.
     * @return   array
     */
    public function update_payment_proof_status( $proof_id, $status, $reason = '' ) {
        global $wpdb;

        $status = sanitize_key( $status );
        if ( ! in_array( $status, array( 'verified', 'rejected' ), true ) ) {
            return array( 'error' => 'Invalid payment proof status.' );
        }

        $proof = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_payment_proofs WHERE id = %d",
                absint( $proof_id )
            )
        );

        if ( ! $proof ) {
            return array( 'error' => 'Payment proof not found.' );
        }

        $reason = sanitize_textarea_field( $reason );
        if ( 'rejected' === $status && '' === $reason ) {
            return array( 'error' => 'Please enter a rejection reason.' );
        }

        $payment_status = 'verified' === $status ? 'paid' : 'rejected';
        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}banoks_online_orders WHERE id = %d",
                absint( $proof->online_order_id )
            )
        );

        if ( ! $order ) {
            return array( 'error' => 'Online order not found.' );
        }

        if ( 'pending' !== $proof->status || 'pending_verification' !== $order->payment_status || ! in_array( $order->order_status, array( self::ONLINE_STATUS_PENDING, self::ONLINE_STATUS_VERIFYING ), true ) ) {
            return array( 'error' => 'This payment proof has already been decided or the order has already moved forward.' );
        }

        $wpdb->update(
            $wpdb->prefix . 'banoks_payment_proofs',
            array(
                'status'      => $status,
                'verified_by' => get_current_user_id(),
                'verified_at' => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $proof_id ) )
        );

        $order_update = array(
            'payment_status' => $payment_status,
            'updated_at'     => current_time( 'mysql' ),
        );

        if ( 'rejected' === $status ) {
            $order_update['order_status'] = self::ONLINE_STATUS_REJECTED;
            $order_update['cancelled_at'] = current_time( 'mysql' );
        }

        $wpdb->update(
            $wpdb->prefix . 'banoks_online_orders',
            $order_update,
            array( 'id' => absint( $proof->online_order_id ) )
        );

        if ( 'rejected' === $status ) {
            $this->create_online_order_status_log(
                $proof->online_order_id,
                $order->order_status,
                self::ONLINE_STATUS_REJECTED,
                'GCash payment rejected: ' . $reason
            );
        }

        return array( 'success' => true );
    }

    /**
     * Create an online order and related rows.
     *
     * @since    1.0.7
     * @param    array $data Order data.
     * @return   array
     */
    public function create_online_order( $data ) {
        global $wpdb;

        $customer = ! empty( $data['customer_id'] ) ? $this->get_customer( absint( $data['customer_id'] ) ) : null;

        if ( ! $customer && ! empty( $data['new_customer'] ) ) {
            $customer = $this->create_customer( $data['new_customer'] );
            if ( is_array( $customer ) && isset( $customer['error'] ) ) {
                return $customer;
            }
        }

        if ( empty( $customer ) ) {
            return array( 'error' => 'Please select or create a customer.' );
        }

        $fulfillment_type = isset( $data['fulfillment_type'] ) ? sanitize_key( $data['fulfillment_type'] ) : 'delivery';
        if ( ! in_array( $fulfillment_type, array( 'delivery', 'pickup' ), true ) ) {
            return array( 'error' => 'Invalid fulfillment type.' );
        }

        $delivery_area      = null;
        $delivery_area_id   = 0;
        $delivery_area_name = '';
        $delivery_address   = '';
        $delivery_fee       = 0;

        if ( 'delivery' === $fulfillment_type ) {
            $delivery_area_id = isset( $data['delivery_area_id'] ) ? absint( $data['delivery_area_id'] ) : 0;
            $delivery_area    = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}banoks_delivery_areas WHERE id = %d",
                    $delivery_area_id
                )
            );

            if ( ! $delivery_area || ! intval( $delivery_area->is_deliverable ) ) {
                return array( 'error' => 'Please select a deliverable area.' );
            }

            $delivery_area_id   = intval( $delivery_area->id );
            $delivery_area_name = $delivery_area->area_name;
            $delivery_address   = isset( $data['delivery_address'] ) ? sanitize_textarea_field( $data['delivery_address'] ) : $customer->address;
            $delivery_fee       = floatval( $delivery_area->delivery_fee );
        }

        $cart_quantities = array();
        $items           = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

        foreach ( $items as $product_id => $quantity ) {
            $product_id = absint( $product_id );
            $quantity   = absint( $quantity );

            if ( $product_id && $quantity ) {
                $cart_quantities[ $product_id ] = $quantity;
            }
        }

        if ( empty( $cart_quantities ) ) {
            return array( 'error' => 'Please add at least one product.' );
        }

        $product_ids  = array_keys( $cart_quantities );
        $placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );
        $products     = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id, product_name, current_price, COALESCE(is_available, 1) AS is_available, COALESCE(is_active, 1) AS is_active
                 FROM {$wpdb->prefix}banoks_items
                 WHERE product_id IN ({$placeholders})",
                $product_ids
            ),
            OBJECT_K
        );

        if ( count( $products ) !== count( $product_ids ) ) {
            return array( 'error' => 'One or more products could not be found.' );
        }

        $validated_items = array();
        $subtotal        = 0;

        foreach ( $cart_quantities as $product_id => $quantity ) {
            $product = $products[ $product_id ];

            if ( ! intval( $product->is_active ) || ! intval( $product->is_available ) ) {
                return array( 'error' => $product->product_name . ' is currently unavailable.' );
            }

            $price      = floatval( $product->current_price );
            $line_total = $price * $quantity;
            $subtotal  += $line_total;

            $validated_items[] = array(
                'product_id'   => $product_id,
                'product_name' => $product->product_name,
                'quantity'     => $quantity,
                'price'        => $price,
                'subtotal'     => $line_total,
            );
        }

        $recipe_context = 'pickup' === $fulfillment_type ? 'pickup' : 'delivery';
        $recipe_result = $this->validate_recipe_inventory_for_items( $validated_items, $recipe_context );
        if ( isset( $recipe_result['error'] ) ) {
            return $recipe_result;
        }

        $payment_method          = isset( $data['payment_method'] ) ? sanitize_key( $data['payment_method'] ) : 'cod';
        $allowed_payment_methods = 'pickup' === $fulfillment_type ? array( 'pay_at_pickup', 'gcash' ) : array( 'cod', 'gcash' );
        if ( ! in_array( $payment_method, $allowed_payment_methods, true ) ) {
            return array( 'error' => 'Invalid payment method.' );
        }

        $payment_status = 'gcash' === $payment_method ? 'pending_verification' : 'unpaid';
        $total_amount   = $subtotal + $delivery_fee;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'banoks_online_orders',
            array(
                'online_order_id'    => $this->generate_online_order_public_id(),
                'branch_key'         => 'manukan_branch',
                'customer_id'        => intval( $customer->id ),
                'customer_public_id' => $customer->customer_id,
                'customer_name'      => $customer->full_name,
                'customer_phone'     => $customer->phone,
                'delivery_address'   => $delivery_address,
                'delivery_area_id'   => $delivery_area_id,
                'delivery_area_name' => $delivery_area_name,
                'fulfillment_type'   => $fulfillment_type,
                'payment_method'     => $payment_method,
                'payment_status'     => $payment_status,
                'order_status'       => self::ONLINE_STATUS_PENDING,
                'subtotal'           => $subtotal,
                'delivery_fee'       => $delivery_fee,
                'total_amount'       => $total_amount,
                'notes'              => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
                'created_at'         => current_time( 'mysql' ),
                'updated_at'         => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return array( 'error' => 'Could not create online order.' );
        }

        $online_order_id = $wpdb->insert_id;
        foreach ( $validated_items as $item ) {
            $wpdb->insert(
                $wpdb->prefix . 'banoks_online_order_items',
                array(
                    'online_order_id' => $online_order_id,
                    'product_id'      => $item['product_id'],
                    'product_name'    => $item['product_name'],
                    'quantity'        => $item['quantity'],
                    'price'           => $item['price'],
                    'subtotal'        => $item['subtotal'],
                ),
                array( '%d', '%d', '%s', '%d', '%f', '%f' )
            );
        }

        $this->create_online_order_status_log( $online_order_id, '', self::ONLINE_STATUS_PENDING, 'Online order created.' );

        if ( 'gcash' === $payment_method ) {
            $wpdb->insert(
                $wpdb->prefix . 'banoks_payment_proofs',
                array(
                    'online_order_id'  => $online_order_id,
                    'reference_number' => '',
                    'screenshot_url'   => isset( $data['payment_screenshot_url'] ) ? esc_url_raw( $data['payment_screenshot_url'] ) : '',
                    'attachment_id'    => isset( $data['payment_attachment_id'] ) ? absint( $data['payment_attachment_id'] ) : 0,
                    'status'           => 'pending',
                    'created_at'       => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s', '%d', '%s', '%s' )
            );
        }

        return array(
            'success'         => true,
            'online_order_id' => $online_order_id,
        );
    }

    /**
     * Create a status log row.
     *
     * @since    1.0.7
     * @param    int    $online_order_id Online order internal ID.
     * @param    string $old_status Old status.
     * @param    string $new_status New status.
     * @param    string $note Note.
     * @return   bool
     */
    public function create_online_order_status_log( $online_order_id, $old_status, $new_status, $note = '' ) {
        global $wpdb;

        return false !== $wpdb->insert(
            $wpdb->prefix . 'banoks_online_order_status_logs',
            array(
                'online_order_id' => absint( $online_order_id ),
                'old_status'      => sanitize_key( $old_status ),
                'new_status'      => sanitize_key( $new_status ),
                'updated_by'      => get_current_user_id(),
                'note'            => sanitize_textarea_field( $note ),
                'created_at'      => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s' )
        );
    }

    /**
     * Group database rows by online order ID.
     *
     * @since    1.0.9
     * @param    array $rows Rows.
     * @return   array
     */
    private function group_rows_by_order_id( $rows ) {
        $grouped = array();

        foreach ( $rows as $row ) {
            $order_id = intval( $row->online_order_id );
            if ( ! isset( $grouped[ $order_id ] ) ) {
                $grouped[ $order_id ] = array();
            }
            $grouped[ $order_id ][] = $row;
        }

        return $grouped;
    }
}

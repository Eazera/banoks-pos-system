<?php
/**
 * Cashier dashboard view.
 *
 * @link       https://banoks.com
 * @since      1.0.0
 * @package    Banoks_POS
 * @subpackage Banoks_POS/admin/partials
 * @author     Christian Fulache
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$this->display_admin_header();
?>

<div class="wrap banoks-pos-admin banoks-pos-page banoks-dashboard">
    <div class="products-header">
        <div class="header-info">
            <h1>Cashier</h1>
            <p>Daily cash flow, branch stock warnings, and walk-in order queue.</p>
        </div>
    </div>
    <div class="banoks-stats-grid">
        <div class="stat-card">
            <h3>Today's Walk-in Sales</h3>
            <p class="amount">&#8369;<?php echo esc_html( number_format( $total_sales, 2 ) ); ?></p>
        </div>
        <div class="stat-card">
            <h3>Today's General Expenses</h3>
            <p class="amount">&#8369;<?php echo esc_html( number_format( $total_expenses, 2 ) ); ?></p>
        </div>
        <div class="stat-card highlighted">
            <h3>Today's Walk-in Final Sale</h3>
            <p class="amount">&#8369;<?php echo esc_html( number_format( $final_sale, 2 ) ); ?></p>
        </div>
    </div>

    <?php if ( ! empty( $critical_inventory_alerts ) ) : ?>
        <div class="banoks-stock-alert-panel banoks-dashboard-stock-alert">
            <div>
                <h2>Critical Ingredient Alerts</h2>
                <p>These Manukan Branch inventory items need attention before more orders are prepared.</p>
            </div>
            <div class="banoks-stock-alert-list">
                <?php foreach ( $critical_inventory_alerts as $alert ) : ?>
                    <span class="banoks-stock-alert-chip <?php echo 'out' === $alert->alert_type ? 'is-out' : 'is-low'; ?>">
                        <strong><?php echo esc_html( $alert->item_name ); ?></strong>
                        <?php echo esc_html( $alert->formatted_stock ); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="banoks-dashboard-actions">
        <div class="filter-buttons">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=banoks-pos&view=pending' ) ); ?>" class="button <?php echo ( 'pending' === $view ) ? 'button-primary ' : ''; ?>">Active Queue</a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=banoks-pos&view=history&status=all' ) ); ?>" class="button <?php echo ( 'history' === $view ) ? 'button-primary' : ''; ?>">Order History</a>
        </div>

        <?php if ( 'history' === $view ) : ?>
            <div class="search-box">
                <input type="text" id="order-search" value="BNK-ORD-" placeholder="Search Order ID">
            </div>

            <div class="status-filter">
                <select id="banoks-order-status-filter">
                    <option value="all" <?php selected( $status_filter, 'all' ); ?>>All History</option>
                    <option value="preparing" <?php selected( $status_filter, 'preparing' ); ?>>Preparing</option>
                    <option value="completed" <?php selected( $status_filter, 'completed' ); ?>>Completed</option>
                    <option value="cancelled" <?php selected( $status_filter, 'cancelled' ); ?>>Cancelled</option>
                </select>
            </div>

            <form method="get" class="date-filter-form" style="display:inline;">
                <input type="hidden" name="page" value="banoks-pos">
                <?php if ( ! empty( $view ) ) : ?><input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>"><?php endif; ?>
                <?php if ( ! empty( $status_filter ) ) : ?><input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>"><?php endif; ?>
                <div class="date-picker-wrap">
                    <input type="date" name="date" id="banoks-dashboard-date" value="<?php echo esc_attr( $active_date ); ?>" onchange="this.form.submit()">
                    <?php if ( $has_date_param ) : ?><a href="<?php echo esc_url( remove_query_arg( 'date' ) ); ?>" class="clear-filter" title="Clear Filter">&times;</a><?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=banoks-pos-pos&date=' . rawurlencode( $active_date ) ) ); ?>" class="button button-large button-secondary">New Order</a>
    </div>

    <div class="order-grid">
        <?php if ( ! empty( $orders ) ) : ?>
            <?php foreach ( $orders as $order ) : ?>
                <?php
                $order_created_at = date_create_immutable_from_format( 'Y-m-d H:i:s', $order->entry_timestamp, wp_timezone() );
                $order_created_ts = $order_created_at ? $order_created_at->getTimestamp() * 1000 : strtotime( $order->entry_timestamp ) * 1000;
                $order_is_aging   = $order_created_at && in_array( $order->status, array( 'pending', 'preparing' ), true ) && ( time() - $order_created_at->getTimestamp() ) >= 30 * 60;
                ?>
                <div
                    class="order-card banoks-aging-order-card <?php echo $order_is_aging ? 'banoks-order-aging-warning' : ''; ?>"
                    data-id="<?php echo esc_attr( $order->order_id ); ?>"
                    data-status="<?php echo esc_attr( $order->status ); ?>"
                    data-order-created-ts="<?php echo esc_attr( $order_created_ts ); ?>"
                >
                    <div class="order-header">
                        <div class="id-date-wrap">
                            <span class="order-id"><?php echo esc_html( sprintf( 'BNK-ORD-%06d', $order->order_id ) ); ?></span>
                            <span class="order-date" style="display:block; font-size:0.8rem; color:#888;"><?php echo esc_html( date( 'M d, Y', strtotime( $order->date ) ) ); ?></span>
                            <span class="banoks-order-age-warning">30+ min waiting</span>
                        </div>
                        <div class="status-cashier-wrap" style="text-align: right;">
                            <span class="order-status status-<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( ucfirst( $order->status ) ); ?></span>
                            <span class="order-cashier" style="display:block; font-size:0.75rem; color:#999; margin-top:5px; font-weight:600;">By: <?php echo esc_html( $order->created_by ); ?></span>
                        </div>
                    </div>

                    <div class="order-items">
                        <?php
                        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}banoks_order_items WHERE order_id = %d", $order->order_id ) );
                        foreach ( $items as $item ) :
                            $product_name = $wpdb->get_var( $wpdb->prepare( "SELECT product_name FROM {$wpdb->prefix}banoks_items WHERE product_id = %d", $item->product_id ) );
                            ?>
                            <div class="order-item">
                                <span><?php echo esc_html( $product_name ); ?> x <?php echo esc_html( $item->qty ); ?></span>
                                <span>&#8369;<?php echo esc_html( number_format( $item->sub_total, 2 ) ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <span class="order-total">&#8369;<?php echo esc_html( number_format( $order->grand_total, 2 ) ); ?></span>
                        <div class="order-actions">
                            <?php if ( 'pending' === $order->status ) : ?>
                                <button class="button button-primary action-prepare" data-id="<?php echo esc_attr( $order->order_id ); ?>">Prepare</button>
                                <button class="button action-cancel" data-id="<?php echo esc_attr( $order->order_id ); ?>">Cancel</button>
                            <?php elseif ( 'preparing' === $order->status ) : ?>
                                <button class="button button-primary action-complete" data-id="<?php echo esc_attr( $order->order_id ); ?>">Complete</button>
                                <button class="button action-cancel" data-id="<?php echo esc_attr( $order->order_id ); ?>">Cancel</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="empty-msg">No orders found for this selection.</div>
        <?php endif; ?>
    </div>
</div>

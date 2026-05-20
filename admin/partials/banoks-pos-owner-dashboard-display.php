<?php
/**
 * Owner dashboard.
 *
 * @package Banoks_POS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$request_labels = array(
    'expense_request'             => 'Expense Request',
    'stock_purchase_request'      => 'Outside Stock Purchase',
    'production_transfer_request' => 'Production Transfer',
);
?>

<div class="wrap banoks-pos-admin banoks-pos-page banoks-owner-dashboard-page">
    <div class="products-header">
        <div class="header-info">
            <h1>Owner Dashboard</h1>
            <p>Manage approvals, stock, cash, reports, and setup from one place.</p>
        </div>
    </div>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>
    <?php if ( ! empty( $error ) ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <div class="banoks-owner-card-grid">
        <?php foreach ( $owner_cards as $card ) : ?>
            <?php if ( ! empty( $card['modal'] ) ) : ?>
                <button type="button" class="banoks-owner-card banoks-open-owner-branch-picker" data-target="#<?php echo esc_attr( $card['modal'] ); ?>">
                    <strong>
                        <?php echo esc_html( $card['label'] ); ?>
                        <?php if ( ! empty( $card['badge'] ) ) : ?>
                            <span class="banoks-owner-card-badge"><?php echo esc_html( number_format_i18n( absint( $card['badge'] ) ) ); ?></span>
                        <?php endif; ?>
                    </strong>
                    <span><?php echo esc_html( $card['desc'] ); ?></span>
                </button>
            <?php else : ?>
                <a class="banoks-owner-card" href="<?php echo esc_url( $card['url'] ); ?>">
                    <strong>
                        <?php echo esc_html( $card['label'] ); ?>
                        <?php if ( ! empty( $card['badge'] ) ) : ?>
                            <span class="banoks-owner-card-badge"><?php echo esc_html( number_format_i18n( absint( $card['badge'] ) ) ); ?></span>
                        <?php endif; ?>
                    </strong>
                    <span><?php echo esc_html( $card['desc'] ); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="banoks-admin-edit-modal banoks-owner-branch-modal" id="banoks-owner-product-branch-modal" aria-hidden="true">
        <div class="banoks-admin-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="banoks-owner-product-branch-title">
            <div class="banoks-admin-edit-header">
                <div>
                    <h2 id="banoks-owner-product-branch-title">Choose a Branch</h2>
                    <p>Select the branch for Product Management stock availability.</p>
                </div>
                <button type="button" class="banoks-admin-edit-close" aria-label="Close branch chooser">&times;</button>
            </div>

            <div class="banoks-owner-branch-grid">
                <?php foreach ( $owner_product_branches as $branch ) : ?>
                    <a class="banoks-owner-branch-card" href="<?php echo esc_url( admin_url( 'admin.php?page=banoks-pos-products&branch_key=' . sanitize_key( $branch->branch_key ) ) ); ?>">
                        <strong><?php echo esc_html( $branch->branch_name ); ?></strong>
                        <span>Open Product Management</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <section class="banoks-stock-panel banoks-owner-approval-panel">
        <h2>Recent Requests</h2>
        <div class="banoks-stock-table-scroll">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Requested By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_requests as $request ) : ?>
                        <tr>
                            <td><?php echo esc_html( mysql2date( 'M j, Y g:i A', $request->created_at ) ); ?></td>
                            <td><?php echo esc_html( isset( $request_labels[ $request->request_type ] ) ? $request_labels[ $request->request_type ] : $request->request_type ); ?></td>
                            <td><?php echo esc_html( $request->description ); ?></td>
                            <td><span class="category-pill"><?php echo esc_html( ucwords( str_replace( '_', ' ', $request->request_status ) ) ); ?></span></td>
                            <td><?php echo esc_html( $request->requester_name ? $request->requester_name : 'User #' . $request->requested_by ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ( empty( $recent_requests ) ) : ?>
                        <tr><td colspan="5">No requests yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

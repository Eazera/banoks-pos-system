<?php
/**
 * Requests management page.
 *
 * @link       https://banoks.com
 * @since      1.0.0
 * @package    Banoks_POS
 * @subpackage Banoks_POS/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_owner = current_user_can( 'manage_options' );
$request_labels = array(
    'expense_request'             => 'Expense Request',
    'stock_purchase_request'      => 'Production Stock Purchase',
    'production_transfer_request' => 'Production Transfer',
);
?>

<div class="wrap banoks-pos-admin banoks-pos-page banoks-expenses-page <?php echo $is_owner ? 'banoks-requests-owner-page' : 'banoks-requests-worker-page'; ?>">
    <div class="products-header">
        <div class="header-info">
            <h1>Requests</h1>
            <?php if ( $is_owner ) : ?>
                <p>Review worker requests, approve stock or expense actions, and monitor recent decisions.</p>
            <?php else : ?>
                <p>Submit requests for owner approval and review your recent request status.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>

    <?php if ( ! empty( $error ) ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <?php if ( $is_owner ) : ?>
        <div class="banoks-request-overview">
            <div>
                <span>Pending</span>
                <strong><?php echo esc_html( number_format_i18n( count( $pending_requests ) ) ); ?></strong>
            </div>
            <div>
                <span>Recent</span>
                <strong><?php echo esc_html( number_format_i18n( count( $recent_requests ) ) ); ?></strong>
            </div>
            <div>
                <span>Approved Expenses</span>
                <strong><?php echo esc_html( number_format_i18n( count( $expenses ) ) ); ?></strong>
            </div>
        </div>

        <section class="banoks-stock-panel banoks-owner-approval-panel banoks-request-review-panel">
            <div class="banoks-request-section-header">
                <div>
                    <h2>Pending Requests</h2>
                    <p>Review each request before it affects stock or finance.</p>
                </div>
            </div>
            <?php if ( empty( $pending_requests ) ) : ?>
                <p class="empty-msg">No pending requests.</p>
            <?php else : ?>
                <div class="banoks-request-list">
                    <?php foreach ( $pending_requests as $request ) : ?>
                        <?php
                        $request_type_label = isset( $request_labels[ $request->request_type ] ) ? $request_labels[ $request->request_type ] : $request->request_type;
                        $request_quantity   = floatval( $request->quantity ) > 0 ? rtrim( rtrim( number_format( floatval( $request->quantity ), 3, '.', '' ), '0' ), '.' ) . ' ' . $request->unit : '';
                        $requester_name     = $request->requester_name ? $request->requester_name : 'User #' . $request->requested_by;
                        ?>
                        <button type="button" class="banoks-request-row banoks-open-owner-request" data-target="#banoks-expenses-owner-request-<?php echo esc_attr( $request->id ); ?>">
                            <span class="banoks-request-row-type"><?php echo esc_html( $request_type_label ); ?></span>
                            <span class="banoks-request-row-content">
                                <span class="banoks-request-row-main"><?php echo esc_html( $request->description ); ?></span>
                                <?php if ( ! empty( $request->item_name ) ) : ?>
                                    <span class="banoks-request-row-item"><?php echo esc_html( $request->item_name . ( $request_quantity ? ' - ' . $request_quantity : '' ) ); ?></span>
                                <?php else : ?>
                                    <span class="banoks-request-row-item"><?php echo esc_html( $requester_name ); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="banoks-request-row-meta"><?php echo esc_html( mysql2date( 'M j, g:i A', $request->created_at ) ); ?></span>
                            <span class="banoks-request-row-action">View</span>
                        </button>

                        <div class="banoks-admin-edit-modal banoks-owner-request-modal" id="banoks-expenses-owner-request-<?php echo esc_attr( $request->id ); ?>" aria-hidden="true">
                            <div class="banoks-admin-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="banoks-expenses-owner-request-title-<?php echo esc_attr( $request->id ); ?>">
                                <div class="banoks-admin-edit-header">
                                    <h2 id="banoks-expenses-owner-request-title-<?php echo esc_attr( $request->id ); ?>">Request Details</h2>
                                    <button type="button" class="banoks-admin-edit-close" aria-label="Close request details">&times;</button>
                                </div>

                                <div class="banoks-owner-request-detail-grid">
                                    <div>
                                        <span>Type</span>
                                        <strong><?php echo esc_html( $request_type_label ); ?></strong>
                                    </div>
                                    <div>
                                        <span>Requested By</span>
                                        <strong><?php echo esc_html( $requester_name ); ?></strong>
                                    </div>
                                    <div>
                                        <span>Date</span>
                                        <strong><?php echo esc_html( mysql2date( 'M j, Y g:i A', $request->created_at ) ); ?></strong>
                                    </div>
                                    <?php if ( ! empty( $request->item_name ) ) : ?>
                                        <div>
                                            <span>Item / Quantity</span>
                                            <strong><?php echo esc_html( $request->item_name . ( $request_quantity ? ' - ' . $request_quantity : '' ) ); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( floatval( $request->estimated_cost ) > 0 ) : ?>
                                        <div>
                                            <span>Estimated Amount</span>
                                            <strong>&#8369;<?php echo esc_html( number_format( floatval( $request->estimated_cost ), 2 ) ); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="banoks-owner-request-copy">
                                    <label>Description / Reason</label>
                                    <p><?php echo esc_html( $request->description ); ?></p>
                                </div>
                                <?php if ( ! empty( $request->note ) ) : ?>
                                    <div class="banoks-owner-request-copy">
                                        <label>Notes</label>
                                        <p><?php echo esc_html( $request->note ); ?></p>
                                    </div>
                                <?php endif; ?>

                                <form method="post" class="banoks-request-decision-form">
                                    <?php wp_nonce_field( 'banoks_owner_request_action' ); ?>
                                    <input type="hidden" name="banoks_owner_request_action" value="1">
                                    <input type="hidden" name="request_id" value="<?php echo esc_attr( $request->id ); ?>">
                                    <label for="expenses-decision-note-<?php echo esc_attr( $request->id ); ?>">Decision Note</label>
                                    <textarea id="expenses-decision-note-<?php echo esc_attr( $request->id ); ?>" name="decision_note" rows="3" placeholder="Required for rejection. Optional for approval."></textarea>
                                    <div class="modal-actions">
                                        <button type="button" class="button banoks-admin-edit-cancel">Close</button>
                                        <button type="submit" name="decision" value="rejected" class="button">Reject</button>
                                        <button type="submit" name="decision" value="approved" class="button button-primary">Approve</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="banoks-stock-panel banoks-owner-approval-panel banoks-request-history-panel">
            <div class="banoks-request-section-header">
                <div>
                    <h2>Recent Requests</h2>
                    <p>Latest submitted, approved, and rejected request activity.</p>
                </div>
            </div>
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
    <?php else : ?>
        <div class="banoks-expense-form-panel">
            <form method="post" class="banoks-expense-form" id="banoks-expense-form">
                <?php wp_nonce_field( 'banoks_pos_expense_action' ); ?>
                <input type="hidden" name="banoks_pos_save_expense" value="1">

                <div class="expense-field">
                    <label for="request-type">Request Type</label>
                    <select id="request-type" name="request_type" required>
                        <option value="expense_request">Expense Request</option>
                        <option value="stock_purchase_request">Production Stock Purchase Request</option>
                        <option value="production_transfer_request">Production Stock Transfer Request</option>
                    </select>
                </div>

                <div class="expense-field">
                    <label for="expense-description">Description / Reason</label>
                    <input type="text" id="expense-description" name="description" required>
                </div>

                <div class="expense-field banoks-request-stock-field">
                    <label for="request-inventory-item">Inventory Item</label>
                    <select id="request-inventory-item" name="inventory_item_id">
                        <option value="">Select item</option>
                        <?php foreach ( $inventory_items as $inventory_item ) : ?>
                            <option value="<?php echo esc_attr( $inventory_item->id ); ?>" data-unit="<?php echo esc_attr( $inventory_item->unit ); ?>"><?php echo esc_html( $inventory_item->item_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="expense-field banoks-request-stock-field">
                    <label for="request-quantity">Quantity</label>
                    <input type="number" id="request-quantity" name="quantity" min="0.001" step="0.001" inputmode="decimal">
                </div>

                <div class="expense-field banoks-request-stock-field">
                    <label for="request-unit">Unit</label>
                    <select id="request-unit" name="unit">
                        <?php foreach ( $unit_options as $unit_key => $unit_label ) : ?>
                            <option value="<?php echo esc_attr( $unit_key ); ?>"><?php echo esc_html( $unit_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="expense-field banoks-request-amount-field">
                    <label for="expense-amount">Estimated Amount</label>
                    <input type="number" id="expense-amount" name="amount" min="0" step="0.01" inputmode="decimal">
                </div>

                <div class="expense-field">
                    <label for="expense-date">Date</label>
                    <input type="date" id="expense-date" name="date" value="<?php echo esc_attr( $expense_form_date ); ?>" required>
                </div>

                <div class="expense-field banoks-request-cash-source-field">
                    <label for="expense-cash-source">Requested Cash Source</label>
                    <select id="expense-cash-source" name="cash_source" required>
                        <?php foreach ( $cash_source_options as $source_key => $source_label ) : ?>
                            <option value="<?php echo esc_attr( $source_key ); ?>" <?php selected( 'store_cash', $source_key ); ?>><?php echo esc_html( $source_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="expense-field">
                    <label for="request-note">Notes</label>
                    <textarea id="request-note" name="note" rows="3"></textarea>
                </div>

                <button type="submit" class="button button-primary">Submit Request</button>
            </form>
        </div>

        <div class="banoks-expenses-list-panel">
            <div class="banoks-expenses-list-header">
                <h2>My / Recent Requests</h2>
            </div>

            <div class="banoks-expenses-table-scroll">
                <?php if ( ! empty( $my_requests ) ) : ?>
                    <table class="widefat striped banoks-expenses-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $my_requests as $request ) : ?>
                                <tr>
                                    <td><?php echo esc_html( isset( $request_labels[ $request->request_type ] ) ? $request_labels[ $request->request_type ] : ucwords( str_replace( '_', ' ', $request->request_type ) ) ); ?></td>
                                    <td><?php echo esc_html( $request->description ); ?></td>
                                    <td><?php echo esc_html( $request->item_name ? $request->item_name : '-' ); ?></td>
                                    <td><?php echo esc_html( floatval( $request->quantity ) > 0 ? rtrim( rtrim( number_format( floatval( $request->quantity ), 3, '.', '' ), '0' ), '.' ) . ' ' . $request->unit : '-' ); ?></td>
                                    <td>
                                        <?php if ( 'production_transfer_request' === $request->request_type ) : ?>
                                            -
                                        <?php else : ?>
                                            &#8369;<?php echo esc_html( number_format( floatval( $request->estimated_cost ), 2 ) ); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="category-pill"><?php echo esc_html( ucwords( str_replace( '_', ' ', $request->request_status ) ) ); ?></span></td>
                                    <td><?php echo esc_html( date( 'M d, Y g:i A', strtotime( $request->created_at ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="empty-msg">No requests submitted yet.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( $is_owner ) : ?>
        <div class="banoks-expenses-list-panel">
            <div class="banoks-expenses-list-header">
                <div>
                    <h2>Approved Expense History</h2>
                    <p>Expense requests that were approved and recorded as paid.</p>
                </div>
                <form method="get" class="banoks-expenses-filter-form">
                    <input type="hidden" name="page" value="banoks-pos-expenses">
                    <div class="banoks-expense-filter-field">
                        <label for="expense-filter-date">Filter Date</label>
                        <input type="date" id="expense-filter-date" name="expense_date" value="<?php echo esc_attr( $expense_filter_date ); ?>" onchange="this.form.submit()">
                    </div>
                    <?php if ( ! empty( $expense_filter_date ) ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=banoks-pos-expenses' ) ); ?>" class="clear-filter">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="banoks-expenses-table-scroll">
                <?php if ( ! empty( $expenses ) ) : ?>
                    <table class="widefat striped banoks-expenses-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Paid From</th>
                                <th>Expense Date</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $expenses as $expense ) : ?>
                                <?php
                                $delete_url = wp_nonce_url(
                                    admin_url( 'admin.php?page=banoks-pos-expenses&expense_action=delete&expense_id=' . absint( $expense->expense_id ) ),
                                    'delete_expense_' . absint( $expense->expense_id )
                                );
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $expense->description ); ?></td>
                                    <td>&#8369;<?php echo esc_html( number_format( $expense->amount, 2 ) ); ?></td>
                                    <td><?php echo esc_html( isset( $cash_source_options[ $expense->cash_source ] ) ? $cash_source_options[ $expense->cash_source ] : $cash_source_options['store_cash'] ); ?></td>
                                    <td><?php echo esc_html( date( 'M d, Y', strtotime( $expense->date ) ) ); ?></td>
                                    <td><?php echo esc_html( date( 'M d, Y g:i A', strtotime( $expense->created_at ) ) ); ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="<?php echo esc_url( $delete_url ); ?>" class="btn-delete banoks-delete-expense" title="Delete Expense">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="empty-msg">No expenses recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

# Banoks POS System v1.2.2 Release Notes

## Summary

Banoks POS System v1.2.2 is the first GitHub-ready release of the Eazera-built WordPress POS plugin. This release packages the core in-store POS workflow, online ordering, inventory controls, finance tracking, reporting, and GitHub-based plugin update support.

## What's New

- Added GitHub release update support for WordPress plugin updates.
- Added cashier role support with access limited to Banoks POS screens.
- Added walk-in POS order handling with cash and GCash payment support.
- Added online ordering support with cart, checkout, delivery and pickup options.
- Added online order notifications, pending order counts, and admin status updates.
- Added GCash payment proof review and status management.
- Added product management with product images, availability controls, pricing, categories, and stock settings.
- Added delivery area management with deliverable status, delivery fees, and sorting.
- Added stock management for inventory items, stock locations, purchases, movements, and low-stock alerts.
- Added owner dashboard and admin navigation for daily operations.
- Added cash management support for store cash, cash on hand, GCash balance, and bank balance.
- Added expense tracking with branch and cash-source support.
- Added business reports combining walk-in and online sales data.
- Added PDF report export support.
- Added database tables and migrations for orders, products, customers, online orders, payment proofs, inventory, stock logs, expenses, branches, and delivery areas.

## Improvements

- Restricted plugin assets so admin CSS and JavaScript load only on Banoks POS screens.
- Added cache-busting based on local asset file modification time.
- Added Chart.js loading only on the reports screen.
- Improved order status flow for preparing, completed, and cancelled states.
- Added stock deduction and restoration logic for order status changes.
- Added status logs for online order updates.
- Added branch-aware reporting and inventory tracking.

## Installation / Update Notes

- Upload the plugin folder to WordPress, or install the GitHub release ZIP.
- Activate `Banoks POS System` from the WordPress Plugins screen.
- Plugin activation creates or updates the required database tables automatically.
- The plugin version is `1.2.2`.
- GitHub updater repository is configured as `Eazera/banoks-pos-system`.

## Known Notes

- Make sure the GitHub release tag matches the plugin version, for example `v1.2.2`.
- For WordPress update checks to work correctly, publish this as a GitHub Release rather than only pushing source code.
- Existing sites should back up the database before updating, especially because this release includes custom POS, order, inventory, and finance tables.


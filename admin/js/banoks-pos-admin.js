(function ($) {
    'use strict';

    $(function () {
        let cart = [];

        // --- Product Image Upload ---
        const $productImageInput = $('#product_image_id');
        if ($productImageInput.length && typeof wp !== 'undefined' && wp.media) {
            let productImageFrame;

            $('#banoks-upload-product-image').on('click', function (event) {
                event.preventDefault();

                if (productImageFrame) {
                    productImageFrame.open();
                    return;
                }

                productImageFrame = wp.media({
                    title: 'Select Product Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });

                productImageFrame.on('select', function () {
                    const attachment = productImageFrame.state().get('selection').first().toJSON();
                    const imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

                    $productImageInput.val(attachment.id);
                    $('.banoks-product-image-preview')
                        .addClass('has-image')
                        .html('<img src="' + imageUrl + '" alt="">');
                    $('#banoks-remove-product-image').show();
                });

                productImageFrame.open();
            });

            $('#banoks-remove-product-image').on('click', function (event) {
                event.preventDefault();
                $productImageInput.val('');
                $('.banoks-product-image-preview')
                    .removeClass('has-image')
                    .html('<span>No image selected</span>');
                $(this).hide();
            });
        }

        // --- Helper Functions ---

        function getModalIconState(title) {
            const normalizedTitle = (title || '').toLowerCase();

            if (normalizedTitle.includes('success')) {
                return {
                    stateClass: 'is-success',
                    iconClass: 'dashicons-yes-alt'
                };
            }

            if (normalizedTitle.includes('error')) {
                return {
                    stateClass: 'is-error',
                    iconClass: 'dashicons-dismiss'
                };
            }

            if (/delete|clear|generate|update|save|are you sure/.test(normalizedTitle)) {
                return {
                    stateClass: 'is-warning',
                    iconClass: 'dashicons-warning'
                };
            }

            return {
                stateClass: 'is-info',
                iconClass: 'dashicons-info'
            };
        }

        function showBanoksModal(title, message, onConfirm) {
            const $modal = $('#banoks-modal');
            const iconState = getModalIconState(title);
            const $modalIcon = $modal.find('.modal-icon');
            const $modalIconGlyph = $modalIcon.find('.dashicons');

            $modalIcon
                .removeClass('is-warning is-info is-success is-error')
                .addClass(iconState.stateClass);
            $modalIconGlyph
                .removeClass('dashicons-warning dashicons-info dashicons-yes-alt dashicons-dismiss')
                .addClass(iconState.iconClass);

            $modal.find('#modal-title').text(title);
            $modal.find('#modal-message').text(message);
            $modal.addClass('active');

            $('#modal-confirm-btn').off('click').on('click', function () {
                $modal.removeClass('active');
                if (onConfirm) onConfirm();
            });

            $('#modal-cancel-btn').off('click').on('click', function () {
                $modal.removeClass('active');
            });
        }

        // --- Inventory category modal ---
        const $inventoryCategoryModal = $('#banoks-inventory-category-modal');
        if ($inventoryCategoryModal.length) {
            const $categorySelect = $('#inventory_category');
            const $categoryInput = $('#banoks-new-inventory-category');

            function closeInventoryCategoryModal() {
                $inventoryCategoryModal.removeClass('active').attr('aria-hidden', 'true');
                $categoryInput.val('');
            }

            $('#banoks-add-inventory-category').on('click', function () {
                $inventoryCategoryModal.addClass('active').attr('aria-hidden', 'false');
                setTimeout(function () {
                    $categoryInput.trigger('focus');
                }, 50);
            });

            $('#banoks-cancel-inventory-category').on('click', closeInventoryCategoryModal);

            $inventoryCategoryModal.on('click', function (event) {
                if (event.target === this) {
                    closeInventoryCategoryModal();
                }
            });

            $('#banoks-save-inventory-category').on('click', function () {
                const categoryName = $.trim($categoryInput.val());
                if (!categoryName) {
                    $categoryInput.trigger('focus');
                    return;
                }

                let exists = false;
                $categorySelect.find('option').each(function () {
                    if ($.trim($(this).val()).toLowerCase() === categoryName.toLowerCase()) {
                        $(this).prop('selected', true);
                        exists = true;
                        return false;
                    }
                    return true;
                });

                if (!exists) {
                    $('<option>', {
                        value: categoryName,
                        text: categoryName,
                        selected: true
                    }).appendTo($categorySelect);
                }

                closeInventoryCategoryModal();
            });

            $categoryInput.on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    $('#banoks-save-inventory-category').trigger('click');
                }

                if (event.key === 'Escape') {
                    closeInventoryCategoryModal();
                }
            });
        }

        // --- Product category modal ---
        const $productCategoryModal = $('#banoks-product-category-modal');
        if ($productCategoryModal.length) {
            const $productCategorySelect = $('#category');
            const $productCategoryInput = $('#banoks-new-product-category');

            function closeProductCategoryModal() {
                $productCategoryModal.removeClass('active').attr('aria-hidden', 'true');
                $productCategoryInput.val('');
            }

            $('#banoks-add-product-category').on('click', function () {
                $productCategoryModal.addClass('active').attr('aria-hidden', 'false');
                setTimeout(function () {
                    $productCategoryInput.trigger('focus');
                }, 50);
            });

            $('#banoks-cancel-product-category').on('click', closeProductCategoryModal);

            $productCategoryModal.on('click', function (event) {
                if (event.target === this) {
                    closeProductCategoryModal();
                }
            });

            $('#banoks-save-product-category').on('click', function () {
                const categoryName = $.trim($productCategoryInput.val());
                if (!categoryName) {
                    $productCategoryInput.trigger('focus');
                    return;
                }

                let exists = false;
                $productCategorySelect.find('option').each(function () {
                    if ($.trim($(this).val()).toLowerCase() === categoryName.toLowerCase()) {
                        $(this).prop('selected', true);
                        exists = true;
                        return false;
                    }
                    return true;
                });

                if (!exists) {
                    $('<option>', {
                        value: categoryName,
                        text: categoryName,
                        selected: true
                    }).appendTo($productCategorySelect);
                }

                closeProductCategoryModal();
            });

            $productCategoryInput.on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    $('#banoks-save-product-category').trigger('click');
                }

                if (event.key === 'Escape') {
                    closeProductCategoryModal();
                }
            });
        }

        // --- Overall balance transaction filters ---
        const $financeFilterModal = $('#banoks-finance-filter-modal');
        if ($financeFilterModal.length) {
            const $financeTableRows = $('.banoks-finance-overall-row');
            const $financeNoResults = $('.banoks-finance-overall-no-results');
            const $financeTotals = $('.banoks-finance-filter-totals');
            const $financeTotalIn = $('#banoks-finance-filter-total-in');
            const $financeTotalOut = $('#banoks-finance-filter-total-out');
            const pesoFormatter = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            });

            function closeFinanceFilterModal() {
                $financeFilterModal.removeClass('active').attr('aria-hidden', 'true');
            }

            function openFinanceFilterModal() {
                $financeFilterModal.addClass('active').attr('aria-hidden', 'false');
                setTimeout(function () {
                    $('#banoks-finance-filter-date-from').trigger('focus');
                }, 50);
            }

            function getFinanceFilterValues() {
                return {
                    dateFrom: $('#banoks-finance-filter-date-from').val() || '',
                    dateTo: $('#banoks-finance-filter-date-to').val() || '',
                    type: $('#banoks-finance-filter-type').val() || '',
                    source: $('#banoks-finance-filter-source').val() || '',
                    destination: $('#banoks-finance-filter-destination').val() || '',
                    effect: $('#banoks-finance-filter-effect').val() || ''
                };
            }

            function applyFinanceFilters(showTotals) {
                const filters = getFinanceFilterValues();
                let visibleRows = 0;
                let totalIn = 0;
                let totalOut = 0;

                $financeTableRows.each(function () {
                    const $row = $(this);
                    const rowDate = $row.data('date') || '';
                    const matches = (!filters.dateFrom || rowDate >= filters.dateFrom)
                        && (!filters.dateTo || rowDate <= filters.dateTo)
                        && (!filters.type || $row.data('type') === filters.type)
                        && (!filters.source || $row.data('source') === filters.source)
                        && (!filters.destination || $row.data('destination') === filters.destination)
                        && (!filters.effect || $row.data('effect') === filters.effect);

                    $row.toggle(matches);

                    if (matches) {
                        const amount = parseFloat($row.data('amount')) || 0;
                        visibleRows += 1;

                        if ($row.data('effect') === 'in') {
                            totalIn += amount;
                        } else if ($row.data('effect') === 'out') {
                            totalOut += amount;
                        }
                    }
                });

                $financeNoResults.toggle($financeTableRows.length > 0 && visibleRows === 0);
                $financeTotalIn.text(pesoFormatter.format(totalIn));
                $financeTotalOut.text(pesoFormatter.format(totalOut));
                $financeTotals.toggle(!!showTotals);
            }

            $('.banoks-finance-filter-trigger').on('click', openFinanceFilterModal);

            $('.banoks-finance-filter-form').on('submit', function (event) {
                event.preventDefault();
                applyFinanceFilters(true);
                closeFinanceFilterModal();
            });

            $('.banoks-finance-filter-clear').on('click', function () {
                $('.banoks-finance-filter-form').find('select').val('');
                applyFinanceFilters(false);
                closeFinanceFilterModal();
            });

            $financeFilterModal.on('click', function (event) {
                if (event.target === this) {
                    closeFinanceFilterModal();
                }
            });

            $financeFilterModal.find('.banoks-admin-edit-close').on('click', closeFinanceFilterModal);

            $(document).on('keydown', function (event) {
                if (event.key === 'Escape' && $financeFilterModal.hasClass('active')) {
                    closeFinanceFilterModal();
                }
            });
        }

        // --- Product recipe rows ---
        const $recipeBuilder = $('#banoks-recipe-builder');
        if ($recipeBuilder.length) {
            const $recipeRows = $recipeBuilder.find('.banoks-recipe-rows');

            $('#banoks-add-recipe-row').on('click', function () {
                const $firstRow = $recipeRows.find('.banoks-recipe-row:first');
                if (!$firstRow.length) {
                    return;
                }

                const $newRow = $firstRow.clone();
                $newRow.find('select').val('');
                $newRow.find('select[name="recipe_applies_to[]"]').val('all');
                $newRow.find('input').val('');
                $recipeRows.append($newRow);
            });

            $recipeBuilder.on('click', '.banoks-remove-recipe-row', function () {
                if ($recipeRows.find('.banoks-recipe-row').length <= 1) {
                    $(this).closest('.banoks-recipe-row').find('select').val('');
                    $(this).closest('.banoks-recipe-row').find('select[name="recipe_applies_to[]"]').val('all');
                    $(this).closest('.banoks-recipe-row').find('input').val('');
                    return;
                }

                $(this).closest('.banoks-recipe-row').remove();
            });
        }

        // --- Stock movement date range auto-filter ---
        const $stockMovementFilters = $('.banoks-stock-movement-filters');
        if ($stockMovementFilters.length) {
            let stockMovementRequest = null;

            $stockMovementFilters.on('change', 'input[type="date"]', function () {
                const $form = $(this).closest('form');
                const startDate = $form.find('input[name="movement_start"]').val();
                const endDate = $form.find('input[name="movement_end"]').val();

                if (startDate && endDate) {
                    const url = window.location.pathname + '?' + $form.serialize();
                    const $tableWrap = $('.banoks-stock-table-scroll');

                    if (stockMovementRequest) {
                        stockMovementRequest.abort();
                    }

                    $tableWrap.addClass('is-loading');
                    stockMovementRequest = $.get(url)
                        .done(function (html) {
                            const $html = $('<div>').append($.parseHTML(html));
                            const $newTable = $html.find('.banoks-stock-table-scroll').first();

                            if ($newTable.length) {
                                $tableWrap.html($newTable.html());
                                window.history.replaceState({}, '', url);
                            }
                        })
                        .always(function () {
                            $tableWrap.removeClass('is-loading');
                            stockMovementRequest = null;
                        });
                }
            });
        }

        // --- Reports transactions date range auto-filter ---
        const $transactionFilters = $('.banoks-transactions-filter-form');
        if ($transactionFilters.length) {
            let transactionRequest = null;

            $transactionFilters.on('submit', function (event) {
                event.preventDefault();
            });

            $transactionFilters.on('change', 'input[type="date"]', function () {
                const $form = $(this).closest('form');
                const startDate = $form.find('input[name="transactions_start_date"]').val();
                const endDate = $form.find('input[name="transactions_end_date"]').val();

                if (startDate && endDate) {
                    const url = window.location.pathname + '?' + $form.serialize();
                    const $tableWrap = $('.banoks-report-transactions-scroll');

                    if (transactionRequest) {
                        transactionRequest.abort();
                    }

                    $tableWrap.addClass('is-loading');
                    transactionRequest = $.get(url)
                        .done(function (html) {
                            const $html = $('<div>').append($.parseHTML(html));
                            const $newTable = $html.find('.banoks-report-transactions-scroll').first();

                            if ($newTable.length) {
                                $tableWrap.html($newTable.html());
                                window.history.replaceState({}, '', url);
                            }
                        })
                        .always(function () {
                            $tableWrap.removeClass('is-loading');
                            transactionRequest = null;
                        });
                }
            });
        }

        // --- Shared toggle visual state fallback ---
        function syncBanoksToggle($toggle) {
            $toggle.toggleClass('is-checked', $toggle.find('input[type="checkbox"]').prop('checked'));
        }

        $('.banoks-toggle-control').each(function () {
            syncBanoksToggle($(this));
        });

        $(document).on('change', '.banoks-toggle-control input[type="checkbox"]', function () {
            syncBanoksToggle($(this).closest('.banoks-toggle-control'));
        });

        // --- Stock movement cash balance option ---
        const $movementType = $('#movement_type');
        if ($movementType.length) {
            const $cashToggle = $('.banoks-cash-balance-toggle');
            const $cashInput = $('#affects_cash_balance');
            const $cashSourceField = $('.banoks-stock-cash-source-field');

            function syncCashBalanceToggle() {
                const movementType = $movementType.val();
                const isCashPurchaseType = movementType === 'stock_in' || movementType === 'correction';

                if (movementType === 'stock_in') {
                    $cashInput.prop('checked', true);
                } else {
                    $cashInput.prop('checked', false);
                }
                $cashToggle.toggle(isCashPurchaseType);
                $cashSourceField.toggle(isCashPurchaseType && $cashInput.prop('checked'));
                syncBanoksToggle($cashToggle);
            }

            syncCashBalanceToggle();
            $movementType.on('change', syncCashBalanceToggle);
            $cashInput.on('change', function () {
                $cashSourceField.toggle($(this).prop('checked'));
            });
        }

        // --- Request form stock fields ---
        const $requestType = $('#request-type');
        if ($requestType.length) {
            const $stockFields = $('.banoks-request-stock-field');
            const $amountField = $('.banoks-request-amount-field');
            const $cashSourceField = $('.banoks-request-cash-source-field');
            const $amount = $('#expense-amount');
            const $cashSource = $('#expense-cash-source');
            const $itemSelect = $('#request-inventory-item');
            const $unitSelect = $('#request-unit');

            function syncRequestFields() {
                const type = $requestType.val();
                const isStockRequest = type === 'stock_purchase_request' || type === 'production_transfer_request';
                const needsAmount = type !== 'production_transfer_request';
                const needsCashSource = type !== 'production_transfer_request';
                $stockFields.toggle(isStockRequest);
                $stockFields.find('select, input').prop('required', isStockRequest);
                $unitSelect.prop('disabled', isStockRequest).prop('required', false);
                $amountField.toggle(needsAmount);
                $amount.prop('required', needsAmount);
                $cashSourceField.toggle(needsCashSource);
                $cashSource.prop('required', needsCashSource);
                if (!needsAmount) {
                    $amount.val('');
                }
                if (!needsCashSource) {
                    $cashSource.val('store_cash');
                }
            }

            $requestType.on('change', syncRequestFields);
            $itemSelect.on('change', function () {
                const unit = $(this).find(':selected').data('unit');
                if (unit) {
                    $unitSelect.val(unit);
                }
            });
            syncRequestFields();
        }

        // --- Admin edit modals ---
        function openAdminEditModal($modal) {
            $modal.attr('aria-hidden', 'false').addClass('active');
        }

        function closeAdminEditModal($modal) {
            $modal.attr('aria-hidden', 'true').removeClass('active');
        }

        $(document).on('click', '.banoks-open-owner-request, .banoks-open-owner-branch-picker, .banoks-open-pay-bill', function () {
            const target = $(this).data('target');
            const $modal = target ? $(target) : $();
            if ($modal.length) {
                openAdminEditModal($modal);
            }
        });

        $(document).on('click', '.banoks-open-finance-claim', function () {
            const target = $(this).data('target');
            const $modal = target ? $(target) : $();
            if ($modal.length) {
                openAdminEditModal($modal);
            }
        });

        $(document).on('click', '.banoks-open-delivery-edit', function () {
            const $button = $(this);
            const $modal = $('#banoks-delivery-edit-modal');

            $('#delivery-modal-area-id').val($button.data('id') || '');
            $('#delivery-modal-area-name').val($button.data('name') || '');
            $('#delivery-modal-fee').val($button.data('fee') || '0');
            $('#delivery-modal-sort').val($button.data('sort') || '0');
            $('#delivery-modal-deliverable').prop('checked', parseInt($button.data('deliverable'), 10) === 1);
            syncBanoksToggle($('#delivery-modal-deliverable').closest('.banoks-toggle-control'));
            openAdminEditModal($modal);
        });

        $(document).on('click', '.banoks-open-stock-add', function () {
            const $modal = $('#banoks-stock-add-modal');
            if ($modal.length) {
                openAdminEditModal($modal);
                setTimeout(function () {
                    $('#item_name').trigger('focus');
                }, 50);
            }
        });

        function openStockMovementHistory($trigger) {
            const itemId = String($trigger.data('item-id') || '');
            const itemName = $trigger.data('item-name') || 'Inventory Item';
            const locationKey = String($trigger.data('location-key') || '');
            const $modal = $('#banoks-stock-movements-modal');

            if (!$modal.length || !itemId) {
                return;
            }

            $modal.data('active-item-id', itemId);
            $modal.data('active-location-key', locationKey);
            $modal.find('#banoks-stock-history-date-from, #banoks-stock-history-date-to').val('');
            $modal.find('#banoks-stock-history-type, #banoks-stock-history-location').val('');
            syncStockMovementTypeOptions(locationKey);
            syncStockMovementLocationOptions(locationKey);
            $('#banoks-stock-movements-title').text(itemName + ' Movements');
            syncStockMovementHistoryFilters();
            openAdminEditModal($modal);
        }

        function getStockMovementTypesForLocation(locationKey) {
            if (locationKey === 'production') {
                return ['stock_in', 'transfer_out', 'manual_adjustment'];
            }

            if (locationKey === 'manukan_branch') {
                return ['transfer_in', 'recipe_usage', 'recipe_restore'];
            }

            return [];
        }

        function syncStockMovementTypeOptions(locationKey) {
            const allowedTypes = getStockMovementTypesForLocation(locationKey);
            const $typeFilter = $('#banoks-stock-history-type');

            $typeFilter.find('option').each(function () {
                const optionValue = $(this).val();
                const isAllowed = !optionValue || allowedTypes.indexOf(optionValue) !== -1;
                $(this).prop('hidden', !isAllowed).prop('disabled', !isAllowed);
            });
        }

        function syncStockMovementLocationOptions(locationKey) {
            const $locationFilter = $('#banoks-stock-history-location');

            $locationFilter.find('option').each(function () {
                const optionValue = $(this).val();
                const isAllowed = !optionValue || optionValue === locationKey;
                $(this).prop('hidden', !isAllowed).prop('disabled', !isAllowed);
            });
        }

        function syncStockMovementHistoryFilters() {
            const $modal = $('#banoks-stock-movements-modal');
            const itemId = String($modal.data('active-item-id') || '');
            const activeLocationKey = String($modal.data('active-location-key') || '');
            const allowedTypes = getStockMovementTypesForLocation(activeLocationKey);
            const dateFrom = $modal.find('#banoks-stock-history-date-from').val();
            const dateTo = $modal.find('#banoks-stock-history-date-to').val();
            const movementType = $modal.find('#banoks-stock-history-type').val();
            const locationKey = $modal.find('#banoks-stock-history-location').val();
            let visibleRows = 0;

            $modal.find('.banoks-stock-movement-history-row').each(function () {
                const $row = $(this);
                const rowDate = String($row.data('date') || '');
                const rowMovementType = String($row.data('movement-type') || '');
                const rowLocationKey = String($row.data('location-key') || '');
                const matchesItem = String($row.data('item-id')) === itemId;
                const matchesActiveLocation = !activeLocationKey || rowLocationKey === activeLocationKey;
                const matchesFrom = !dateFrom || rowDate >= dateFrom;
                const matchesTo = !dateTo || rowDate <= dateTo;
                const matchesType = !movementType || rowMovementType === movementType;
                const matchesAllowedType = !allowedTypes.length || allowedTypes.indexOf(rowMovementType) !== -1;
                const matchesLocation = !locationKey || rowLocationKey === locationKey;
                const isVisible = matchesItem && matchesActiveLocation && matchesAllowedType && matchesFrom && matchesTo && matchesType && matchesLocation;

                $row.toggle(isVisible);
                if (isVisible) {
                    visibleRows += 1;
                }
            });

            $('#banoks-stock-movements-description').text(visibleRows ? 'Showing dated movement history for this selected item.' : 'No movement history matches the selected filters.');
            $modal.find('.banoks-stock-movement-history-empty').toggle(visibleRows === 0);
        }

        $(document).on('click', '.banoks-open-stock-movements', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openStockMovementHistory($(this));
        });

        $(document).on('click', '.banoks-stock-item-row', function (event) {
            if ($(event.target).closest('button, a, input, select, textarea').length) {
                return;
            }

            openStockMovementHistory($(this));
        });

        $(document).on('keydown', '.banoks-stock-item-row', function (event) {
            if ($(event.target).closest('button, a, input, select, textarea').length) {
                return;
            }

            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openStockMovementHistory($(this));
        });

        $(document).on('change', '#banoks-stock-history-date-from, #banoks-stock-history-date-to, #banoks-stock-history-type, #banoks-stock-history-location', syncStockMovementHistoryFilters);

        $(document).on('click', '#banoks-stock-history-clear', function () {
            const $modal = $('#banoks-stock-movements-modal');
            $modal.find('#banoks-stock-history-date-from, #banoks-stock-history-date-to').val('');
            $modal.find('#banoks-stock-history-type, #banoks-stock-history-location').val('');
            syncStockMovementTypeOptions(String($modal.data('active-location-key') || ''));
            syncStockMovementLocationOptions(String($modal.data('active-location-key') || ''));
            syncStockMovementHistoryFilters();
        });

        $(document).on('click', '.banoks-stock-movement-toggle', function (event) {
            event.preventDefault();
            const $button = $(this);
            const $menu = $button.closest('.banoks-stock-movement-menu');
            const isOpen = $menu.toggleClass('is-open').hasClass('is-open');

            $button.attr('aria-expanded', isOpen ? 'true' : 'false');
            $menu.find('.banoks-stock-movement-dropdown').attr('aria-hidden', isOpen ? 'false' : 'true');
        });

        $(document).on('click', function (event) {
            if ($(event.target).closest('.banoks-stock-movement-menu').length) {
                return;
            }

            $('.banoks-stock-movement-menu.is-open')
                .removeClass('is-open')
                .find('.banoks-stock-movement-toggle').attr('aria-expanded', 'false');
            $('.banoks-stock-movement-dropdown').attr('aria-hidden', 'true');
        });

        $(document).on('click', '.banoks-open-stock-movement', function () {
            const $button = $(this);
            const $modal = $('#banoks-stock-movement-modal');
            const movementAction = $button.data('movement-action') || 'stock_in';
            const movementType = $button.data('movement-type') || 'stock_in';
            const movementLabel = $button.data('movement-label') || 'Stock Movement';
            const location = $button.data('location') || 'production';
            const isBranchStock = movementAction === 'add_branch_stock';
            const $branchSelect = $('#movement_location_key_select');

            function showStockMovementStep(step) {
                $modal.find('.banoks-stock-movement-step').hide().filter('[data-step="' + step + '"]').show();
            }

            $('#banoks-stock-movement-title').text(movementLabel);
            $('#banoks-stock-movement-description').text(isBranchStock ? 'Transfer stock from Production Inventory to a selected branch.' : 'Add inventory stock to Production Inventory.');
            $('#banoks-stock-transfer-source').text(isBranchStock ? 'Production Inventory' : 'Supplier / New Stock');
            $('#banoks-stock-transfer-destination').text(isBranchStock ? 'Selected Branch Store' : 'Production Inventory');
            $('#banoks-stock-transfer-source-stock, #banoks-stock-transfer-destination-stock').text('Remaining: -').removeClass('is-warning');
            $('#banoks_stock_movement_action').val(movementAction);
            $('#movement_type').val(movementType).trigger('change');
            $('#movement_location_key').val(isBranchStock ? '' : 'production');
            $branchSelect.val('');
            $('.banoks-stock-unit-cost-field').toggle(!isBranchStock);
            $('.banoks-cash-balance-toggle, .banoks-stock-cash-source-field').toggle(!isBranchStock);
            $('#affects_cash_balance').prop('checked', !isBranchStock);
            syncBanoksToggle($('.banoks-cash-balance-toggle'));
            $modal.find('[data-step="item"] .banoks-stock-movement-back').toggle(isBranchStock);
            $('#adjust_inventory_item_id').val('');
            $('#quantity').val('');
            $('#movement_unit_cost').val('');
            $('#inventory_note').val('');
            $('.banoks-stock-movement-menu').removeClass('is-open');
            $('.banoks-stock-movement-toggle').attr('aria-expanded', 'false');
            $('.banoks-stock-movement-dropdown').attr('aria-hidden', 'true');

            if ($modal.length) {
                showStockMovementStep(isBranchStock ? 'branch' : 'item');
                openAdminEditModal($modal);
                setTimeout(function () {
                    (isBranchStock ? $('#movement_location_key_select') : $('#adjust_inventory_item_id')).trigger('focus');
                }, 50);
            }
        });

        $(document).on('change', '#movement_location_key_select', function () {
            $('#movement_location_key').val($(this).val() || '');
            const branchName = $(this).val() ? $(this).find(':selected').text() : 'Selected Branch Store';
            $('#banoks-stock-transfer-destination').text(branchName);
            updateStockTransferRemainingPreview();
        });

        function formatStockPreviewQuantity(value) {
            const number = Number(value) || 0;
            return number.toLocaleString(undefined, { maximumFractionDigits: 3 });
        }

        function getSelectedMovementStock() {
            const $selected = $('#adjust_inventory_item_id option:selected');

            return {
                unit: $selected.data('unit') || '',
                production: parseFloat($selected.data('production-stock')) || 0,
                branch: parseFloat($selected.data('branch-stock')) || 0,
            };
        }

        function setTransferStockText($target, current, after, unit) {
            const suffix = unit ? ' ' + unit : '';
            $target
                .text('Remaining: ' + formatStockPreviewQuantity(current) + suffix + ' -> ' + formatStockPreviewQuantity(after) + suffix)
                .toggleClass('is-warning', after <= 0);
        }

        function updateStockTransferRemainingPreview() {
            const action = $('#banoks_stock_movement_action').val();
            const quantity = parseFloat($('#quantity').val()) || 0;
            const stock = getSelectedMovementStock();

            if (!$('#adjust_inventory_item_id').val()) {
                $('#banoks-stock-transfer-source-stock, #banoks-stock-transfer-destination-stock').text('Remaining: -').removeClass('is-warning');
                return;
            }

            if (action === 'add_branch_stock') {
                setTransferStockText($('#banoks-stock-transfer-source-stock'), stock.production, stock.production - quantity, stock.unit);
                setTransferStockText($('#banoks-stock-transfer-destination-stock'), stock.branch, stock.branch + quantity, stock.unit);
            } else {
                $('#banoks-stock-transfer-source-stock').text('Remaining: New stock').removeClass('is-warning');
                setTransferStockText($('#banoks-stock-transfer-destination-stock'), stock.production, stock.production + quantity, stock.unit);
            }
        }

        $(document).on('click', '.banoks-stock-movement-next', function () {
            const nextStep = $(this).data('next-step');
            const action = $('#banoks_stock_movement_action').val();

            if (nextStep === 'item' && action === 'add_branch_stock' && !$('#movement_location_key_select').val()) {
                $('#movement_location_key_select').trigger('focus');
                return;
            }

            if (nextStep === 'details' && !$('#adjust_inventory_item_id').val()) {
                $('#adjust_inventory_item_id').trigger('focus');
                return;
            }

            if (nextStep === 'details') {
                const itemName = $('#adjust_inventory_item_id option:selected').text() || 'Selected Item';
                const destination = action === 'add_branch_stock'
                    ? ($('#movement_location_key_select option:selected').text() || 'Selected Branch Store')
                    : 'Production Inventory';

                $('#banoks-stock-movement-description').text(itemName);
                $('#banoks-stock-transfer-destination').text(destination);
                updateStockTransferRemainingPreview();
            }

            $('#banoks-stock-movement-modal .banoks-stock-movement-step').hide().filter('[data-step="' + nextStep + '"]').show();
        });

        $(document).on('change input', '#adjust_inventory_item_id, #quantity', updateStockTransferRemainingPreview);

        $(document).on('click', '.banoks-stock-movement-back', function () {
            const backStep = $(this).data('back-step');
            const action = $('#banoks_stock_movement_action').val();
            const targetStep = backStep === 'branch' && action !== 'add_branch_stock' ? 'item' : backStep;

            $('#banoks-stock-movement-modal .banoks-stock-movement-step').hide().filter('[data-step="' + targetStep + '"]').show();
        });

        $(document).on('submit', '#banoks-stock-movement-modal form', function (event) {
            if ($('#banoks_stock_movement_action').val() !== 'add_branch_stock') {
                return;
            }

            if (!$('#movement_location_key_select').val()) {
                event.preventDefault();
                $('#movement_location_key_select').trigger('focus');
            }
        });

        $(document).on('click', '.banoks-open-stock-edit', function () {
            const $button = $(this);
            const $modal = $('#banoks-stock-edit-modal');
            const category = $button.data('category') || 'Ingredients';
            const $categorySelect = $('#stock-modal-category');

            if (!$categorySelect.find('option').filter(function () {
                return $(this).val().toLowerCase() === String(category).toLowerCase();
            }).length) {
                $('<option>', { value: category, text: category }).appendTo($categorySelect);
            }

            $('#stock-modal-item-id').val($button.data('id') || '');
            $('#stock-modal-item-name').val($button.data('name') || '');
            $('#stock-modal-category').val(category);
            $('#stock-modal-unit').val($button.data('unit') || 'pcs');
            $('#stock-modal-unit-cost').val($button.data('unit-cost') || '0.00');
            $('#stock-modal-low-stock').val($button.data('low-stock') || '0');
            $('#stock-modal-active').prop('checked', parseInt($button.data('active'), 10) === 1);
            syncBanoksToggle($('#stock-modal-active').closest('.banoks-toggle-control'));
            openAdminEditModal($modal);
        });

        $(document).on('click', '.banoks-admin-edit-close, .banoks-admin-edit-cancel, .banoks-admin-edit-modal', function (event) {
            if (event.target !== this) {
                return;
            }

            closeAdminEditModal($(this).closest('.banoks-admin-edit-modal'));
        });

        // --- Request/expense confirmations ---
        const $expenseForm = $('#banoks-expense-form');
        if ($expenseForm.length) {
            let expenseSaveConfirmed = false;

            $expenseForm.on('submit', function (event) {
                if (expenseSaveConfirmed) {
                    expenseSaveConfirmed = false;
                    return;
                }

                event.preventDefault();

                showBanoksModal(
                    'Submit Request?',
                    'Are you sure you want to submit this request for owner approval?',
                    function () {
                        expenseSaveConfirmed = true;
                        $expenseForm[0].submit();
                    }
                );
            });

        }

        $('.banoks-delete-expense').on('click', function (event) {
            event.preventDefault();
            const deleteUrl = $(this).attr('href');

            showBanoksModal(
                'Delete Expense?',
                'Are you sure you want to delete this approved expense history?',
                function () {
                    window.location.href = deleteUrl;
                }
            );
        });

        const $payBillForm = $('#banoks-pay-bill-form');
        if ($payBillForm.length) {
            let payBillConfirmed = false;

            $payBillForm.on('submit', function (event) {
                if (payBillConfirmed) {
                    payBillConfirmed = false;
                    return;
                }

                event.preventDefault();

                showBanoksModal(
                    'Record Paid Bill?',
                    'This will immediately reduce the selected finance account and add an approved expense.',
                    function () {
                        payBillConfirmed = true;
                        $payBillForm[0].submit();
                    }
                );
            });
        }

        // --- POS Logic ---

        // Add to Cart from Grid
        $('.product-item').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const price = parseFloat($(this).data('price'));

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.qty++;
            } else {
                cart.push({ id, name, price, qty: 1 });
            }

            renderCart();
        });

        // Quantity Adjusters
        $(document).on('click', '.qty-btn', function () {
            const id = $(this).data('id');
            const change = $(this).data('change');
            const item = cart.find(i => i.id === id);

            if (item) {
                item.qty += change;
                if (item.qty <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
                renderCart();
            }
        });

        // Delete from Cart
        $(document).on('click', '.cart-delete', function () {
            const id = $(this).data('id');
            cart = cart.filter(item => item.id !== id);
            renderCart();
        });

        $('#pos-money-received, #pos-payment-method').on('input change', function () {
            updatePaymentChange(getCartTotal());
        });

        function getCartTotal() {
            return cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        }

        function updatePaymentChange(total) {
            const received = parseFloat($('#pos-money-received').val()) || 0;
            const paymentMethod = $('#pos-payment-method').val() || 'cash';
            const change = Math.max(received - total, 0);

            if (paymentMethod === 'gcash') {
                $('#pos-change-amount').text('\u20b10.00');
                $('#pos-change-amount').removeClass('insufficient');
                return;
            }

            $('#pos-change-amount').text('\u20b1' + change.toFixed(2));
            $('#pos-change-amount').toggleClass('insufficient', received > 0 && received < total);
        }

        function renderCart() {
            const cartList = $('#pos-cart-items');
            cartList.empty();

            if (cart.length === 0) {
                cartList.append('<div class="empty-msg">Select items to start order</div>');
                $('#pos-grand-total').text('\u20b10.00');
                $('#pos-money-received').val('');
                $('#pos-payment-method').val('cash');
                updatePaymentChange(0);
                $('#pos-generate-btn').prop('disabled', true);
                return;
            }

            let grandTotal = 0;
            cart.forEach(item => {
                const subtotal = item.price * item.qty;
                grandTotal += subtotal;
                cartList.append(`
                    <div class="cart-item">
                        <div class="item-name">${item.name}</div>
                        <div class="qty-wrap">
                            <button class="qty-btn" data-id="${item.id}" data-change="-1">-</button>
                            <span class="qty">${item.qty}</span>
                            <button class="qty-btn" data-id="${item.id}" data-change="1">+</button>
                        </div>
                        <div class="item-price">\u20b1${item.price.toFixed(2)}</div>
                        <button class="cart-delete" data-id="${item.id}">Delete</button>
                    </div>
                `);
            });

            $('#pos-grand-total').text('\u20b1' + grandTotal.toFixed(2));
            updatePaymentChange(grandTotal);
            $('#pos-generate-btn').prop('disabled', false);
        }

        // Product Search & Category Filter
        $('#product-search, #product-category').on('keyup change', function () {
            const searchTerm = $('#product-search').val().toLowerCase();
            const category = $('#product-category').val();

            $('.product-item').each(function () {
                const name = $(this).data('name').toLowerCase();
                const itemCat = $(this).data('category');

                const matchesSearch = name.includes(searchTerm);
                const matchesCat = !category || itemCat === category;

                if (matchesSearch && matchesCat) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Generate Order
        $('#pos-generate-btn').on('click', function () {
            const $btn = $(this);
            const orderDate = $('#pos-order-date').val();
            const paymentMethod = $('#pos-payment-method').val() || 'cash';

            const formattedId = $('#current-order-id').text();

            showBanoksModal(
                'Generate Order?',
                `Are you sure you want to generate ${formattedId} for ${orderDate}?`,
                function () {
                    $btn.prop('disabled', true).text('Generating...');

                    $.ajax({
                        url: banoksPOS.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'banoks_pos_place_order',
                            nonce: banoksPOS.nonce,
                            items: cart,
                            order_date: orderDate,
                            payment_method: paymentMethod
                        },
                        success: function (response) {
                            if (response.success) {
                                $btn.text('Generated');
                                showBanoksModal('Success!', 'Order has been generated successfully.', function() {
                                    window.location.href = banoksPOS.dashboard_url;
                                });
                            } else {
                                const message = response.data && response.data.message ? response.data.message : 'Unable to generate order.';
                                showBanoksModal('Error', message);
                                $btn.prop('disabled', false).text('Generate Order');
                            }
                        },
                        error: function () {
                            showBanoksModal('Error', 'Unable to generate order. Please try again.');
                            $btn.prop('disabled', false).text('Generate Order');
                        }
                    });
                }
            );
        });

        // Clear/Cancel
        $('#pos-clear-btn, #pos-cancel-btn').on('click', function () {
            showBanoksModal(
                'Clear Cart?',
                'Are you sure you want to clear all items in the cart?',
                function() {
                    cart = [];
                    $('#pos-money-received').val('');
                    renderCart();
                }
            );
        });


        // --- Dashboard Logic ---

        // Date Picker Filtering - Auto Update
        $('#banoks-dashboard-date').on('change', function() {
            updateDashboardFilters(true);
        });

        // Status Filter - Auto Update & Clear Search
        $('#banoks-order-status-filter').on('change', function() {
            $('#order-search').val('BNK-ORD-'); // Clear any typed search
            updateDashboardFilters(false);
        });

        // Smart Search - Protect Prefix & Trigger
        $('#order-search').on('keydown', function(e) {
            const prefix = 'BNK-ORD-';
            const val = $(this).val();
            
            // Prevent deleting prefix
            if ((e.key === 'Backspace' || e.key === 'Delete') && val.length <= prefix.length) {
                if (this.selectionStart <= prefix.length) {
                    e.preventDefault();
                }
            }
        }).on('keyup', function(e) {
            if (e.key === 'Enter') {
                updateDashboardFilters(false);
            }
        });

        function updateDashboardFilters(forceDate) {
            const url = new URL(window.location.href);
            const date = $('#banoks-dashboard-date').val();
            const status = $('#banoks-order-status-filter').val() || 'all';
            const search = $('#order-search').val();

            if (date && (forceDate || url.searchParams.has('date'))) {
                url.searchParams.set('date', date);
            } else if (!forceDate) {
                url.searchParams.delete('date');
            }

            url.searchParams.set('view', 'history');
            url.searchParams.set('status', status);
            
            if (search && search !== 'BNK-ORD-') {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }

            window.location.href = url.toString();
        }

        // Status Updates (Prepare/Complete/Cancel)
        $('.action-prepare, .action-complete, .action-cancel').on('click', function () {
            const $btn = $(this);
            const orderId = $btn.data('id');
            let newStatus = 'cancelled';

            if ($btn.hasClass('action-prepare')) {
                newStatus = 'preparing';
            } else if ($btn.hasClass('action-complete')) {
                newStatus = 'completed';
            }

            const formattedId = 'BNK-ORD-' + orderId.toString().padStart(6, '0');

            showBanoksModal(
                'Update Status?',
                `Mark ${formattedId} as ${newStatus}?`,
                function () {
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: banoksPOS.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'banoks_pos_update_order_status',
                            nonce: banoksPOS.nonce,
                            order_id: orderId,
                            status: newStatus
                        },
                        success: function (response) {
                            if (response.success) {
                                $btn.closest('.order-card').fadeOut(function () {
                                    location.reload(); 
                                });
                            } else {
                                const message = response.data && response.data.message ? response.data.message : 'Unable to update order status.';
                                showBanoksModal('Error', message);
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function () {
                            showBanoksModal('Error', 'Unable to update order status. Please try again.');
                            $btn.prop('disabled', false);
                        }
                    });
                }
            );
        });

        // --- Reports Logic (Charts) ---
        let banoksOrderAudioContext = null;
        let banoksOrderSoundEnabled = false;
        const banoksAcknowledgedOrderIdsKey = 'banoksAcknowledgedOnlineOrderIds';
        let banoksAcknowledgedOrderIds = [];

        try {
            banoksAcknowledgedOrderIds = JSON.parse(window.localStorage.getItem(banoksAcknowledgedOrderIdsKey) || '[]');
            if (!Array.isArray(banoksAcknowledgedOrderIds)) {
                banoksAcknowledgedOrderIds = [];
            }
        } catch (error) {
            banoksAcknowledgedOrderIds = [];
        }

        function ensureOrderAudioContext() {
            if (!banoksOrderAudioContext) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) {
                    return null;
                }
                banoksOrderAudioContext = new AudioContext();
            }
            if (banoksOrderAudioContext.state === 'suspended') {
                banoksOrderAudioContext.resume();
            }
            return banoksOrderAudioContext;
        }

        function playNewOrderSound() {
            const context = ensureOrderAudioContext();
            if (!context) {
                return;
            }

            [0, 0.18, 0.36].forEach(function (offset) {
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, context.currentTime + offset);
                gain.gain.setValueAtTime(0.0001, context.currentTime + offset);
                gain.gain.exponentialRampToValueAtTime(0.18, context.currentTime + offset + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + offset + 0.14);
                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start(context.currentTime + offset);
                oscillator.stop(context.currentTime + offset + 0.16);
            });
        }

        function armOrderSound() {
            banoksOrderSoundEnabled = true;
            ensureOrderAudioContext();
            $(document).off('pointerdown mousedown click keydown touchstart', armOrderSound);
        }

        function refreshOnlineOrdersPanel(force) {
            if (!$('.banoks-online-orders-page').length) {
                return;
            }

            if (!force && $('#banoks-online-order-detail-modal.is-open, #banoks-online-action-modal.is-open').length) {
                return;
            }

            const currentView = $('.banoks-online-status-tab.is-active').data('online-view') || 'pending';

            $.get(window.location.href, function (html) {
                const $fresh = $('<div>').append($.parseHTML(html, document, true));
                const $freshPage = $fresh.find('.banoks-online-orders-page');
                if ($freshPage.length) {
                    $('.banoks-online-orders-page').replaceWith($freshPage);
                    showOnlineOrderView(currentView);
                }
            });
        }

        $(document).on('pointerdown mousedown click keydown touchstart', armOrderSound);

        function rememberAcknowledgedOrderIds(orderIds) {
            banoksAcknowledgedOrderIds = Array.from(new Set(banoksAcknowledgedOrderIds.concat(orderIds))).slice(-100);
            try {
                window.localStorage.setItem(banoksAcknowledgedOrderIdsKey, JSON.stringify(banoksAcknowledgedOrderIds));
            } catch (error) {
                // Ignore storage failures; the current page still keeps the IDs in memory.
            }
        }

        function notifyNewOnlineOrders(orders) {
            if (!orders.length) {
                return;
            }

            const orderIds = orders.map(function (order) {
                return String(order.id);
            });

            if (window.navigator && typeof window.navigator.vibrate === 'function') {
                window.navigator.vibrate([180, 80, 180]);
            }

            if (banoksOrderSoundEnabled) {
                playNewOrderSound();
            }

            rememberAcknowledgedOrderIds(orderIds);
        }

        function updateAgingOrderWarnings() {
            const warningAgeMs = 30 * 60 * 1000;
            const activeStatuses = ['pending', 'verifying', 'preparing', 'ready_for_pickup', 'delivering'];
            const now = Date.now();

            $('.banoks-aging-order-card').each(function () {
                const $card = $(this);
                const created = parseInt($card.attr('data-order-created-ts'), 10) || 0;
                const status = String($card.attr('data-status') || '').toLowerCase();
                const shouldWarn = created > 0 && activeStatuses.indexOf(status) !== -1 && now - created >= warningAgeMs;

                $card.toggleClass('banoks-order-aging-warning', shouldWarn);
            });
        }

        function updateNavBadge($badge, count) {
            $badge.data('count', count).text(count);
            if (count > 0) {
                $badge.show();
            } else {
                $badge.hide();
            }
        }

        function pollWalkInOrderCount() {
            if (typeof banoksPOS === 'undefined' || !banoksPOS.ajax_url || !banoksPOS.nonce) {
                return;
            }

            $.ajax({
                url: banoksPOS.ajax_url,
                type: 'POST',
                data: {
                    action: 'banoks_pos_walk_in_order_count',
                    nonce: banoksPOS.nonce
                },
                success: function (response) {
                    if (!response.success || !response.data) {
                        return;
                    }

                    const count = parseInt(response.data.count, 10) || 0;
                    updateNavBadge($('#banoks-walk-in-order-badge'), count);
                }
            });
        }

        function pollOnlineOrderNotifications() {
            if (typeof banoksPOS === 'undefined' || !banoksPOS.ajax_url || !banoksPOS.nonce) {
                return;
            }

            $.ajax({
                url: banoksPOS.ajax_url,
                type: 'POST',
                data: {
                    action: 'banoks_pos_online_order_notifications',
                    nonce: banoksPOS.nonce
                },
                success: function (response) {
                    if (!response.success || !response.data) {
                        return;
                    }

                    const orders = Array.isArray(response.data.orders) ? response.data.orders : [];
                    const count = parseInt(response.data.count, 10) || orders.length;
                    updateNavBadge($('#banoks-online-order-badge'), count);

                    const newOrders = orders.filter(function (order) {
                        return order && banoksAcknowledgedOrderIds.indexOf(String(order.id)) === -1;
                    });

                    if (newOrders.length) {
                        notifyNewOnlineOrders(newOrders);
                        refreshOnlineOrdersPanel();
                    }
                }
            });
        }

        pollWalkInOrderCount();
        setInterval(pollWalkInOrderCount, 15000);
        pollOnlineOrderNotifications();
        setInterval(pollOnlineOrderNotifications, 15000);
        updateAgingOrderWarnings();
        setInterval(updateAgingOrderWarnings, 30000);

        let pendingOnlineActionForm = null;

        function showOnlineOrderView(view) {
            const targetView = view || 'pending';

            $('.banoks-online-status-tab').toggleClass('is-active', false);
            $('.banoks-online-status-tab[data-online-view="' + targetView + '"]').toggleClass('is-active', true);
            $('.banoks-online-status-section').removeClass('is-active');
            $('.banoks-online-status-section[data-online-section="' + targetView + '"]').addClass('is-active');

            if (targetView === 'history') {
                filterOnlineOrders();
            }
        }

        function filterOnlineOrders() {
            const search = ($('#banoks-online-search').val() || '').toLowerCase().trim();
            const status = $('#banoks-online-status-filter').val() || '';
            const payment = $('#banoks-online-payment-filter').val() || '';
            let visibleCount = 0;
            const $historyRows = $('.banoks-online-history-row');

            $historyRows.each(function () {
                const $row = $(this);
                const matchesSearch = !search || String($row.data('search') || '').includes(search);
                const matchesStatus = !status || $row.data('status') === status;
                const matchesPayment = !payment || $row.data('payment') === payment;
                const show = matchesSearch && matchesStatus && matchesPayment;

                $row.toggle(show);
                if (show) {
                    visibleCount++;
                }
            });

            $('.banoks-online-filter-empty').toggle(visibleCount === 0 && $historyRows.length > 0);
        }

        $(document).on('click', '.banoks-online-status-tab', function () {
            showOnlineOrderView($(this).data('online-view') || 'pending');
        });

        $(document).on('input change', '#banoks-online-search, #banoks-online-status-filter, #banoks-online-payment-filter', filterOnlineOrders);
        showOnlineOrderView($('.banoks-online-status-tab.is-active').data('online-view') || 'pending');

        function openOnlineOrderDetailModal($card) {
            const $modal = $('#banoks-online-order-detail-modal');
            const $body = $('#banoks-online-modal-items-body');
            let items = [];

            try {
                items = JSON.parse($card.attr('data-items') || '[]');
            } catch (error) {
                items = [];
            }

            $('#banoks-online-modal-order-id').text($card.data('public-id') || '');
            $('#banoks-online-modal-date').text($card.data('created') || '');
            $('#banoks-online-modal-customer').text($card.data('customer') || '');
            $('#banoks-online-modal-phone').text($card.data('phone') || '');
            $('#banoks-online-modal-fulfillment').text($card.data('fulfillment-label') || '');
            $('#banoks-online-modal-area').text($card.data('area') || ($card.data('fulfillment') === 'pickup' ? 'Pickup' : ''));
            $('#banoks-online-modal-address').text($card.data('address') || ($card.data('fulfillment') === 'pickup' ? 'Pickup order' : ''));
            $('#banoks-online-modal-payment').text($card.data('payment-label') || '');
            $('#banoks-online-modal-total').text('\u20b1' + ($card.data('total') || '0.00'));
            const proofUrl = $card.data('proof-url') || '';
            const proofStatus = $card.data('proof-status') || '';
            $('#banoks-online-modal-proof').text(proofUrl ? '' : 'No payment proof needed');
            $('#banoks-online-modal-proof-link').toggle(!!proofUrl).attr('href', proofUrl || '#');
            const proofId = parseInt($card.data('proof-id'), 10) || 0;
            const canReviewProof = $card.data('payment') === 'gcash' && proofId && proofStatus === 'pending';
            $('#banoks-online-payment-proof-form').toggle(!!canReviewProof);
            $('#banoks-online-modal-proof-id').val(proofId || '');
            $('#banoks-online-modal-proof-status-input').val('');
            $('#banoks-online-rejection-reason').val('').prop('required', false);
            $('.banoks-rejection-fields').hide();
            $('#banoks-online-payment-proof-form button[data-proof-status="verified"]')
                .prop('disabled', !!proofUrl)
                .attr('title', proofUrl ? 'Open the screenshot before verifying this GCash payment.' : '');

            const driver = $card.data('driver') || '';
            $('#banoks-online-modal-driver-row').toggle(!!driver);
            $('#banoks-online-modal-driver').text(driver);

            const notes = $card.data('notes') || '';
            $('#banoks-online-modal-notes-row').toggle(!!notes);
            $('#banoks-online-modal-notes').text(notes);

            $body.empty();
            if (items.length) {
                items.forEach(function (item) {
                    const quantity = parseInt(item.quantity, 10) || 0;
                    const price = parseFloat(item.price) || 0;
                    const subtotal = parseFloat(item.subtotal) || 0;
                    $('<tr>')
                        .append($('<td>').text(item.name || 'Item'))
                        .append($('<td>').text(quantity))
                        .append($('<td>').text('\u20b1' + price.toFixed(2)))
                        .append($('<td>').text('\u20b1' + subtotal.toFixed(2)))
                        .appendTo($body);
                });
            } else {
                $('<tr>').append($('<td colspan="4">').text('No item details found.')).appendTo($body);
            }

            $modal.attr('aria-hidden', 'false').addClass('is-open');
        }

        $(document).on('click', '.banoks-online-order-row', function (event) {
            if ($(event.target).closest('form, button, a, input, select, textarea').length) {
                return;
            }
            openOnlineOrderDetailModal($(this));
        });

        $(document).on('click', '.banoks-review-payment-button', function (event) {
            event.preventDefault();
            openOnlineOrderDetailModal($(this).closest('.banoks-online-order-row'));
        });

        $(document).on('click', '#banoks-online-payment-proof-form button[data-proof-status]', function (event) {
            const proofStatus = $(this).data('proof-status') || '';
            $('#banoks-online-modal-proof-status-input').val(proofStatus);

            if (proofStatus !== 'rejected') {
                $('.banoks-rejection-fields').hide();
                $('#banoks-online-rejection-reason').prop('required', false);
            }

            if (proofStatus === 'rejected') {
                const $reason = $('#banoks-online-rejection-reason');
                if (!$reason.prop('required')) {
                    event.preventDefault();
                    $('.banoks-rejection-fields').show();
                    $reason.prop('required', true).trigger('focus');
                    return;
                }
            }
        });

        $(document).on('click', '#banoks-online-modal-proof-link', function () {
            $('#banoks-online-payment-proof-form button[data-proof-status="verified"]')
                .prop('disabled', false)
                .attr('title', '');
        });

        $(document).on('keydown', '.banoks-online-order-row', function (event) {
            if ($(event.target).closest('form, button, a, input, select, textarea').length) {
                return;
            }
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openOnlineOrderDetailModal($(this));
            }
        });

        $(document).on('submit', '.banoks-online-status-form', function (event) {
            const $form = $(this);
            const nextStatus = $form.find('input[name="new_status"]').val();
            const label = $form.find('.banoks-online-action-button').text();
            const orderId = $form.closest('.banoks-online-order-row').data('public-id') || 'this order';

            event.preventDefault();
            pendingOnlineActionForm = this;

            $('#banoks-online-action-title').text(label + '?');
            $('#banoks-online-action-message').text('Are you sure you want to ' + label.toLowerCase() + ' for ' + orderId + '?');
            $('.banoks-online-delivery-fields').toggle(nextStatus === 'delivering');
            $('.banoks-online-cancel-fields').toggle(nextStatus === 'cancelled');
            $('#banoks-modal-driver-name, #banoks-modal-driver-contact, #banoks-modal-cancel-reason').val('');
            $('#banoks-online-action-confirm').prop('disabled', false).text('Yes, Continue');
            $('#banoks-online-action-modal').attr('aria-hidden', 'false').addClass('is-open');
        });

        $(document).on('click', '#banoks-online-action-cancel', function () {
            pendingOnlineActionForm = null;
            closeBanoksModals($('#banoks-online-action-modal'));
        });

        $(document).on('click', '#banoks-online-action-confirm', function () {
            if (!pendingOnlineActionForm) {
                return;
            }

            const $form = $(pendingOnlineActionForm);
            const nextStatus = $form.find('input[name="new_status"]').val();

            if (nextStatus === 'delivering') {
                const driverName = $('#banoks-modal-driver-name').val().trim();
                const driverContact = $('#banoks-modal-driver-contact').val().trim();

                if (!driverName || !driverContact) {
                    $('#banoks-online-action-message').text('Please enter the delivery driver name and contact number.');
                    return;
                }

                $form.find('input[name="driver_name"]').val(driverName);
                $form.find('input[name="driver_contact"]').val(driverContact);
            }

            if (nextStatus === 'cancelled') {
                const cancelReason = $('#banoks-modal-cancel-reason').val().trim();

                if (!cancelReason) {
                    $('#banoks-online-action-message').text('Please enter the cancellation reason.');
                    return;
                }

                $form.find('input[name="status_note"]').val(cancelReason);
            }

            const requestData = $form.serializeArray();
            requestData.push({ name: 'action', value: 'banoks_pos_update_online_order_status' });
            requestData.push({ name: 'nonce', value: banoksPOS.nonce });

            $('#banoks-online-action-confirm').prop('disabled', true).text('Updating...');

            $.ajax({
                url: banoksPOS.ajax_url,
                type: 'POST',
                data: $.param(requestData),
                success: function (response) {
                    if (!response || !response.success) {
                        $('#banoks-online-action-message').text(response && response.data && response.data.message ? response.data.message : 'Unable to update this order.');
                        return;
                    }

                    pendingOnlineActionForm = null;
                    closeBanoksModals($('#banoks-online-action-modal'));
                    refreshOnlineOrdersPanel(true);
                    pollOnlineOrderNotifications();
                },
                error: function () {
                    $('#banoks-online-action-message').text('Unable to update this order. Please try again.');
                },
                complete: function () {
                    $('#banoks-online-action-confirm').prop('disabled', false).text('Confirm');
                }
            });
        });

        $(document).on('submit', '#banoks-online-payment-proof-form', function (event) {
            const $form = $(this);
            const submitter = event.originalEvent && event.originalEvent.submitter ? event.originalEvent.submitter : null;
            const submittedStatus = submitter ? $(submitter).data('proof-status') : '';
            const proofStatus = submittedStatus || $('#banoks-online-modal-proof-status-input').val();

            event.preventDefault();
            $('#banoks-online-modal-proof-status-input').val(proofStatus);

            if (!proofStatus) {
                alert('Please choose whether to verify or reject this payment proof.');
                return;
            }

            if (proofStatus === 'rejected' && !$('#banoks-online-rejection-reason').val().trim()) {
                $('.banoks-rejection-fields').show();
                $('#banoks-online-rejection-reason').prop('required', true).trigger('focus');
                return;
            }

            const requestData = $form.serializeArray();
            requestData.push({ name: 'action', value: 'banoks_pos_update_payment_proof' });
            requestData.push({ name: 'nonce', value: banoksPOS.nonce });

            $form.find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: banoksPOS.ajax_url,
                type: 'POST',
                data: $.param(requestData),
                success: function (response) {
                    if (!response || !response.success) {
                        alert(response && response.data && response.data.message ? response.data.message : 'Unable to update the payment proof.');
                        return;
                    }

                    closeBanoksModals($('#banoks-online-order-detail-modal'));
                    refreshOnlineOrdersPanel(true);
                    pollOnlineOrderNotifications();
                },
                error: function () {
                    alert('Unable to update the payment proof. Please try again.');
                },
                complete: function () {
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        });

        let $lastReportTransactionTrigger = null;

        function closeBanoksModals($modals) {
            const activeElement = document.activeElement;
            const shouldRestoreReportFocus = $modals.filter('#banoks-report-transaction-modal.is-open').length > 0;

            $modals.each(function () {
                if (activeElement && this.contains(activeElement)) {
                    activeElement.blur();
                }
            });

            $modals.attr('aria-hidden', 'true').removeClass('is-open');

            if (shouldRestoreReportFocus && $lastReportTransactionTrigger && $lastReportTransactionTrigger.length) {
                $lastReportTransactionTrigger.trigger('focus');
            }
        }

        function openReportTransactionModal($row) {
            const $modal = $('#banoks-report-transaction-modal');
            const $body = $('#banoks-report-modal-items-body');
            let items = [];

            $lastReportTransactionTrigger = $row;

            try {
                items = JSON.parse($row.attr('data-order-items') || '[]');
            } catch (error) {
                items = [];
            }

            $('#banoks-report-modal-order-id').text($row.data('order-id') || '');
            $('#banoks-report-modal-date').text($row.data('order-date') || '');
            $('#banoks-report-modal-type').text($row.data('order-type') || '');
            $('#banoks-report-modal-status').text($row.data('order-status') || '');
            $('#banoks-report-modal-total').text('\u20b1' + ($row.data('order-total') || '0.00'));

            $body.empty();
            if (items.length) {
                items.forEach(function (item) {
                    const quantity = parseInt(item.quantity, 10) || 0;
                    const price = parseFloat(item.price) || 0;
                    const subtotal = parseFloat(item.subtotal) || 0;
                    $('<tr>')
                        .append($('<td>').text(item.name || 'Item'))
                        .append($('<td>').text(quantity))
                        .append($('<td>').text('\u20b1' + price.toFixed(2)))
                        .append($('<td>').text('\u20b1' + subtotal.toFixed(2)))
                        .appendTo($body);
                });
            } else {
                $('<tr>')
                    .append($('<td colspan="4">').text('No item details found for this transaction.'))
                    .appendTo($body);
            }

            $modal.attr('aria-hidden', 'false').addClass('is-open');
            $modal.find('.banoks-report-modal-close').trigger('focus');
        }

        $(document).on('click', '.banoks-report-transaction-row', function () {
            openReportTransactionModal($(this));
        });

        $(document).on('keydown', '.banoks-report-transaction-row', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openReportTransactionModal($(this));
            }
        });

        $(document).on('click', '.banoks-report-modal-close, #banoks-report-transaction-modal, #banoks-online-order-detail-modal, #banoks-online-action-modal', function (event) {
            if (event.target !== this) {
                return;
            }
            closeBanoksModals($('#banoks-report-transaction-modal, #banoks-online-order-detail-modal, #banoks-online-action-modal'));
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeBanoksModals($('#banoks-report-transaction-modal, #banoks-online-order-detail-modal, #banoks-online-action-modal'));
            }
        });

        if ($('#banoksSalesChart').length) {
            const rawData = $('#daily-sales-data').val();
            const salesData = rawData ? JSON.parse(rawData) : [];
            
            const labels = salesData.map(d => d.date);
            const totals = salesData.map(d => parseFloat(d.total));

            const ctx = document.getElementById('banoksSalesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Revenue (\u20b1)',
                        data: totals,
                        borderColor: '#111827',
                        backgroundColor: 'rgba(17, 24, 39, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#111827',
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: function(value) { return '\u20b1' + value; }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });

})(jQuery);

(function($) {

    $(document).ready(function() {

        // Show / Hide passenger list filter
        $('.wbtm-section-toggle').click(function() {
                let $this = $(this);
                $('.mage-custom-filter-area').slideToggle('fast', "swing", function() {
                    if ($this.find('i').hasClass('fa-arrow-circle-up')) {
                        $this.find('i').removeClass('fa-arrow-circle-up');
                        $this.find('i').addClass('fa-arrow-circle-down');
                    } else {
                        $this.find('i').removeClass('fa-arrow-circle-down');
                        $this.find('i').addClass('fa-arrow-circle-up');
                    }

                });
            })
            // $('.mage-custom-filter-area').slideUp();

        // Notice dismisable
        $(document).on('click', '.is-dismissible .notice-dismiss', function(e) {
            e.preventDefault();
            $(this).parents('.is-dismissible').remove();
        });

        // Bus Report Detail
        $('.wbbm_bus_detail--report .wbbm_detail_inside').click(function() {
            let $this = $(this);
            let parent = $this.parents('tr');
            let bus_id = $this.parents('td').attr('data-bus-id');

            if (parent.next('.wbbm_report_detail').hasClass('show')) {
                parent.next('.wbbm_report_detail').removeClass('show');
                parent.next('.wbbm_report_detail').hide();
                return;
            }

            $('.wbbm-main-table tbody tr.wbbm_report_detail').each(function() {
                if ($(this).hasClass('show')) {
                    $(this).removeClass('show');
                    $(this).hide();
                }
            });

            $.ajax({
                url: ajaxurl,
                type: 'post',
                dataType: 'html',
                data: { bus_id: bus_id, action: 'wbbm_get_bus_details' },
                beforeSend: function() {
                    $this.parent().siblings('.wbbm_report_loading').show();
                },
                success: function(data) {
                    if (data) {
                        if (parent.next('.wbbm_report_detail').children().length == 0) {
                            $(data).insertAfter(parent);
                            parent.next('.wbbm_report_detail').slideDown(100);
                        }
                        if (parent.next('.wbbm_report_detail').hasClass('show')) {
                            parent.next('.wbbm_report_detail').hide();
                        } else {
                            parent.next('.wbbm_report_detail').slideDown(100);
                        }
                        parent.next('.wbbm_report_detail').toggleClass('show');

                        $this.parent().siblings('.wbbm_report_loading').hide();
                    }
                }
            });

        });


        // filtering field add by change bus

        $(document).on('change','[name="bus_id"]',function (e){
            let  $this = $(this);
            let  bus_id = $(this).val();
            let target=$this.closest('.mage-custom-filter-area-outer').find('.mage-custom-filter-area');

            $.ajax({
                url: ajaxurl,
                type: 'post',
                dataType: 'html',
                data: {
                    bus_id: bus_id,
                    action: 'wbbm_custom_field_for_single_bus'
                },
                beforeSend: function() {
                    dLoader(target);
                },
                success: function(data) {
                    $('.extra_field_for_single_bus').html(data);
                    dLoaderRemove(target);
                }
            });
        });


        // order wise detail report
        $('.wbbm_order_detail--report .wbbm_detail_inside').click(function() {
            let $this = $(this);
            let parent = $this.parents('tr');
            let order_id = $this.parents('td').attr('data-order-id');

            if (parent.next('.wbbm_report_detail').hasClass('show')) {
                parent.next('.wbbm_report_detail').removeClass('show');
                parent.next('.wbbm_report_detail').hide();
                return;
            }

            $('.wbbm-main-table-order-wise tbody tr.wbbm_report_detail').each(function() {
                if ($(this).hasClass('show')) {
                    $(this).removeClass('show');
                    $(this).hide();
                }
            });

            $.ajax({
                url: ajaxurl,
                type: 'post',
                dataType: 'html',
                data: { order_id: order_id, action: 'wbbm_get_order_details' },
                beforeSend: function() {
                    $this.parent().siblings('.wbbm_report_loading').show();
                },
                success: function(data) {
                    if (data) {
                        if (parent.next('.wbbm_report_detail').children().length == 0) {
                            $(data).insertAfter(parent);
                            parent.next('.wbbm_report_detail').slideDown(100);
                        }
                        if (parent.next('.wbbm_report_detail').hasClass('show')) {
                            parent.next('.wbbm_report_detail').hide();
                        } else {
                            parent.next('.wbbm_report_detail').slideDown(100);
                        }
                        parent.next('.wbbm_report_detail').toggleClass('show');

                        $this.parent().siblings('.wbbm_report_loading').hide();
                        // $this.toggleClass('wbbm_report_detail_active');
                    }
                }
            });

        });

        $('#user_id').select2({
            width: 'resolve',
            theme: "classic"
        });
        $('#bus_id').select2({
            width: 'resolve',
            theme: "classic"
        });
        $('#boarding_point').select2({
            width: 'resolve',
            theme: "classic"
        });
        $('#dropping_point').select2({
            width: 'resolve',
            theme: "classic"
        });

        $("#one_from_date").datepicker({
            dateFormat: "yy-mm-dd",
        });
        $("#one_to_date").datepicker({
            dateFormat: "yy-mm-dd",
        });

        $("#three_from_date").datepicker({
            dateFormat: "yy-mm-dd",
        });
        $("#three_to_date").datepicker({
            dateFormat: "yy-mm-dd",
        });

        $('#mage-f-journey-date').datepicker({
            dateFormat: "yy-mm-dd",
        });

        $('input[name="filter_booking_date"]').datepicker({
            dateFormat: "yy-mm-dd",
        });

        $('#j_date').datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0
        });
        $('#rr_date').datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0
        });

        /*
        $('input[name="j_date"]').datepicker({
            format: "yyyy-mm-dd",
            autoHide: true,
            startDate: new Date()
        });
        $('input[name="r_date"]').datepicker({
            format: "yyyy-mm-dd",
            autoHide: true,
            startDate: new Date()
        });
        */

        // Bus Item slide
        $('.wbtm_bus_detail_btn').click(function() {
            let target = $(this).parents('.wbtm_bus_list_item').find('.item_bottom');
            if (target.is(':visible')) {
                target.slideUp(300);
            } else {
                $('.wbtm_bus_list_item').find('.item_bottom').slideUp(300);
                target.slideDown(300);
            }
        });

        // Without Seat Plan
        $('.mage-seat-qty input').on('input', function() {
            let $this = $(this);
            let parents = $(this).parents('.admin-bus-details');
            let price = $this.attr('data-price');
            let type = $this.attr('data-seat-type');
            let qty = $this.val();
            qty = qty > 0 ? qty : 0;
            $this.parent().siblings('.mage-seat-price').find('.price-figure').text(price * qty);
            $this.parent().siblings('.mage-seat-price').attr('data-price', (price * qty));

            let p = 0.00;
            $this.parents('.mage-seat-table').find('tbody tr').each(function() {
                if (parseFloat($(this).find('.mage-seat-price').attr('data-price'))) {
                    p = p + parseFloat($(this).find('.mage-seat-price').attr('data-price'));
                }

            });

            $this.parents('.mage-seat-table').find('.mage-price-total .price-figure').text(parseFloat(p));

            // Enable Booking Button
            if (type == 'adult') {
                if (qty > 0) {
                    $('.no-seat-submit-btn').prop('disabled', false);
                } else {
                    $('.no-seat-submit-btn').prop('disabled', true);
                }
            }

            // Append Custom Registration Field
            if (type) {
                if (qty > 0) { // If Adult, Child or Infant have qty then Passenger info of Extra service will be remove
                    mageCustomRegField($('.mage-seat-qty input'), 'es', 0);
                }
                mageCustomRegField($(this), type, qty);
            }

            mage_form_builder_conditional_show($this);

            // Grand total show
            mageGrandPrice(parents);

        });

        // Change qty button
        $('.wbtm-qty-change').click(function(e) {
            e.preventDefault();
            let changeType = $(this).attr('data-qty-change');
            let targetEle = $(this).siblings('.qty-input');
            let qty = parseInt(targetEle.val());
            let qtyUpdated = 0;

            if (changeType == 'inc') {
                qtyUpdated = (qty > 0 ? qty + 1 : 1);
            } else {
                qtyUpdated = (qty > 0 ? qty - 1 : 0);
            }

            targetEle.val(qtyUpdated); // Update qty
            targetEle.trigger('input');
        });




        $('#wbtm_start').select2({
            width: 'resolve',
            theme: "classic"
        });
        $('#wbtm_end').select2({
            width: 'resolve',
            theme: "classic"
        });

        // Custom Tab
        $('.clickme button').click(function(e) {
            e.preventDefault();
            $('.clickme button').removeClass('wbtm_tab_active');
            $(this).addClass('wbtm_tab_active');
            var tagid = $(this).attr('data-tag');
            var tab_no = $(this).attr('data-tab-no');
            $('.wbtm_content_item').removeClass('active').addClass('hide');

            $('#' + tagid).addClass('active').removeClass('hide');

            $.ajax({
                url: wbtm_ajaxurl,
                type: 'post',
                data: { tab_no: tab_no, action: 'wbtm_tab_assign' }
            });
        });
        // Custom Tab END

        // Detail Toggle
        $('.admin-general-bus-detail-toggle').click(function() {
            let target = $(this).parents('.admin-bus-list').next('.admin-bus-details');
            if (target.is(':visible')) {
                target.slideUp(300);
            } else {
                $('.admin-bus-list').next('.admin-bus-details').slideUp(300);
                target.slideDown(300);
            }
        });

        // Extra service
        // qty inc and dec
        $('.wbtm_extra_service_table .qty_dec').click(function(e) {
            e.preventDefault();
            let target = $(this).siblings('.extra-qty-box');
            let qty = target.val();
            let min = target.attr('min');

            if (qty >= 1) {
                qty = parseInt(qty) - 1
                target.val(qty);
            } else {
                target.val(0);
            }
            target.trigger('input');

            mage_form_builder_conditional_show($(this));
        });

        $('.wbtm_extra_service_table .qty_inc').click(function(e) {
            e.preventDefault();
            let target = $(this).siblings('.extra-qty-box');
            let qty = target.val();
            let max = target.attr('max');

            if (qty <= parseInt(max)) {
                qty = parseInt(qty) + 1
                target.val(qty);
            }
            target.trigger('input');

            mage_form_builder_conditional_show($(this));
        });


        $(document).on('input', '.exs_change_value input', function() {
            let $this = $(this);
            let parent = $(this).parent();
            let $this_val = $this.val();
            let other_val = parent.siblings('.exs_change_value').find('input').val();
            parent.siblings('.exs_total').find('.price_figure').text($this_val * other_val);
        });
        // qty inc and dec END

        $('.wbtm_extra_service_table .extra-qty-box').on('input', function() {
            let parent = $(this).parents('.admin-bus-details');
            let price = $(this).attr('data-price');
            let qty = $(this).val();
            let total = qty > 0 ? qty * price : 0;

            $(this).parents('tr').attr('data-total', total);
            if (total > 0) {
                parent.find('.single_add_to_cart_button').show();
            }

            mageGrandPrice(parent);
        });

        // Custom per price set on extra service
        $('.extra_service_per_price').on('input', function() {
            let $this = $(this);
            let value = $this.val();
            let t = $this.parents('td').prev().find('.extra-qty-box');
            t.attr('data-price', value);
            $('.wbtm_extra_service_table .extra-qty-box').trigger('input');

        });

        // Extra service END

        // Notification hide
        function wbtm_notification_hide(time, speed) {
            $(".wbtm_notification").delay(time).fadeOut(speed);
        }

        // admin bag qty oparation
        $(document).on({
            click: function() {
                let target = $(this).siblings('input');
                let value = (parseInt(target.val()) - 1) > 0 ? (parseInt(target.val()) - 1) : 0;
                target.attr('value', value);
                target.trigger('input');
                mageTicketQty(target, value);
                let target2 = $(this).parents('form');
                admin_mageGrandPrice(target2);
            }
        }, '.mage_qty_dec');
        $(document).on({
            click: function() {
                let target = $(this).siblings('input');
                let max = parseInt(target.attr('max'));
                if (parseInt(target.val()) < max) {
                    let value = parseInt(target.val()) + 1;
                    target.attr('value', value);
                    target.trigger('input');
                    mageTicketQty(target, value);

                }
                let target2 = $(this).parents('form');
                admin_mageGrandPrice(target2);
            }
        }, '.mage_qty_inc');
        // admin bag qty oparation END

    });

    function mageTicketQty(target, value) {
        let minSeat = parseInt(target.attr('min'));
        let maxSeat = parseInt(target.attr('max'));
        target.siblings('.mage_qty_inc , .mage_qty_dec').removeClass('mage_disabled');
        if (value < minSeat || isNaN(value) || value === 0) {
            value = minSeat;
            target.siblings('.mage_qty_dec').addClass('mage_disabled');
        }
        if (value > maxSeat) {
            value = maxSeat;
            target.siblings('.mage_qty_inc').addClass('mage_disabled');
        }
        target.val(value);

    }

    // Show Grand Price
    function mageGrandPrice(parent) {
        let grand_ele = parent.find('.mage-grand-total .mage-price-figure');

        // price items
        let seat_price = 0.00; // 1
        if (parent.find('.mage-price-total .price-figure').text()) {
            seat_price = parseFloat(parent.find('.mage-price-total .price-figure').text());
        }

        let extra_price = 0;
        parent.find('.wbtm_extra_service_table tbody tr').each(function() { // 2
            extra_price += parseFloat($(this).attr('data-total'));
        });

        // Sum all items
        let grand_total = seat_price + extra_price;

        if (grand_total) {
            grand_ele.text(php_vars.currency_symbol + grand_total.toFixed(2));
            parent.find('button[name="add-to-cart-admin"]').prop('disabled', false);
        } else {
            grand_ele.text(php_vars.currency_symbol + "0.00");
            parent.find('button[name="add-to-cart-admin"]').prop('disabled', true);
        }
    }

    function admin_mageGrandPrice(parent) {
        let bus_type = parent.find('input[name="wbtm_bus_type"]').val();
        let grand_ele = parent.find('.mage-grand-total .mage-price-figure');
        let bus_zero_price_allow = parent.find('input[name="wbtm_bus_zero_price_allow"]').val();
        let bagPerPrice = 0;
        let bagQty = 0;
        let bagPrice = 0;

        // price items
        let seat_price = parseFloat(parent.find('.mage-price-total .price-figure').text()); // 1
        let extra_price = 0;
        parent.find('.wbtm_extra_service_table tbody tr').each(function() { // 2
            extra_price += parseFloat($(this).attr('data-total'));
        });

        // Extra bag price
        parent.find('.mage_customer_info_area input[name="extra_bag_quantity[]"]').each(function(index) {
            bagPerPrice = parseFloat($(this).attr('data-price'));
            bagQty += parseInt($(this).attr('value'));
            bagPrice += parseFloat($(this).val()) * bagPerPrice;
        });

        // Sum all items
        let grand_total = seat_price + extra_price + bagPrice;

        if (grand_total) {
            grand_ele.text(php_vars.currency_symbol + grand_total.toFixed(2));
            //parent.find('button[name="add-to-cart"]').prop('disabled', false);
        } else {
            grand_ele.text(php_vars.currency_symbol + "0.00");
            //(bus_type == 'general') ? parent.find('button[name="add-to-cart"]').prop('disabled', true): null;
        }


    }

    // Custom Reg Field New way
    function mageCustomRegField($this, seatType, qty, onlyES = false) {
        let parent = $this.parents('.admin-bus-details');
        let bus_id = parent.attr('data-bus-id');
        $.ajax({
            url: wbtm_ajaxurl,
            type: 'POST',
            async: true,
            data: { busID: bus_id, seatType: seatType, seats: qty, onlyES: onlyES, action: 'wbtm_form_builder' },
            beforeSend: function() {
                parent.find('.wbtm-form-builder-loading').show();
            },
            success: function(data) {
                let s = seatType.toLowerCase();
                if (data !== '') {
                    $(".wbtm-form-builder-" + s).html(data).find('.mage_hidden_customer_info_form').each(function(index) {
                        onlyES ? $(this).find('input[name="seat_name[]"]').remove() : null;
                        if (seatType != 'es') {
                            let h = $(this).find('.mage_title h5').text();
                            $(this).find('.mage_title h5').html(h + ' ' + (index + 1));
                        }
                        $(this).removeClass('mage_hidden_customer_info_form').find('.mage_form_list').slideDown(200);
                        //$(this).find('.mage_title h5').html(seatType + ' : ' + (index + 1));
                    });

                } else {
                    parent.find(".wbtm-form-builder-" + s).empty();
                }
                parent.find('.wbtm-form-builder-loading').hide();
            }
        });
    }



    function mage_form_builder_conditional_show($this) {
        let seat_plan_type = $this.parents('.admin-bus-details').find('input[name="wbtm_order_seat_plan"]').val();

        // ES qty
        let es_table = $this.parents('.admin-bus-details').find('.wbtm_extra_service_table');
        let es_qty = 0;
        es_table.find('tbody tr').each(function() {
            tp = $(this).find('.extra-qty-box').val();
            es_qty += tp > 0 ? parseInt(tp) : 0;
        });

        // Seat qty
        let seat_qty = 0;
        if (seat_plan_type == 'yes') {
            seat_qty = $this.parents('.admin-bus-details').find('input[name="total_seat"]').val();
            seat_qty = seat_qty ? seat_qty : 0;

            if (es_qty > 0 && parseInt(seat_qty) < 1) { // Only es
                wbtm_seat_plan_form_builder_new($this, 'ES', true);
            }
            if (es_qty == 0) { // Only es
                $this.parents('.admin-bus-details').find(".mage_customer_info_area .seat_name_ES").remove();
            }

        } else {
            let parents = $this.parents('.mage-no-seat').find('.mage-seat-table');
            seat_qty = 0;
            parents.find('tbody tr').each(function() {
                tp = $(this).find('.qty-input').val();
                seat_qty += tp > 0 ? parseInt(tp) : 0;
            });

            if (es_qty > 0 && seat_qty < 1) {
                mageCustomRegField(parents.find('.mage-seat-qty input'), 'es', "1", true);
            } else {
                mageCustomRegField(parents.find('.mage-seat-qty input'), 'es', "0", true);
            }
        }
    }


})(jQuery);
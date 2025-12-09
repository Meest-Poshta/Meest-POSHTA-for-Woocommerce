jQuery(document).ready(function() {
    let MeestParcel = function($) {
        
        let initSenderAddress = function() {
            new MeestAddress({
                type: 'sender',
                inputContainer: 'tr',
                separateContainer: false,
                deliveryType: {
                    block: $('input[type="radio"][name="sender[delivery_type]"]'),
                    branch: $('#meest_sender_branch_delivery'),
                    poshtomat: $('#meest_sender_poshtomat_delivery'),
                    address: $('#meest_sender_address_delivery')
                },
                country: {
                    code: $('#meest_sender_country'),
                    id: $('#meest_sender_country_id'),
                    text: $('#meest_sender_country_text')
                },
                region: {
                    text: $('#meest_sender_region_text')
                },
                city: {
                    id: $('#meest_sender_city_id'),
                    text: $('#meest_sender_city_text')
                },
                street: {
                    id: $('#meest_sender_street_id'),
                    text: $('#meest_sender_street_text')
                },
                building: $('#meest_sender_building'),
                flat: $('#meest_sender_flat'),
                postcode: {
                    text: $('#meest_sender_postcode')
                },
                branch: {
                    id: $('#meest_sender_branch_id'),
                    text: $('#meest_sender_branch_text')
                },
                poshtomat: {
                    id: $('#meest_sender_poshtomat_id'),
                    text: $('#meest_sender_poshtomat_text')
                }
            }).init();
        };

        let initReceiverAddress = function() {
            new MeestAddress({
                type: 'receiver',
                inputContainer: 'tr',
                separateContainer: false,
                deliveryType: {
                    block: $('input[type="radio"][name="receiver[delivery_type]"]'),
                    branch: $('#meest_receiver_branch_delivery'),
                    poshtomat: $('#meest_receiver_poshtomat_delivery'),
                    address: $('#meest_receiver_address_delivery')
                },
                country: {
                    code: $('#meest_receiver_country'),
                    id: $('#meest_receiver_country_id'),
                    text: $('#meest_receiver_country_text')
                },
                region: {
                    text: $('#meest_receiver_region_text')
                },
                city: {
                    id: $('#meest_receiver_city_id'),
                    text: $('#meest_receiver_city_text')
                },
                street: {
                    id: $('#meest_receiver_street_id'),
                    text: $('#meest_receiver_street_text')
                },
                building: $('#meest_receiver_building'),
                flat: $('#meest_receiver_flat'),
                postcode: {
                    container: 'tr',
                    text: $('#meest_receiver_postcode')
                },
                branch: {
                    id: $('#meest_receiver_branch_id'),
                    text: $('#meest_receiver_branch_text')
                },
                poshtomat: {
                    id: $('#meest_receiver_poshtomat_id'),
                    text: $('#meest_receiver_poshtomat_text')
                }
            }).init();
        };

        let initParcel = function() {
            $('#meest_parcel_pack_type').change(function() {
                if (this.value === '') {
                    $('#meest_parcel_lwh').css('display', 'grid');
                } else {
                    $('#meest_parcel_lwh').hide();
                }
            });
        };

        return {
            init: function() {
                initSenderAddress();
                initReceiverAddress();
                initParcel();
            }
        };
    }(jQuery);

    MeestParcel.init();
});

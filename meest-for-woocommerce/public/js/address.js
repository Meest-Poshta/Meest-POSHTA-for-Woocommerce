let MeestAddress = function(data) {
    let type = data.type || 'billing',
        inputContainer = data.inputContainer || 'tr',
        separateContainer = data.separateContainer || false,
        deliveryType = data.deliveryType ? {
            block: data.deliveryType.block,
            branch: data.deliveryType.branch,
            address: data.deliveryType.address
        } : null,
        country = {
            code: data.country.code,
            id: data.country.id,
            text: data.country.text
        },
        region = {
            text: data.region.text
        },
        city = {
            id: data.city.id,
            text: data.city.text
        },
        street = {
            id: data.street.id,
            text: data.street.text
        },
        building = data.building,
        flat = data.flat,
        postcode = {
            container: data.postcode.container,
            text: data.postcode.text
        },
        branch = data.branch ? {
            id: data.branch.id,
            text: data.branch.text
        } : null,
        poshtomat = data.poshtomat ? {
            id: data.poshtomat.id,
            text: data.poshtomat.text
        } : null;

    return {
        initCountry: function(country) {
            return country.selectWoo({
                minimumInputLength: null,
                ajax: {
                    type: 'POST',
                    url: meest.ajaxUrl,
                    delay: 600,
                    dataType: 'json',
                    data: params => ({
                        action: meest.actions.get_country,
                        text: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text,
                            code: item.code
                        }))
                    })
                }
            });
        },

        initCity: function(city, country) {
            const countryVal = country.val();
            // Поддерживаем и код 'UA' и UUID Украины
            if (countryVal === meest.country_id.ua || countryVal === 'UA') {
                return city.selectWoo({
                    ajax: {
                        type: 'POST',
                        url: meest.ajaxUrl,
                        delay: 600,
                        dataType: 'json',
                        data: params => ({
                            action: meest.actions.get_city,
                            country: country.val(),
                            text: params.term
                        }),
                        processResults: data => ({
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                region: item.region,
                                city: item.city
                            }))
                        })
                    }
                });
            }
        },

        initStreet: function(street, city, country) {
            const countryVal = country.val();
            // Поддерживаем и код 'UA' и UUID Украины
            if (countryVal === meest.country_id.ua || countryVal === 'UA') {
                return street.selectWoo({
                    ajax: {
                        type: 'POST',
                        url: meest.ajaxUrl,
                        delay: 600,
                        dataType: 'json',
                        data: params => ({
                            action: meest.actions.get_street,
                            city: city.val(),
                            text: params.term
                        }),
                        processResults: data => ({
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text
                            }))
                        })
                    }
                });
            }
        },

        initBranch: function(branch, city, type) {
            return branch.selectWoo({
                ajax: {
                    type: 'POST',
                    url: meest.ajaxUrl,
                    delay: 600,
                    dataType: 'json',
                    data: params => {
                        // Используем ID города (UUID) из city.val()
                        return {
                            action: meest.actions.get_branch,
                            city: city.val(),
                            text: params.term
                        };
                    },
                    processResults: data => {
                        // Проверяем что data это массив
                        if (!Array.isArray(data)) {
                            return { results: [] };
                        }
                        
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                description: item.description
                            }))
                        };
                    }
                },
                templateResult: function(option) {
                    // Исправлена опечатка в закрывающем теге small
                    return jQuery('<strong>' + option.text + '</strong>' + 
                        (option.description ? '<div><small>' + option.description + '</small></div>' : ''));
                }
            });
        },

        initPoshtomat: function(poshtomat, city, type) {
            return poshtomat.selectWoo({
                ajax: {
                    type: 'POST',
                    url: meest.ajaxUrl,
                    delay: 600,
                    dataType: 'json',
                    data: params => {
                        // Используем ID города (UUID) из city.val()
                        return {
                            action: meest.actions.get_poshtomat,
                            city: city.val(),
                            text: params.term
                        };
                    },
                    processResults: data => {
                        // Проверяем что data это массив
                        if (!Array.isArray(data)) {
                            return { results: [] };
                        }
                        
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                description: item.description
                            }))
                        };
                    }
                },
                templateResult: function(option) {
                    return jQuery('<strong>' + option.text + '</strong>' + 
                        (option.description ? '<div><small>' + option.description + '</small></div>' : ''));
                }
            });
        },

        init: function() {
            let self = this;

            deliveryType && deliveryType.block.change(function() {
                if (this.value === 'branch') {
                    branch && branch.id.removeAttr('disabled').closest(inputContainer).show();
                    poshtomat && poshtomat.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    street.id.closest(inputContainer).hide();
                    building.closest(inputContainer).hide();
                    flat.closest(inputContainer).hide();
                    postcode.text.closest(inputContainer).hide();

                    branch && self.initBranch(branch.id, city.id, type).on('select2:select', e => branch.text.val(e.params.data.text));
                } else if (this.value === 'poshtomat') {
                    poshtomat && poshtomat.id.removeAttr('disabled').closest(inputContainer).show();
                    branch && branch.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    street.id.closest(inputContainer).hide();
                    building.closest(inputContainer).hide();
                    flat.closest(inputContainer).hide();
                    postcode.text.closest(inputContainer).hide();

                    poshtomat && self.initPoshtomat(poshtomat.id, city.id, type).on('select2:select', e => poshtomat.text.val(e.params.data.text));
                } else {
                    branch && branch.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    poshtomat && poshtomat.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    street.id.removeAttr('disabled').closest(inputContainer).show();
                    building.removeAttr('disabled').closest(inputContainer).show();
                    flat.removeAttr('disabled').closest(inputContainer).show();
                    postcode.text.removeAttr('disabled').closest(inputContainer).show();

                    self.initStreet(street.id, city.id, country.id).on('select2:select', e => street.text.val(e.params.data.text));
                }
            });

            self.initCountry(country.id).on('select2:select', function(e) {
                if (e.params) {
                    country.code && country.code.val(e.params.data.code);
                    country.text.val(e.params.data.text);
                }

                const selectedCountry = country.id.val();
                if (selectedCountry === meest.country_id.ua || selectedCountry === 'UA') {
                    region.text.closest(inputContainer).hide();
                    deliveryType && deliveryType.block.closest(inputContainer).show();
                    city.id.removeAttr('disabled').closest(inputContainer).show();
                    postcode.text.attr('disabled', 'disabled').closest(postcode.container).hide();

                    if (separateContainer) {
                        city.text.closest(inputContainer).hide();
                        street.text.closest(inputContainer).hide();
                    } else {
                        city.text.hide();
                        street.text.hide();
                    }

                    self.initCity(city.id, country.id).on('select2:select', e => {
                        city.text.val(e.params.data.city);
                        street.id.empty().trigger('change');
                        street.text.val('');
                        branch && branch.id.empty().trigger('change');
                        branch && branch.text.val('');
                        poshtomat && poshtomat.id.empty().trigger('change');
                        poshtomat && poshtomat.text.val('');
                    });

                    if (deliveryType && deliveryType.branch && deliveryType.branch.is(':checked')) {
                        branch && branch.id.removeAttr('disabled').closest(inputContainer).show();
                        poshtomat && poshtomat.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                        street.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                        building.attr('disabled', 'disabled').closest(inputContainer).hide();
                        flat.attr('disabled', 'disabled').closest(inputContainer).hide();
                        postcode.text.attr('disabled', 'disabled').closest(inputContainer).hide();

                        branch && self.initBranch(branch.id, city.id, type).on('select2:select', e => branch.text.val(e.params.data.text));
                    } else if (deliveryType && deliveryType.poshtomat && deliveryType.poshtomat.is(':checked')) {
                        poshtomat && poshtomat.id.removeAttr('disabled').closest(inputContainer).show();
                        branch && branch.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                        street.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                        building.attr('disabled', 'disabled').closest(inputContainer).hide();
                        flat.attr('disabled', 'disabled').closest(inputContainer).hide();
                        postcode.text.attr('disabled', 'disabled').closest(inputContainer).hide();

                        poshtomat && self.initPoshtomat(poshtomat.id, city.id, type).on('select2:select', e => poshtomat.text.val(e.params.data.text));
                    } else {
                        branch && branch.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                        poshtomat && poshtomat.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                        street.id.removeAttr('disabled').closest(inputContainer).show();
                        building.removeAttr('disabled').closest(inputContainer).show();
                        flat.removeAttr('disabled').closest(inputContainer).show();
                        postcode.text.removeAttr('disabled').closest(postcode.container).show();

                        self.initStreet(street.id, city.id, country.id).on('select2:select', e => street.text.val(e.params.data.text));
                    }
                } else {
                    region.text.removeAttr('disabled').closest(inputContainer).show();
                    deliveryType && deliveryType.block.closest(inputContainer).hide();
                    city.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    street.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    branch && branch.id.attr('disabled', 'disabled').closest(inputContainer).hide();
                    building.removeAttr('disabled').closest(inputContainer).show();
                    flat.removeAttr('disabled').closest(inputContainer).show();
                    postcode.text.removeAttr('disabled').closest(postcode.container).show();

                    if (separateContainer) {
                        city.text.removeAttr('disabled').closest(inputContainer).show();
                        street.text.removeAttr('disabled').closest(inputContainer).show();
                    } else {
                        city.text.show();
                        street.text.show();
                    }
                }
            });

            // Инициализируем город сразу если страна уже выбрана (Украина)
            const currentCountry = country.id.val();
            if (currentCountry === meest.country_id.ua || currentCountry === 'UA') {
                self.initCity(city.id, country.id).on('select2:select', e => {
                    city.text.val(e.params.data.city);
                    street.id.empty().trigger('change');
                    street.text.val('');
                    branch && branch.id.empty().trigger('change');
                    branch && branch.text.val('');
                    poshtomat && poshtomat.id.empty().trigger('change');
                    poshtomat && poshtomat.text.val('');
                });
            }

            country.id.trigger('change');
        }
    };
};

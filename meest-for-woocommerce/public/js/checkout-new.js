/**
 * Meest Checkout - Новая реализация для стандартного WooCommerce checkout
 */
(function($) {
    'use strict';

    const MeestCheckout = {
        
        init: function() {
            this.bindEvents();
            this.checkShippingMethod();
        },

        bindEvents: function() {
            const self = this;
            
            // При изменении метода доставки
            $(document.body).on('change', 'input[name^="shipping_method"]', function() {
                self.checkShippingMethod();
            });

            // При обновлении checkout
            $(document.body).on('updated_checkout', function() {
                self.checkShippingMethod();
            });

            // При изменении ship_to_different_address
            $(document).on('change', '#ship-to-different-address-checkbox', function() {
                self.checkShippingMethod();
            });
        },

        checkShippingMethod: function() {
            const self = this;
            const selectedMethod = $('input[name^="shipping_method"]:checked').val();
            
            if (selectedMethod && selectedMethod.indexOf('meest') === 0) {
                self.showFields();
            } else {
                self.hideFields();
            }
        },

        showFields: function() {
            const fieldType = this.getFieldType();
            const container = $('#meest-' + fieldType + '-fields');
            
            if (container.length && container.is(':empty')) {
                this.renderFields(fieldType, container);
            }
            
            $('.meest-shipping-fields').show();
            
            // Скрываем стандартные поля адреса WooCommerce
            this.hideWooCommerceAddressFields();
        },

        hideFields: function() {
            $('.meest-shipping-fields').hide();
            
            // Показываем обратно стандартные поля адреса WooCommerce
            this.showWooCommerceAddressFields();
        },
        
        hideWooCommerceAddressFields: function() {
            // Скрываем поля адреса в зависимости от типа (billing или shipping)
            const fieldType = this.getFieldType();
            
            // Скрываем все поля с классом address-field, НО НЕ поле страны
            $(`.woocommerce-${fieldType}-fields .form-row.address-field`).not(`#${fieldType}_country_field`).addClass('meest-hide-field');
            
            // Дополнительно скрываем конкретные поля адреса (без страны)
            const fieldsToHide = [
                `#${fieldType}_address_1_field`,
                `#${fieldType}_address_2_field`,
                `#${fieldType}_city_field`,
                `#${fieldType}_postcode_field`,
                `#${fieldType}_state_field`
            ];
            
            fieldsToHide.forEach(function(selector) {
                $(selector).addClass('meest-hide-field');
            });
        },
        
        showWooCommerceAddressFields: function() {
            // Убираем класс скрытия со всех полей
            $('.form-row.address-field').removeClass('meest-hide-field');
            
            // Убираем со всех полей которые мы скрывали
            $('[id$="_address_1_field"], [id$="_address_2_field"], [id$="_city_field"], [id$="_postcode_field"], [id$="_state_field"]')
                .removeClass('meest-hide-field');
        },

        getFieldType: function() {
            return $('#ship-to-different-address-checkbox').is(':checked') ? 'shipping' : 'billing';
        },

        renderFields: function(type, container) {
            const data = meestCheckoutData;
            const i18n = data.i18n;
            const enabled = data.enabledDeliveryTypes;
            
            // Определяем первый активный тип доставки для checked
            let firstEnabled = '';
            if (enabled.branch) firstEnabled = 'branch';
            else if (enabled.poshtomat) firstEnabled = 'poshtomat';
            else if (enabled.address) firstEnabled = 'address';
            
            // Формируем радио-кнопки только для активных типов
            let deliveryTypeRadios = '';
            if (enabled.branch) {
                deliveryTypeRadios += `
                    <label>
                        <input type="radio" name="meest_${type}_delivery_type" value="branch" ${firstEnabled === 'branch' ? 'checked' : ''}>
                        ${i18n.deliveryToBranch}
                    </label>`;
            }
            if (enabled.poshtomat) {
                deliveryTypeRadios += `
                    <label>
                        <input type="radio" name="meest_${type}_delivery_type" value="poshtomat" ${firstEnabled === 'poshtomat' ? 'checked' : ''}>
                        ${i18n.deliveryToPoshtomat}
                    </label>`;
            }
            if (enabled.address) {
                deliveryTypeRadios += `
                    <label>
                        <input type="radio" name="meest_${type}_delivery_type" value="address" ${firstEnabled === 'address' ? 'checked' : ''}>
                        ${i18n.deliveryToAddress}
                    </label>`;
            }
            
            // Подсчитываем количество активных типов
            const enabledCount = (enabled.branch ? 1 : 0) + (enabled.poshtomat ? 1 : 0) + (enabled.address ? 1 : 0);
            
            // Показываем радио-кнопки только если типов больше одного
            const deliveryTypeBlock = enabledCount > 1 ? `
                <p class="form-row form-row-wide">
                    ${deliveryTypeRadios}
                </p>` : `
                <input type="hidden" name="meest_${type}_delivery_type" value="${firstEnabled}">`;
            
            const html = `
                <div class="meest-fields-wrapper">
                    <h3>${i18n.formTitle}</h3>
                    
                    <!-- Тип доставки -->
                    ${deliveryTypeBlock}
                    
                    <!-- Скрытое поле страны (всегда Украина) -->
                    <input type="hidden" name="meest_${type}_country_id" id="meest_${type}_country_id" value="${data.countryId}">
                    
                    <!-- Город -->
                    <p class="form-row form-row-wide meest-city-wrapper">
                        <label>${i18n.selectCity} <span class="required">*</span></label>
                        <input type="text" 
                               name="meest_${type}_city_search" 
                               id="meest_${type}_city_search" 
                               class="meest-city-input input-text" 
                               placeholder="${i18n.selectCity}"
                               autocomplete="off"
                               required>
                        <input type="hidden" name="meest_${type}_city_id" id="meest_${type}_city_id">
                        <input type="hidden" name="meest_${type}_city_text" id="meest_${type}_city_text">
                        <ul class="meest-city-dropdown" id="meest_${type}_city_dropdown" style="display:none;"></ul>
                    </p>
                    
                    <!-- Отделение (для доставки в отделение или поштомат) -->
                    <p class="form-row form-row-wide meest-branch-field meest-branch-wrapper">
                        <label id="meest_${type}_branch_label">${i18n.selectBranch} <span class="required">*</span></label>
                        <input type="text" 
                               name="meest_${type}_branch_search" 
                               id="meest_${type}_branch_search" 
                               class="meest-branch-input input-text" 
                               placeholder="${i18n.selectBranch}"
                               autocomplete="off"
                               required>
                        <input type="hidden" name="meest_${type}_branch_id" id="meest_${type}_branch_id">
                        <input type="hidden" name="meest_${type}_branch_text" id="meest_${type}_branch_text">
                        <ul class="meest-branch-dropdown" id="meest_${type}_branch_dropdown" style="display:none;"></ul>
                    </p>
                    
                    <!-- Адрес (для доставки на адрес) -->
                    <div class="meest-address-fields" style="display:none;">
                        <p class="form-row form-row-wide">
                            <label>${i18n.enterStreet} <span class="required">*</span></label>
                            <input type="text" name="meest_${type}_street_text" id="meest_${type}_street_text" class="input-text">
                            <input type="hidden" name="meest_${type}_street_id" id="meest_${type}_street_id">
                        </p>
                        <p class="form-row form-row-first">
                            <label>${i18n.enterBuilding} <span class="required">*</span></label>
                            <input type="text" name="meest_${type}_building" id="meest_${type}_building" class="input-text">
                        </p>
                        <p class="form-row form-row-last">
                            <label>${i18n.enterFlat}</label>
                            <input type="text" name="meest_${type}_flat" id="meest_${type}_flat" class="input-text">
                        </p>
                    </div>
                </div>
            `;
            
            container.html(html);
            this.initFieldHandlers(type);
        },

        initFieldHandlers: function(type) {
            const self = this;
            
            // Переключение типа доставки
            $(`input[name="meest_${type}_delivery_type"]`).on('change', function() {
                const deliveryType = $(this).val();
                
                if (deliveryType === 'branch' || deliveryType === 'poshtomat') {
                    // Показываем поле отделения
                    $('.meest-branch-field').show();
                    $('.meest-address-fields').hide();
                    
                    // Меняем label и placeholder
                    const label = deliveryType === 'poshtomat' ? meestCheckoutData.i18n.selectPoshtomat : meestCheckoutData.i18n.selectBranch;
                    $(`#meest_${type}_branch_label`).html(label + ' <span class="required">*</span>');
                    $(`#meest_${type}_branch_search`).attr('placeholder', label);
                    
                    // Очищаем поле при смене типа
                    $(`#meest_${type}_branch_search`).val('');
                    $(`#meest_${type}_branch_id`).val('');
                    $(`#meest_${type}_branch_text`).val('');
                    $(`#meest_${type}_branch_dropdown`).hide().empty();
                } else {
                    // Показываем поля адреса
                    $('.meest-branch-field').hide();
                    $('.meest-address-fields').show();
                }
            });

            // Автокомплит для города
            let citySearchTimeout;
            
            // При фокусе на поле города - показываем все города
            $(`#meest_${type}_city_search`).on('focus', function() {
                const search = $(this).val().trim();
                // Если поле пустое, загружаем все города
                if (search.length === 0) {
                    self.searchCities(type, '');
                }
            });
            
            $(`#meest_${type}_city_search`).on('input', function() {
                const search = $(this).val().trim();
                
                // Очищаем ID если меняем текст
                $(`#meest_${type}_city_id`).val('');
                $(`#meest_${type}_city_text`).val('');
                
                clearTimeout(citySearchTimeout);
                
                if (search.length < 2) {
                    $(`#meest_${type}_city_dropdown`).hide().empty();
                    return;
                }
                
                citySearchTimeout = setTimeout(function() {
                    self.searchCities(type, search);
                }, 500); // debounce 500ms
            });
            
            // Клик вне dropdown - закрыть
            $(document).on('click', function(e) {
                const clickedInput = $(e.target).attr('id') === `meest_${type}_city_search`;
                const clickedDropdown = $(e.target).closest(`#meest_${type}_city_dropdown`).length > 0;
                
                if (!clickedInput && !clickedDropdown) {
                    $(`#meest_${type}_city_dropdown`).hide();
                }
            });
            
            // Обновление позиции dropdown при скролле
            $(window).on('scroll resize', function() {
                const dropdown = $(`#meest_${type}_city_dropdown`);
                if (dropdown.is(':visible')) {
                    const input = $(`#meest_${type}_city_search`)[0];
                    if (input) {
                        const rect = input.getBoundingClientRect();
                        const dropdownEl = dropdown[0];
                        dropdownEl.style.setProperty('top', (rect.bottom + 2) + 'px', 'important');
                        dropdownEl.style.setProperty('left', rect.left + 'px', 'important');
                        dropdownEl.style.setProperty('width', rect.width + 'px', 'important');
                    }
                }
            });

            // Автокомплит для отделений
            let branchSearchTimeout;
            
            // При фокусе на поле отделения - показываем все отделения
            $(`#meest_${type}_branch_search`).on('focus', function() {
                const cityId = $(`#meest_${type}_city_id`).val();
                const search = $(this).val().trim();
                
                if (!cityId) {
                    return; // Сначала нужно выбрать город
                }
                
                // Если поле пустое, загружаем все отделения
                if (search.length === 0) {
                    const deliveryType = $(`input[name="meest_${type}_delivery_type"]:checked`).val();
                    self.searchBranches(type, cityId, '', deliveryType);
                }
            });
            
            $(`#meest_${type}_branch_search`).on('input', function() {
                const search = $(this).val().trim();
                const cityId = $(`#meest_${type}_city_id`).val();
                
                // Очищаем ID если меняем текст
                $(`#meest_${type}_branch_id`).val('');
                $(`#meest_${type}_branch_text`).val('');
                
                clearTimeout(branchSearchTimeout);
                
                if (!cityId) {
                    $(`#meest_${type}_branch_dropdown`).hide().empty();
                    return; // Сначала нужно выбрать город
                }
                
                if (search.length < 2) {
                    $(`#meest_${type}_branch_dropdown`).hide().empty();
                    return;
                }
                
                branchSearchTimeout = setTimeout(function() {
                    const deliveryType = $(`input[name="meest_${type}_delivery_type"]:checked`).val();
                    self.searchBranches(type, cityId, search, deliveryType);
                }, 500); // debounce 500ms
            });
            
            // Клик вне dropdown відділень - закрыть
            $(document).on('click', function(e) {
                const clickedInput = $(e.target).attr('id') === `meest_${type}_branch_search`;
                const clickedDropdown = $(e.target).closest(`#meest_${type}_branch_dropdown`).length > 0;
                
                if (!clickedInput && !clickedDropdown) {
                    $(`#meest_${type}_branch_dropdown`).hide();
                }
            });
            
            // Обновление позиции dropdown при скролле
            $(window).on('scroll resize', function() {
                const dropdown = $(`#meest_${type}_branch_dropdown`);
                if (dropdown.is(':visible')) {
                    const input = $(`#meest_${type}_branch_search`)[0];
                    if (input) {
                        const rect = input.getBoundingClientRect();
                        const dropdownEl = dropdown[0];
                        dropdownEl.style.setProperty('top', (rect.bottom + 2) + 'px', 'important');
                        dropdownEl.style.setProperty('left', rect.left + 'px', 'important');
                        dropdownEl.style.setProperty('width', rect.width + 'px', 'important');
                    }
                }
            });
            
            // Автокомплит для улиц
            let streetSearchTimeout;
            
            // При фокусе на поле улицы - показываем все улицы
            $(`#meest_${type}_street_text`).on('focus', function() {
                const cityId = $(`#meest_${type}_city_id`).val();
                const search = $(this).val().trim();
                
                if (!cityId) {
                    return; // Сначала нужно выбрать город
                }
                
                // Если поле пустое, загружаем все улицы
                if (search.length === 0) {
                    self.searchStreets(type, cityId, '');
                }
            });
            
            $(`#meest_${type}_street_text`).on('input', function() {
                const search = $(this).val().trim();
                const cityId = $(`#meest_${type}_city_id`).val();
                
                // Очищаем скрытое поле ID
                $(`#meest_${type}_street_id`).val('');
                
                clearTimeout(streetSearchTimeout);
                
                if (!cityId) {
                    return; // Сначала нужно выбрать город
                }
                
                if (search.length < 2) {
                    $(`#meest_${type}_street_dropdown`).hide().empty();
                    return;
                }
                
                streetSearchTimeout = setTimeout(function() {
                    self.searchStreets(type, cityId, search);
                }, 500);
            });
            
            // Клик вне dropdown улиц - закрыть
            $(document).on('click', function(e) {
                const clickedInput = $(e.target).attr('id') === `meest_${type}_street_text`;
                const clickedDropdown = $(e.target).closest(`#meest_${type}_street_dropdown`).length > 0;
                
                if (!clickedInput && !clickedDropdown) {
                    $(`#meest_${type}_street_dropdown`).hide();
                }
            });
            
            // Инициализируем поля при загрузке на основе выбранного типа доставки
            $(`input[name="meest_${type}_delivery_type"]:checked`).trigger('change');
        },

        searchCities: function(type, search) {
            const self = this;
            let dropdown = $(`#meest_${type}_city_dropdown`);
            const i18n = meestCheckoutData.i18n;
            
            // Если dropdown еще в своем контейнере - перемещаем в body
            if (dropdown.parent().attr('id') !== 'meest-city-dropdown-container') {
                dropdown.appendTo('body');
            }
            
            dropdown.html(`<li class="loading">${i18n.loading}</li>`);
            
            // Позиционируем dropdown под input ДО показа
            const input = $(`#meest_${type}_city_search`)[0];
            const rect = input.getBoundingClientRect();
            
            const dropdownTop = rect.bottom + 2;
            const dropdownLeft = rect.left;
            const dropdownWidth = rect.width;
            
            // Применяем стили позиционирования
            const dropdownEl = dropdown[0];
            dropdownEl.style.setProperty('display', 'block', 'important');
            dropdownEl.style.setProperty('position', 'fixed', 'important');
            dropdownEl.style.setProperty('top', dropdownTop + 'px', 'important');
            dropdownEl.style.setProperty('left', dropdownLeft + 'px', 'important');
            dropdownEl.style.setProperty('width', dropdownWidth + 'px', 'important');
            dropdownEl.style.setProperty('z-index', '999999', 'important');
            dropdownEl.style.setProperty('background', '#fff', 'important');
            dropdownEl.style.setProperty('border', '1px solid #ddd', 'important');
            dropdownEl.style.setProperty('border-radius', '0', 'important');
            dropdownEl.style.setProperty('max-height', '250px', 'important');
            dropdownEl.style.setProperty('overflow-y', 'auto', 'important');
            dropdownEl.style.setProperty('list-style', 'none', 'important');
            dropdownEl.style.setProperty('margin', '0', 'important');
            dropdownEl.style.setProperty('padding', '0', 'important');
            dropdownEl.style.setProperty('box-shadow', '0 4px 8px rgba(0,0,0,0.2)', 'important');
            
            $.ajax({
                url: meestCheckoutData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'meest_get_cities',
                    nonce: meestCheckoutData.nonce,
                    country_id: meestCheckoutData.countryId,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data.cities && response.data.cities.length > 0) {
                        let html = '';
                        response.data.cities.forEach(function(city) {
                            html += `<li class="city-item" data-id="${city.id}" data-name="${city.name}">${city.name}</li>`;
                        });
                        dropdown.html(html);
                        
                        // Обработчик клика по городу
                        dropdown.find('.city-item').on('click', function() {
                            const cityId = $(this).data('id');
                            const cityName = $(this).data('name');
                            
                            $(`#meest_${type}_city_search`).val(cityName);
                            $(`#meest_${type}_city_id`).val(cityId);
                            $(`#meest_${type}_city_text`).val(cityName);
                            dropdown.hide();
                            
                            // Очищаем поле отделения при смене города
                            $(`#meest_${type}_branch_search`).val('');
                            $(`#meest_${type}_branch_id`).val('');
                            $(`#meest_${type}_branch_text`).val('');
                            $(`#meest_${type}_branch_dropdown`).hide().empty();
                        });
                    } else {
                        dropdown.html(`<li class="no-results">${i18n.selectCity}</li>`);
                    }
                },
                error: function() {
                    dropdown.html(`<li class="error">Error loading cities</li>`);
                }
            });
        },

        searchStreets: function(type, cityId, search) {
            const self = this;
            let dropdown = $(`#meest_${type}_street_dropdown`);
            const i18n = meestCheckoutData.i18n;
            
            // Если dropdown еще не создан, создаем его
            if (dropdown.length === 0) {
                $('body').append(`<ul id="meest_${type}_street_dropdown" class="meest-street-dropdown"></ul>`);
                dropdown = $(`#meest_${type}_street_dropdown`);
            }
            
            // Перемещаем в body если нужно
            if (dropdown.parent().attr('id') !== 'meest-street-dropdown-container') {
                dropdown.appendTo('body');
            }
            
            dropdown.html(`<li class="loading">${i18n.loading}</li>`).show();
            
            $.ajax({
                url: meestCheckoutData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'meest_get_streets',
                    nonce: meestCheckoutData.nonce,
                    city_id: cityId,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data.streets && response.data.streets.length > 0) {
                        let html = '';
                        response.data.streets.forEach(function(street) {
                            html += `<li class="street-item" data-id="${street.id}" data-name="${street.name}">${street.name}</li>`;
                        });
                        dropdown.html(html);
                        
                        // Позиционируем dropdown под input
                        const input = $(`#meest_${type}_street_text`)[0];
                        const rect = input.getBoundingClientRect();
                        const dropdownTop = rect.bottom + 2;
                        const dropdownLeft = rect.left;
                        const dropdownWidth = rect.width;
                        
                        const dropdownEl = dropdown[0];
                        dropdownEl.style.setProperty('display', 'block', 'important');
                        dropdownEl.style.setProperty('position', 'fixed', 'important');
                        dropdownEl.style.setProperty('top', dropdownTop + 'px', 'important');
                        dropdownEl.style.setProperty('left', dropdownLeft + 'px', 'important');
                        dropdownEl.style.setProperty('width', dropdownWidth + 'px', 'important');
                        dropdownEl.style.setProperty('z-index', '999999', 'important');
                        dropdownEl.style.setProperty('background', '#fff', 'important');
                        dropdownEl.style.setProperty('border', '1px solid #ddd', 'important');
                        dropdownEl.style.setProperty('border-radius', '4px', 'important');
                        dropdownEl.style.setProperty('max-height', '250px', 'important');
                        dropdownEl.style.setProperty('overflow-y', 'auto', 'important');
                        dropdownEl.style.setProperty('list-style', 'none', 'important');
                        dropdownEl.style.setProperty('margin', '0', 'important');
                        dropdownEl.style.setProperty('padding', '0', 'important');
                        dropdownEl.style.setProperty('box-shadow', '0 4px 8px rgba(0,0,0,0.2)', 'important');
                        
                        // Обработчик клика по улице
                        dropdown.find('.street-item').on('click', function() {
                            const streetId = $(this).data('id');
                            const streetName = $(this).data('name');
                            
                            $(`#meest_${type}_street_text`).val(streetName);
                            $(`#meest_${type}_street_id`).val(streetId);
                            dropdown.hide();
                        });
                    } else {
                        dropdown.html(`<li class="no-results">${i18n.enterStreet}</li>`).show();
                    }
                },
                error: function() {
                    dropdown.html(`<li class="error">Error loading streets</li>`).show();
                }
            });
        },

        searchBranches: function(type, cityId, search, deliveryType) {
            const self = this;
            let dropdown = $(`#meest_${type}_branch_dropdown`);
            const i18n = meestCheckoutData.i18n;
            
            // Определяем тип доставки (по умолчанию branch)
            deliveryType = deliveryType || 'branch';
            
            // Если dropdown еще в своем контейнере - перемещаем в body
            if (dropdown.parent().attr('id') !== 'meest-branch-dropdown-container') {
                dropdown.appendTo('body');
            }
            
            dropdown.html(`<li class="loading">${i18n.loading}</li>`);
            
            // Позиционируем dropdown под input ДО показа
            const input = $(`#meest_${type}_branch_search`)[0];
            const rect = input.getBoundingClientRect();
            
            const dropdownTop = rect.bottom + 2;
            const dropdownLeft = rect.left;
            const dropdownWidth = rect.width;
            
            // Применяем стили позиционирования
            const dropdownEl = dropdown[0];
            dropdownEl.style.setProperty('display', 'block', 'important');
            dropdownEl.style.setProperty('position', 'fixed', 'important');
            dropdownEl.style.setProperty('top', dropdownTop + 'px', 'important');
            dropdownEl.style.setProperty('left', dropdownLeft + 'px', 'important');
            dropdownEl.style.setProperty('width', dropdownWidth + 'px', 'important');
            dropdownEl.style.setProperty('z-index', '999999', 'important');
            dropdownEl.style.setProperty('background', '#fff', 'important');
            dropdownEl.style.setProperty('border', '1px solid #ddd', 'important');
            dropdownEl.style.setProperty('border-radius', '0', 'important');
            dropdownEl.style.setProperty('max-height', '250px', 'important');
            dropdownEl.style.setProperty('overflow-y', 'auto', 'important');
            dropdownEl.style.setProperty('list-style', 'none', 'important');
            dropdownEl.style.setProperty('margin', '0', 'important');
            dropdownEl.style.setProperty('padding', '0', 'important');
            dropdownEl.style.setProperty('box-shadow', '0 4px 8px rgba(0,0,0,0.2)', 'important');
            
            $.ajax({
                url: meestCheckoutData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'meest_get_branches',
                    nonce: meestCheckoutData.nonce,
                    city_id: cityId,
                    search: search,
                    delivery_type: deliveryType
                },
                success: function(response) {
                    if (response.success && response.data.branches && response.data.branches.length > 0) {
                        let html = '';
                        response.data.branches.forEach(function(branch) {
                            html += `<li class="branch-item" data-id="${branch.id}" data-name="${branch.name}">${branch.name}</li>`;
                        });
                        dropdown.html(html);
                        
                        // Обработчик клика по отделению
                        dropdown.find('.branch-item').on('click', function() {
                            const branchId = $(this).data('id');
                            const branchName = $(this).data('name');
                            
                            $(`#meest_${type}_branch_search`).val(branchName);
                            $(`#meest_${type}_branch_id`).val(branchId);
                            $(`#meest_${type}_branch_text`).val(branchName);
                            dropdown.hide();
                        });
                    } else {
                        const placeholder = deliveryType === 'poshtomat' ? i18n.selectPoshtomat : i18n.selectBranch;
                        dropdown.html(`<li class="no-results">${placeholder}</li>`);
                    }
                },
                error: function() {
                    dropdown.html(`<li class="error">Error loading branches</li>`);
                }
            });
        }
    };

    // Инициализация при загрузке DOM
    $(document).ready(function() {
        MeestCheckout.init();
    });

})(jQuery);

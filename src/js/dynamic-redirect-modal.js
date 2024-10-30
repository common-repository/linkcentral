(function($) {
    'use strict';

    const VARIABLE_TYPES = {
        country: {
            label: 'Country',
            multiSelect: true,
            options: linkcentral_data.countries,
            maxInstances: 1
        },
        device: {
            label: 'Device',
            multiSelect: true,
            options: {
                desktop: 'Desktop',
                mobile: 'Mobile',
                tablet: 'Tablet'
            },
            maxInstances: 1
        },
        date: {
            label: 'Date',
            multiSelect: false,
            inputType: 'date',
            maxInstances: 1
        },
        time: {
            label: 'Time',
            multiSelect: false,
            inputType: 'time',
            maxInstances: 1
        }
    };

    const CONDITION_TYPES = {
        is: 'is',
        is_not: 'is not',
        is_before: 'is before',
        is_after: 'is after',
        is_on: 'is on',
        is_between: 'is between',
        is_not_between: 'is not between'
    };

    const isPremium = linkcentral_data.can_use_premium_code__premium_only === '1';

    $(document).ready(function() {
        const modal = $('#linkcentral-dynamic-redirect-modal');
        const btn = $('#linkcentral-dynamic-redirect');
        const span = $('.linkcentral-modal-close');
        const rulesContainer = $('#linkcentral-rules-container');
        const addRuleBtn = $('#linkcentral-add-rule');
        const saveRulesBtn = $('#linkcentral-save-rules');

        let ruleCount = 0;

        function initializeEventListeners() {
            btn.on('click', openModal);
            span.on('click', closeModal);
            $(window).on('click', closeModalOnOutsideClick);
            if (isPremium) {
                addRuleBtn.on('click', addNewRule);
                saveRulesBtn.on('click', validateAndSaveRules);
            }
            rulesContainer
                .on('change', '.linkcentral-variable-type', handleVariableTypeChange)
                .on('click', '.linkcentral-add-variable', addVariable)
                .on('click', '.linkcentral-remove-variable', removeVariable)
                .on('click', '.linkcentral-remove-rule', removeRule);
            $(document)
                .on('click', '.linkcentral-multi-select-input', toggleMultiSelect)
                .on('change', '.linkcentral-multi-select-dropdown input[type="checkbox"]', updateMultiSelectInput)
                .on('click', function(e) {
                    if (!$(e.target).closest('.linkcentral-multi-select').length) {
                        closeAllMultiSelects();
                    }
                });
        }

        function openModal(e) {
            e.preventDefault();
            modal.show();
            if (isPremium) {
                loadExistingRules();
                $('#linkcentral-add-rule, #linkcentral-save-rules').show();
            } else {
                $('#linkcentral-rules-container').empty();
                $('#linkcentral-add-rule, #linkcentral-save-rules').hide();
            }
        }

        function closeModal() {
            modal.hide();
        }

        function closeModalOnOutsideClick(event) {
            if (event.target === modal[0]) {
                closeModal();
            }
        }

        function addNewRule(e) {
            e.preventDefault();
            addRule();
        }

        function addRule(ruleData = {}) {
            $('#linkcentral-no-rules-message').remove();
            $('#linkcentral-static-rule').remove();
            ruleCount++;
            const ruleHtml = createRuleHtml(ruleCount, ruleData);
            rulesContainer.append(ruleHtml);

            if (ruleData.variables) {
                const ruleElement = rulesContainer.find(`.linkcentral-rule[data-rule-id="${ruleCount}"]`);
                ruleData.variables.forEach((variable, index) => {
                    if (index > 0) {
                        ruleElement.find('.linkcentral-add-variable').click();
                    }
                    const variableContainer = ruleElement.find('.linkcentral-variable-container').eq(index);
                    variableContainer.find('.linkcentral-variable-type').val(variable[0]).change();
                    setVariableValue(variableContainer, variable);
                });
            }
            updateRuleNumbers();
            checkForEmptyRules();
            updateVariableOptions(ruleCount);
        }

        function createRuleHtml(ruleId, ruleData = {}) {
            return `
                <div class="linkcentral-rule" data-rule-id="${ruleId}">
                    <div class="linkcentral-rule-header">
                        <h4 class="rule-number">Rule ${ruleId}</h4>
                        <button class="linkcentral-remove-rule button button-secondary">Remove Rule</button>
                    </div>
                    <div class="linkcentral-rule-content">
                        <div class="linkcentral-variables-container">
                            ${createVariableSelectorHtml(true, ruleId)}
                        </div>
                        <button class="linkcentral-add-variable button button-secondary">Add Variable</button>
                    </div>
                    <div class="linkcentral-destination-container">
                        <label for="linkcentral-rule-destination-${ruleId}">Go to Destination URL:</label>
                        <input type="url" id="linkcentral-rule-destination-${ruleId}" class="linkcentral-rule-destination" name="destination" placeholder="Enter the destination URL" value="${ruleData.destination || 'https://'}">
                    </div>
                </div>
            `;
        }

        function createVariableSelectorHtml(isFirst = false, ruleId) {
            const variableOptions = Object.entries(VARIABLE_TYPES).map(([value, { label }]) => 
                `<option value="${value}">${label}</option>`
            ).join('');

            return `
                <div class="linkcentral-variable-container ${isFirst ? 'first-variable' : ''}">
                    <div class="linkcentral-variable-type-column">
                        <span class="linkcentral-condition-label">${isFirst ? 'IF' : 'AND'}</span>
                        <select class="linkcentral-variable-type" data-rule-id="${ruleId}">
                            <option value="">Select Variable</option>
                            ${variableOptions}
                        </select>
                    </div>
                    <div class="linkcentral-variable-value-column">
                        <span class="linkcentral-variable-value-container"></span>
                    </div>
                    ${isFirst ? '' : `
                        <div class="linkcentral-variable-remove-column">
                            <button class="linkcentral-remove-variable button button-secondary">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    `}
                </div>
            `;
        }

        function handleVariableTypeChange() {
            const valueContainer = $(this).closest('.linkcentral-variable-container').find('.linkcentral-variable-value-container');
            const removeBtn = $(this).closest('.linkcentral-variable-container').find('.linkcentral-remove-variable');
            const selectedType = $(this).val();
            const ruleId = $(this).data('rule-id');

            valueContainer.empty();
            if (selectedType) {
                removeBtn.show();
                valueContainer.html(createVariableValueHtml(selectedType));
                updateVariableOptions(ruleId);
            } else {
                removeBtn.hide();
            }

            if (selectedType === 'date' || selectedType === 'time') {
                const condition = valueContainer.find('.linkcentral-condition');
                const startInput = valueContainer.find(`.linkcentral-${selectedType}-start`);
                const separator = valueContainer.find(`.linkcentral-${selectedType}-separator`);
                const endInput = valueContainer.find(`.linkcentral-${selectedType}-end`);

                condition.on('change', function() {
                    if ($(this).val() === 'is between' || $(this).val() === 'is not between') {
                        separator.show();
                        endInput.show();
                    } else {
                        separator.hide();
                        endInput.hide();
                    }
                });
            }
        }

        function createVariableValueHtml(type) {
            const variableType = VARIABLE_TYPES[type];
            if (!variableType) return '';

            let conditionHtml = createConditionHtml(type);
            let valueHtml = '';

            if (variableType.multiSelect) {
                valueHtml = createMultiSelectHtml(type, variableType.options);
            } else if (variableType.inputType) {
                if (type === 'date' || type === 'time') {
                    valueHtml = `
                        <input type="${variableType.inputType}" class="linkcentral-variable-value linkcentral-${type}-start">
                        <span class="linkcentral-${type}-separator" style="display:none;"> and </span>
                        <input type="${variableType.inputType}" class="linkcentral-variable-value linkcentral-${type}-end" style="display:none;">
                    `;
                } else {
                    valueHtml = `<input type="${variableType.inputType}" class="linkcentral-variable-value">`;
                }
            }

            return `
                <div class="linkcentral-input-group">
                    ${conditionHtml}
                    ${valueHtml}
                </div>
            `;
        }

        function createConditionHtml(type) {
            let conditions;
            switch (type) {
                case 'country':
                case 'device':
                    conditions = [CONDITION_TYPES.is, CONDITION_TYPES.is_not];
                    break;
                case 'date':
                    conditions = [CONDITION_TYPES.is_before, CONDITION_TYPES.is_after, CONDITION_TYPES.is_on, CONDITION_TYPES.is_between, CONDITION_TYPES.is_not_between];
                    break;
                case 'time':
                    conditions = [CONDITION_TYPES.is, CONDITION_TYPES.is_before, CONDITION_TYPES.is_after, CONDITION_TYPES.is_between, CONDITION_TYPES.is_not_between];
                    break;
                default:
                    conditions = Object.values(CONDITION_TYPES);
            }

            return `
                <select class="linkcentral-condition">
                    ${conditions.map(condition => `<option value="${condition}">${condition}</option>`).join('')}
                </select>
            `;
        }

        function createMultiSelectHtml(type, options) {
            const optionsHtml = Object.entries(options).map(([value, label]) => 
                `<label><input type="checkbox" value="${value}"> ${label}</label>`
            ).join('');

            return `
                <div class="linkcentral-multi-select">
                    <input type="text" class="linkcentral-multi-select-input" readonly placeholder="Select ${type}">
                    <div class="linkcentral-multi-select-dropdown" style="display:none;">
                        ${optionsHtml}
                    </div>
                </div>
            `;
        }

        function addVariable(e) {
            e.preventDefault();
            const ruleId = $(this).closest('.linkcentral-rule').data('rule-id');
            $(this).before(createVariableSelectorHtml(false, ruleId));
            updateVariableOptions(ruleId);
        }

        function removeVariable() {
            const variableContainer = $(this).closest('.linkcentral-variable-container');
            const ruleId = variableContainer.find('.linkcentral-variable-type').data('rule-id');
            if (!variableContainer.hasClass('first-variable')) {
                variableContainer.remove();
                updateVariableOptions(ruleId);
            }
        }

        function removeRule() {
            $(this).closest('.linkcentral-rule').remove();
            updateRuleNumbers();
            checkForEmptyRules();
        }

        function loadExistingRules() {
            const existingRules = JSON.parse($('#linkcentral_dynamic_rules').val() || '[]');
            rulesContainer.empty();
            ruleCount = 0;
            if (existingRules && existingRules.length > 0) {
                existingRules.forEach(addRule);
            }
            checkForEmptyRules();
        }

        function validateAndSaveRules(e) {
            e.preventDefault();
            e.stopPropagation();

            if (!isPremium) {
                alert('Dynamic Redirects are only available in the premium version.');
                return;
            }

            let geolocationAlertShown = false;

            const rules = [];
            let isValid = true;

            $('.linkcentral-rule:not(.linkcentral-static-rule)').each(function() {
                const $rule = $(this);
                const rule = {
                    variables: [],
                    destination: $rule.find('.linkcentral-rule-destination').val().trim()
                };

                $rule.find('.linkcentral-rule-error').remove();
                let ruleErrors = new Set();

                $rule.find('.linkcentral-variable-container').each(function() {
                    const type = $(this).find('.linkcentral-variable-type').val();
                    const condition = $(this).find('.linkcentral-condition').val();
                    const value = getVariableValue($(this));
                    
                    if (!type || (Array.isArray(value) && value.length === 0) || (!Array.isArray(value) && !value)) {
                        isValid = false;
                        ruleErrors.add('One or more variables are incomplete.');
                    } else {
                        rule.variables.push([type, condition, value]);
                        if (type === 'country' && (!linkcentral_data.geolocation_service || linkcentral_data.geolocation_service === 'none') && !geolocationAlertShown) {
                            alert("Warning: you have used a Country variable, but no geolocation service is set in the plugin settings. Please set a service first.");
                            geolocationAlertShown = true;
                        }
                    }
                });

                if (!rule.destination) {
                    isValid = false;
                    ruleErrors.add('Destination URL is required.');
                } else if (!isValidURL(rule.destination)) {
                    isValid = false;
                    ruleErrors.add('Invalid Destination URL.');
                }

                if (ruleErrors.size > 0) {
                    $rule.append(`<div class="linkcentral-rule-error">${Array.from(ruleErrors).join('<br>')}</div>`);
                }

                if (rule.variables.length > 0 && isValidURL(rule.destination)) {
                    rules.push(rule);
                }
            });

            if (isValid) {
                $('#linkcentral_dynamic_rules').val(JSON.stringify(rules));
                closeModal();
                updateDynamicButtonAppearance();
            }
        }

        function isValidURL(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }

        function updateRuleNumbers() {
            $('.linkcentral-rule:not(.linkcentral-static-rule)').each(function(index) {
                $(this).find('.rule-number').text(`Rule ${index + 1}`);
                $(this).attr('data-rule-id', index + 1);
            });
            ruleCount = $('.linkcentral-rule:not(.linkcentral-static-rule)').length;
        }

        function checkForEmptyRules() {
            if ($('.linkcentral-rule:not(.linkcentral-static-rule)').length === 0) {
                $('#linkcentral-no-rules-message').remove();
                $('#linkcentral-static-rule').remove();
                $('#linkcentral-rules-container').append('<p id="linkcentral-no-rules-message">No rules set yet. Click "Add Rule" to create a new rule.</p>');
            } else {
                $('#linkcentral-no-rules-message').remove();
                if ($('#linkcentral-static-rule').length === 0) {
                    $('#linkcentral-rules-container').append(createStaticRuleHtml());
                }
            }
        }

        function createStaticRuleHtml() {
            return `
                <div id="linkcentral-static-rule" class="linkcentral-rule linkcentral-static-rule">
                    <div class="linkcentral-rule-header">
                        <h4 class="rule-number">Default Rule</h4>
                    </div>
                    <div class="linkcentral-rule-content">
                        <p>If none of the above rules are met, the default Destination URL will be used.</p>
                    </div>
                </div>
            `;
        }

        function updateVariableOptions(ruleId) {
            const rule = $(`.linkcentral-rule[data-rule-id="${ruleId}"]`);
            const usedTypes = rule.find('.linkcentral-variable-type').map(function() {
                return $(this).val();
            }).get();

            const typeCounts = usedTypes.reduce((acc, type) => {
                acc[type] = (acc[type] || 0) + 1;
                return acc;
            }, {});

            rule.find('.linkcentral-variable-type').each(function() {
                const currentValue = $(this).val();
                $(this).find('option').each(function() {
                    if (this.value) {
                        const count = typeCounts[this.value] || 0;
                        const maxInstances = VARIABLE_TYPES[this.value].maxInstances;
                        const isDisabled = count >= maxInstances && this.value !== currentValue;
                        $(this).prop('disabled', isDisabled);
                        
                        // Add or remove the (max X) text
                        const originalText = VARIABLE_TYPES[this.value].label;
                        if (isDisabled) {
                            $(this).text(`${originalText} (max ${maxInstances})`);
                        } else {
                            $(this).text(originalText);
                        }
                    }
                });
            });
        }

        function toggleMultiSelect(e) {
            e.stopPropagation();
            const dropdown = $(this).siblings('.linkcentral-multi-select-dropdown');
            $('.linkcentral-multi-select-dropdown').not(dropdown).hide();
            dropdown.toggle();
        }

        function updateMultiSelectInput() {
            const dropdown = $(this).closest('.linkcentral-multi-select-dropdown');
            const input = dropdown.siblings('.linkcentral-multi-select-input');
            const selectedValues = dropdown.find('input[type="checkbox"]:checked').map(function() {
                return $(this).parent().text().trim();
            }).get();
            input.val(selectedValues.join(', '));
        }

        function updateDynamicButtonAppearance() {
            const button = $('#linkcentral-dynamic-redirect');
            const rules = JSON.parse($('#linkcentral_dynamic_rules').val() || '[]');
            button.toggleClass('rules-set', rules.length > 0);
        }

        function getVariableValue($container) {
            const type = $container.find('.linkcentral-variable-type').val();
            const variableType = VARIABLE_TYPES[type];

            if (!variableType) {
                return '';
            }
            
            if (variableType.multiSelect) {
                return $container.find('.linkcentral-multi-select-dropdown input[type="checkbox"]:checked').map(function() {
                    return this.value;
                }).get();
            } else if (type === 'date' || type === 'time') {
                const condition = $container.find('.linkcentral-condition').val();
                const startValue = $container.find(`.linkcentral-${type}-start`).val();
                const endValue = $container.find(`.linkcentral-${type}-end`).val();
                return (condition === 'is between' || condition === 'is not between') ? [startValue, endValue] : startValue;
            } else {
                return $container.find('.linkcentral-variable-value').val();
            }
        }

        function setVariableValue($container, variable) {
            const variableType = VARIABLE_TYPES[variable[0]];
            
            if (variableType.multiSelect) {
                $container.find('.linkcentral-multi-select-dropdown input[type="checkbox"]').each(function() {
                    $(this).prop('checked', variable[2].includes(this.value));
                });
                updateMultiSelectInput.call($container.find('.linkcentral-multi-select-dropdown input[type="checkbox"]').first());
            } else if (variable[0] === 'date' || variable[0] === 'time') {
                const condition = variable[1];
                $container.find('.linkcentral-condition').val(condition).trigger('change');
                if ((condition === 'is between' || condition === 'is not between') && Array.isArray(variable[2])) {
                    $container.find(`.linkcentral-${variable[0]}-start`).val(variable[2][0]);
                    $container.find(`.linkcentral-${variable[0]}-end`).val(variable[2][1]);
                } else {
                    $container.find(`.linkcentral-${variable[0]}-start`).val(variable[2]);
                }
            } else {
                $container.find('.linkcentral-variable-value').val(variable[2]);
            }
            
            if (variable[1]) {
                $container.find('.linkcentral-condition').val(variable[1]);
            }
        }

        function closeAllMultiSelects() {
            $('.linkcentral-multi-select-dropdown').hide();
        }

        initializeEventListeners();
        updateDynamicButtonAppearance();
    });

})(jQuery);
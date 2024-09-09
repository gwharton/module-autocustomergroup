define([
    'jquery',
    'Magento_Sales/order/create/scripts'
], function (jQuery) {
    'use strict';
    AdminOrder.prototype.validateTaxId = function(parameters){
        var params = {
            country: $(parameters.countryElementId).value,
            postcode: $(parameters.postcodeElementId).value,
            tax: $(parameters.taxIdElementId).value
        };

        if (this.storeId !== false) {
            params.store_id = this.storeId;
        }

        new Ajax.Request(parameters.validateUrl, {
            parameters: params,
            onSuccess: function (response) {
                response = response.responseText.evalJSON();
                let groupActionRequired = 'inform';
                let message = '';
                let currentGroupId = Number($(parameters.groupIdHtmlId).value);
                let currentGroupName = $$('#' + parameters.groupIdHtmlId + ' > option[value=' + currentGroupId + ']')[0].text;
                let newGroupId = response.group ?? currentGroupId;
                let newGroupName = $$('#' + parameters.groupIdHtmlId + ' > option[value=' + newGroupId + ']')[0].text;
                if (response.success) {
                    if (response.valid) {
                        message = 'Tax Identifier is VALID (' + response.message + ').';
                    } else {
                        message = 'Tax Identifier is INVALID (' + response.message + ').';
                    }
                } else {
                    message = 'AutoCustomerGroup encountered an error while checking the Tax Identifier. (' + response.message + ').';
                }
                if (response.group && response.group !== currentGroupId) {
                    message += '\n\nAutoCustomerGroup recommends the group changes from ' + currentGroupName + ' to ' + newGroupName + '.';
                    groupActionRequired = 'change';
                } else {
                    message += '\n\nAutoCustomerGroup does not recommend a group change.';
                }
                if (groupActionRequired === "inform") {
                    alert(message);
                }
                if (groupActionRequired === "change") {
                    if (confirm(message + '\n\nProceed with group change?')) {
                        $$('#' + parameters.groupIdHtmlId + ' option').each(function (o) {
                            o.selected = Number(o.readAttribute('value')) === newGroupId;
                        });
                        this.saveData(this.serializeData('order-addresses'));
                        this.loadArea(['data'], true, this.serializeData('order-form_account').toObject());
                        this.loadArea(['data'], true, this.serializeData('order-addresses').toObject());
                    }
                }
            }.bind(this)
        });
    }
});

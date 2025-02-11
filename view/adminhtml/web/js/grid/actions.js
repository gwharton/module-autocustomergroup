define([
    'Magento_Customer/js/grid/columns/actions',
], function (Actions) {
    'use strict';

    return Actions.extend({
        onAction: function (data) {
            this._super();
            if (data.action === 'validate') {
                this.source().reload({
                    refresh: true
                });
            }
        }
    });
});

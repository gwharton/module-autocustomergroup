define([
    'Magento_Customer/js/grid/massactions',
], function (Massactions) {
    'use strict';

    return Massactions.extend({
        onAction: function (data) {
            this._super();
            if (data.action === 'validate') {
                this.source.reload({
                    refresh: true
                });
            }
        }
    });
});

$(function() {

    const form = $('form#export-form');

    const queryField = $('input#query').closest('.field');
    const queryItemsField = $('input#query_items').closest('.field');
    const queryItemSetsField = $('input#query_item_sets').closest('.field');
    const queryMediaField = $('input#query_media').closest('.field');
    const formatMultivalueSeparatorField = $('input#multivalue_separator').closest('.field');

    const resourceSelect = $('select#resource');
    const formatSelect = $('select#format');

    const prepareQueryFields = function() {
        queryField.hide();
        queryItemsField.hide();
        queryItemSetsField.hide();
        queryMediaField.hide();
        switch (resourceSelect.val()) {
            case 'items':
                queryItemsField.show();
                break;
            case 'item_sets':
                queryItemSetsField.show();
                break;
            case 'media':
                queryMediaField.show();
                break;
            default:
                queryField.show();
        }
    };

    const prepareFormatFields = function() {
        formatMultivalueSeparatorField.hide();
        switch (formatSelect.val()) {
            case 'csv':
                formatMultivalueSeparatorField.show();
                break;
            case 'jsonld':
                formatMultivalueSeparatorField.hide();
                break;
            default:
                // do nothing
        }
    }

    // Prepare query fields on load.
    prepareQueryFields();
    prepareFormatFields();

    resourceSelect.on('change', function(e) {
        prepareQueryFields();
    });
    formatSelect.on('change', function(e) {
        prepareFormatFields();
    });

});

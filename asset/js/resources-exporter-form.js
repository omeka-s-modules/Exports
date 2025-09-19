$(function() {

    const form = $('form#export-form');

    const fieldQuery = $('#query_field');
    const fieldQueryItems = $('#query_items_field');
    const fieldQueryItemSets = $('#query_item_sets_field');
    const fieldQueryMedia = $('#query_media_field');

    const elementResource = $('select#resource');

    const prepareFields = function() {
        fieldQuery.hide();
        fieldQueryItems.hide();
        fieldQueryItemSets.hide();
        fieldQueryMedia.hide();
        switch (elementResource.val()) {
            case 'items':
                fieldQueryItems.show();
                break;
            case 'item_sets':
                fieldQueryItemSets.show();
                break;
            case 'media':
                fieldQueryMedia.show();
                break;
            default:
                fieldQuery.show();
        }
    };

    prepareFields();

    elementResource.on('change', function(e) {
        prepareFields();
    });

});

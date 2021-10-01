$(document).ready(function () {
    let selectingElement;
    
    // Show or hide button by selector.
    const show = selector => selectingElement.find(selector).removeClass('inactive');
    const hide = selector => selectingElement.find(selector).addClass('inactive');

    selectingElement = $('#content').find('.pid-form-element');
    itemID = selectingElement.attr('item-id');
    pidEditURL = selectingElement.attr('pid-edit-url');
    
    // Grab and display PID attribute, if it exists
    if (selectingElement.attr('item-pid')) {
        pidValue = selectingElement.attr('item-pid');
        selectingElement.find('.pid-display').text(pidValue);
        show('.pid-form-remove');
        hide('.pid-form-mint');
    }
    
    // Handle the button that mints/creates a PID via selected service and assigns to object.
    $('#content').on('click', '.pid-form-mint', function (e) {
        pidTarget = selectingElement.attr('item-api-url');
        mintPID(pidEditURL, pidTarget, itemID);
    });
    
    // Handle the button that opens PID removal confirmation sidebar.
    $('#content').on('click', '.pid-form-remove', function (e) {
        Omeka.openSidebar($('#sidebar-remove-pid'));
    });
    
    // Handle the button that removes PID value from item.
    $('#content').on('click', '.pid-form-delete', function (e) {
        toRemovePID = selectingElement.attr('item-pid');
        deletePID(pidEditURL, toRemovePID, itemID);
    });
    
    /**
     * Mint a PID using API of selected service.
     */
    function mintPID(pidEditURL, pidTarget, itemID)
    {
        $.ajax({
            type: 'POST',
            url: pidEditURL,
            data: { 
                'target' : pidTarget,
                'itemID' : itemID
            },
            success: function(data) {
                selectingElement.find('.pid-display').text(data);
                selectingElement.attr('item-pid', data);
                show('.pid-form-remove');
                hide('.pid-form-mint');
            },
            failure: function(errMsg) {
                selectingElement.find('.pid-display').text(errMsg);
            }
        });
    }
    
    /**
     * Delete PID from Omeka DB and remove Omeka URI target via PID service API.
     */
    function deletePID(pidEditURL, toRemovePID, itemID)
    {
        $.ajax({
            type: 'POST',
            url: pidEditURL,
            data: { 
                'itemID' : itemID,
                'toRemovePID' : toRemovePID
            },
            success: function(data) {
                Omeka.closeSidebar($('#sidebar-remove-pid'));
                // selectingElement.find('.pid-display').empty();
                selectingElement.find('.pid-display').text(data);
                show('.pid-form-mint');
                hide('.pid-form-remove');
            },
            failure: function(errMsg) {
                selectingElement.find('.pid-display').text(errMsg);
            }
        });
    }
});
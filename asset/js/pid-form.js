$(document).ready(function () {
    let selectingElement;
    
    // Show or hide button by selector.
    const show = selector => selectingElement.find(selector).removeClass('inactive');
    const hide = selector => selectingElement.find(selector).addClass('inactive');

    selectingElement = $('#content').find('.pid-form-element');
    itemID = selectingElement.data('itemId');
    pidEditURL = selectingElement.data('pidEditUrl');
    
    // Grab and display PID attribute, if it exists
    if (selectingElement.data('itemPid')) {
        pidValue = selectingElement.data('itemPid');
        selectingElement.find('.pid-display').text(pidValue);
        show('.pid-form-remove');
        hide('.pid-form-mint');
    }
    
    // Handle the button that mints/creates a PID via selected service and assigns to object.
    $('#content').on('click', '.pid-form-mint', function (e) {
        pidTarget = selectingElement.data('itemApiUrl');
        mintPID(pidEditURL, pidTarget, itemID);
    });
    
    // Handle the button that opens PID removal confirmation sidebar.
    $('#content').on('click', '.pid-form-remove', function (e) {
        Omeka.openSidebar($('#sidebar-remove-pid'));
    });
    
    // Handle the button that removes PID value from item.
    $('#content').on('click', '.pid-form-delete', function (e) {
        toRemovePID = selectingElement.data('itemPid');
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
                if (!data) {
                    // empty data means PID service connection error
                    selectingElement.find('.pid-display').text(Omeka.jsTranslate('Could not mint PID, check credentials/settings.'));
                } else {
                    selectingElement.find('.pid-display').text(data);
                    selectingElement.data('itemPid', data);
                    show('.pid-form-remove');
                    hide('.pid-form-mint');
                }
            },
            error: function(errMsg) {
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
                if (!data) {
                    Omeka.closeSidebar($('#sidebar-remove-pid'));
                    // empty data means PID service connection error
                    selectingElement.find('.pid-display').text(Omeka.jsTranslate('Could not delete PID, check credentials/settings.'));
                } else {
                    Omeka.closeSidebar($('#sidebar-remove-pid'));
                    selectingElement.removeAttr('data-item-pid');
                    selectingElement.find('.pid-display').text('');
                    show('.pid-form-mint');
                    hide('.pid-form-remove');
                }
            },
            failure: function(errMsg) {
                selectingElement.find('.pid-display').text(errMsg);
            }
        });
    }
});

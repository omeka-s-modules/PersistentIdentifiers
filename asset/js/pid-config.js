$(document).ready(function () {
    let selectingElement;
    
    ezidRadio = $('input[type="radio"][value="ezid"]');
    dataciteRadio = $('input[type="radio"][value="datacite"]');
    
    // Show or hide config settings by selected PID service
    const show = selector => $('#content').find(selector).removeClass('inactive');
    const hide = selector => $('#content').find(selector).addClass('inactive');
    
    if (ezidRadio.prop('checked')) {
        show('#ezid-configuration');
    }
    
    if (dataciteRadio.prop('checked')) {
        show('#datacite-configuration');
        show('#datacite-required-metadata');
    }
    
    ezidRadio.change(function() {
        if (this.checked) {
            show('#ezid-configuration');
            hide('#datacite-configuration');
            hide('#datacite-required-metadata');
        }
    });
    
    dataciteRadio.change(function() {
        if (this.checked) {
            show('#datacite-configuration');
            show('#datacite-required-metadata');
            hide('#ezid-configuration');
        }
    });
});

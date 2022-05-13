$(document).ready(function () {
    let selectingElement;
    
    ezidRadio = $('input[type="radio"][value="ezid"]');
    dataciteRadio = $('input[type="radio"][value="datacite"]');
    
    // Show or hide config settings by selected PID service
    const show = selector => $('#content').find(selector).removeClass('inactive');
    const hide = selector => $('#content').find(selector).addClass('inactive');
    
    if (ezidRadio.prop('checked')) {
        show('#ezid-configuration');

        // Enable/disable relevant inputs
        $("input[name^='ezid']").prop("disabled", false);
        $("input[name^='datacite']").prop("disabled", true);
        $("select[name^='datacite']").prop("disabled", true);
        $("select[id^='datacite']").removeAttr('required');
    }
    
    if (dataciteRadio.prop('checked')) {
        show('#datacite-configuration');
        show('#datacite-required-metadata');

        // Enable/disable relevant inputs
        $("input[name^='datacite']").prop("disabled", false);
        $("select[name^='datacite']").prop("disabled", false);
        $("select[id^='datacite']").attr('required', 'required');
        $("input[name^='ezid']").prop("disabled", true);
    }
    
    ezidRadio.change(function() {
        if (this.checked) {
            show('#ezid-configuration');
            hide('#datacite-configuration');
            hide('#datacite-required-metadata');

            // Enable/disable relevant inputs
            $("input[name^='ezid']").prop("disabled", false);
            $("input[name^='datacite']").prop("disabled", true);
            $("select[name^='datacite']").prop("disabled", true);
            $("select[id^='datacite']").removeAttr('required');
        }
    });
    
    dataciteRadio.change(function() {
        if (this.checked) {
            show('#datacite-configuration');
            show('#datacite-required-metadata');
            hide('#ezid-configuration');

            // Enable/disable relevant inputs
            $("input[name^='datacite']").prop("disabled", false);
            $("select[name^='datacite']").prop("disabled", false);
            $("select[id^='datacite']").attr('required', 'required');
            $("input[name^='ezid']").prop("disabled", true);
        }
    });
});

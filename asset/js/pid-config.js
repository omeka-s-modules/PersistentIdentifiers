$(document).ready(function () {
    let selectingElement;

    ezidRadio = $('input[type="radio"][value="ezid"]');
    dataciteRadio = $('input[type="radio"][value="datacite"]');
    localArkRadio = $('input[type="radio"][value="localark"]');

    // Show or hide config settings by selected PID service
    const show = selector => $('#content').find(selector).removeClass('inactive');
    const hide = selector => $('#content').find(selector).addClass('inactive');

    const hideAll = () => {
        hide('#ezid-configuration');
        hide('#datacite-configuration');
        hide('#datacite-required-metadata');
        hide('#datacite-optional-metadata');
        hide('#local-ark-configuration');
        $("input[name^='ezid']").prop("disabled", true);
        $("input[name^='datacite']").prop("disabled", true);
        $("select[name^='datacite']").prop("disabled", true);
        $("select[id^='datacite']").removeAttr('required');
        $("input[name^='local-ark']").prop("disabled", true);
    };

    if (ezidRadio.prop('checked')) {
        hideAll();
        show('#ezid-configuration');
        $("input[name^='ezid']").prop("disabled", false);
    }

    if (dataciteRadio.prop('checked')) {
        hideAll();
        show('#datacite-configuration');
        show('#datacite-required-metadata');
        show('#datacite-optional-metadata');
        $("input[name^='datacite']").prop("disabled", false);
        $("select[name^='datacite']").prop("disabled", false);
        $("select[id^='datacite']").attr('required', 'required');
    }

    if (localArkRadio.prop('checked')) {
        hideAll();
        show('#local-ark-configuration');
        $("input[name^='local-ark']").prop("disabled", false);
    }

    ezidRadio.change(function() {
        if (this.checked) {
            hideAll();
            show('#ezid-configuration');
            $("input[name^='ezid']").prop("disabled", false);
        }
    });

    dataciteRadio.change(function() {
        if (this.checked) {
            hideAll();
            show('#datacite-configuration');
            show('#datacite-required-metadata');
            show('#datacite-optional-metadata');
            $("input[name^='datacite']").prop("disabled", false);
            $("select[name^='datacite']").prop("disabled", false);
            $("select[id^='datacite']").attr('required', 'required');
        }
    });

    localArkRadio.change(function() {
        if (this.checked) {
            hideAll();
            show('#local-ark-configuration');
            $("input[name^='local-ark']").prop("disabled", false);
        }
    });
});

$(document).ready(function () {
    const ezidRadio = $('input[type="radio"][value="ezid"]');
    const dataciteRadio = $('input[type="radio"][value="datacite"]');
    const localArkRadio = $('input[type="radio"][value="localark"]');

    const show = selector => $(selector).removeClass('inactive');
    const hide = selector => $(selector).addClass('inactive');
    const disableSection = selector => $(selector).find(':input').prop('disabled', true).removeAttr('required');
    const enableSection = selector => $(selector).find(':input').prop('disabled', false);

    const hideAll = () => {
        hide('#ezid-configuration');
        hide('#datacite-configuration');
        hide('#datacite-required-metadata');
        hide('#datacite-optional-metadata');
        hide('#local-ark-configuration');
        disableSection('#ezid-configuration');
        disableSection('#datacite-configuration');
        disableSection('#datacite-required-metadata');
        disableSection('#datacite-optional-metadata');
        disableSection('#local-ark-configuration');
    };

    const activateEzid = () => {
        hideAll();
        show('#ezid-configuration');
        enableSection('#ezid-configuration');
        $('#ezid-configuration').find('[required-original="true"]').attr('required', 'required');
    };

    const activateDatacite = () => {
        hideAll();
        show('#datacite-configuration');
        show('#datacite-required-metadata');
        show('#datacite-optional-metadata');
        enableSection('#datacite-configuration');
        enableSection('#datacite-required-metadata');
        enableSection('#datacite-optional-metadata');
        $('#datacite-required-metadata').find('select').attr('required', 'required');
    };

    const activateLocalArk = () => {
        hideAll();
        show('#local-ark-configuration');
        enableSection('#local-ark-configuration');
        $('#local-ark-naan').attr('required', 'required');
    };

    hideAll();

    if (ezidRadio.prop('checked')) activateEzid();
    if (dataciteRadio.prop('checked')) activateDatacite();
    if (localArkRadio.prop('checked')) activateLocalArk();

    ezidRadio.on('change', function () { if (this.checked) activateEzid(); });
    dataciteRadio.on('change', function () { if (this.checked) activateDatacite(); });
    localArkRadio.on('change', function () { if (this.checked) activateLocalArk(); });
});

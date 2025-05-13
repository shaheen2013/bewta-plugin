// assets/js/bewta-admin.js
jQuery(document).ready(function($) {
    console.log('Bewta Admin JS loaded');

    $('.bewta-form-dropdown').on('change', function() {
        var plugin = $(this).data('plugin');
        var formId = $(this).val();
        var formText = $(this).find('option:selected').text();

        if (!formId) return;

        // prevent duplicate
        if ($('.bewta-form-setting[data-plugin="' + plugin + '"][data-form-id="' + formId + '"]').length) return;

        var inputHtml = '<div class="bewta-form-setting" data-plugin="' + plugin + '" data-form-id="' + formId + '">';
        inputHtml += '<label>' + formText + '</label>';
        inputHtml += '<input type="text" name="bewta_campaign_settings[' + plugin + '][' + formId + ']" style="width:400px; margin-top:5px;" />';
        inputHtml += '<button type="button" class="button bewta-remove-setting" style="margin-top:5px;">Remove</button>';
        inputHtml += '</div>';

        $(this).after(inputHtml);
    });

    $(document).on('click', '.bewta-remove-setting', function() {
        $(this).closest('.bewta-form-setting').remove();
    });
});

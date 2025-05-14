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

    $('#bewta_form_capture_mode').on('change', function() {
        if ($(this).val() === 'api') {
            $('#bewta_api_shortcode_row').show();
        } else {
            $('#bewta_api_shortcode_row').hide();
            $('#bewta_generated_shortcode').val('');
        }
    });

    $('#bewta_generate_shortcode').on('click', function() {
        console.log('clicked....');
        
        $('#bewta_generated_shortcode').val('Generating...');
        $.ajax({
            url: bewtaSettings.ajax_url,
            method: 'POST',
            data: {
                action: 'bewta_generate_api_shortcode',
                nonce: bewtaSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#bewta_generated_shortcode').val(response.data);
                } else {
                    $('#bewta_generated_shortcode').val('Error: ' + response.data);
                }
            },
            error: function() {
                $('#bewta_generated_shortcode').val('Request failed.');
            }
        });
    });
});

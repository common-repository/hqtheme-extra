(function ($) {

    HQTTemplatesAPI = {

        _stored_data: {},

        _request: function (args, resource) {
            // Add nonce
            args['_ajax_nonce'] = hqtTemplatesData._ajax_nonce
            args['templates_import_screen'] = $('.hqt-templates-import-screen').data('template-type')

            let jqxhr = $.get({
                url: ajaxurl,
                data: args
            }).done(function (res) {
                if (res.success) {
                    HQTTemplatesAPI._stored_data[resource] = res.data
                    $(document).trigger(args.trigger, res.data)
                } else {
                    $(document).trigger('hqt-templates-api-request-error')
                }
            }).fail(function (err) {
                $(document).trigger('hqt-templates-api-request-error')
            })
        },

    }

})(jQuery)

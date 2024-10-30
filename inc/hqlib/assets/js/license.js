(function ($) {

    HQLicense = {

        license_field: null,
        container: null,
        key: '',

        init: function () {
            HQLicense.license_field = $('#' + hqLicenseData.license_field_name);
            HQLicense.container = HQLicense.license_field.closest('.hqt-container')
            $(document).on('click', '#license_activate', HQLicense.activate);
            $(document).on('click', '#license_deactivate', HQLicense.deactivate);
        },

        activate: function (event) {
            event.preventDefault();

            $(document).trigger('hqlib/container/loader/show', HQLicense.container);

            HQLicense.key = HQLicense.license_field.val();
            if (HQLicense.key.length < 10) {
                alert('Invalid License Key');
                $(document).trigger('hqlib/container/loader/hide', HQLicense.container);
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'hqtheme-license-activate',
                    [hqLicenseData.license_field_name]: HQLicense.key,
                    _ajax_nonce: hqLicenseData._ajax_nonce
                },
                beforeSend: function () {

                }
            }).fail(function (jqXHR) {
                $(document).trigger('hqlib/container/loader/hide', HQLicense.container);
            }).done(function (result) {
                if (result.success) {
                    location.reload();
                } else {
                    $(document).trigger('hqlib/container/loader/hide', HQLicense.container);
                    alert(result.data);
                }
            });
        },

        deactivate: function (event) {
            event.preventDefault();

            $(document).trigger('hqlib/container/loader/show', $(this).closest('.hqt-container'));

            HQLicense.key = HQLicense.license_field.val();
            if (HQLicense.key.length < 10) {
                alert('Invalid License Key');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'hqtheme-license-deactivate',
                    [hqLicenseData.license_field_name]: HQLicense.key,
                    _ajax_nonce: hqLicenseData._ajax_nonce
                },
                beforeSend: function () {

                }
            }).fail(function (jqXHR) {
                $(document).trigger('hqlib/container/loader/hide', HQLicense.container);
            }).done(function (result) {
                if (result.success) {
                    location.reload();
                } else {
                    $(document).trigger('hqlib/container/loader/hide', HQLicense.container);
                    alert(result.data);
                }
            });
        }
    };

    // Run
    $(function () {
        HQLicense.init();
    });

})(jQuery);
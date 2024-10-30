(function ($) {

    HQTemplatesDetails = {
        current_template: [],
        current_screen: '',

        templateData: {},

        init: function ()
        {
            // Bind
            $(document).on('click', '.hqt-preview-demo', HQTemplatesDetails._preview);
            $(document).on('click', '.devices button', HQTemplatesDetails._previewDevice);
            $(document).on('click', '.next-theme', HQTemplatesDetails._nextTheme);
            $(document).on('click', '.previous-theme', HQTemplatesDetails._previousTheme);

            $(document).on('click', '.collapse-sidebar', HQTemplatesDetails._collapseSidebar);
            $(document).on('click', '.close-full-overlay', HQTemplatesDetails._closeFullOverlay);
            $(document).on('hqt-templates-api-template-details-loaded', HQTemplatesDetails._renderDemoPreview);


        },

        _previewDevice: function (event) {
            let device = $(event.currentTarget).data('device');

            $('.theme-install-overlay')
                    .removeClass('preview-desktop preview-tablet preview-mobile')
                    .addClass('preview-' + device)
                    .data('current-preview-device', device);

            HQTemplatesDetails._tooglePreviewDeviceButtons(device);
        },

        _tooglePreviewDeviceButtons: function (newDevice) {
            let $devices = $('.wp-full-overlay-footer .devices');

            $devices.find('button')
                    .removeClass('active')
                    .attr('aria-pressed', false);

            $devices.find('button.preview-' + newDevice)
                    .addClass('active')
                    .attr('aria-pressed', true);
        },

        _closeFullOverlay: function (event) {
            event.preventDefault();

            // Prevent close if import process is started?
            if ($('body').hasClass('importing-site')) {
                return;
            }

            $('body').removeClass('importing-site');
            $('.previous-theme, .next-theme').removeClass('disabled');
            $('.theme-install-overlay').css('display', 'none');
            $('.theme-install-overlay').remove();
            $('.theme-preview-on').removeClass('theme-preview-on');
        },

        _collapseSidebar: function () {
            event.preventDefault();

            overlay = $('.wp-full-overlay');

            if (overlay.hasClass('expanded')) {
                overlay.removeClass('expanded');
                overlay.addClass('collapsed');
                return;
            }

            if (overlay.hasClass('collapsed')) {
                overlay.removeClass('collapsed');
                overlay.addClass('expanded');
                return;
            }
        },

        _preview: function (event) {

            event.preventDefault();

            let template_id = $(this).parents('.template-item').data('id') || 0;

            if (template_id) {
                HQTemplatesDetails._get_template_details(template_id);
            }
        },

        _get_template_details: function (template_id) {
            let args = {
                action: 'hqt_template_details',
                params: {
                    'template_id': template_id
                },
                trigger: 'hqt-templates-api-template-details-loaded',
            };

            HQTTemplatesAPI._request(args, 'template_' + template_id);
        },

        _previousTheme: function (event) {
            event.preventDefault();
            currentDemo = $('.theme-preview-on');
            currentDemo.removeClass('theme-preview-on');
            prevDemo = currentDemo.prev('.theme');
            prevDemo.addClass('theme-preview-on');

            let template_id = $(this).parents('.wp-full-overlay-header').data('gid') || 0;

            if (HQTTemplatesAPI._stored_data['templates_listing'].results) {
                let previous_template_id = false;
                for (let k in HQTTemplatesAPI._stored_data['templates_listing'].results) {
                    if (HQTTemplatesAPI._stored_data['templates_listing'].results[k].id == template_id) {
                        if (previous_template_id !== false) {
                            HQTemplatesDetails._get_template_details(previous_template_id);
                            return;
                        }
                        break;
                    }
                    previous_template_id = HQTTemplatesAPI._stored_data['templates_listing'].results[k].id;
                }

                // Loop
                let keys = Object.keys(HQTTemplatesAPI._stored_data['templates_listing'].results);
                HQTemplatesDetails._get_template_details(HQTTemplatesAPI._stored_data['templates_listing'].results[keys[keys.length - 1]].id);
                return;
            }
        },

        _nextTheme: function (event) {
            event.preventDefault();
            currentDemo = $('.theme-preview-on')
            currentDemo.removeClass('theme-preview-on');
            nextDemo = currentDemo.next('.theme');
            nextDemo.addClass('theme-preview-on');

            let template_id = $(this).parents('.wp-full-overlay-header').data('gid') || 0;
            
            if (HQTTemplatesAPI._stored_data['templates_listing'].results) {
                let stopOnNext = false;
                for (let k in HQTTemplatesAPI._stored_data['templates_listing'].results) {
                    if (stopOnNext) {
                        HQTemplatesDetails._get_template_details(HQTTemplatesAPI._stored_data['templates_listing'].results[k].id);
                        return;
                    }
                    if (HQTTemplatesAPI._stored_data['templates_listing'].results[k].id == template_id) {
                        stopOnNext = true
                    }
                }
                // Loop
                HQTemplatesDetails._get_template_details(HQTTemplatesAPI._stored_data['templates_listing'].results[Object.keys(HQTTemplatesAPI._stored_data['templates_listing'].results)[0]].id);
                return;
            }
        },

        _renderDemoPreview: function (event, data) {
            // pass `has_active_license` value
            data['has_active_license'] = HQTemplatesGrid.has_active_license;

            // Remove previous preview
            $('.theme-install-overlay').remove();

            // Create preview and render tempalate
            let template = wp.template('hqt-templates-template-preview');

            HQTemplatesDetails.templateData = data;

            $('#template-details').html(template(data));

            // Options by template type
            if ('sites' === HQTemplatesGrid.template_type) { // Sites
                $('.hqtheme-install-plugins').show();
                $('.hqtheme-import-customizer').show();
                $('.hqtheme-import-content').show();
                $('.hqtheme-reset-demo').show();
            } else if ('templates' === HQTemplatesGrid.template_type) { // Templates
                $('.hqtheme-install-plugins').show();
                $('.hqtheme-import-customizer').hide();
                $('.hqtheme-import-content').show();
                $('.hqtheme-reset-demo').hide();
            }

            HQTemplatesDetails._bindImportSettings();

            // Show overlay
            $('.theme-install-overlay').css('display', 'block');
        },

        _bindImportSettings: function () {
            var requires = $('.hqtheme-import-options').find('li[data-requires]');
            if (requires.length) {
                $.each(requires, function (i, value) {
                    var $checkbox = $(value).find('.checkbox');
                    // Switch on all the required settings
                    $checkbox.on('change', function () {
                        if ($(this).is(':checked')) {
                            $.each($(value).data('requires'), function (i, required) {
                                $('.hqtheme-' + required).find('.checkbox').prop("checked", true);
                            });
                        }
                    });
                    // Switch off related settings
                    $.each($(value).data('requires'), function (i, required) {
                        $('.hqtheme-' + required).find('.checkbox').on('change', function () {
                            if ($(this).not(':checked')) {
                                $checkbox.prop("checked", false);
                            }
                        });
                    });
                });
            }
        },

    }

    // Run
    $(function () {
        HQTemplatesDetails.init();
    });

})(jQuery);
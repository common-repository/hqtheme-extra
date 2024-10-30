(function ($) {

    HQTemplatesInstall = {

        /**
         * If false prevent check if checkbox is checked
         */
        importingThemeDemo: true,

        /**
         * Template for import
         */
        template_id: null,
        inactive_plugins: [],
        data_for_delete: [],
        post_ids_for_fix: [],
        /**
         * Installation results html element
         */
        install_result: null,

        init: function ()
        {
            // Sites
            if ('sites' === HQTemplatesGrid.template_type) {
                // Start
                $(document).on('click', '.hqt-install-template-btn', HQTemplatesInstall.startImport);
                // Delete previous
                $(document).on('hqt/demo/start/ready', HQTemplatesInstall.deleteAllPreviousData);
                // Plugins
                $(document).on('hqt/demo/delete_previuos/ready', HQTemplatesInstall.pluginsInstall);
                // Genedal Configs like Pods
                $(document).on('hqt/demo/required_plugins/ready', HQTemplatesInstall.generalConfigs);
                // Templates
                //$(document).on('hqt/demo/general_configs/ready', HQTemplatesInstall.importTemplates);
                // Content
                $(document).on('hqt/demo/general_configs/ready', HQTemplatesInstall.importContent);
                // Customizer data 
                $(document).on('hqt/demo/import_content/ready', HQTemplatesInstall.importCustomizerData);
                // Fix Custom Fonts
                $(document).on('hqt/demo/customizer_data/ready', HQTemplatesInstall.fixCustomFonts);

                // Fixes
                $(document).on('hqt/demo/fix_custom_fonts/ready', HQTemplatesInstall.importFixes);
                // Finish
                $(document).on('hqt/demo/fixes/ready', HQTemplatesInstall.importReady);
            }
            // Templates
            else if ('popup' === HQTemplatesGrid.template_type) {
                // Start 
                $(document).on('click', '.hq-popup-import-btn', HQPopupImport.startImport);
                // Plugins
                $(document).on('hqt/demo/start/ready', HQTemplatesInstall.pluginsInstall);
                // Content
                $(document).on('hqt/demo/required_plugins/ready', HQTemplatesInstall.importPopup);
                // Fix Ids
                $(document).on('hqt/demo/import_popup/ready', HQTemplatesInstall.importFixes);
                // Ready
                $(document).on('hqt/demo/fixes/ready', HQTemplatesInstall.importReady);
            }

            // Plugin Install & Activate.
            $(document).on('wp-plugin-installing', HQTemplatesInstall.pluginInstalling);
            $(document).on('wp-plugin-install-error', HQTemplatesInstall.pluginInstallError);
            $(document).on('wp-plugin-install-success', HQTemplatesInstall.pluginInstallSuccess);
            // Finish
            $(document).on('hqt/demo/import/done', HQTemplatesInstall.importDone);
            $(document).on('hqt/demo/import/close_after_import', HQTemplatesInstall.refreshSetupWizardPage);
            $(document).on('click', '#btn-import-close', HQTemplatesInstall.dismissPopup);
            // Fail
            $(document).on('hqt/demo/import/fail', HQTemplatesInstall.importFail);
        },

        startImport: function (event) {
            let header = $(event.currentTarget).parents('.wp-full-overlay-sidebar').find('.wp-full-overlay-header');
            HQTemplatesInstall.template_id = header.data('gid');

            // Disable 'Install theme info' section
            $('.install-theme-info').addClass('loading');

            // Display import ovelay and messages
            HQTemplatesInstall.install_result = $('#theme-install-result');
            HQTemplatesInstall.install_result.show();

            HQTemplatesInstall.setCurrentStatus('Starting import...');
            HQTemplatesInstall.setProgress(10);

            HQTemplatesInstall.templateData = HQTemplatesDetails.templateData

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'hqtheme-import-start',
                    template_id: HQTemplatesInstall.template_id,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                },
                beforeSend: function () {}
            }).fail(function (jqXHR) {
                console.log(jqXHR);
                HQTemplatesInstall.setCurrentStatus('<b>Start Failed!</b>');
                $(document).trigger('hqt/demo/start/fail');
            }).done(function (result) {
                if (result.success) {
                    $(document).trigger('hqt/demo/start/ready');
                } else {
                    HQTemplatesInstall.setCurrentStatus('<b>Start Failed!</b>', result.data);
                    $(document).trigger('hqt/demo/start/fail');
                }
            });

        },

        importFixes: function () {
            HQTemplatesInstall.setCurrentStatus('Finalizing import...');
            HQTemplatesInstall.setProgress(90);
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hqtheme-import-fixes',
                    template_type: HQTemplatesGrid.template_type,
                    template_id: HQTemplatesInstall.template_id,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                }
            }).fail(function (jqXHR) {
                HQTemplatesInstall.setCurrentStatus('Finalizing Fixes import failed.');
            }).done(function (result) {
                if (result.success) {
                    HQTemplatesInstall.post_ids_for_fix = result.data.elementor_posts_ids;
                    HQTemplatesInstall.fixPosts();
                } else {
                    HQTemplatesInstall.setCurrentStatus('<b>Finalizing Fixes import failed!</b>', result.data);
                    $(document).trigger('hqt/demo/import/fail');
                }
            });
        },

        fixPosts: function () {

            let post_ids_for_fix = HQTemplatesInstall.post_ids_for_fix;

            let id_for_fix = 0;

            if (undefined !== post_ids_for_fix && undefined !== post_ids_for_fix[0]) {
                id_for_fix = post_ids_for_fix[0];
            }

            if (0 == id_for_fix) {
                $(document).trigger('hqt/demo/fixes/ready');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hqtheme-import-fix-elementor-post',
                    id: id_for_fix,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                }
            }).fail(function (jqXHR) {
                console.log(jqXHR);
                HQTemplatesInstall.setCurrentStatus('<b>Fixing post failed!</b>');
                HQTemplatesInstall.post_ids_for_fix = HQTemplatesInstall._removeDataFromList(id_for_fix, HQTemplatesInstall.post_ids_for_fix);
                HQTemplatesInstall.fixPosts();
            }).done(function (result) {
                HQTemplatesInstall.setCurrentStatus('Successfully fixed post ID: ' + id_for_fix, result.data || '');
                HQTemplatesInstall.post_ids_for_fix = HQTemplatesInstall._removeDataFromList(id_for_fix, HQTemplatesInstall.post_ids_for_fix);
                HQTemplatesInstall.fixPosts();

            });
        },

        setProgress: function (progress) {
            HQTemplatesInstall.install_result.find('#progress-bar').val(progress);
        },

        setCurrentStatus: function (title, description = '') {
            let status_title_element = HQTemplatesInstall.install_result.find('.current-importing-status-title');
            status_title_element.append('<p>' + title + '</p>');
            status_title_element.animate({scrollTop: status_title_element.prop("scrollHeight")}, 100);
            if (description.length) {
                HQTemplatesInstall.install_result.find('.current-importing-status-description').html('<p>' + description + '</p>');
        }
        },

        importReady: function () {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hqtheme-import-finish',
                    old_url: HQTemplatesDetails.templateData.demo_url,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                }
            }).fail(function (jqXHR) {

            }).done(function (result) {

            });

            HQTemplatesInstall.setProgress(100);
            // Show instruction text
            HQTemplatesInstall.install_result.find('.import-body').hide();
            HQTemplatesInstall.install_result.find('.import-status').hide();
            HQTemplatesInstall.install_result.find('.import-instructions').show();
            HQTemplatesInstall.setCurrentStatus('<b>Import completed!</b>');
            $(document).trigger('hqt/demo/import/done');
        },

        generalConfigs: function () {
            HQTemplatesInstall.setCurrentStatus('Setting general configs...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'hqtheme-import-set-general-configs',
                    template_id: HQTemplatesInstall.template_id,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                },

                beforeSend: function () {}
            }).fail(function (jqXHR) {
                HQTemplatesInstall.setCurrentStatus('<b>Setting general configs failed!</b>');
                $(document).trigger('hqt/demo/general_configs/fail');
            }).done(function (result) {
                if (result.success) {
                    HQTemplatesInstall.setCurrentStatus('Setting general configs ready.');
                    $(document).trigger('hqt/demo/general_configs/ready');
                } else {
                    HQTemplatesInstall.setCurrentStatus('<b>Setting general configs failed!</b>', result.data);
                    $(document).trigger('hqt/demo/general_configs/fail');
                }
            });
        },

        fixCustomFonts: function () {
            HQTemplatesInstall.setCurrentStatus('Fixing custom fonts...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'hqtheme-import-fix-custom-fonts',
                    template_id: HQTemplatesInstall.template_id,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                },

                beforeSend: function () {}
            }).fail(function (jqXHR) {
                HQTemplatesInstall.setCurrentStatus('<b>Fixing custom fonts failed!</b>');
                $(document).trigger('hqt/demo/fix_custom_fonts/fail');
            }).done(function (result) {
                if (result.success) {
                    HQTemplatesInstall.setCurrentStatus('Fixing custom fonts ready.');
                    $(document).trigger('hqt/demo/fix_custom_fonts/ready');
                } else {
                    HQTemplatesInstall.setCurrentStatus('<b>Fixing custom fonts failed!</b>', result.data);
                    $(document).trigger('hqt/demo/fix_custom_fonts/fail');
                }
            });
        },

        importDone: function () {
            // Show close button
            HQTemplatesInstall.install_result.find('.import-footer').show();

            // Enable 'Install theme info' section
            $('.install-theme-info').removeClass('loading');
        },
        
        refreshSetupWizardPage: function () {
            if ($('.marmot-theme_page_marmot-theme-setup .step-current .step-3').length) {
                location.reload();
            }
        },

        importFail: function () {
            HQTemplatesInstall.setCurrentStatus('<b>Error occured, please try again.</b>');
            HQTemplatesInstall.setProgress(0);
            $(document).trigger('hqt/demo/import/done');
        },

        dismissPopup: function () {
            $(document).trigger('hqt/demo/import/close_after_import');
            // Reset popup
            HQTemplatesInstall.setProgress(0);
            HQTemplatesInstall.install_result.find('.current-importing-status-title').html('');
            HQTemplatesInstall.install_result.find('.current-importing-status-description').html('');
            HQTemplatesInstall.install_result.find('.import-body').show();
            HQTemplatesInstall.install_result.find('.import-status').show();
            HQTemplatesInstall.install_result.find('.import-instructions').hide();
            HQTemplatesInstall.install_result.find('.import-footer').hide();
            // Close popup
            HQTemplatesInstall.install_result.hide();
            // Close Preview
            $('.close-full-overlay').trigger('click');
        },

        deleteAllPreviousData: function () {
            HQTemplatesInstall.setProgress(20);
            if (HQTemplatesInstall.is_enabled('reset-demo')) {
                HQTemplatesInstall.setCurrentStatus('Deleting prevous data...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'hqtheme-import-get-reset-data',
                        _ajax_nonce: hqtTemplatesData._ajax_nonce
                    },
                    beforeSend: function () {}
                }).fail(function (jqXHR) {
                    console.log(jqXHR);
                    HQTemplatesInstall.setCurrentStatus('<b>Deleting all previous data failed!</b>');
                    $(document).trigger('hqt/demo/import/fail');
                }).done(function (result) {
                    if (result.success) {
                        HQTemplatesInstall.data_for_delete = result.data;
                        HQTemplatesInstall.deletePreviousData();
                    } else {
                        HQTemplatesInstall.setCurrentStatus('<b>Deleting all previous data failed!</b>', result.data);
                        $(document).trigger('hqt/demo/import/fail');
                    }
                });
            } else {
                $(document).trigger('hqt/demo/delete_previuos/ready');
            }

        },

        deletePreviousData: function () {
            let data_for_delete = HQTemplatesInstall.data_for_delete;

            let id_for_detele = 0;
            let type_for_delete = '';

            if (undefined !== data_for_delete.posts && undefined !== data_for_delete.posts[0]) {
                id_for_detele = data_for_delete.posts[0];
                type_for_delete = 'posts';
            }

            if ('' === type_for_delete && undefined !== data_for_delete.terms && undefined !== data_for_delete.terms[0]) {
                id_for_detele = data_for_delete.terms[0];
                type_for_delete = 'terms';
            }

            if (undefined === id_for_detele || id_for_detele == 0) {
                $(document).trigger('hqt/demo/delete_previuos/ready');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hqtheme-import-delete-' + type_for_delete,
                    id: id_for_detele,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                }
            }).fail(function (jqXHR) {
                console.log(jqXHR);
                HQTemplatesInstall.setCurrentStatus('<b>Deleting previous data failed!</b>');
                $(document).trigger('hqt/demo/import/fail');
            }).done(function (result) {
                if (result.success) {
                    let type_for_delete_single = type_for_delete.substring(0, type_for_delete.length - 1);
                    HQTemplatesInstall.setCurrentStatus('Successfully deleted ' + type_for_delete_single + ' ID: ' + id_for_detele, result.data || '');
                    HQTemplatesInstall.data_for_delete[type_for_delete] = HQTemplatesInstall._removeDataFromList(id_for_detele, HQTemplatesInstall.data_for_delete[type_for_delete]);
                    HQTemplatesInstall.deletePreviousData();
                } else {
                    HQTemplatesInstall.setCurrentStatus('<b>Deleting previous data failed!</b>', result.data);
                    $(document).trigger('hqt/demo/import/fail');
                }
            });
        },

        /**
         * Bulk Plugin Active & Install
         */
        pluginsInstall: function () {
            HQTemplatesInstall.setProgress(30);
            if (HQTemplatesInstall.is_enabled('install-plugins') || false === HQTemplatesInstall.importingThemeDemo) {

                HQTemplatesInstall.setCurrentStatus('Checking required plugins...');

                let templateData = HQTemplatesInstall.templateData;

                if (0 === templateData.required_plugins.length) {
                    HQTemplatesInstall.setCurrentStatus('Plugins Ready.');
                    $(document).trigger('hqt/demo/required_plugins/ready');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'hqtheme-import-required-plugins',
                        required_plugins: templateData.required_plugins,
                        _ajax_nonce: hqtTemplatesData._ajax_nonce
                    },
                    beforeSend: function () {
                    }
                }).fail(function (jqXHR) {
                    console.log(jqXHR);
                    HQTemplatesInstall.setCurrentStatus('<b>Plugins install failed!</b>');
                    $(document).trigger('hqt/demo/import/fail');
                }).done(function (result) {
                    if (result.success) {
                        HQTemplatesInstall.inactive_plugins = result.data.required_plugins.inactive;
                        HQTemplatesInstall.notinstalled = result.data.required_plugins.notinstalled;
                        if (result.data.required_plugins.notinstalled.length) {
                            HQTemplatesInstall.installAllPlugins(result.data.required_plugins.notinstalled);
                        } else {
                            HQTemplatesInstall.activateAllPlugins();
                        }
                    } else {
                        HQTemplatesInstall.setCurrentStatus('<b>Plugins install failed!</b>', result.data);
                        $(document).trigger('hqt/demo/import/fail');
                    }
                });
            } else {
                $(document).trigger('hqt/demo/required_plugins/ready');
            }
        },
        /**
         * Install All Plugins.
         * @param {type} not_installed_plugins
         */
        installAllPlugins: function (not_installed_plugins) {
            HQTemplatesInstall.setCurrentStatus('Installing required plugins...');

            $.each(not_installed_plugins, function (index, plugin) {
                // Add each plugin activate request in Ajax queue.
                // @see wp-admin/js/updates.js
                wp.updates.queue.push({
                    action: 'install-plugin', // Required action.
                    data: {
                        slug: plugin.slug,
                        init: plugin.init,
                        name: plugin.name,
                        success: function () {
                            $(document).trigger('wp-plugin-install-success', [plugin]);
                        },
                        error: function () {
                            $(document).trigger('wp-plugin-install-error', [plugin]);
                        }
                    }
                });
            });

            // Required to set queue.
            wp.updates.queueChecker();
        },

        /**
         * Installing Plugin
         * @param {type} event
         * @param {type} args
         */
        pluginInstalling: function (event, args) {
            event.preventDefault();

            HQTemplatesInstall.setCurrentStatus('Installing plugin ' + args.name + '...');

            console.groupCollapsed('Installing plugin "' + args.name + '"');
        },

        /**
         * Install Success
         * @param {type} event
         * @param {type} response
         */
        pluginInstallSuccess: function (event, response) {

            event.preventDefault();
            console.groupEnd();

            HQTemplatesInstall.setCurrentStatus('Plugin ' + response.name + ' installed');

            // WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
            setTimeout(function () {
                HQTemplatesInstall.inactive_plugins.push(response);
                HQTemplatesInstall.notinstalled = HQTemplatesInstall._removePluginFromList(response.init, HQTemplatesInstall.notinstalled);
                if (HQTemplatesInstall.notinstalled.length == 0) {
                    HQTemplatesInstall.activateAllPlugins();
                }
            }, 1500);
        },

        /**
         * Plugin Installation Error.
         * @param {type} event
         * @param {type} response
         */
        pluginInstallError: function (event, response) {

            event.preventDefault();

            HQTemplatesInstall.setCurrentStatus('Plugin ' + response.name + ' error');

            wp.updates.queue = [];
            wp.updates.queueChecker();

            console.groupEnd();
            $(document).trigger('hqt/demo/import/fail');
        },

        activateAllPlugins: function () {
            activate_plugins = HQTemplatesInstall.inactive_plugins;

            if (undefined === HQTemplatesInstall.inactive_plugins[0]) {
                HQTemplatesInstall.setCurrentStatus('Plugins ready.');
                $(document).trigger('hqt/demo/required_plugins/ready');
                return;
            }

            plugin = HQTemplatesInstall.inactive_plugins[0];

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    'action': 'hqtheme-import-required-plugin-activate',
                    'init': plugin.init,
                    _ajax_nonce: hqtTemplatesData._ajax_nonce
                }
            }).fail(function (jqXHR) {
                HQTemplatesInstall.setCurrentStatus('Failed plugin activation: ' + plugin.name);
                console.log('Failed plugin activation url - ' + plugin.name);
                console.groupEnd();
                $(document).trigger('hqt/demo/import/fail');
            }).done(function (result) {
                if (result.success) {
                    $.ajax({
                        url: result.data.activation_url.replace(/&amp;/g, "&"),
                        type: 'GET',
                    }).fail(function (jqXHR) {
                        HQTemplatesInstall.setCurrentStatus('Failed plugin activation: ' + plugin.name);
                        console.log('Failed plugin activation- ' + plugin.name);
                        console.groupEnd();
                        $(document).trigger('hqt/demo/import/fail');
                    }).done(function (result) {
                        HQTemplatesInstall.setCurrentStatus('Successfully activated plugin: ' + plugin.name);
                        HQTemplatesInstall.inactive_plugins = HQTemplatesInstall._removePluginFromList(plugin.init, HQTemplatesInstall.inactive_plugins);

                        if (0 === HQTemplatesInstall.inactive_plugins.length) {
                            // trigger finish
                            HQTemplatesInstall.setCurrentStatus('Plugins ready.');
                            $(document).trigger('hqt/demo/required_plugins/ready');
                            console.groupEnd('Activating required plugins...');
                        } else {
                            // activate next plugin
                            HQTemplatesInstall.activateAllPlugins();
                        }
                    });
                } else {
                    HQTemplatesInstall.setCurrentStatus('Failed Plugin Activation - ' + plugin.name, result.data || '');
                    $(document).trigger('hqt/demo/import/fail');
                }
            });
        },

        _removePluginFromList: function (removeItem, pluginsList) {
            return jQuery.grep(pluginsList, function (value) {
                return value.init != removeItem;
            });
        },

        _removeDataFromList: function (removeItem, pluginsList) {
            return jQuery.grep(pluginsList, function (value) {
                return value != removeItem;
            });
        },

        importCustomizerData: function (event, response) {
            HQTemplatesInstall.setProgress(80);
            if (HQTemplatesInstall.is_enabled('import-customizer')) {

                HQTemplatesInstall.setCurrentStatus('Importing Customizer Settings...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hqtheme-import-customizer-settings',
                        template_id: HQTemplatesInstall.template_id,
                        _ajax_nonce: hqtTemplatesData._ajax_nonce
                    }
                }).fail(function (jqXHR) {
                    console.log(jqXHR);
                    HQTemplatesInstall.setCurrentStatus('Customizer settings import failed');
                    $(document).trigger('hqt/demo/import/fail');
                }).done(function (result) {
                    if (result.success) {
                        HQTemplatesInstall.setCurrentStatus('Customizer settings import done');
                        $(document).trigger('hqt/demo/customizer_data/ready');
                    } else {
                        HQTemplatesInstall.setCurrentStatus('Customizer settings import failed!', result.data || '');
                        $(document).trigger('hqt/demo/import/fail');
                    }
                });
            } else {
                $(document).trigger('hqt/demo/customizer_data/ready');
            }
        },

        importContent: function () {
            HQTemplatesInstall.setProgress(70);
            HQTemplatesInstall.import('content');
        },

        importTemplates: function () {
            HQTemplatesInstall.setProgress(50);
            HQTemplatesInstall.import('elementor-templates');
        },

        importPopup: function () {
            HQTemplatesInstall.setProgress(70);
            HQTemplatesInstall.import('popup');
        },

        import: function (content_type) {

            if (HQTemplatesInstall.is_enabled('import-' + content_type) || false === HQTemplatesInstall.importingThemeDemo) {

                console.log('Importing ' + content_type);

                HQTemplatesInstall.setCurrentStatus('Importing ' + content_type + '...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'hqtheme-import-content',
                        template_id: HQTemplatesInstall.template_id,
                        template_type: content_type,
                        _ajax_nonce: hqtTemplatesData._ajax_nonce
                    },
                    beforeSend: function () {
                    }
                }).fail(function (jqXHR) {
                    console.log(jqXHR);
                    HQTemplatesInstall.setCurrentStatus('Importing ' + content_type + ' Failed!');
                    $(document).trigger('hqt/demo/import/fail');
                }).done(function (xml_data) {

                    xml_data.data.url = wp.url.addQueryArgs(
                            xml_data.data.url,
                            {
                                _ajax_nonce: hqtTemplatesData._ajax_nonce,
                                template_type: content_type
                            }
                    );
                    // 2. Fail - Prepare XML Data.
                    if (false === xml_data.success) {
                        var error_msg = xml_data.data.error || xml_data.data
                    } else {

                        var xml_processing = $('.hqt-templates-import-screen').attr('data-xml-processing');
                        if ('yes' === xml_processing) {
                            return;
                        }

                        $('.hqt-templates-import-screen').attr('data-xml-processing', 'yes');

                        $('.current-importing-status-description').html('').show();

                        var evtSource = new EventSource(xml_data.data.url);
                        evtSource.onmessage = function (message) {
                            var data = JSON.parse(message.data);
                            switch (data.action) {
                                case 'updateDelta':
                                    console.log(data.delta, data.type);
                                    break;

                                case 'complete':
                                    HQTemplatesInstall.setCurrentStatus('Importing ' + content_type + ' completed.');
                                    evtSource.close();

                                    $('.current-importing-status-description').hide();
                                    $('.hqt-templates-import-screen').removeAttr('data-xml-processing');

                                    $(document).trigger('hqt/demo/import_' + content_type + '/ready');

                                    break;
                            }
                        };
                        evtSource.addEventListener('log', function (message) {
                            var data = JSON.parse(message.data);
                            var message = data.message || '';
                            console.log(message);
                            if (message && 'info' === data.level) {
                                message = message.replace(/"/g, function (letter) {
                                    return '';
                                });
                                $('.current-importing-status-description').html(message);
                            }
                        });
                    }
                });
            } else {
                $(document).trigger('hqt/demo/import_' + content_type + '/ready');
            }
        },

        is_enabled: function (action) {
            if ($('.hqtheme-' + action).find('.checkbox').is(':checked')) {
                return true;
            }
            return false;
        }
    };

    // Run
    $(function () {
        HQTemplatesInstall.init();
    });

})(jQuery);
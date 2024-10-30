(function ($) {

    'use strict';

    var HQLib = {

        translate: [],

        init: function () {
            HQLib.translate = hqlibData.translate;

            HQLib.initTabs();
            HQLib.initSelect2();
            HQLib.initFieldsConditionalLogic();
            HQLib.initRepeater();
            HQLib.initActionButtons();
            HQLib.initFormSubmit();
            $(document).on('hqlib/container/loader/show', HQLib.showLoader);
            $(document).on('hqlib/container/loader/hide', HQLib.hideLoader);

            $(document).on('hqlib/container/saved', HQLib.toggleAjaxNotification);
        },
        getUrlParam: function (param, url = null) {
            if (url) {
                return (RegExp(param + '=' + '(.+?)(&|$)').exec(url) || [, null])[1];
            } else {
                var sPageURL = window.location.search.substring(1);
                var sURLVariables = sPageURL.split('&');
                for (var i = 0; i < sURLVariables.length; i++) {
                    var sParameterName = sURLVariables[i].split('=');
                    if (sParameterName[0] === param) {
                        return sParameterName[1];
                    }
                }
        }
        },
        // HQ Tabs
        initTabs: function () {
            $(".hqt-tabs-nav li a").click(function (e) {
                e.preventDefault();
            });

            $(".hqt-tabs-nav li").click(function () {
                var href = $(this).find("a").attr("href"),
                        tab_id = HQLib.getUrlParam('tab', href),
                        items = $(this).siblings(),
                        tabs = $(this).closest('.hqt-tabs-nav').siblings('.hqt-tabs-content').find('.hqt-tab');

                items.removeClass('active');
                tabs.removeClass('active');

                $('#' + tab_id).addClass('active'); // Show tab
                $(this).addClass('active');         // Adding active class to clicked tab

                if (history.pushState) {
                    window.history.pushState({}, "", href);
                } else {
                    document.location.href = href;
                }
            });
        },
        select2: function ($selector) {
            if ($selector.is('[data-ajax]')) {
                // Init Select2 with remote data source (AJAX)
                $selector.select2({
                    ajax: {
                        type: 'POST',
                        url: ajaxurl, // AJAX URL is predefined in WordPress admin
                        dataType: 'json',
                        delay: 250, // Delay in ms while typing when to perform a AJAX search
                        data: function (params) {
                            return {
                                q: params.term, // search query
                                action: 'hqlib_select2', // AJAX action for admin-ajax.php
                                options_type: $(this).data('options-type') || 'post',
                                object_type: $(this).data('object-type') || 'post',
                                _ajax_nonce: hqlibData._ajax_nonce
                            };
                        },
                        processResults: function (data) {
                            var options = [];
                            if (data.results) {
                                // Data is the array of objects, and each of them contains Value and Label of the option
                                $.each(data.results, function (index, row) {
                                    options.push({id: row.value, text: row.label});
                                });
                            }
                            return {
                                results: options
                            };
                        },
                        cache: true
                    }
                });
            } else {
                // Init Select2 with regular data source (Options)
                $selector.select2();
            }
        },
        initSelect2: function () {
            $('.hqt-form-control.__select2').each(function (i, el) {
                HQLib.select2($(el));
            });
        },
        initDateTimePicker: function () {
            $('.hqt-form-control.__datetime').each(function (i, el) {
                var options = $(el).data('options') || {};
                $(el).datepicker({
                    language: 'en'
                });
            });
        },
        // HQ Repeater
        initRepeater: function () {
            $('.hqt-repeater__container').hqRepeater();
        },
        // HQ fields conditional display
        initFieldsConditionalLogic: function () {
            $('.hqt-field[data-conditions]').conditionize();
        },
        // Show container loader
        showLoader: function (event, container) {
            if (!$(container).find('spinner-wrap').length) {
                $(container).append('<div class="spinner-wrap"><span class="spinner"></span></div>');
            }
            $('body').addClass('loading-content');
        },
        // Hide container loader
        hideLoader: function (event, container) {
            $(container).find('.spinner-wrap').remove();
            $('body').removeClass('loading-content');
        },
        initFormSubmit: function () {
            $('.hqt-container > form.hqt-form').each(function () {
                var $form = $(this);

                // Append submit button loader
                $form.find('.hqt-container-footer button[type="submit"]').each(function () {
                    var $button = $(this);
                    $button.append('<span class="hqt-btn-loader"><span></span><span></span><span></span><span></span></span>');
                    $button.find('.hqt-btn-loader span').css('background', $button.css('color'));
                });

                $form.submit(function (e) {
                    if (typeof window.FormData === 'function') {
                        HQLib.formSubmit($form, e);
                    }
                });

                $form.on('form/loader/show', function () {
                    $(this).addClass('loading');
                })
                $form.on('form/loader/hide', function () {
                    $(this).removeClass('loading');
                })
            });
        },
        formSubmit: function (form, event) {
            var $form = $(form);

            if (true === $form.data('ajax-submit')) {
                // Ajax submit action
                event.preventDefault();

                var formData = $form.serializeArray();
                formData.push({name: 'action', value: 'hqlib_save_options'});

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    beforeSend: function () {
                        $form.trigger('form/loader/show');
                    }
                }).complete(function (jqXHR) {
                    $form.trigger('form/loader/hide');
                }).fail(function (jqXHR) {
                    $(document).trigger('hqlib/container/saved', ['Error while saving settings. Please try again.', 2]);
                }).done(function (result) {
                    if (result.success) {
                        $(document).trigger('hqlib/container/saved');
                    } else {
                        $(document).trigger('hqlib/container/saved', ['Error while saving settings. Please try again.', 2]);
                    }
                });
            } else {
                // Just show loader
                $form.trigger('form/loader/show');
            }
        },
        initActionButtons: function () {
            // Install / Activate plugins
            $('[data-hqt-action-btn]').each(function (i, el) {
                var $button = $(this);
                var action = $button.data('action');

                // Change button text
                if ($button.data('action-label')) {
                    var label = $button.html(), actionLabel;

                    if ('install-activate' == action) {
                        if ($button.data('install-url').length) {
                            actionLabel = HQLib._('install');
                        } else {
                            if ($button.data('deactivate-url').length) {
                                actionLabel = HQLib._('deactivate');
                            } else {
                                actionLabel = HQLib._('activate');
                            }
                        }
                    } else if ('enable' == action) {
                        actionLabel = HQLib._('enable');
                    }

                    switch ($button.data('action-label')) {
                        case 'prepend':
                            $button.html(actionLabel + ' ' + label);
                            break;
                        case 'replace':
                            $button.html(actionLabel);
                            break;
                    }
                }
                // Wrap button text in span tag
                if (!$button.find('.btn-label').length) {
                    $button.wrapInner('<span class="btn-label"></span>');

                    // Append some html for ajax spinner on button only elements
                    if (!$button.find('.hqt-btn-loader').length) {
                        $button.append('<span class="hqt-btn-loader"><span></span><span></span><span></span><span></span></span>');
                        $button.find('.hqt-btn-loader span').css('background', $button.css('color'));
                    }
                }

                // Bind button click event
                HQLib.bindActionButton($button);
            });
        },
        bindActionButton: function (button) {
            var $button = $(button);
            $button.off('click');
            $button.on('click', function (e) {
                e.preventDefault();
                var $this = $(this);

                if ($this.hasClass('loading')) {
                    return false;
                }

                switch ($this.data('action')) {
                    case 'install-activate':
                        if ($this.data('install-url').length) {
                            // Install if plugin is missing
                            HQLib.installPluginByBtn($this);
                        } else {
                            if ($this.data('deactivate-url') && $this.data('deactivate-url').length) {
                                // Deactivate if plugin is installed
                                HQLib.deactivatePluginByBtn($this);
                            } else {
                                // Activate if plugin is installed
                                HQLib.activatePluginByBtn($this);
                            }
                        }
                        break;
                    case 'enable':
                        // Enable option
                        HQLib.enableOptionByBtn($this);
                        break;
                }
            });

            // Bind show/hide button loader
            $button.on('loader/show', function () {
                $(this).addClass('loading');
            });
            $button.on('loader/hide', function () {
                $(this).removeClass('loading');
                HQLib.bindActionButton($(this));
            });
        },
        installPluginByBtn: function (button) {
            var $button = $(button);

            if (!$button.data('install-url').length) {
                return false;
            }

            $.ajax({
                url: $button.data('install-url'),
                type: 'GET',
                beforeSend: function (xhr) {
                    $button.trigger('loader/show');
                    $button.find('.btn-label').html(HQLib._('installing'));
                }
            }).complete(function (jqXHR) {
                $button.trigger('loader/hide');
            }).fail(function (jqXHR) {
                $button.find('.btn-label').html(HQLib._('install'));
                alert('Plugin installation fail. Please try again.');
            }).done(function (result) {
                // Display notification
                $(document).trigger('hqlib/container/saved', ['Plugin successfully installed.']);
                // Set `Activate` button
                $button.find('.btn-label').html(HQLib._('activate'));
                $button.data('install-url', '');
                HQLib.bindActionButton($button);
            });
        },
        activatePluginByBtn: function (button) {
            var $button = $(button);

            if (!$button.data('activate-url').length) {
                return false;
            }

            $.ajax({
                url: $button.data('activate-url'),
                type: 'GET',
                beforeSend: function (xhr) {
                    $button.trigger('loader/show');
                    $button.find('.btn-label').html(HQLib._('activating'));
                }
            }).complete(function (jqXHR) {
                $button.trigger('loader/hide');
            }).fail(function (jqXHR) {
                $(document).trigger('hqlib/container/saved', ['Plugin activation fail. Please try again.', 2]);
            }).done(function (result) {
                HQLib.buttonCallbackAction($button);
            });
        },
        deactivatePluginByBtn: function (button) {
            var $button = $(button);

            if (!$button.data('deactivate-url') || !$button.data('deactivate-url').length) {
                return false;
            }

            $.ajax({
                url: $button.data('deactivate-url'),
                type: 'GET',
                beforeSend: function (xhr) {
                    $button.trigger('loader/show');
                    $button.find('.btn-label').html(HQLib._('deactivating'));
                }
            }).complete(function (jqXHR) {
                $button.trigger('loader/hide');
            }).fail(function (jqXHR) {
                $(document).trigger('hqlib/container/saved', ['Plugin deactivation fail. Please try again.', 2]);
            }).done(function (result) {
                HQLib.buttonCallbackAction($button);
            });
        },
        enableOptionByBtn: function (button) {
            var $button = $(button);
            var $form = $button.closest('form');
            var formData = $form.serializeArray();
            var [container, option] = $button.data('option');
            var data = [];

            data.push({name: 'hqt-container[' + HQLib.addHqlibPrefix(container) + ']', value: HQLib.addHqlibPrefix(container)});
            data.push({name: HQLib.addHqlibPrefix(option), value: 'on'});
            data.push({name: 'action', value: 'hqlib_save_options'});
            data.push(formData.filter(x => x.name === 'hqt_options_nonce')[0]);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: data,
                beforeSend: function () {
                    $button.trigger('loader/show');
                    $button.find('.btn-label').html(HQLib._('enabling'));
                }
            }).complete(function (jqXHR) {
                $button.trigger('loader/hide');
            }).fail(function (jqXHR) {
                $(document).trigger('hqlib/container/saved', ['Error occurred. Please try again later.', 2]);
            }).done(function (result) {
                if (result.success) {
                    $button.find('.btn-label').html(HQLib._('enabled'));
                }
                HQLib.buttonCallbackAction($button);
            });

        },
        buttonCallbackAction: function (button) {
            var $button = $(button);
            if (!$button.data('callback').length) {
                return false;
            }
            switch ($button.data('callback')) {
                case 'refresh-page':
                    location.reload();
                    break;
                case 'label-activated':
                    $button.replaceWith(HQLib._('activated'));
                    break;
                case 'hide':
                    $button.fadeOut();
            }
        },
        addHqlibPrefix: function (name) {
            if (!name.match("^" + hqlibData.hqlib_prefix)) {
                name = hqlibData.hqlib_prefix + name;
            }
            return name;
        },
        toggleAjaxNotification: function (e, message = 'Settings saved successfully!', status) {
            // TODO improve if multiple notifications comes
            var type = 'success';
            if ('undefined' !== typeof status) {
                switch (parseInt(status)) {
                    case 1:
                        type = 'success';
                        break;
                    case 2:
                        type = 'error';
                        break;
                }
            }
            var notification = $('<div class="hqt-ajax-notification __' + type + '">' + message + '</div>');
            $('body').append(notification);

            // Add class for transition effect
            setTimeout(function () {
                notification.addClass('show');
            }, 1);

            setTimeout(function () {
                notification.fadeOut(function () {
                    $(this).remove();
                });
            }, 2000);
        },
        _: function (key) {
            if (HQLib.translate[key].length) {
                return HQLib.translate[key];
            }
            return false;
        }
    };

    $.fn.hqRepeater = function () {
        $.fn.repeaterAddContainer = function ($container, $reset = false) {
            // Find next Item ID
            var newItemId, select2values = {};
            $container.siblings('.hqt-repeater__container').addBack().each(function () {
                if (!newItemId) {
                    newItemId = parseInt($(this).data('repeater-item')) + 1;
                } else {
                    var id = parseInt($(this).data('repeater-item'));
                    if (id >= newItemId) {
                        newItemId = id + 1;
                    }
                }
            });

            // Prepare Select2 fields for cloning
            $container.find('.hqt-form-control.__select2').each(function () {
                // Store Select2 values for population
                select2values[$(this).attr('name').replace(/\[([\d)]+)\]/, '[' + newItemId + ']')] = $(this).select2('data').map(function (option) {
                    return option.id || false;
                });
                $(this).select2("destroy")
                        .removeAttr('data-select2-id')
                        .removeAttr('aria-hidden')
                        .removeAttr('tabindex');
                $(this).find('option').removeAttr('data-select2-id');
            });

            // Clone container
            var $cloned = $container.clone();

            // Update cloned elements
            $cloned.attr('data-repeater-item', newItemId);
            $cloned.find('.hqt-repeater__id > span').html(newItemId + 1);
            $cloned.find('[id][name]').each(function (i, el) {
                var oldNameAttribute = $(el).attr('name'),
                        newIdAttribute = $(el).attr('id').replace(/__\d__/g, '__' + newItemId + '__'),
                        newNameAttribute = $(el).attr('name').replace(/\[([\d)]+)\]/, '[' + newItemId + ']');

                $(el).attr('id', newIdAttribute);
                $(el).attr('name', newNameAttribute);
                $(el).closest('.hqt-field').find('label').attr('for', newIdAttribute);
                // Checkbox
                if ($(el).is('[type="checkbox"]')) {
                    // Update hidden field
                    $(el).siblings('input[type="hidden"]').attr('name', newNameAttribute);
                }
                // Select
                if ($(el).is('select')) {
                    // Select2
                    if ($(el).is('.__select2')) {
                        // Update hidden field
                        $(el).siblings('input[type="hidden"]').attr('name', newNameAttribute);
                        // Update select options
                        $(el).find('option').removeAttr('data-select2-id');
                    } else {
                        $(el).val($container.find('[name="' + oldNameAttribute + '"]').val());
                    }
                }

            });

            // Append cloned container
            $container.parent().append($cloned);
            // Bind container actions
            $.fn.repeaterBindContainer($cloned);

            // Init Select2
            $container.find('.hqt-form-control.__select2').each(function (i, el) {
                HQLib.select2($(el));
            });
            $cloned.find('.hqt-form-control.__select2').each(function (i, el) {
                HQLib.select2($(el));
                // Populate Select2
                $(el).val(select2values[$(el).attr('name')]).trigger("change");
            });

            // Init Datepicker
            $cloned.find('.hqt-form-control.__datetime').each(function (i, el) {
                $(el).datepicker();
            });

            // Reset values
            if ($reset) {
                $.fn.resetContainer($cloned);
            }

            // Bind conditional fields
            $cloned.find('.hqt-field[data-conditions]').conditionize();

            // Expand repeated container
            $cloned.removeClass('collapsed');

            // Animated scroll to the cloned container
            $([document.documentElement, document.body]).animate({
                scrollTop: $cloned.offset().top - 42
            }, 1000);
        };
        $.fn.repeaterRemoveContainer = function ($container) {
            if ($container.siblings('.hqt-repeater__container').addBack().length > 1) {
                $container.remove();
            } else {
                $.fn.resetContainer($container);
            }
        };
        $.fn.repeaterToggleContainer = function ($container) {
            $container.toggleClass('collapsed');
        };
        $.fn.resetContainer = function ($container) {
            $container.find('[id][name]').each(function (i, el) {
                var default_value = $(el).closest('.hqt-field').data('default-value');
                if ($(el).is('input[type="text"], input[type="tel"], input[type="number"], input[type="password"], input[type="email"], input[type="url"], textarea, select')) {
                    if ($(el).is('.__select2')) {
                        $(el).val(default_value || null);
                    } else {
                        $(el).val(default_value);
                    }
                }
                if ($(el).is('input[type="checkbox"]')) {
                    $(el).prop('checked', (default_value ? 'checked' : false));
                }
                $(el).trigger("change");
            });
        };
        $.fn.repeaterBindContainer = function ($container) {
            var $id = $container.find('.hqt-repeater__id'),
                    $new = $container.find('.hqt-repeater-action.__new'),
                    $copy = $container.find('.hqt-repeater-action.__copy'),
                    $remove = $container.find('.hqt-repeater-action.__remove'),
                    $toggle = $container.find('.hqt-repeater-action.__toggle');

            $new.on('click', function () {
                $.fn.repeaterAddContainer($container, true);
            });
            $copy.on('click', function () {
                $.fn.repeaterAddContainer($container);
            });
            $remove.on('click', function () {
                $.fn.repeaterRemoveContainer($container);
            });
            $toggle.on('click', function () {
                $.fn.repeaterToggleContainer($container);
            });
            $id.on('click', function () {
                $.fn.repeaterToggleContainer($container);
            });
        };

        return this.each(function () {
            $.fn.repeaterBindContainer($(this));
        });
    };

    $.fn.conditionize = function (options) {
        var settings = $.extend({
            hideJS: true
        }, options);

        $.fn.showField = function ($section) {
            $section.prop('hidden', false);
            $section.find('[id^="_hqt_"]').prop('disabled', false);
        };
        $.fn.hideField = function ($section) {
            $section.prop('hidden', true);
            $section.find('[id^="_hqt_"]').prop('disabled', 'disabled');
        };

        $.fn.getFieldValue = function (name, $section) {
            var field;
            if ($section.parents('[data-repeater-field]').length) {
                // Conditional repeater field
                var $repeaterContainer = $section.closest('.hqt-repeater__container'),
                        repeaterItem = $repeaterContainer.data('repeater-item'),
                        repeaterField = $repeaterContainer.data('repeater-field'),
                        field = '[id="' + repeaterField + '__' + repeaterItem + '__' + name.replace('_hqt_', '') + '"]:not(:disabled)';
            } else {
                // Container conditional field
                field = '[id="' + name + '"]:not(:disabled)';
            }
            if ($(field).is('select, input[type="text"]')) {
                if ($(field).is('.__select2')) {
                    var value = $(field).select2('data');
                    if ($(field).prop('multiple')) {
                        var result = [];
                        $.each(value, function (index, row) {
                            result.push(row.id);
                        });
                        return result;
                    } else {
                        return value[0].id;
                    }
                } else {
                    return $(field).val();
                }
            } else if ($(field).is('input[type="checkbox"]')) {
                return $(field + ':checked').val();
            }

        };

        $.fn.passAll = function (res) {
            return res.every(function (r) {
                return (1 === r);
            });
        };

        $.fn.passAny = function (res) {
            return (-1 !== $.inArray(1, res));
        };

        $.fn.showOrHide = function (relation, rules, $section) {
            var res = rules.map(function (rule) {
                var result = 0;
                switch (rule.compare) {
                    case '=':
                        if ($.fn.getFieldValue(rule.field, $section) == rule.value) {
                            result = 1;
                        }
                        break;
                    case '!=':
                        if ($.fn.getFieldValue(rule.field, $section) != rule.value) {
                            result = 1;
                        }
                        break;
                    case '<':
                        if ($.fn.getFieldValue(rule.field, $section) < rule.value) {
                            result = 1;
                        }
                        break;
                    case '<=':
                        if ($.fn.getFieldValue(rule.field, $section) <= rule.value) {
                            result = 1;
                        }
                        break;
                    case '>':
                        if ($.fn.getFieldValue(rule.field, $section) > rule.value) {
                            result = 1;
                        }
                        break;
                    case '>=':
                        if ($.fn.getFieldValue(rule.field, $section) >= rule.value) {
                            result = 1;
                        }
                        break;
                    case 'IN':
                        if ($.isArray(rule.value) && -1 != $.inArray($.fn.getFieldValue(rule.field, $section), rule.value)) {
                            result = 1;
                        }
                        break;
                    case 'NOT IN':
                        if ($.isArray(rule.value) && -1 == $.inArray($.fn.getFieldValue(rule.field, $section), rule.value)) {
                            result = 1;
                        }
                        break;
                    case 'INCLUDES':
                        if ($.isArray($.fn.getFieldValue(rule.field, $section)) && -1 != $.inArray(rule.value.toString(), $.fn.getFieldValue(rule.field, $section))) {
                            result = 1;
                        }
                        break;
                    case 'EXCLUDES':
                        if ($.isArray($.fn.getFieldValue(rule.field, $section)) && -1 == $.inArray(rule.value.toString(), $.fn.getFieldValue(rule.field, $section))) {
                            result = 1;
                        }
                        break;
                }
                return result;
            });
            if ('AND' === relation && $.fn.passAll(res)) {
                $.fn.showField($section);
            } else if ('OR' === relation && $.fn.passAny(res)) {
                $.fn.showField($section);
            } else {
                $.fn.hideField($section);
            }
        };

        return this.each(function () {
            var {relation, rules} = $(this).data('conditions');
            var listenToFields = [];
            var $section = $(this);
            $.each(rules, function (index, rule) {
                var selector;
                if ($section.parents('[data-repeater-field]').length) {
                    // Listen to repeater field
                    var $repeaterContainer = $section.closest('.hqt-repeater__container'),
                            repeaterItem = $repeaterContainer.data('repeater-item'),
                            repeaterField = $repeaterContainer.data('repeater-field'),
                            selector = '[id="' + repeaterField + '__' + repeaterItem + '__' + rule.field.replace('_hqt_', '') + '"]';
                } else {
                    // Listen to container field
                    selector = '[id="' + rule.field + '"]';
                }
                if ($(selector).length) {
                    listenToFields.push(selector);
                }
            });
            var listenTo = listenToFields.join(', ');
            //Set up event listener
            $(listenTo).on('change', function () {
                $.fn.showOrHide(relation, rules, $section);
            });
            //If setting was chosen, hide everything first...
            if (settings.hideJS) {
                $(this).prop('hidden', true);
            }
            //Show based on current value on page load
            $.fn.showOrHide(relation, rules, $section);
        });
    };

    HQLib.init();

}(jQuery));
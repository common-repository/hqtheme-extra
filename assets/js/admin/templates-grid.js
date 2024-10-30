(function ($) {

    HQTemplatesGrid = {

        has_active_license: null,
        template_type: '',
        _search_timeout: null,

        _api_params: {},

        _first_time_loaded: true,

        init: function ()
        {
            HQTemplatesGrid.has_active_license = hqtTemplatesGridData.has_active_license;
            HQTemplatesGrid.template_type = $('.hqt-templates-import-screen').data('template-type')

            $(document).on('hqt-templates-api-post-loaded', HQTemplatesGrid._reinitGrid);

            $(document).on('click', '.filter-links a', HQTemplatesGrid._filterClick);
            $(document).on('keyup input', '.wp-filter-search', HQTemplatesGrid._search);

            // Trigger initial load
            $('.wp-filter-search').trigger('keyup');
            //$('.hqt-filter-category a.all').trigger('click');
        },

        _filterClick: function (event) {

            event.preventDefault();

            $(this).parents('.filter-links').find('a').removeClass('current');
            $(this).addClass('current');

            // Empty the search input
            $('.wp-filter-search').val('');

            // Hire Current results
            //$('#hqt-templates-results').hide().css('height', '');

            // Add loading
            $('body').addClass('loading-content');

            // Show results
            HQTemplatesGrid._searchTemplates();
        },

        _search: function () {

            let search_val = $('.wp-filter-search').val() || '';
            if (search_val.length && search_val.length < 3) {
                return;
            }
            $('.filter-links.hqt-filter-category').find('a').removeClass('current');
            $('.filter-links.hqt-filter-category').find('[rel="all"]').addClass('current');
            
            $('body').addClass('loading-content');

            window.clearTimeout(HQTemplatesGrid._search_timeout);
            HQTemplatesGrid._search_timeout = window.setTimeout(function () {
                HQTemplatesGrid._search_timeout = null;
                // Show results
                HQTemplatesGrid._searchTemplates();
            }, 500);

        },

        _api_set_param_search_string: function () {
            let search_val = $('.wp-filter-search').val() || '';
            if ('' !== search_val) {
                HQTemplatesGrid._api_params['search'] = search_val;
            }
        },

        _api_set_param_category: function () {
            let selected_category = $('.filter-links.hqt-filter-category').find('.current').attr('rel');
            if ('all' !== selected_category) {
                HQTemplatesGrid._api_params['category'] = selected_category;
            }
        },

        _searchTemplates: function (resetPagedCount = true, trigger = 'hqt-templates-api-post-loaded') {

            // Prepare Params for API request.
            HQTemplatesGrid._api_params = {};

            HQTemplatesGrid._api_set_param_search_string();
            HQTemplatesGrid._api_set_param_category();

            let args = {
                action: 'hqt_search_templates',
                template_type: HQTemplatesGrid.template_type,
                params: HQTemplatesGrid._api_params,
                trigger: trigger,
            };

            HQTTemplatesAPI._request(args, 'templates_listing');
        },

        _reinitGrid: function (event, data) {
            let template = wp.template('hqt-templates-list');

            $('body').removeClass('loading-content');

            $('#hqt-templates-results').show().html(template(data.results));

        },

    };

    // Run
    $(function () {
        HQTemplatesGrid.init();
    });

})(jQuery);
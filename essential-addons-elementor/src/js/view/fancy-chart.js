var ChartHandler = function ($scope, $) {
    let $fancy_chart_wrap = $('.eael_fanct_chart_wrapper', $scope),
        $data_options = $fancy_chart_wrap.data('options');
        
        //To format Y-axis values of tooltip
        if( ( "undefined" !== $data_options.tooltip.y.prefix ) || ( "undefined" !== $data_options.tooltip.y.suffix ) ) {
            $data_options.tooltip.y.formatter = function( val ) {
                return $data_options.tooltip.y.prefix + val + $data_options.tooltip.y.suffix;
            }
        }

    let eael_fancy_chart_id = $('.eael_fancy_chart', $scope).attr('id');
    if (undefined !== $data_options) {
        var chart = new ApexCharts(document.querySelector('#' + eael_fancy_chart_id), $data_options);
        chart.render();
    }
};

jQuery(window).on("elementor/frontend/init", function () {
    if (ea.elementStatusCheck('fancyChart')) {
        return false;
    }

    elementorFrontend.hooks.addAction(
        "frontend/element_ready/eael-fancy-chart.default",
        ChartHandler
    );
});
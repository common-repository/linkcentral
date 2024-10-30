import ApexCharts from 'apexcharts';

class TotalClicks {
    constructor() {
        this.chart = null;
        this.selectedLinkId = null;
        this.selectedLinkTitle = null;
        this.trackUniqueVisitors = linkcentral_insights_data.track_unique_visitors === '1';
    }

    init() {
        this.initChart();
        this.setupEventListeners();
        this.loadStats(7);
        this.updateAllLinksButtonState(true);
    }

    initChart() {
        const options = {
            chart: { 
                type: 'area',
                height: 300,
                toolbar: {
                    show: true
                },
                zoom: {
                    type: 'x',
                    enabled: false
                },
            },
            series: [
                {
                    name: 'Total Clicks',
                    data: []
                }
            ],
            xaxis: {
                type: 'datetime',
                tooltip: {
                    enabled: false
                },
                labels: {
                    datetimeUTC: false
                }
            },
            yaxis: {
                title: {
                    text: 'Clicks'
                },
                min: 0
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val;
                },
                style: {
                    fontSize: '11px',
                    fontWeight: 'bold'
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.5,
                    opacityTo: 0,
                    stops: [0, 90, 100]
                }
            },
            colors: ['#12668A'],
            tooltip: {
                x: {
                    format: 'dd MMM yyyy'
                }
            }
        };

        if (this.trackUniqueVisitors) {
            options.series.push({
                name: 'Unique Clicks',
                data: []
            });
            options.colors.push('rgb(255, 205, 86)');
        }

        this.chart = new ApexCharts(document.querySelector("#linkcentral-total-clicks-chart"), options);
        this.chart.render();
    }

    setupEventListeners() {
        // Event listener for timeframe change
        jQuery('#linkcentral-timeframe').on('change', (e) => this.handleTimeframeChange(e));
        // Event listener for custom date apply button
        jQuery('#linkcentral-apply-custom').on('click', () => this.handleCustomDateApply());
        
        // Update event listener for the "All Links" button
        jQuery('#linkcentral-all-links').on('click', (e) => {
            e.preventDefault();
            this.resetToAllLinks();
        });
        
        // Only set up autocomplete for premium users
        if (linkcentral_insights_data.can_use_premium_code__premium_only === '1') {
            jQuery('#linkcentral-link-search').autocomplete({
                source: this.searchLinks.bind(this),
                minLength: 2,
                select: (event, ui) => this.handleLinkSelect(event, ui)
            });
        }
    }

    searchLinks(request, response) {
        jQuery.ajax({
            url: linkcentral_admin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'linkcentral_insights_search_links',
                nonce: linkcentral_admin.nonce,
                search: request.term
            },
            success: (data) => {
                if (data.success) {
                    const links = data.data.map(item => ({
                        label: item.title + ' (' + item.slug + ')',
                        value: item.id,
                        title: item.title
                    }));
                    response(links);
                } else {
                    console.error('Error searching links:', data.data);
                    response([]);
                }
            },
            error: (jqXHR, textStatus, errorThrown) => {
                console.error('AJAX error:', textStatus, errorThrown);
                response([]);
            }
        });
    }

    handleLinkSelect(event, ui) {
        this.selectedLinkId = ui.item.value;
        this.selectedLinkTitle = ui.item.title;
        
        // Update the search input with the selected link title
        jQuery('#linkcentral-link-search').val(this.selectedLinkTitle);
        
        const timeframe = jQuery('#linkcentral-timeframe').val();
        this.loadSpecificLinkStats(this.selectedLinkId, timeframe);
        
        this.updateAllLinksButtonState(false);
        
        return false; // Prevent default behavior
    }

    resetToAllLinks() {
        this.selectedLinkId = null;
        this.selectedLinkTitle = null;
        jQuery('#linkcentral-link-search').val('');
        const timeframe = jQuery('#linkcentral-timeframe').val();
        this.loadStats(timeframe);
        this.updateAllLinksButtonState(true);
    }

    updateAllLinksButtonState(isSelected) {
        const $allLinksButton = jQuery('#linkcentral-all-links');
        if (isSelected) {
            $allLinksButton.addClass('selected');
        } else {
            $allLinksButton.removeClass('selected');
        }
    }

    handleTimeframeChange(e) {
        const value = e.target.value;
        if (value === 'custom') {
            jQuery('#linkcentral-custom-range').show();
        } else {
            jQuery('#linkcentral-custom-range').hide();
            if (this.selectedLinkId && this.selectedLinkId !== 'all') {
                this.loadSpecificLinkStats(this.selectedLinkId, value);
            } else {
                this.loadStats(value);
            }
        }
    }

    handleCustomDateApply() {
        const startDate = jQuery('#linkcentral-date-from').val();
        const endDate = jQuery('#linkcentral-date-to').val();
        if (startDate && endDate) {
            if (this.selectedLinkId && this.selectedLinkId !== 'all') {
                this.loadSpecificLinkStats(this.selectedLinkId, 'custom', startDate, endDate);
            } else {
                this.loadStats('custom', startDate, endDate);
            }
        }
    }

    loadStats(days, startDate = null, endDate = null) {
        jQuery.ajax({
            url: linkcentral_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'linkcentral_get_stats',
                nonce: linkcentral_admin.nonce,
                days: days,
                start_date: startDate,
                end_date: endDate
            },
            success: (response) => {
                if (response.success) {
                    this.updateChart(response.data);
                    this.updateAllLinksButtonState(true);
                }
            }
        });
    }

    loadSpecificLinkStats(linkId, days, startDate = null, endDate = null) {
        jQuery.ajax({
            url: linkcentral_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'linkcentral_get_specific_link_stats',
                nonce: linkcentral_admin.nonce,
                link_id: linkId,
                days: days,
                start_date: startDate,
                end_date: endDate
            },
            success: (response) => {
                if (response.success) {
                    this.updateChart(response.data);
                    this.updateAllLinksButtonState(false);
                }
            }
        });
    }

    updateChart(data) {
        if (!data || !data.labels || !data.clicks) {
            return;
        }

        const seriesData = data.labels.map((label, index) => {
            return [new Date(label).getTime(), data.clicks[index]];
        });

        const newSeries = [{
            name: 'Total Clicks',
            data: seriesData
        }];

        if (this.trackUniqueVisitors && data.unique_clicks) {
            const uniqueSeriesData = data.labels.map((label, index) => {
                return [new Date(label).getTime(), data.unique_clicks[index]];
            });
            newSeries.push({
                name: 'Unique Clicks',
                data: uniqueSeriesData
            });
        }

        this.chart.updateOptions({
            series: newSeries,
            xaxis: {
                type: 'datetime',
                categories: data.labels
            }
        });
    }
}

export default TotalClicks;
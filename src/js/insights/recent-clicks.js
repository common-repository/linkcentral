class RecentClicks {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.trackUserAgent = linkcentral_admin.track_user_agent; // Use the passed setting
    }

    init() {
        if (typeof linkcentral_initial_recent_clicks_data !== 'undefined') {
            this.updateRecentClicksTable(linkcentral_initial_recent_clicks_data);
            this.initializeRecentClicksPagination(linkcentral_initial_recent_clicks_data);
        } else {
            this.loadRecentClicks();
        }
        this.setupEventListeners();
    }

    setupEventListeners() {
        const $pagination = jQuery('#linkcentral-recent-clicks-table').next('.tablenav');
        // Set up pagination event listeners
        $pagination.on('click', '.first-page', (e) => this.handlePagination(e, 1));
        $pagination.on('click', '.prev-page', (e) => this.handlePagination(e, this.currentPage - 1));
        $pagination.on('click', '.next-page', (e) => this.handlePagination(e, this.currentPage + 1));
        $pagination.on('click', '.last-page', (e) => this.handlePagination(e, this.totalPages));
        // Set up event listener for page input
        jQuery('#recent-clicks-current-page').on('keydown', (e) => this.handlePageInput(e));
    }

    handlePagination(e, page) {
        e.preventDefault();
        this.loadRecentClicks(page); // Load clicks for the specified page
    }

    handlePageInput(e) {
        if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            const page = parseInt(e.target.value);
            if (page > 0 && page <= this.totalPages) {
                this.loadRecentClicks(page); // Load clicks for the input page
            }
        }
    }

    loadRecentClicks(page = 1) {
        jQuery.ajax({
            url: linkcentral_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'linkcentral_get_recent_clicks', // Updated action name
                nonce: linkcentral_admin.nonce,
                page: page
            },
            success: (response) => {
                if (response.success) {
                    this.updateRecentClicksTable(response.data); // Update table with new data
                }
            }
        });
    }

    updateRecentClicksTable(data) {
        const $table = jQuery('#linkcentral-recent-clicks-table tbody');
        $table.empty(); // Clear existing table rows

        data.clicks.forEach((click) => {
            let userAgentCell = '';
            if (this.trackUserAgent) {
                if (!click.user_agent_info.browser || !click.user_agent_info.device) {
                    userAgentCell = '<td class="column-user-agent">-</td>';
                } else {
                    userAgentCell = `
                        <td class="column-user-agent">
                            <span class="browser-icon browser-${click.user_agent_info.browser.toLowerCase()}" title="${click.user_agent_info.browser}"></span>
                            <span class="dashicons ${click.user_agent_info.device_icon}" title="${click.user_agent_info.device}"></span>
                            <span class="os-info">${click.user_agent_info.os}</span>
                        </td>
                    `;
                }
            }

            const deletedIndicator = click.is_deleted ? ' <span class="dashicons dashicons-no" title="This link has been deleted"></span>' : '';
            const trashedIndicator = click.is_trashed ? ' <span class="dashicons dashicons-trash" title="This link is in the trash"></span>' : '';
            const rowClass = click.is_deleted ? 'linkcentral-deleted-link' : (click.is_trashed ? 'linkcentral-trashed-link' : '');

            const postTitle = click.is_deleted ? 'Deleted Link' : 
                `<a href="${click.edit_link}">${click.post_title}</a>`;
            const slug = click.is_deleted ? '' : '/' + click.slug;
            const referringUrl = click.is_deleted ? '' : click.referring_url;
            const destinationUrl = click.destination_url;

            // Append new row to the table
            $table.append(`
                <tr class="${rowClass}">
                    <td class="column-title">${postTitle}${deletedIndicator}${trashedIndicator}</td>
                    <td class="column-slug">${slug}</td>
                    <td class="column-referring_url">${referringUrl}</td>
                    <td class="column-destination_url">${destinationUrl}</td>
                    ${this.trackUserAgent ? userAgentCell : ''}
                    <td class="column-timestamp">${click.formatted_date}</td>
                </tr>
            `);
        });

        this.updateRecentClicksPagination(data); // Update pagination after table update
    }

    updateRecentClicksPagination(data) {
        const $pagination = jQuery('#linkcentral-recent-clicks-table').next('.tablenav').find('.tablenav-pages');
        
        // Update pagination display
        $pagination.find('.total-pages').text(data.total_pages);
        $pagination.find('#recent-clicks-current-page').val(data.current_page);

        const startItem = (data.current_page - 1) * data.items_per_page + 1;
        const endItem = Math.min(data.current_page * data.items_per_page, data.total_items);
        $pagination.find('.displaying-num').text(`${startItem}-${endItem} of ${data.total_items} items`);

        // Enable/disable pagination buttons based on current page
        $pagination.find('.first-page, .prev-page').toggleClass('disabled', data.current_page <= 1);
        $pagination.find('.next-page, .last-page').toggleClass('disabled', data.current_page >= data.total_pages);

        this.currentPage = data.current_page;
        this.totalPages = data.total_pages;
    }

    initializeRecentClicksPagination(data) {
        this.updateRecentClicksPagination(data); // Initialize pagination with initial data
    }
}

export default RecentClicks;
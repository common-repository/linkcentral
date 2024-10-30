class MostPopularLinks {
    constructor() {
        // Initialize pagination variables
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalItems = 0;
        this.itemsPerPage = 10;
        this.trackUniqueVisitors = linkcentral_insights_data.track_unique_visitors === '1';
    }

    init() {
        // Load top links for the last 7 days by default
        this.loadTopLinks('7');
        // Set up event listeners for user interactions
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Set up event listeners for timeframe changes and pagination controls
        jQuery('#linkcentral-top-links-timeframe').on('change', (e) => this.handleTimeframeChange(e));
        jQuery('#linkcentral-top-links-table').next('.tablenav').on('click', '.first-page', (e) => this.handlePagination(e, 1));
        jQuery('#linkcentral-top-links-table').next('.tablenav').on('click', '.prev-page', (e) => this.handlePagination(e, this.currentPage - 1));
        jQuery('#linkcentral-top-links-table').next('.tablenav').on('click', '.next-page', (e) => this.handlePagination(e, this.currentPage + 1));
        jQuery('#linkcentral-top-links-table').next('.tablenav').on('click', '.last-page', (e) => this.handlePagination(e, this.totalPages));
        jQuery('#top-links-current-page').on('keydown', (e) => this.handlePageInput(e));
    }

    handleTimeframeChange(e) {
        // Load top links based on the selected timeframe
        this.loadTopLinks(e.target.value);
    }

    handlePagination(e, page) {
        e.preventDefault();
        // Validate the page number and load top links for the specified page
        if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
            this.loadTopLinks(jQuery('#linkcentral-top-links-timeframe').val(), page);
        }
    }

    handlePageInput(e) {
        if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            const page = parseInt(e.target.value);
            // Validate the page number and load top links for the specified page
            if (page > 0 && page <= this.totalPages) {
                this.loadTopLinks(jQuery('#linkcentral-top-links-timeframe').val(), page);
            }
        }
    }

    loadTopLinks(timeframe, page = 1) {
        // Make an AJAX request to fetch top links data
        jQuery.ajax({
            url: linkcentral_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'linkcentral_get_top_links',
                nonce: linkcentral_admin.nonce,
                timeframe: timeframe,
                page: page
            },
            success: (response) => {
                if (response.success) {
                    // Update the table with the fetched data
                    this.updateTopLinksTable(response.data);
                } else {
                    console.error('Error loading top links:', response.data);
                }
            },
            error: (jqXHR, textStatus, errorThrown) => {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });
    }

    updateTopLinksTable(data) {
        const $table = jQuery('#linkcentral-top-links-table tbody');
        $table.empty();

        data.links.forEach(link => {
            const rowClass = link.is_deleted ? 'linkcentral-deleted-link' : (link.is_trashed ? 'linkcentral-trashed-link' : '');
            const deletedIndicator = link.is_deleted ? ' <span class="dashicons dashicons-no" title="This link has been deleted"></span>' : '';
            const trashedIndicator = link.is_trashed ? ' <span class="dashicons dashicons-trash" title="This link is in the trash"></span>' : '';
            const dynamicIndicator = link.has_dynamic_rules ? ' <span class="dashicons dashicons-randomize" title="Dynamic redirects enabled"></span>' : '';

            let uniqueClicksColumn = '';
            if (this.trackUniqueVisitors) {
                uniqueClicksColumn = `<td class="column-unique-clicks">${link.unique_clicks}</td>`;
            }

            const row = `
                <tr class="${rowClass}">
                    <td class="column-title">
                        ${link.is_deleted ? 'Deleted Link' : `<a href="${link.edit_link}">${link.post_title}</a>`}
                        ${deletedIndicator}${trashedIndicator}
                    </td>
                    <td class="column-slug">${link.is_deleted ? '' : '/' + link.slug}</td>
                    <td class="column-destination_url">${link.is_deleted ? '' : link.destination_url}${dynamicIndicator}</td>
                    <td class="column-total-clicks">${link.total_clicks}</td>
                    ${uniqueClicksColumn}
                </tr>
            `;
            $table.append(row);
        });

        this.updatePagination(data);
    }

    updatePagination(data) {
        // Update pagination variables
        this.currentPage = data.current_page;
        this.totalPages = data.total_pages;
        this.totalItems = data.total_items;

        const $pagination = jQuery('#linkcentral-top-links-table').next('.tablenav').find('.tablenav-pages');
        const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
        const endItem = Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
        
        // Update pagination display
        $pagination.find('.displaying-num').text(this.totalItems > 0 ? `${this.totalItems} items` : 'No items');
        $pagination.find('.total-pages').text(this.totalPages);
        $pagination.find('#top-links-current-page').val(this.currentPage);
        $pagination.find('.tablenav-paging-text').text(` of ${this.totalPages}`);

        // Enable/disable pagination buttons based on current page
        $pagination.find('.first-page, .prev-page').toggleClass('disabled', this.currentPage === 1);
        $pagination.find('.last-page, .next-page').toggleClass('disabled', this.currentPage === this.totalPages);

        // Update the paging text
        if (this.totalItems > 0) {
            $pagination.find('.tablenav-paging-text').text(`${startItem}-${endItem} of ${this.totalItems}`);
        } else {
            $pagination.find('.tablenav-paging-text').text('0 items');
        }
    }
}

export default MostPopularLinks;
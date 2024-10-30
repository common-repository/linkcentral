if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded. LinkCentral may not work correctly.');
}

(function($) {
    'use strict';

    $(document).ready(function() {
        // Form validation for adding/editing links
        $('#post').on('submit', function(e) {
            var destinationUrl = $('#linkcentral_destination_url').val();
            var customSlug = $('#post_name').val(); // Updated to use post_name

            if (!destinationUrl || !customSlug) {
                e.preventDefault();
                alert(linkcentral_admin.required_fields_message);
                return false;
            }

            if (!isValidUrl(destinationUrl)) {
                e.preventDefault();
                alert(linkcentral_admin.invalid_url_message);
                return false;
            }
        });

        // Custom URL slug generator with AJAX check
        $('#title').on('blur', function() {
            var title = $(this).val();
            if (title && !$('#post_name').val()) {
                var slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                checkSlug(slug);
            }
        });

        // Check slug availability when custom slug field changes or loses focus
        $('#post_name').on('blur change', function() {
            var slug = $(this).val();
            if (slug) {
                checkSlug(slug);
            }
        });

        function checkSlug(slug) {
            var postId = $('#post_ID').val() || 0;
            $.ajax({
                url: linkcentral_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'linkcentral_check_slug',
                    nonce: linkcentral_admin.nonce,
                    slug: slug,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        $('#post_name').val(response.data.unique_slug);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error checking slug.');
                }
            });
        }

        // Copy short URL to clipboard
        $('.linkcentral-copy-url').on('click', function(e) {
            e.preventDefault();
            var shortUrl = $(this).data('url');
            copyToClipboard(shortUrl);
            updateButtonText($(this), linkcentral_admin.copied_message, linkcentral_admin.copy_message);
        });

        // Remove 'Pending Review' option
        if (typeof linkcentral_post_type !== 'undefined' && linkcentral_post_type == 'linkcentral_link') {
            // Remove 'Pending Review' from status dropdown
            $('#post-status-select option[value="pending"]').remove();
        }

        // Copy URL functionality
        $('#linkcentral-copy-url').on('click', function() {
            var urlPrefix = $('#linkcentral-url-prefix').text().trim();
            var slug = $('#post_name').val();
            var fullUrl = urlPrefix + slug;

            copyToClipboard(fullUrl);
            updateButtonText($(this), linkcentral_admin.copied_message);
        });

        // Copy shortcode functionality (if it exists in HTML)
        $('.linkcentral-copy-shortcode').on('click', function(e) {
            e.preventDefault();
            var shortcode = $(this).data('shortcode');
            copyToClipboard(shortcode);
            updateButtonText($(this), linkcentral_admin.copied_message, linkcentral_admin.copy_shortcode_message);
        });

        // Toggle CSS classes visibility
        if(linkcentral_admin.can_use_premium_code__premium_only){
            $('#linkcentral_css_classes_option').on('change', function() {
                if ($(this).val() === 'default') {
                    $('#linkcentral_custom_css_classes').hide();
                } else {
                    $('#linkcentral_custom_css_classes').show();
                }
            });
        }

        // Administrative note functionality
        $('.linkcentral-edit-note').on('click', function(e) {
            e.preventDefault();
            $('.linkcentral-note-display').hide();
            $('.linkcentral-note-edit').show();
        });

        $('.linkcentral-cancel-edit').on('click', function() {
            $('.linkcentral-note-edit').hide();
            $('.linkcentral-note-display').show();
        });

        $('.linkcentral-save-note').on('click', function() {
            var newNote = $('#linkcentral_note').val();
            $('.linkcentral-note-text').text(newNote);
            $('.linkcentral-note-edit').hide();
            $('.linkcentral-note-display').show();
        });
    });

    // Helper function to validate URLs
    function isValidUrl(url) {
        var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
            '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
            '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
            '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
        return !!pattern.test(url);
    }

    // Helper function to copy text to clipboard
    function copyToClipboard(text) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(text).select();
        document.execCommand("copy");
        $temp.remove();
    }

    // Helper function to update button text temporarily
    function updateButtonText($button, tempText, originalText) {
        var _originalText = originalText || $button.text();
        $button.text(tempText);
        setTimeout(function() {
            $button.text(_originalText);
        }, 2000);
    }

})(jQuery);
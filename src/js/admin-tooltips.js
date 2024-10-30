(function($) {
    $(document).ready(function() {
        // Iterate over each element with the class 'linkcentral-info-icon'
        $('.linkcentral-info-icon').each(function() {
            var $icon = $(this);
            var tooltipContent = $icon.attr('data-tooltip');
            // Create a tooltip element and hide it initially
            var $tooltip = $('<div class="linkcentral-tooltip">' + tooltipContent + '</div>').hide();
            
            // Append the tooltip to the body
            $('body').append($tooltip);

            // Function to show the tooltip
            var showTooltip = function() {
                var iconPos = $icon.offset();
                var iconWidth = $icon.outerWidth();
                var tooltipWidth = $tooltip.outerWidth();
                // Position the tooltip below the icon and center it
                $tooltip.css({
                    top: iconPos.top + $icon.outerHeight() + 10,
                    left: iconPos.left - (tooltipWidth * 0.75) + (iconWidth / 2)
                }).fadeIn(200); // Fade in the tooltip
            };

            // Function to hide the tooltip
            var hideTooltip = function() {
                $tooltip.fadeOut(200); // Fade out the tooltip
            };

            // Show the tooltip on mouse enter
            $icon.on('mouseenter', showTooltip);
            // Hide the tooltip on mouse leave, with a delay to allow for hover over the tooltip
            $icon.on('mouseleave', function() {
                setTimeout(function() {
                    if (!$tooltip.is(':hover')) {
                        hideTooltip();
                    }
                }, 100);
            });

            // Prevent the tooltip from hiding when hovered over
            $tooltip.on('mouseenter', function() {
                clearTimeout(hideTooltip);
            });

            // Hide the tooltip when the mouse leaves the tooltip
            $tooltip.on('mouseleave', hideTooltip);
        });
    });
})(jQuery);
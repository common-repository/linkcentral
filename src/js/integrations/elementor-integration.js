/**
 * Handles text selection in the Elementor inline editor.
 * 
 * This function performs the following tasks:
 * 1. Checks if there's a valid text selection.
 * 2. Determines if the selection is within a LinkCentral link.
 * 3. Controls the visibility of the Elementor editor toolbar:
 *    - Hides the toolbar if the selection is within a LinkCentral link, and shows a tooltip.
 *    - Shows the toolbar if the selection is not within a LinkCentral link, and hides the tooltip.
 * 
 * The function is triggered on text selection changes.
 */

document.addEventListener('DOMContentLoaded', function() {
    let activeTooltip = null;

    function handleSelection() {
        const selection = window.getSelection();
        if (selection.rangeCount === 0) return;

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        const linkElement = container.nodeType === Node.ELEMENT_NODE ? container.closest('a[data-linkcentral-id-sync]') : container.parentElement.closest('a[data-linkcentral-id-sync]');

        const toolbar = document.querySelector('.elementor-editor-active .pen-menu');
        if (toolbar) {
            const editLinkButton = toolbar.querySelector('.pen-icon[data-action="createlink"]');
            if (editLinkButton) {
                if (linkElement) {
                    // Hide the toolbar
                    toolbar.style.display = 'none';

                    // Get the position of the link element
                    const rect = linkElement.getBoundingClientRect();
                    const position = `${rect.top},${rect.left}`;

                    // Show or update tooltip when not active or position of linkElement is different
                    if (!activeTooltip || 
                        activeTooltip.getAttribute('data-linkcentral-position') !== position) {
                        showTooltip(linkElement, position);
                    }
                } else {
                    // Hide tooltip
                    hideTooltip();
                }
            }
        }
    }

    function showTooltip(element, position) {
        hideTooltip(); // Ensure any existing tooltip is removed

        const linkId = element.getAttribute('data-linkcentral-id-sync');

        const tooltip = document.createElement('div');
        tooltip.className = 'linkcentral-elementor-tooltip';
        tooltip.setAttribute('data-linkcentral-position', position);
        tooltip.innerHTML = `
            <img src="${linkcentral_data.plugin_url}assets/images/linkcentral-logo.svg" alt="LinkCentral Logo" class="linkcentral-logo">
            <div class="linkcentral-tooltip-content">
                <strong>LinkCentral - synchronized with ID: ${linkId}</strong>
                Please use the Text Editor for editing.
            </div>
        `;
        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        tooltip.style.top = `${rect.bottom + window.scrollY + 10}px`;
        tooltip.style.left = `${rect.left + window.scrollX + (rect.width / 2) - (tooltipRect.width / 2)}px`;

        // Add fade-in effect
        setTimeout(() => tooltip.style.opacity = '1', 10);

        activeTooltip = tooltip;
    }

    function hideTooltip() {
        if (activeTooltip) {
            activeTooltip.remove();
            activeTooltip = null;
        }
    }

    // Add event listener for selection changes
    document.addEventListener('selectionchange', handleSelection);
    document.addEventListener('focusout', hideTooltip);

    // Add necessary styles
    const style = document.createElement('style');
    style.textContent = `
        .linkcentral-elementor-tooltip {
            position: absolute;
            background-color: #f6f7f7;
            color: #595959;
            padding: 10px;
            border: 1px solid #a7aaad;
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .15);
            font-size: 14px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            display: flex;
            align-items: flex-start;
            font-family: var(--e-global-typography-text-font-family), Sans-serif;
        }
        .linkcentral-elementor-tooltip .linkcentral-logo {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            margin-top: 3px;
        }
        .linkcentral-tooltip-content {
            display: flex;
            flex-direction: column;
        }
    `;
    document.head.appendChild(style);
});
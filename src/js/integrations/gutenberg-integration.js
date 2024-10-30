/**
 * LinkCentral Gutenberg Integration
 * 
 * This file integrates LinkCentral functionality into the Gutenberg editor,
 * allowing users to easily insert and manage LinkCentral links within text and button blocks.
 */

// Get the logo URL from the global data object
const logoUrl = linkcentral_gutenberg_data.plugin_url + 'assets/images/linkcentral-logo.svg';

// Preload the logo image for faster rendering
const preloadedLogo = new Image();
preloadedLogo.src = logoUrl;

(function(wp) {
    // Destructure necessary WordPress components for easier access
    const {
        richText: { registerFormatType, applyFormat, removeFormat },
        element: { createElement: el, useState, useEffect, createPortal, useRef, useCallback },
        i18n: { __ },
        components: { Modal, Spinner, Button, TextControl, ToggleControl },
        data: { useSelect },
        blockEditor: { URLPopover, BlockControls }
    } = wp;

    /**
     * Utility function to get the document from the editor, whether inside an iframe or not
     * This is necessary because the editor might be rendered in an iframe in some setups
     */
    function getEditorDocument() {
        const iframe = document.querySelector('iframe[name="editor-canvas"]');
        return iframe ? iframe.contentDocument || iframe.contentWindow.document : document;
    }

    /**
     * LinkCentralButton Component
     * 
     * This is the main component that handles the LinkCentral functionality in the editor.
     * It manages the state for link selection, editing, and insertion.
     */
    const LinkCentralButton = function(props) {
        // State variables for managing the component's behavior
        const [isOpen, setIsOpen] = useState(false);
        const [searchTerm, setSearchTerm] = useState('');
        const [searchResults, setSearchResults] = useState([]);
        const [newTab, setNewTab] = useState(false);
        const [selectedLink, setSelectedLink] = useState(null);
        const [showDropdown, setShowDropdown] = useState(false);
        const [isPopoverVisible, setIsPopoverVisible] = useState(false);
        const [popoverAnchor, setPopoverAnchor] = useState(null);
        const [linkData, setLinkData] = useState(null);
        const [linkId, setLinkId] = useState(null);
        const [dropdownPosition, setDropdownPosition] = useState({ top: 0, left: 0 });
        const [isModalOpen, setIsModalOpen] = useState(false);
        const [editingLink, setEditingLink] = useState(null);
        const [editingLinkNewTab, setEditingLinkNewTab] = useState(false);
        const [isGettingData, setIsGettingData] = useState(false);
        const [parameters, setParameters] = useState('');
        const [showMoreOptions, setShowMoreOptions] = useState(false);
        const [linkInsertionType, setLinkInsertionType] = useState(() => {
            // Determine the default link insertion type based on global settings and block type
            const defaultType = linkcentral_gutenberg_data.default_link_insertion_type || 'synchronized';
            return (props.name === 'core/button' && defaultType === 'shortcode') ? 'synchronized' : defaultType;
        });
        const [isSearching, setIsSearching] = useState(false);
        const [isLinkSettingsOpen, setIsLinkSettingsOpen] = useState(false);
        const linkSettingsRef = useRef(null);
        const searchInputRef = useRef(null);

        // State to track which popover is open
        const [openPopover, setOpenPopover] = useState(null);

        // Effect to handle clicks outside of open popovers
        useEffect(() => {
            function handleClickOutside(event) {
                if (openPopover && !event.target.closest(`.${openPopover}`)) {
                    setOpenPopover(null);
                }
            }

            document.addEventListener("mousedown", handleClickOutside);
            return () => {
                document.removeEventListener("mousedown", handleClickOutside);
            };
        }, [openPopover]);

        // Function to toggle popovers
        const togglePopover = (popoverName) => {
            setOpenPopover((prev) => (prev === popoverName ? null : popoverName));
        };

        // Effect to update dropdown position when modal or dropdown visibility changes
        useEffect(() => {
            if (isModalOpen && showDropdown) {
                updateDropdownPosition();
                window.addEventListener('resize', updateDropdownPosition);
                return () => window.removeEventListener('resize', updateDropdownPosition);
            }
        }, [isModalOpen, showDropdown, updateDropdownPosition]);

        // Function to update the position of the search results dropdown
        const updateDropdownPosition = useCallback(() => {
            if (searchInputRef.current) {
                const rect = searchInputRef.current.getBoundingClientRect();
                setDropdownPosition({
                    top: rect.bottom + window.scrollY,
                    left: rect.left + window.scrollX
                });
            }
        }, []);

        // Function to handle opening and closing the modal
        const onToggle = () => {
            const newIsOpen = !isOpen;
            setIsOpen(newIsOpen);
            setIsModalOpen(newIsOpen);
            if (newIsOpen && props.isActive) {
                // If we're opening the modal and there's an active link, fetch its data
                let linkId;
                if (props.name === 'core/button') {
                    linkId = props.attributes['data-linkcentral-id-sync'];
                } else {
                    linkId = props.value.activeFormats?.find(format => format.type === 'linkcentral/link')?.attributes['data-linkcentral-id-sync'];
                }
                if (linkId) {
                    fetchLinkData(linkId);
                }
            } else if (!newIsOpen) {
                // Reset state when closing the modal
                setShowDropdown(false);
                setSearchTerm('');
                setSearchResults([]);
                setEditingLink(null);
                setEditingLinkNewTab(false);
                setParameters('');
            }
        };

        // Function to search for links based on user input
        const searchLinks = (term) => {
            if (term.length >= 2) {
                setIsSearching(true);
                wp.apiFetch({
                    path: `/wp/v2/linkcentral_link?search=${encodeURIComponent(term)}&status=publish`,
                }).then((links) => {
                    if (isModalOpen) {
                        setSearchResults(links);
                        setShowDropdown(true);
                        updateDropdownPosition();
                    }
                }).finally(() => {
                    setIsSearching(false);
                });
            } else {
                setSearchResults([]);
                setShowDropdown(false);
            }
        };

        // Function to handle link selection from search results
        const selectLink = (link) => {
            setSelectedLink(link);
            setSearchTerm(link.title.rendered);
            setShowDropdown(false);
        };

        // Function to fetch link data by ID
        const fetchLinkData = useCallback((linkId) => {
            setLinkId(linkId);
            setLinkData(null);
            setEditingLink(null);
            setEditingLinkNewTab(false);
            setSearchTerm('');
            setIsGettingData(true);
            setParameters('');
            
            wp.apiFetch({ path: `/wp/v2/linkcentral_link/${linkId}` })
                .then((response) => {
                    if (response.status === 'publish') {
                        setLinkData(response);
                        setEditingLink(response);
                        setSearchTerm(response.title.rendered);
                        setSelectedLink(response);
                        // Check if the link has a target="_blank" or parameters attribute
                        let isNewTab = false;
                        if (props.name === 'core/button') {
                            isNewTab = props.attributes.target === '_blank';
                            setParameters(props.attributes['data-linkcentral-parameters'] || '');
                        } else {
                            const format = props.value.activeFormats ? 
                                props.value.activeFormats.find(f => f.type === 'linkcentral/link') : 
                                undefined;
                            isNewTab = format?.attributes?.target === '_blank';
                            setParameters(format?.attributes?.['data-linkcentral-parameters'] || '');
                        }
                        setEditingLinkNewTab(isNewTab);
                        setNewTab(isNewTab);
                    } else {
                        console.error('Link is not published:', response);
                        setLinkData({ error: true });
                    }
                })
                .catch((error) => {
                    console.error('Error fetching link data:', error);
                    setLinkData({ error: true });
                })
                .finally(() => {
                    setIsGettingData(false);
                });
        }, [props.name, props.attributes, props.value]);

        // Effect to handle click events and popover visibility
        useEffect(() => {
            const handleClick = (event) => {
                const editorDocument = getEditorDocument();
                const link = event.target.closest('a[data-linkcentral-id-sync]');
                const popover = event.target.closest('.linkcentral-popover');

                if (link) {
                    event.preventDefault();
                    const newLinkId = link.getAttribute('data-linkcentral-id-sync');
                    
                    // Always update the popover state and fetch new data when clicking a link
                    setPopoverAnchor(link);
                    setIsPopoverVisible(true);
                    fetchLinkData(newLinkId);
                } else if (!popover && isPopoverVisible) {
                    // Close the popover if clicking outside
                    resetPopoverState();
                }

                // Check if the clicked element is a button and initialize the popover
                if (props.name === 'core/button') {
                    const buttonElement = editorDocument.querySelector('.wp-block-button__link');
                    if (buttonElement && props.attributes['data-linkcentral-id-sync']) {
                        setPopoverAnchor(buttonElement);
                        setIsPopoverVisible(true);
                        fetchLinkData(props.attributes['data-linkcentral-id-sync']);
                    }
                }
            };

            const resetPopoverState = () => {
                setIsPopoverVisible(false);
                setPopoverAnchor(null);
                setSelectedLink(null);
                setLinkData(null);
                setLinkId(null);
                setEditingLink(null);
                setEditingLinkNewTab(false);
                setSearchTerm('');
                setParameters('');
            };

            const attachEventListener = () => {
                const currentDocument = getEditorDocument();
                currentDocument.addEventListener('click', handleClick, true);
            };

            const detachEventListener = () => {
                const currentDocument = getEditorDocument();
                currentDocument.removeEventListener('click', handleClick, true);
            };

            if (props.isActive && !isOpen) {
                attachEventListener();
            } else {
                resetPopoverState();
                detachEventListener();
            }

            return () => detachEventListener();
        }, [props.isActive, props.name, props.attributes, isOpen, fetchLinkData]);

        // Function to apply the selected link to the editor content
        const applyLink = () => {
            if (!selectedLink) return;

            const id = selectedLink.id;
            if (!id) {
                console.error('ID is undefined. Full selected link object:', selectedLink);
                return;
            }

            const currentBlock = wp.data.select('core/block-editor').getSelectedBlock();
            const isButtonBlock = currentBlock && currentBlock.name === 'core/button';
            const linkText = selectedLink.title.rendered;

            const linkContent = (() => {
                switch (linkInsertionType) {
                    case 'synchronized':
                        return createSynchronizedLink(id, linkText);
                    case 'direct':
                        const content = createDirectLink(id, linkText);
                        const relAttributes = [
                            selectedLink.nofollow === 'yes' ? 'nofollow' : (selectedLink.nofollow === 'default' ? (selectedLink.global_nofollow ? 'nofollow' : '') : ''),
                            selectedLink.sponsored === 'yes' ? 'sponsored' : (selectedLink.sponsored === 'default' ? (selectedLink.global_sponsored ? 'sponsored' : '') : '')
                        ].filter(Boolean).join(' ');

                        if (relAttributes) content.rel = relAttributes;

                        if (!isButtonBlock) {
                            const cssClasses = selectedLink.css_classes_option === 'replace'
                                ? selectedLink.custom_css_classes
                                : `${selectedLink.global_css_classes || ''} ${selectedLink.custom_css_classes || ''}`.trim();
                            if (cssClasses) content.className = cssClasses;
                        }
                        return content;
                    case 'shortcode':
                        return createShortcode(id);
                }
            })();

            if (isButtonBlock) {
                wp.data.dispatch('core/block-editor').updateBlockAttributes(currentBlock.clientId, {
                    url: null,
                    'data-linkcentral-id-sync': null,
                    'data-linkcentral-parameters': null,
                    className: null,
                    target: null,
                    rel: null,
                    ...linkContent,
                    text: currentBlock.attributes.text || linkText
                });
            } else {
                let newValue = props.value;
                let { start, end } = newValue;

                const activeFormat = wp.richText.getActiveFormat(newValue, 'core/link') || wp.richText.getActiveFormat(newValue, 'linkcentral/link');
                if (activeFormat && start === end) {
                    while (start > 0 && newValue.formats[start - 1]?.some(f => f.type === activeFormat.type)) start--;
                    while (end < newValue.formats.length && newValue.formats[end]?.some(f => f.type === activeFormat.type)) end++;
                }

                newValue = wp.richText.removeFormat(newValue, 'linkcentral/link', start, end);
                newValue = wp.richText.removeFormat(newValue, 'core/link', start, end);

                if (start === end) {
                    newValue = wp.richText.insert(newValue, linkText, start);
                    end = start + linkText.length;
                }

                if (linkInsertionType === 'shortcode') {
                    const selectedText = newValue.text.slice(start, end);
                    newValue = wp.richText.insert(newValue, `${linkContent}${selectedText}[/linkcentral]`, start, end);
                } else {
                    newValue = wp.richText.applyFormat(newValue, { type: linkInsertionType === 'synchronized' ? 'linkcentral/link' : 'core/link', attributes: linkContent }, start, end);
                }

                props.onChange(newValue);
            }

            resetState();
            if (linkInsertionType === 'direct' || linkInsertionType === 'shortcode') document.activeElement.blur();
        };

        // Helper function to create a synchronized link
        const createSynchronizedLink = (id, linkText) => {
            return {
                url: '#linkcentral',
                'data-linkcentral-id-sync': id.toString(),
                ...(editingLink ? (editingLinkNewTab && { target: '_blank', rel: 'noopener noreferrer' }) : (newTab && { target: '_blank', rel: 'noopener noreferrer' })),
                ...(parameters && { 'data-linkcentral-parameters': parameters })
            };
        };

        // Helper function to create a direct link
        const createDirectLink = (id, linkText) => {
            const slug = selectedLink.slug;
            let url = `${linkcentral_gutenberg_data.site_url}/${linkcentral_gutenberg_data.url_prefix}/${slug}`;
            
            // Append parameters to the URL for direct links
            if (parameters) {
                url += url.includes('?') ? '&' : '?';
                url += parameters;
            }

            return {
                url: url,
                ...(editingLink ? (editingLinkNewTab && { target: '_blank', rel: 'noopener noreferrer' }) : (newTab && { target: '_blank', rel: 'noopener noreferrer' }))
            };
        };

        // Helper function to create a shortcode
        const createShortcode = (id) => {
            let shortcode = `[linkcentral id="${id}"`;

            if (editingLink ? editingLinkNewTab : newTab) {
                shortcode += ` newtab="true"`;
            }
            if (parameters) {
                shortcode += ` parameters="${parameters}"`;
            }

            shortcode += `]`;
            return shortcode;
        };

        // Function to remove the link
        const removeLink = () => {
            const currentBlock = wp.data.select('core/block-editor').getSelectedBlock();

            if (currentBlock && currentBlock.name === 'core/button') {
                // Clear attributes for the button block
                wp.data.dispatch('core/block-editor').updateBlockAttributes(currentBlock.clientId, {
                    url: null,
                    'data-linkcentral-id-sync': null
                });
            } else {
                // For non-button blocks, remove the format
                let newValue = wp.richText.removeFormat(props.value, 'linkcentral/link', props.value.start, props.value.end);
                props.onChange(newValue);
            }

            // Reset state
            resetState();
        };

        // Helper function to reset state
        const resetState = () => {
            setSelectedLink(null);
            setIsOpen(false);
            setIsPopoverVisible(false);
            setPopoverAnchor(null);
            setLinkData(null);
            setLinkId(null);
        };

        // Function to render the search results dropdown
        const renderDropdown = () => {
            if (!showDropdown || searchResults.length === 0) return null;

            return createPortal(
                el('ul', {
                    style: {
                        position: 'absolute',
                        top: `${dropdownPosition.top}px`,
                        left: `${dropdownPosition.left}px`,
                        zIndex: 9999999,
                        backgroundColor: 'white',
                        border: '1px solid #ccc',
                        maxHeight: '200px',
                        overflowY: 'auto',
                        width: '300px',
                        listStyle: 'none',
                        padding: '5px',
                        margin: '0',
                        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
                    }
                },
                searchResults.map((link) => 
                    el('li', {
                        key: link.id,
                        style: { 
                            cursor: 'pointer', 
                            padding: '5px', 
                            backgroundColor: selectedLink === link ? '#e0e0e0' : 'transparent' 
                        },
                        onClick: (e) => {
                            e.stopPropagation();
                            selectLink(link);
                        }
                    },
                    el('strong', null, link.title.rendered), ' (', link.slug, ')')
                ))
            , document.body);
        };

        // Function to render the link insertion type popover
        const renderLinkInsertionTypePopover = () => {
            if (openPopover !== 'linkcentral-insert-options-popover') return null;

            const buttonElement = document.querySelector('.linkcentral-insert-options-popover');
            if (!buttonElement) return null;

            const rect = buttonElement.getBoundingClientRect();
            const popoverStyle = {
                position: 'absolute',
                backgroundColor: '#fff',
                border: '1px solid #ccd0d4',
                borderRadius: '4px',
                padding: '8px 8px 0 8px',
                zIndex: 100000,
                boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)',
                width: '220px',
                top: `${rect.bottom + window.scrollY}px`,
                left: `${rect.left + window.scrollX}px`,
            };

            return createPortal(
                el('div', { 
                    style: popoverStyle,
                    className: 'linkcentral-insert-options-popover',
                    onClick: (event) => event.stopPropagation()
                },
                    el('div', { 
                        style: { 
                            display: 'flex', 
                            justifyContent: 'space-between', 
                            alignItems: 'center', 
                            marginBottom: '8px',
                            borderBottom: '1px solid #ccd0d4',
                            paddingBottom: '8px'
                        } 
                    },
                        el('strong', null, __('Select link type', 'linkcentral')),
                        el('a', {
                            href: 'https://designforwp.com/docs/linkcentral/creating-and-using-links/inserting-links-into-your-content/',
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            style: { 
                                fontSize: '12px', 
                                textDecoration: 'none',
                                color: '#007cba'
                            }
                        }, __('What is this?', 'linkcentral'))
                    ),
                    el('ul', { style: { listStyle: 'none', padding: 0, margin: 0 } },
                        ['synchronized', 'direct', 'shortcode'].map(type => (
                            el('li', {
                                key: type,
                                onClick: () => {
                                    if (type !== 'shortcode' || props.name !== 'core/button') {
                                        setLinkInsertionType(type);
                                        setOpenPopover(null);
                                    }
                                },
                                style: {
                                    cursor: type === 'shortcode' && props.name === 'core/button' ? 'not-allowed' : 'pointer',
                                    padding: '8px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                    backgroundColor: linkInsertionType === type ? '#f0f0f0' : 'transparent',
                                    opacity: type === 'shortcode' && props.name === 'core/button' ? 0.5 : 1,
                                    transition: 'background-color 0.3s', // Smooth transition for hover effect
                                },
                                onMouseEnter: (e) => {
                                    if (type !== linkInsertionType) {
                                        e.currentTarget.style.backgroundColor = '#e8e8e8'; // Change to a slightly different color on hover
                                    }
                                },
                                onMouseLeave: (e) => {
                                    if (type !== linkInsertionType) {
                                        e.currentTarget.style.backgroundColor = 'transparent'; // Revert to original color
                                    }
                                }
                            },
                            el('div', { style: { display: 'flex', alignItems: 'center' } },
                                el('span', { 
                                    className: `dashicons ${
                                        type === 'synchronized' ? 'dashicons-update-alt' :
                                        type === 'direct' ? 'dashicons-admin-links' :
                                        'dashicons-shortcode'
                                    }`, 
                                    style: { fontSize: '20px', marginRight: '8px' } 
                                }),
                                el('span', null, __(type.charAt(0).toUpperCase() + type.slice(1), 'linkcentral'))
                            ),
                            el('span', { style: { fontSize: '10px', color: '#888' } },
                                type === 'shortcode' && props.name === 'core/button' ? 
                                    __('Unavailable', 'linkcentral') :
                                    type === linkcentral_gutenberg_data.default_link_insertion_type && __('Default', 'linkcentral')
                            )
                            )
                        ))
                    )
                ),
                document.body
            );
        };

        // Function to render the link settings popover
        const renderLinkSettingsPopover = () => {
            if (openPopover !== 'link-settings-popover') return null;

            const buttonElement = document.querySelector('.link-settings-popover');
            if (!buttonElement) return null;

            const rect = buttonElement.getBoundingClientRect();
            const popoverStyle = {
                position: 'absolute',
                backgroundColor: 'white',
                border: '1px solid #ccc',
                borderRadius: '4px',
                padding: '16px',
                width: '200px',
                zIndex: 100000,
                boxShadow: '0 2px 5px rgba(0, 0, 0, 0.2)',
                top: `${rect.bottom + window.scrollY}px`,
                right: `${window.innerWidth - rect.right}px`, // Align right side with the button
            };

            return createPortal(
                el('div', { 
                    style: popoverStyle,
                    className: 'link-settings-popover',
                    onClick: (event) => event.stopPropagation()
                },
                    el(ToggleControl, {
                        label: __('Open in New Tab', 'linkcentral'),
                        checked: editingLink ? editingLinkNewTab : newTab,
                        onChange: (value) => {
                            if (editingLink) {
                                setEditingLinkNewTab(value);
                            } else {
                                setNewTab(value);
                            }
                        },
                    }),
                    el('div', {
                        style: {
                            borderTop: '1px solid #ccc',
                            margin: '10px 0'
                        }
                    }),
                    // More Options content
                    linkcentral_gutenberg_data.can_use_premium_code__premium_only ?
                        el(TextControl, {
                            label: __('Parameters', 'linkcentral'),
                            value: parameters,
                            onChange: (value) => setParameters(value),
                            placeholder: __('e.g., param1=value1&param2=value2', 'linkcentral'),
                            help: __('These will be appended to the link.', 'linkcentral')
                        })
                    :
                        el('div', { className: 'linkcentral-premium-notice' },
                            el('p', { style: { marginBottom: 0 } },
                                __('Unlock more options with ', 'linkcentral'),
                                el('a', {
                                    href: 'admin.php?page=linkcentral-settings#premium',
                                    target: '_blank',
                                    style: { textDecoration: 'underline' }
                                }, __('LinkCentral Premium', 'linkcentral'))
                            )
                        )
                ),
                document.body
            );
        };

        // Effect to handle popover positioning with window resize
        useEffect(() => {
            const handleResize = () => {
                if (openPopover) {
                    // Force a re-render to update the popover position
                    setOpenPopover(null);
                    setTimeout(() => setOpenPopover(openPopover), 0);
                }
            };

            window.addEventListener('resize', handleResize);
            return () => {
                window.removeEventListener('resize', handleResize);
            };
        }, [openPopover]);

        // Render the LinkCentralButton component
        return el(
            'div',
            null,
            // Toolbar button
            el(
                BlockControls,
                null,
                el(
                    wp.components.ToolbarButton,
                    {
                        icon: el('div', 
                            { 
                                style: { 
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px',
                                    padding: '0 8px'
                                }
                            },
                            el('img', {
                                src: logoUrl,
                                alt: 'LinkCentral',
                                width: 20,
                                height: 20,
                                style: { display: 'block' }
                            }),
                            props.isActive && el('span', {}, __('Edit', 'linkcentral'))
                        ),
                        title: __('LinkCentral', 'linkcentral'),
                        onClick: onToggle,
                        isActive: props.isActive,
                    }
                )
            ),
            // Modal for link selection
            isOpen && el(
                Modal,
                {
                    title: editingLink ? __('Edit LinkCentral Link', 'linkcentral') : __('Select LinkCentral Link', 'linkcentral'),
                    onRequestClose: () => {
                        setIsOpen(false);
                        setIsModalOpen(false);
                        setShowDropdown(false);
                        setSearchTerm('');
                        setSearchResults([]);
                        setEditingLink(null);
                        setEditingLinkNewTab(false);
                        setParameters('');
                        setIsGettingData(false);
                        setLinkInsertionType('synchronized');
                    },
                    style: { minWidth: '420px' }
                },
                el('div', null,
                    isGettingData ? 
                        // Show loading indicator for data fetching
                        el('div', { style: { textAlign: 'center', padding: '20px' } },
                            el(Spinner),
                            el('p', null, __('Loading link data...', 'linkcentral'))
                        ) 
                    :
                        // Show regular modal content when not loading
                        el(wp.element.Fragment, null,
                            el('div', { style: { position: 'relative', display: 'flex', alignItems: 'flex-end' } },
                                el('div', { style: { flex: 1, marginRight: '8px' } },
                                    el(TextControl, {
                                        label: __('Search for a link', 'linkcentral'),
                                        value: searchTerm,
                                        onChange: (term) => {
                                            setSearchTerm(term);
                                            searchLinks(term);
                                        },
                                        className: 'linkcentral-search-input',
                                        ref: searchInputRef,
                                        style: { 
                                            fontSize: '16px',
                                            minHeight: '40px'
                                        }
                                    })
                                ),
                                isSearching && el(Spinner, { 
                                    style: { 
                                        position: 'absolute', 
                                        right: '48px', 
                                        top: '50%', 
                                        transform: 'translateY(-50%)' 
                                    } 
                                }),
                                el('div', { ref: linkSettingsRef, style: { position: 'relative' } },
                                    el(Button, {
                                        icon: el('svg', {
                                            xmlns: "http://www.w3.org/2000/svg",
                                            viewBox: "0 0 24 24",
                                            width: 24,
                                            height: 24
                                        },
                                        el('path', { d: "m19 7.5h-7.628c-.3089-.87389-1.1423-1.5-2.122-1.5-.97966 0-1.81309.62611-2.12197 1.5h-2.12803v1.5h2.12803c.30888.87389 1.14231 1.5 2.12197 1.5.9797 0 1.8131-.62611 2.122-1.5h7.628z" }),
                                        el('path', { d: "m19 15h-2.128c-.3089-.8739-1.1423-1.5-2.122-1.5s-1.8131.6261-2.122 1.5h-7.628v1.5h7.628c.3089.8739 1.1423 1.5 2.122 1.5s1.8131-.6261 2.122-1.5h2.128z" })
                                        ),
                                        label: __('Link Settings', 'linkcentral'),
                                        onClick: () => togglePopover('link-settings-popover'),
                                        className: 'link-settings-popover',
                                        style: { marginBottom: '10px' }
                                    }),
                                    renderLinkSettingsPopover()
                                )
                            ),
                            el(
                                'div',
                                { style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '10px' } },
                                el(
                                    'div',
                                    { style: { display: 'flex', alignItems: 'center' } },
                                    el('span', null, __('Insert as:', 'linkcentral')),
                                    el(Button, {
                                        isTertiary: true,
                                        onClick: () => togglePopover('linkcentral-insert-options-popover'),
                                        style: { marginLeft: '2px' },
                                        className: 'linkcentral-insert-options-popover'
                                    },
                                    el('span', null, ` ${linkInsertionType}`),
                                    el('span', { className: 'dashicons dashicons-arrow-down-alt2' })
                                    )
                                ),
                                el(
                                    Button,
                                    {
                                        isPrimary: true,
                                        onClick: applyLink,
                                        disabled: !selectedLink,
                                        style: { marginLeft: 'auto' } // Aligns the button to the right
                                    },
                                    editingLink ? __('Update Link', 'linkcentral') : __('Apply Link', 'linkcentral')
                                )
                            ),
                            renderLinkInsertionTypePopover()
                        )
                )
            ),
            renderDropdown(), // Add this line to render the dropdown
            // Popover for displaying link information
            isPopoverVisible && el(
                URLPopover,
                {
                    anchor: popoverAnchor,
                    onClose: () => {
                        setIsPopoverVisible(false);
                        setPopoverAnchor(null);
                        setSelectedLink(null);
                        setLinkData(null);
                        setLinkId(null);
                    },
                    className: 'linkcentral-popover'
                },
                el('div', {
                    style: { padding: '10px', maxWidth: '400px', minWidth: 'auto', width: '90vw' },
                    onClick: (event) => {
                        event.stopPropagation(); // Prevent the popover from closing when clicking inside it
                    }
                },
                    el('div', { style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } },
                        el('div', { style: { display: 'flex', alignItems: 'center' } },
                            el('img', {
                                src: logoUrl,
                                alt: 'LinkCentral',
                                width: 24,
                                height: 24,
                                style: { marginRight: '8px' }
                            }),
                            el('div', null,
                                el('div', null, 
                                    linkData ? 
                                        linkData.error ? 
                                            el('span', { style: { color: 'red' } }, __('Link not found or inactive', 'linkcentral')) :
                                            el('a', { href: `${linkcentral_gutenberg_data.site_url}/wp-admin/post.php?post=${linkData.id}&action=edit`, target: '_blank', style: { textDecoration: 'none' } }, linkcentral_gutenberg_data.site_url + '/' + linkcentral_gutenberg_data.url_prefix + '/' + linkData.slug) 
                                        : __('Loading...', 'linkcentral')
                                ),
                                el('div', { style: { fontSize: '11px', fontWeight: 'bold' } }, 'Synchronized with ID: ' + linkId)
                            )
                        ),
                        el(Button, {
                            icon: 'editor-unlink',
                            label: __('Remove Link', 'linkcentral'),
                            onClick: removeLink,
                            tabIndex: -1 // Prevents automatic focus
                        })
                    )
                )
            )
        );
    };

    /**
     * TEXT BLOCKS
     * Register the custom format type for text blocks
     */
    registerFormatType('linkcentral/link', {
        title: __('LinkCentral', 'linkcentral'),
        tagName: 'a',
        className: 'linkcentral-link',
        attributes: {
            url: 'href',
            target: 'target',
            rel: 'rel',
            'data-linkcentral-id-sync': 'data-linkcentral-id-sync',
            'data-linkcentral-parameters': 'data-linkcentral-parameters'
        },
        edit: LinkCentralButton
    });

    /**
     * BUTTON BLOCKS
     * Register the custom format type for button blocks
     */

    // Add LinkCentralButton to the button block's toolbar
    wp.hooks.addFilter(
        'editor.BlockEdit',
        'linkcentral/button-toolbar',
        (BlockEdit) => (props) => {
            if (props.name === 'core/button') {
                return el(
                    wp.element.Fragment,
                    null,
                    el(BlockEdit, props),
                    el(BlockControls, null,
                        el(LinkCentralButton, {
                            isActive: !!props.attributes['data-linkcentral-id-sync'],
                            value: props.attributes,
                            onChange: (newAttributes) => props.setAttributes(newAttributes),
                            name: props.name,
                            attributes: props.attributes,
                        })
                    )
                );
            }
            return el(BlockEdit, props);
        }
    );

    // Filter to hide default link popover UI when LinkCentral link is active
    wp.hooks.addFilter(
        'editor.BlockListBlock',
        'linkcentral/button-link-ui',
        function(BlockListBlock) {
            return function(props) {
                if (props.name === 'core/button' && props.attributes['data-linkcentral-id-sync']) {
                    return wp.element.createElement(
                        BlockListBlock,
                        Object.assign({}, props, {
                            isSelected: false // Hide the default popover
                        })
                    );
                }
                return wp.element.createElement(BlockListBlock, props);
            };
        }
    );

    // Extend the core/button block attributes to include data-linkcentral-id-sync and data-linkcentral-parameters
    wp.hooks.addFilter(
        'blocks.registerBlockType',
        'linkcentral/extend-button-attributes',
        function(settings, name) {
            if (name === 'core/button') {
                settings.attributes = Object.assign(settings.attributes, {
                    'data-linkcentral-id-sync': {
                        type: 'string',
                        source: 'attribute',
                        selector: 'a',
                        attribute: 'data-linkcentral-id-sync'
                    },
                    'data-linkcentral-parameters': {
                        type: 'string',
                        source: 'attribute',
                        selector: 'a',
                        attribute: 'data-linkcentral-parameters'
                    }
                });
            }
            return settings;
        }
    );

    // Handle the unlink action to also remove the data-linkcentral-id-sync attribute
    wp.hooks.addFilter(
        'editor.BlockEdit',
        'linkcentral/handle-unlink',
        (BlockEdit) => (props) => {
            if (props.name === 'core/button') {
                const originalOnChange = props.attributes.url && props.setAttributes;
                if (originalOnChange) {
                    props.setAttributes = (newAttributes) => {
                        if (newAttributes.hasOwnProperty('url') && !newAttributes.url) {
                            // URL is being removed, also remove data-linkcentral-id-sync
                            newAttributes['data-linkcentral-id-sync'] = undefined;
                        }
                        originalOnChange(newAttributes);
                    };
                }
            }
            return el(BlockEdit, props);
        }
    );

    // Modify the save function of the button block to include the data-linkcentral-id-sync and data-linkcentral-parameters attributes on the inner <a> tag
    wp.hooks.addFilter(
        'blocks.getSaveElement',
        'linkcentral/button-save',
        function(element, blockType, attributes) {
            if (blockType.name === 'core/button' && attributes['data-linkcentral-id-sync']) {
                return wp.element.cloneElement(element, {
                    children: wp.element.cloneElement(element.props.children, {
                        'data-linkcentral-id-sync': attributes['data-linkcentral-id-sync'],
                        'data-linkcentral-parameters': attributes['data-linkcentral-parameters']
                    })
                });
            }
            return element;
        }
    );

})(window.wp);
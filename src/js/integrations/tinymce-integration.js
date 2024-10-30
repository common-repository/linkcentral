(function() {
    function initLinkCentralPlugin(tinymce) {
        // Check if linkcentral_tinymce_data is available
        if (typeof linkcentral_tinymce_data !== 'object') {
            console.log('LinkCentral data not found');
            return;
        }

        // Add LinkCentral plugin to the TinyMCE plugin manager
        tinymce.PluginManager.add('linkcentral', function(editor, url) {
            const logoUrl = linkcentral_tinymce_data.plugin_url + 'assets/images/linkcentral-logo.svg';

            // Add a button to the TinyMCE toolbar
            editor.addButton('linkcentral', {
                title: 'Insert LinkCentral Link',
                image: logoUrl,
                onclick: function() {
                    openLinkCentralModal(editor);
                },
                // Make button in the toolbar active when a linkcentral link is selected
                onpostrender: function() {
                    var ctrl = this;
                    editor.on('NodeChange', function(e) {
                        ctrl.active(e.element.nodeName.toLowerCase() === 'a' && e.element.hasAttribute('data-linkcentral-id-sync'));
                    });
                }
            });

            // Custom inline toolbar for LinkCentral links
            var linkCentralToolbar;
            editor.on('preinit', function() {
                if (editor.wp && editor.wp._createToolbar) {
                    linkCentralToolbar = editor.wp._createToolbar([
                        'linkcentral_url_display', // Add URL display
                        'linkcentral_edit',
                        'linkcentral_remove'
                    ], true);
                }
            });

            editor.on('wptoolbar', function(event) {
                var linkNode = editor.dom.getParent(event.element, 'a[data-linkcentral-id-sync]');
                if (linkNode) {
                    event.element = linkNode;
                    event.toolbar = linkCentralToolbar;
                    updateLinkCentralUrlDisplay(editor, linkNode);
                }
            });

            // Register the URL display control
            editor.addButton('linkcentral_url_display', {
                type: 'container',
                onPostRender: function() {
                    var element = this.getEl();
                    element.innerHTML = '<div class="linkcentral-url-display" style="display: flex; align-items: center;">' + 
                                            '<img src="' + logoUrl + '" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 5px;">' +
                                            '<div>' +
                                                '<div class="linkcentral-url-display-text"></div>' +
                                                '<div class="linkcentral-url-display-id"></div>' +
                                            '</div>' +
                                        '</div>';
                }
            });

            editor.addButton('linkcentral_edit', {
                title: 'Edit LinkCentral Link',
                icon: 'dashicon dashicons-edit',
                onclick: function() {
                    openLinkCentralModal(editor);
                }
            });

            editor.addButton('linkcentral_remove', {
                title: 'Remove LinkCentral Link',
                icon: 'dashicon dashicons-editor-unlink',
                onclick: function() {
                    editor.execCommand('unlink');
                }
            });

            // Function to open the LinkCentral modal dialog
            function openLinkCentralModal(editor) {
                var selectedNode = editor.selection.getNode();
                var linkNode = editor.dom.getParent(selectedNode, 'a[data-linkcentral-id-sync]');
                
                if (linkNode) {
                    editor.selection.select(linkNode);
                }

                var initialData = {
                    newTab: false,
                    insertAsShortcode: false,
                    parameters: ''
                };

                if (linkNode) {
                    initialData.newTab = linkNode.getAttribute('target') === '_blank';
                    initialData.parameters = linkNode.getAttribute('data-linkcentral-parameters') || '';
                    initialData.linkId = linkNode.getAttribute('data-linkcentral-id-sync');
                }

                // Use global setting for default link insertion type
                // Or set to 'synchronized' if focused link is a synchronized link
                let linkInsertionType = linkNode ? 'synchronized' : (linkcentral_tinymce_data.default_link_insertion_type || 'synchronized');

                editor.windowManager.open({
                    title: linkNode ? 'Edit LinkCentral Link' : 'Insert LinkCentral Link',
                    body: [
                        {
                            type: 'container',
                            name: 'searchContainer',
                            html: '<div style="position: relative;">' +
                                  '<input type="text" id="linkcentral-search" placeholder="Search for a link" style="width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #ccc; background-color: #fff; border-radius: 4px; margin-bottom: 2px;">' +
                                  '<div id="linkcentral-search-results" style="display:none; position: absolute; z-index: 1000; width: 100%; max-height: 200px; overflow-y: auto; border: 1px solid #ccc; background-color: white; margin-top: 5px;"></div>' +
                                  '</div>'
                        },
                        {
                            type: 'checkbox',
                            name: 'newTab',
                            label: 'New tab:',
                            checked: initialData.newTab
                        },
                        {
                            type: linkcentral_tinymce_data.can_use_premium_code__premium_only ? 'textbox' : 'container',
                            name: 'parameters',
                            label: 'Parameters:',
                            html: linkcentral_tinymce_data.can_use_premium_code__premium_only ? '' : '<a href="admin.php?page=linkcentral-settings#premium" target="_blank" class="linkcentral-premium-tag">Premium</a>',
                            value: initialData.parameters,
                            tooltip: 'Add parameters to the link (e.g., param1=value1&param2=value2)'
                        },
                        {
                            type: 'container',
                            name: 'linkInsertionType',
                            html: `
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                    <label>Link Insertion Type:</label>
                                    <a href="https://designforwp.com/docs/linkcentral/creating-and-using-links/inserting-links-into-your-content/" target="_blank" style="text-decoration: none; color: #12668A;font-weight: bold;">What is this?</a>
                                </div>
                                <div id="link-insertion-type-container" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    ${['synchronized', 'direct', 'shortcode'].map(type => `
                                        <div 
                                            data-type="${type}"
                                            style="
                                                cursor: ${type === 'shortcode' && editor.name === 'core/button' ? 'not-allowed' : 'pointer'};
                                                padding: 10px;
                                                border: ${linkInsertionType === type ? '2px solid #12668A' : '2px solid #ccc'};
                                                border-radius: 4px;
                                                text-align: center;
                                                background-color: ${linkInsertionType === type ? '#e0f7fa' : '#f9f9f9'};
                                                flex: 1;
                                                display: flex;
                                                flex-direction: column;
                                                align-items: center;
                                                justify-content: center;
                                                opacity: ${type === 'shortcode' && editor.name === 'core/button' ? 0.5 : 1};
                                            "
                                        >
                                            <i class="mce-ico mce-i-${type === 'synchronized' ? 'reload' : type === 'direct' ? 'link' : 'code'}" style="font-size: 20px; margin-bottom: 5px;"></i>
                                            <span>${type.charAt(0).toUpperCase() + type.slice(1)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            `
                        }
                    ],
                    onsubmit: function(e) {
                        insertLink(editor, e.data, linkInsertionType);
                    },
                    width: 400,
                    height: 250
                });

                // Add event listener for tile clicks
                setTimeout(() => {
                    const container = document.getElementById('link-insertion-type-container');
                    if (container) {
                        container.addEventListener('click', function(event) {
                            const tile = event.target.closest('div[data-type]');
                            if (tile && tile.style.cursor !== 'not-allowed') {
                                linkInsertionType = tile.getAttribute('data-type');
                                Array.from(container.children).forEach(child => {
                                    child.style.border = '2px solid #ccc';
                                    child.style.backgroundColor = '#f9f9f9';
                                });
                                tile.style.border = '2px solid #12668A';
                                tile.style.backgroundColor = '#e0f7fa';
                            }
                        });
                    }
                }, 100);

                // Set focus to the search input field
                setTimeout(() => {
                    const firstInput = document.getElementById('linkcentral-search');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 0);

                // Add event listener to the search input field
                setTimeout(function() {
                    var searchInput = document.getElementById('linkcentral-search');
                    if (searchInput) {
                        if (initialData.linkId) {
                            searchInput.disabled = true;
                            // Fetch and display the current link data
                            fetchLinkData(initialData.linkId, function(linkData) {
                                searchInput.disabled = false;
                                if (linkData) {
                                    searchInput.value = linkData.title;
                                    updateSelectedLink(linkData);
                                }
                            });
                        }

                        searchInput.addEventListener('input', debounce(function(e) {
                            var searchTerm = e.target.value;
                            if (searchTerm.length >= 2) {
                                searchLinks(searchTerm);
                            } else {
                                document.getElementById('linkcentral-search-results').style.display = 'none';
                            }
                        }, 300));
                    }
                }, 100);
            }

            // Function to search for links
            function searchLinks(term) {
                jQuery.ajax({
                    url: linkcentral_tinymce_data.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'linkcentral_tinymce_search_links',
                        nonce: linkcentral_tinymce_data.nonce,
                        search: term
                    },
                    success: function(response) {
                        if (response.success) {
                            updateSearchResults(response.data);
                        } else {
                            console.error('Error searching links:', response.data);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown);
                    }
                });
            }

            // Function to update the search results in the modal
            function updateSearchResults(links) {
                var resultsContainer = document.getElementById('linkcentral-search-results');
                if (!resultsContainer) {
                    return;
                }

                resultsContainer.innerHTML = '';
                if (links.length > 0) {
                    links.forEach(function(link) {
                        var linkElement = document.createElement('div');
                        linkElement.innerHTML = '<span style="font-weight: bold;">' + link.title + '</span> (<span style="font-family:monospace,monospace;font-size:0.9em;vertical-align:middle;">' + link.slug + '</span>)';
                        linkElement.style.padding = '5px';
                        linkElement.style.cursor = 'pointer';
                        linkElement.style.backgroundColor = 'white';
                        linkElement.addEventListener('mouseover', function() {
                            this.style.backgroundColor = '#f0f0f0';
                        });
                        linkElement.addEventListener('mouseout', function() {
                            this.style.backgroundColor = 'white';
                        });
                        linkElement.addEventListener('click', function() {
                            document.getElementById('linkcentral-search').value = link.title;
                            resultsContainer.style.display = 'none';
                            updateSelectedLink(link);
                        });
                        resultsContainer.appendChild(linkElement);
                    });
                    resultsContainer.style.display = 'block';
                } else {
                    resultsContainer.style.display = 'none';
                }
            }

            // Function to update the selected link in the modal
            function updateSelectedLink(link) {
                editor.windowManager.getWindows()[0].selectedLink = JSON.stringify(link);
            }

            // Function to insert the link into the TinyMCE editor
            function insertLink(editor, data, linkInsertionType) {
                var selectedLink = JSON.parse(editor.windowManager.getWindows()[0].selectedLink);
                var linkText = editor.selection.getContent({format: 'text'}) || selectedLink.title;

                editor.undoManager.transact(function() {
                    if (linkInsertionType === 'shortcode') {
                        var shortcode = '[linkcentral id="' + selectedLink.id + '"';
                        if (data.newTab) {
                            shortcode += ' newtab="true"';
                        }
                        if (data.parameters) {
                            shortcode += ' parameters="' + data.parameters + '"';
                        }
                        shortcode += ']' + linkText + '[/linkcentral]';
                        editor.insertContent(shortcode);
                    } else if (linkInsertionType === 'direct') {
                        var slug = selectedLink.slug;
                        var url = linkcentral_tinymce_data.site_url + '/' + linkcentral_tinymce_data.url_prefix + '/' + slug;
                        if (data.parameters) {
                            url += url.includes('?') ? '&' : '?';
                            url += data.parameters;
                        }
                        var directLink = '<a href="' + url + '"';
                        if (data.newTab) {
                            directLink += ' target="_blank"';
                        }

                        // Construct rel attributes
                        var relAttributes = [
                            selectedLink.nofollow === 'yes' ? 'nofollow' : (selectedLink.nofollow === 'default' ? (selectedLink.global_nofollow ? 'nofollow' : '') : ''),
                            selectedLink.sponsored === 'yes' ? 'sponsored' : (selectedLink.sponsored === 'default' ? (selectedLink.global_sponsored ? 'sponsored' : '') : '')
                        ].filter(Boolean).join(' ');

                        if (relAttributes) {
                            directLink += ' rel="' + relAttributes + '"';
                        }

                        // Add CSS classes
                        var cssClasses = selectedLink.css_classes_option === 'replace'
                            ? selectedLink.custom_css_classes
                            : `${selectedLink.global_css_classes || ''} ${selectedLink.custom_css_classes || ''}`.trim();
                        if (cssClasses) {
                            directLink += ' class="' + cssClasses + '"';
                        }

                        directLink += '>' + linkText + '</a>';
                        editor.selection.setContent(directLink);
                    } else { // synchronized
                        var linkcentral = '<a href="#linkcentral" class="linkcentral-link" data-linkcentral-id-sync="' + selectedLink.id + '"';
                        if (data.newTab) {
                            linkcentral += ' target="_blank"';
                        }
                        if (data.parameters) {
                            linkcentral += ' data-linkcentral-parameters="' + data.parameters + '"';
                        }
                        linkcentral += '>' + linkText + '</a>';
                        editor.selection.setContent(linkcentral);
                    }
                });
            }

            // Debounce function to limit the rate at which a function can fire
            function debounce(func, wait) {
                var timeout;
                return function() {
                    var context = this, args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        func.apply(context, args);
                    }, wait);
                };
            }

            // Function to update the URL display in the toolbar
            function updateLinkCentralUrlDisplay(editor, linkNode) {
                // Check if the selected link is the same as the previously selected link
                if (editor.lastSelectedLinkNode === linkNode) {
                    return; // Do not reload the data
                }

                // Store the currently selected link node
                editor.lastSelectedLinkNode = linkNode;

                // Wait for the next tick to ensure the toolbar has been rendered
                setTimeout(function() {
                    // Find all inline toolbars in the document
                    var toolbars = document.querySelectorAll('.mce-inline-toolbar-grp');
                    var activeToolbar;
                    
                    // Find the visible toolbar
                    for (var i = 0; i < toolbars.length; i++) {
                        if (toolbars[i].offsetParent !== null) {
                            activeToolbar = toolbars[i];
                            break;
                        }
                    }

                    if (!activeToolbar) {
                        console.log('Active toolbar not found');
                        return;
                    }

                    var urlDisplayControl = activeToolbar.querySelector('.linkcentral-url-display');
                    if (!urlDisplayControl) {
                        console.log('URL display control not found');
                        return;
                    }

                    var urlDisplayElement = urlDisplayControl.querySelector('.linkcentral-url-display-text');
                    var idDisplayElement = urlDisplayControl.querySelector('.linkcentral-url-display-id');
                    
                    if (urlDisplayElement) {
                        urlDisplayElement.innerHTML = 'Loading preview...';
                    }
                    
                    var linkId = linkNode.getAttribute('data-linkcentral-id-sync');
                    
                    if (idDisplayElement) {
                        idDisplayElement.innerHTML = '<span style="font-size: 10px; font-weight: bold;">Synchronized with ID: ' + linkId + '</span>';
                    }
                    
                    if (linkId) {
                        jQuery.ajax({
                            url: linkcentral_tinymce_data.ajax_url,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'linkcentral_get_link_data',
                                nonce: linkcentral_tinymce_data.nonce,
                                link_id: linkId
                            },
                            success: function(response) {
                                if (response.success) {
                                    var linkData = response.data;
                                    var fullUrl = linkcentral_tinymce_data.site_url + '/' + linkcentral_tinymce_data.url_prefix + '/' + linkData.slug;
                                    var editUrl = linkcentral_tinymce_data.site_url + '/wp-admin/post.php?post=' + linkData.id + '&action=edit';
                                    
                                    if (urlDisplayElement) {
                                        urlDisplayElement.innerHTML = '<a href="' + editUrl + '" target="_blank" style="text-decoration: none;">' + fullUrl + '</a>';
                                    }
                                } else {
                                    urlDisplayElement.innerHTML = '<span style="color:red;">' + response.data + '</span>';
                                    console.error('Error fetching link data:', response.data);
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error('AJAX error:', textStatus, errorThrown);
                            }
                        });
                    }
                }, 0);
            }

            // Add custom styles to make the linkcentral element appear with a gradient border at the bottom in the editor
            editor.on('init', function() {
                var head = editor.getDoc().head;
                var style = editor.getDoc().createElement('style');
                style.type = 'text/css';
                style.innerHTML = `
                    a[data-linkcentral-id-sync],
                    a.linkcentral-link {
                        text-decoration: none;
                        cursor: pointer;
                        border-bottom: 2px solid;
                        border-image: linear-gradient(to right, #12668A, #68C8CB) 1;
                        display: inline-block;
                        padding-bottom: 0;
                        line-height: 1;
                    }
                `;
                head.appendChild(style);
            });
        });

        // Initialize the plugin for existing editors
        if (tinymce.editors.length > 0) {
            tinymce.editors.forEach(function(editor) {
                editor.addCommand('mceLinkCentral', function() {
                    openLinkCentralModal(editor);
                });
            });
        }
    }

    // Function to initialize the plugin when TinyMCE is ready
    function initializeWhenReady() {
        if (typeof window.tinymce !== 'undefined' && window.tinymce.PluginManager) {
            initLinkCentralPlugin(window.tinymce);
        } else {
            // If TinyMCE is not yet available, wait for it to load
            window.addEventListener('tinymce-editor-setup', function() {
                initLinkCentralPlugin(window.tinymce);
            });
        }
    }

    // Check if the DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWhenReady);
    } else {
        initializeWhenReady();
    }

    // Also add event listener for when TinyMCE might load later
    if (typeof window.tinymce !== 'undefined') {
        window.tinymce.on('AddEditor', function(e) {
            e.editor.addCommand('mceLinkCentral', function() {
                openLinkCentralModal(e.editor);
            });
        });
    }

    // Add this new function to fetch link data
    function fetchLinkData(linkId, callback) {
        jQuery.ajax({
            url: linkcentral_tinymce_data.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'linkcentral_get_link_data',
                nonce: linkcentral_tinymce_data.nonce,
                link_id: linkId
            },
            success: function(response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    console.error('Error fetching link data:', response.data);
                    callback(null);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                callback(null);
            }
        });
    }
})();

/**
 * TextTune AI - Gutenberg Editor Integration
 *
 * Adds a "Text optimieren" button to the editor toolbar menu
 * and an optimize button to individual text block toolbars.
 *
 * @package TextTune_AI
 */
(function () {
    'use strict';

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginMoreMenuItem = wp.editPost.PluginMoreMenuItem;
    var select = wp.data.select;
    var dispatch = wp.data.dispatch;
    var apiFetch = wp.apiFetch;
    var addFilter = wp.hooks.addFilter;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var BlockControls = wp.blockEditor.BlockControls;
    var ToolbarGroup = wp.components.ToolbarGroup;
    var ToolbarButton = wp.components.ToolbarButton;
    var __ = wp.i18n.__;

    // Block types that support text optimization.
    var TEXT_BLOCK_TYPES = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/quote',
        'core/pullquote',
        'core/verse',
        'core/preformatted',
    ];

    // SVG icon for the optimize button.
    var optimizeIcon = el(
        'svg',
        { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: 24, height: 24 },
        el('path', {
            d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z',
            fill: 'currentColor',
        })
    );

    /**
     * Serialize all blocks to HTML content.
     *
     * @return {string} The serialized post content.
     */
    function getPostContent() {
        return select('core/editor').getEditedPostContent();
    }

    /**
     * Get the current post type.
     *
     * @return {string} The post type slug.
     */
    function getPostType() {
        return (
            select('core/editor').getCurrentPostType() ||
            (window.texttuneData && window.texttuneData.postType) ||
            'post'
        );
    }

    /**
     * Call the TextTune AI optimize REST endpoint.
     *
     * @param {string} content  The text content to optimize.
     * @param {string} postType The post type slug.
     * @return {Promise<string>} The optimized content.
     */
    function callOptimizeAPI(content, postType) {
        return apiFetch({
            path: '/texttune/v1/optimize',
            method: 'POST',
            data: {
                content: content,
                post_type: postType,
            },
        }).then(function (response) {
            if (response && response.success && response.content) {
                return response.content;
            }
            throw new Error(
                (response && response.message) ||
                __('Unerwartete Antwort vom Server.', 'texttune-ai')
            );
        });
    }

    /**
     * Show a success notice in the editor.
     *
     * @param {string} message The success message.
     */
    function showSuccess(message) {
        dispatch('core/notices').createSuccessNotice(message, {
            type: 'snackbar',
            isDismissible: true,
        });
    }

    /**
     * Show an error notice in the editor.
     *
     * @param {string} message The error message.
     */
    function showError(message) {
        dispatch('core/notices').createErrorNotice(message, {
            isDismissible: true,
        });
    }

    // =========================================================================
    // 1) Full Content Optimization via "More" Menu (three-dot menu)
    // =========================================================================

    var TextTuneFullOptimize = function () {
        var stateArr = useState(false);
        var isLoading = stateArr[0];
        var setIsLoading = stateArr[1];

        function handleFullOptimize() {
            var content = getPostContent();
            if (!content || !content.trim()) {
                showError(__('Der Beitrag hat keinen Inhalt zum Optimieren.', 'texttune-ai'));
                return;
            }

            setIsLoading(true);

            callOptimizeAPI(content, getPostType())
                .then(function (optimized) {
                    var blocks = wp.blocks.parse(optimized);
                    if (blocks && blocks.length > 0) {
                        dispatch('core/block-editor').resetBlocks(blocks);
                        showSuccess(__('Der gesamte Text wurde optimiert!', 'texttune-ai'));
                    } else {
                        showError(__('Die KI-Antwort konnte nicht verarbeitet werden.', 'texttune-ai'));
                    }
                })
                .catch(function (err) {
                    var msg = (err && err.message) || __('Fehler bei der Optimierung.', 'texttune-ai');
                    showError(msg);
                })
                .finally(function () {
                    setIsLoading(false);
                });
        }

        return el(
            PluginMoreMenuItem,
            {
                icon: optimizeIcon,
                onClick: handleFullOptimize,
                disabled: isLoading,
            },
            isLoading
                ? __('Optimierung läuft…', 'texttune-ai')
                : __('Text optimieren (TextTune AI)', 'texttune-ai')
        );
    };

    // Register the plugin for the "More" menu item.
    registerPlugin('texttune-ai', {
        render: TextTuneFullOptimize,
        icon: optimizeIcon,
    });

    // =========================================================================
    // 2) Single Block Optimization via Block Toolbar
    // =========================================================================

    /**
     * Higher-order component that adds an "Optimize" button to text block toolbars.
     */
    var withTextTuneBlockOptimize = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            // Only add to supported text block types.
            if (TEXT_BLOCK_TYPES.indexOf(props.name) === -1) {
                return el(BlockEdit, props);
            }

            var stateArr = useState(false);
            var isLoading = stateArr[0];
            var setIsLoading = stateArr[1];

            function handleBlockOptimize() {
                // Get the block's serialized content.
                var block = select('core/block-editor').getBlock(props.clientId);
                if (!block) return;

                var blockContent = wp.blocks.serialize(block);
                if (!blockContent || !blockContent.trim()) {
                    showError(__('Dieser Block hat keinen Inhalt.', 'texttune-ai'));
                    return;
                }

                setIsLoading(true);

                callOptimizeAPI(blockContent, getPostType())
                    .then(function (optimized) {
                        var newBlocks = wp.blocks.parse(optimized);
                        if (newBlocks && newBlocks.length > 0) {
                            // Replace this single block with the optimized version.
                            dispatch('core/block-editor').replaceBlock(
                                props.clientId,
                                newBlocks
                            );
                            showSuccess(__('Block wurde optimiert!', 'texttune-ai'));
                        } else {
                            showError(
                                __('Die KI-Antwort konnte nicht verarbeitet werden.', 'texttune-ai')
                            );
                        }
                    })
                    .catch(function (err) {
                        var msg =
                            (err && err.message) || __('Fehler bei der Optimierung.', 'texttune-ai');
                        showError(msg);
                    })
                    .finally(function () {
                        setIsLoading(false);
                    });
            }

            return el(
                Fragment,
                null,
                el(
                    BlockControls,
                    { group: 'other' },
                    el(
                        ToolbarGroup,
                        null,
                        el(ToolbarButton, {
                            icon: optimizeIcon,
                            label: isLoading
                                ? __('Optimierung läuft…', 'texttune-ai')
                                : __('Block optimieren (TextTune AI)', 'texttune-ai'),
                            onClick: handleBlockOptimize,
                            disabled: isLoading,
                            isBusy: isLoading,
                        })
                    )
                ),
                el(BlockEdit, props)
            );
        };
    }, 'withTextTuneBlockOptimize');

    addFilter(
        'editor.BlockEdit',
        'texttune-ai/block-optimize',
        withTextTuneBlockOptimize
    );
})();

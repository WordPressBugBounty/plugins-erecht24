(function(blocks, element, blockEditor, components, i18n, serverSideRender) {
    'use strict';

    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var Notice = components.Notice;
    var ServerSideRender = serverSideRender;
    var blockSettings = window.eRecht24LegalTextBlock || {};

    var sharedAttributes = {
        type: {
            type: 'string',
            default: 'imprint'
        },
        lang: {
            type: 'string',
            default: 'de'
        },
        strip_title: {
            type: 'boolean',
            default: false
        }
    };

    function makeEditFn(blockName) {
        return function(props) {
            var attributes = props.attributes;
            var blockProps = useBlockProps({
                className: 'erecht24-block-editor-preview'
            });
            var showRemoteNotice = blockSettings.canSyncRemote === false;

            return [
                el(
                    'div',
                    Object.assign({
                        key: 'preview'
                    }, blockProps),
                    showRemoteNotice ? el(Notice, {
                        status: 'warning',
                        isDismissible: false
                    }, blockSettings.message) : null,
                    el(ServerSideRender, {
                        block: blockName,
                        attributes: attributes
                    })
                ),
                el(
                    InspectorControls, {
                        key: 'inspector'
                    },
                    el(
                        PanelBody, {
                            title: __('Einstellungen', 'erecht24'),
                            initialOpen: true
                        },
                        el(
                            'div', {
                                className: 'erecht24-block-inspector-stack'
                            },
                            el(SelectControl, {
                                __nextHasNoMarginBottom: true,
                                __next40pxDefaultSize: true,
                                label: __('Rechtstext', 'erecht24'),
                                value: attributes.type,
                                options: [{
                                        label: __('Impressum', 'erecht24'),
                                        value: 'imprint'
                                    },
                                    {
                                        label: __('Datenschutzerklärung', 'erecht24'),
                                        value: 'privacy_policy'
                                    },
                                    {
                                        label: __('Datenschutzerklärung für Social Media', 'erecht24'),
                                        value: 'privacy_policy_social_media'
                                    }
                                ],
                                onChange: function(value) {
                                    props.setAttributes({
                                        type: value
                                    });
                                }
                            }),
                            el(SelectControl, {
                                __nextHasNoMarginBottom: true,
                                __next40pxDefaultSize: true,
                                label: __('Sprache', 'erecht24'),
                                value: attributes.lang,
                                options: [{
                                        label: __('Deutsch', 'erecht24'),
                                        value: 'de'
                                    },
                                    {
                                        label: __('Englisch', 'erecht24'),
                                        value: 'en'
                                    }
                                ],
                                onChange: function(value) {
                                    props.setAttributes({
                                        lang: value
                                    });
                                }
                            }),
                            el(ToggleControl, {
                                __nextHasNoMarginBottom: true,
                                label: __('H1 entfernen', 'erecht24'),
                                checked: attributes.strip_title,
                                onChange: function(value) {
                                    props.setAttributes({
                                        strip_title: value
                                    });
                                }
                            })
                        )
                    )
                )
            ];
        };
    }

    blocks.registerBlockType('erecht24/legal-text', {
        title: __('eRecht24 Rechtstext', 'erecht24'),
        icon: 'universal-access-alt',
        category: 'widgets',
        apiVersion: 3,
        attributes: sharedAttributes,
        edit: makeEditFn('erecht24/legal-text'),
        save: function() {
            return null;
        }
    });

    // Legacy alias for blocks inserted by the v3 plugin — hidden from inserter,
    // but fully functional so existing posts need no manual intervention.
    blocks.registerBlockType('erecht24/erecht24', {
        title: __('eRecht24 Rechtstext', 'erecht24'),
        icon: 'universal-access-alt',
        category: 'widgets',
        apiVersion: 3,
        attributes: sharedAttributes,
        supports: {
            inserter: false
        },
        edit: makeEditFn('erecht24/erecht24'),
        save: function() {
            return null;
        }
    });
}(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n,
    window.wp.serverSideRender
));

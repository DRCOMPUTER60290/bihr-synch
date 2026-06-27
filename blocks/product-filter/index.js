(function( wp ) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;

    registerBlockType( 'bihr/product-filter', {
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return el( 'div', {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Réglages', 'bihr-synch' ) },
                        el( TextControl, {
                            label: __( 'Titre', 'bihr-synch' ),
                            value: attributes.title,
                            onChange: function( value ) { setAttributes( { title: value } ); },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Afficher le bouton', 'bihr-synch' ),
                            checked: attributes.showButton,
                            onChange: function( value ) { setAttributes( { showButton: value } ); },
                        } )
                    )
                ),
                el( 'div', { className: 'bihr-block-placeholder', style: {
                    padding: '20px',
                    background: '#f0f6fc',
                    border: '1px dashed #2271b1',
                    borderRadius: '4px',
                    textAlign: 'center',
                } },
                    el( 'span', { style: { fontSize: '24px' } }, '📦' ),
                    el( 'h3', {}, __( 'Filtre Produits BIHR', 'bihr-synch' ) ),
                    el( 'p', { style: { color: '#666' } }, attributes.title ),
                    el( 'p', { style: { fontSize: '12px', color: '#999' } }, __( 'Ce bloc affiche le filtre de catégories produits en frontend.', 'bihr-synch' ) ),
                )
            );
        },
        save: function() {
            return null;
        },
    } );
} )( window.wp );

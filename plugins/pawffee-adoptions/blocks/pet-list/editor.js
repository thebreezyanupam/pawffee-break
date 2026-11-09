/**
 * Editor UI for the Adoptable pets block. Plain JavaScript (no build step):
 * a live server-rendered preview plus sidebar settings.
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'pawffee/pet-list', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Which pets to show', 'pawffee-adoptions' ) },
						el( components.SelectControl, {
							label: __( 'Species', 'pawffee-adoptions' ),
							value: a.species,
							options: [
								{ label: __( 'Dogs and cats', 'pawffee-adoptions' ), value: 'all' },
								{ label: __( 'Dogs only', 'pawffee-adoptions' ), value: 'dog' },
								{ label: __( 'Cats only', 'pawffee-adoptions' ), value: 'cat' }
							],
							onChange: function ( v ) { set( { species: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( components.ToggleControl, {
							label: __( 'Include pets with an adoption pending', 'pawffee-adoptions' ),
							checked: a.showPending,
							onChange: function ( v ) { set( { showPending: v } ); },
							__nextHasNoMarginBottom: true
						} ),
						el( components.ToggleControl, {
							label: __( 'Include adopted pets', 'pawffee-adoptions' ),
							help: __( 'Useful for a "Happy tails" page.', 'pawffee-adoptions' ),
							checked: a.showAdopted,
							onChange: function ( v ) { set( { showAdopted: v } ); },
							__nextHasNoMarginBottom: true
						} )
					)
				),
				el( serverSideRender, { block: 'pawffee/pet-list', attributes: a } )
			);
		}
	} );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender, window.wp.i18n ) );

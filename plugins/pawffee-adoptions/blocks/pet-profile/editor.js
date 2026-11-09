/**
 * Editor preview for the Pet profile block (used in the single pet template).
 */
( function ( blocks, element, blockEditor, serverSideRender ) {
	var el = element.createElement;

	blocks.registerBlockType( 'pawffee/pet-profile', {
		edit: function ( props ) {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el( serverSideRender, {
					block: 'pawffee/pet-profile',
					attributes: props.attributes,
					urlQueryArgs: { post_id: props.context.postId }
				} )
			);
		}
	} );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.serverSideRender ) );

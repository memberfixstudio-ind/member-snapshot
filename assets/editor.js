/**
 * Editor side of the Member Snapshot block. Written without a build step:
 * the card itself is rendered by PHP and previewed with ServerSideRender.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var Disabled = wp.components.Disabled;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'member-snapshot/card', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Card', 'member-snapshot' ) },
						el( TextControl, {
							label: __( 'Heading', 'member-snapshot' ),
							help: __( 'Leave empty for "Welcome back, <first name>".', 'member-snapshot' ),
							value: a.title,
							onChange: function ( v ) {
								set( { title: v } );
							},
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Show memberships', 'member-snapshot' ),
							checked: a.showMemberships,
							onChange: function ( v ) {
								set( { showMemberships: v } );
							},
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Show courses', 'member-snapshot' ),
							checked: a.showCourses,
							onChange: function ( v ) {
								set( { showCourses: v } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el(
					Disabled,
					null,
					el( ServerSideRender, {
						block: 'member-snapshot/card',
						attributes: a,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );

<?php
/*
 * Plugin Name: Simple Footnotes Editor Button
 * Plugin URI: http://wordpress.org/extend/plugins/simple-footnotes-editor-button/
 * Plugin Description: Adds a button to the TinyMCE editor toolbar in the second row that makes it easy for users to add footnotes with the proper syntax.
 * Version: 0.2
 * Author: Andrew Patton
 * Author URI: http://www.purecobalt.com/
 * License: CC0
 * Text Domain: 
 * Domain Path:
 */
class simple_footnotes_editor_button {

	/**
	 * Constructor: hooks up plugin initialization to 'tinymce_before_init' action
	 *
	 * @since Simple Footnotes Editor Button 0.2
	 */
	function simple_footnotes_editor_button() {
		// Set up initialization on tinymce_before_init (to prevent code from being loaded on pages that are not loading TinyMCE)
		add_action( 'init', array( &$this, 'init_simple_footnotes_editor_button' ) );
	}

	/**
	 * Initialize plugin hooks with user permission checks
	 *
	 * @since Simple Footnotes Editor Button 0.2
	 */
	function init_simple_footnotes_editor_button() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
			return;
		
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			// Load plugin text domain
			load_plugin_textdomain( 'simple-footnotes-editor-button', false, basename( dirname( __FILE__ ) ) . '/languages' );
			add_filter( 'tiny_mce_before_init', array( &$this, 'add_tinymce_footnote_plugin' ) );
			add_filter( 'mce_buttons_2', array( &$this, 'register_tinymce_footnote_button' ) );
			// Hook into admin and regular footers to include
			add_action( 'after_wp_tiny_mce', array( &$this, 'the_footnote_button_dialog' ) );
		}
	}

	/**
	 * Add custom button handler to TinyMCE settings JS for Simple Footnotes plugin
	 *
	 * @since Simple Footnotes Editor Button 0.1
	 *
	 * @param $settings array of TinyMCE settings
	 * @return $settings array of TinyMCE settings
	 */
	function add_tinymce_footnote_plugin( $settings ) {
		// Add a setup function which registers our simple button to prompt a dialog box and insert entered content wrapped with [ref]...[/ref]
		// Use inline JS (instead of an external JS file) to simplify translation
		$setup_function = 'function(ed) {
			ed.addButton("simple-footnote", {
				title : "' . __( 'Insérer une note de bas de page', 'simple-footnotes-editor-button' ) . '",
				image : "' . plugins_url( 'footnote-icon.png', __FILE__ ) . '",
				onclick : function() {
					ed.windowManager.open({
						id : "simple-footnotes-editor-button",
						width : 480,
						height : "auto",
						wpDialog : true,
						title : "' . __( 'Insérer une note de bas de page', 'simple-footnotes-editor-button' ) . '"
					});
				}
			});
	   }';
	   if ( ! $settings['setup'] )
	   	$settings['setup'] = '';
	   
	   // Strip out new lines (they will break the JS) and add the setup function
		$settings['setup'] .= str_replace( "\n", '', str_replace( "\t", ' ', $setup_function ) );
		return $settings;
	}

	/**
	 * Filter TinyMCE wysiwig editor to include our button
	 *
	 * @since Simple Footnotes Editor Button 0.1
	 *
	 * @param $buttons TinyMCE button array
	 * @return $buttons TinyMCE button array
	 */
	function register_tinymce_footnote_button( $buttons ) {
		// Add our custom button one before the end (before the help button)
		array_splice( $buttons, count( $buttons ) - 1, 0, array( 'simple-footnote' ) );

		return $buttons;
	}

	/**
	 * Print the contents of the HTML dialog box and its accompanying JS
	 *
	 * @since Simple Footnotes Editor Button 0.1
	 */
	function the_footnote_button_dialog() {
	?>
		<div style="display:none;">
			<form id="simple-footnotes-editor-button" tabindex="-1">
				<div style="margin: 1em">
					<p class="howto"><?php _e( 'Entrer le contenu de la note de bas de page', 'simple-footnotes-editor-button' ); ?></p>
					<textarea id="simple-footnotes-editor-button-content" rows="4" style="width: 95%; margin-bottom: 1em"></textarea>
					<div class="submitbox" style="margin-bottom: 1em">
						<div id="simple-footnotes-editor-button-insert" class="alignright">
							<input type="submit" value="<?php esc_attr_e( 'Insérer', 'simple-footnotes-editor-button' ); ?>" class="button-primary">
						</div>
						<div id="simple-footnotes-editor-button-cancel">
							<a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
						</div>
					</div>
				</div>
			</form>
		</div>
		<script>
		(function($){
			var plugin_name = '#simple-footnotes-editor-button';
			$(plugin_name).on('submit', function(evt) {
				var $content = $(plugin_name + '-content'),
				    footnote = $.trim($content.val());
				
				// Now that we have the footnote content, clear the textarea
				$content.val('');
				if (footnote.length)
					tinyMCE.activeEditor.execCommand('mceInsertContent', false, '[ref]' + footnote + '[/ref]');
				
				tinyMCEPopup.close();
				evt.preventDefault();
			});
			$(plugin_name + '-cancel').on('click', function(evt) {
				evt.preventDefault();
				tinyMCEPopup.close();
			});
			// Customize toolbar icon img element to support hover and image sprite (has to be window.onLoad because toolbar is not yet loaded)
			$(window).on('load', function() {
				$('.mce_simple-footnote img').css({
					height: '40px'
				}).on('mouseenter', function() {
					$(this).css('margin-top', '-20px');
				}).on('mouseleave', function() {
					$(this).css('marginTop', '');
				}).parent().css({
					height: '20px',
					overflow: 'hidden'
				});
			});
		})(jQuery);
		</script>
	<?php
	}
}

new simple_footnotes_editor_button();

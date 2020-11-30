<?php
/*
Plugin Name: Clear Transient From Dashboard
Description: Logging in as admin, you can clear all transients from the dashboard.
Author: minkapi
version: 2.2
*/
add_action( 'load-index.php', 'CTFD_clear_transient' );
function CTFD_clear_transient() {
	global $wpdb;
	$post_data = wp_unslash( $_POST );

	if( ! empty( $_POST['action'] ) && $_POST['action'] === 'clear-transient' ) {
		check_admin_referer( 'CTFD-clear-transient', 'CTFD-clear-transient-nonce' );

		if ( current_user_can( 'manage_options' ) ) {
			$results = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_%';" );
			foreach ( $results as $option_name ) {
				if ( ! preg_match( '/^_transient_timeout_/', $option_name ) ) {
					$transient_name =  preg_replace( '/^_transient_/', '', $option_name );
					/**
					 * Filter for specifying whether to do delete_transient().
					 *
					 * @since 1.0
					 *
					 * @param bool   Boolean value as to whether to delete.
					 * @param string $transient_name A string of transient name.
					 */
					if ( apply_filters( 'CTFD_delete_transient', true, $transient_name ) ) {
						delete_transient( $transient_name );
					}
				}
			}
	
			if ( is_multisite() ) {
				$results = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%';" );
				foreach ( $results as $option_name ) {
					if ( ! preg_match( '/^_site_transient_timeout_/', $option_name ) ) {
						$transient_name =  preg_replace( '/^_site_transient_/', '', $option_name );
						/**
						 * Filter for specifying whether to do delete_site_transient().
						 *
						 * @since 1.0
						 *
						 * @param bool   Boolean value as to whether to delete.
						 * @param string $transient_name A string of transient name.
						 */
						if ( apply_filters( 'CTFD_delete_site_transient', true, $transient_name ) ) {
							delete_site_transient( $transient_name );
						}
					}
				}
			}

			if ( wp_using_ext_object_cache() ) {
				wp_cache_flush();
			}
		}

	}
}

add_action( 'wp_dashboard_setup', 'CTFD_dashboard_widget' );
function CTFD_dashboard_widget() {
	if ( current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget( 'ctfd-clear-transient', __( 'Clear Transient' ), 'CTFD_dashboard_widget_content' );
	}
}
function CTFD_dashboard_widget_content() {
?>
<form id="ctfd-clear-transient-form" class="form-content" name="ctfd-clear-transient" method="post" action="">
	<p><?php _e( 'Pressing the "Clear Transient" button clears all transients.', 'CTFD-clear-transient' ); ?></p>
	<input type="hidden" name="action" value="clear-transient">
	<?php wp_nonce_field( 'CTFD-clear-transient', 'CTFD-clear-transient-nonce' ); ?>
	<?php submit_button( __( 'Clear Transient', 'CTFD-clear-transient' ), 'primary', 'CTFD-clear-transient-submit' ); ?>
</form>
<?php
}

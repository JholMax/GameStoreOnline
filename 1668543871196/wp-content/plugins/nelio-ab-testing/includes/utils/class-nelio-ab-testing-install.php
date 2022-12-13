<?php
/**
 * The file that includes installation-related functions and actions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class configures WordPress and installs some capabilities.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Install {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_Install
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Experiment_Post_Type_Register the single instance of this class.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	/**
	 * Hook in tabs.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function init() {

		$main_file = nelioab()->plugin_path . '/nelio-ab-testing.php';
		register_activation_hook( $main_file, array( $this, 'install' ) );

		add_action( 'admin_init', array( $this, 'check_version' ), 5 );

	}//end init()

	/**
	 * Checks the currently-installed version and, if it's old, installs the new one.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function check_version() {

		$last_version = get_option( 'nab_version' );
		$this_version = nelioab()->plugin_version;
		if ( ! defined( 'IFRAME_REQUEST' ) && ( $last_version !== $this_version ) ) {

			// Update version.
			update_option( 'nab_version', $this_version );

			/**
			 * Fires once the plugin has been updated.
			 *
			 * @param string $this_version current version of the plugin.
			 * @param string $last_version previous installed version of the plugin.
			 *
			 * @since 5.0.0
			 */
			do_action( 'nab_updated', $this_version, $last_version );

		}//end if

	}//end check_version()

	/**
	 * Install Nelio A/B Testing.
	 *
	 * This function registers new post types, adds a few capabilities, and more.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function install() {

		if ( ! defined( 'NELIO_AB_TESTING_INSTALLING' ) ) {
			define( 'NELIO_AB_TESTING_INSTALLING', true );
		}//end if

		// Installation actions here.
		self::set_proper_permissions();

		/**
		 * Fires once the plugin has been installed.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_installed' );

	}//end install()

	/**
	 * Creates capabilities for editing experiments and assigns them to different
	 * user roles.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	private function set_proper_permissions() {

		$contributor_caps = array(
			'read_nab_experiment',
			'read_private_nab_experiments',
		);

		$author_caps = array_merge(
			$contributor_caps,
			array(
				'create_nab_experiments',
				'delete_nab_experiments',
				'delete_others_nab_experiments',
				'delete_private_nab_experiments',
				'edit_nab_experiments',
				'edit_others_nab_experiments',
				'edit_private_nab_experiments',
			)
		);

		// Set new roles.
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( $author_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if

		$role = get_role( 'editor' );
		if ( $role ) {
			foreach ( $author_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if

		$role = get_role( 'author' );
		if ( $role ) {
			foreach ( $author_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if

		$role = get_role( 'contributor' );
		if ( $role ) {
			foreach ( $contributor_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if

	}//end set_proper_permissions()

}//end class


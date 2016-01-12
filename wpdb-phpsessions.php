<?php
/**
 * Plugin Name: WPDB PHP Sessions
 * Description: Use $wpdb to store $_SESSION data.
 */

class Pj_WPDB_Session_Handler {
	public static $instance = null;
	public static $config = null;

	public $wpdb = null;
	public $table = 'pj_wpdb_sessions';
	public $version = 1;

	/**
	 * Open a session.
	 */
	public function open() {
		return true;
	}

	/**
	 * Close a session.
	 */
	public function close() {
		return true;
	}

	/**
	 * Read session data.
	 *
	 * @param sting $id Session id.
	 * @return mixed Session data or null.
	 */
	public function read( $id ) {
		if ( ! $this->wpdb )
			return null;

		return @$this->wpdb->get_var( $this->wpdb->prepare( "SELECT `data` FROM `{$this->table}` WHERE `id` = %s;", $id ) );
	}

	/**
	 * Write a session.
	 *
	 * @param string $id Session id.
	 * @param string $data Session data (serialized for session storage).
	 */
	public function write( $id, $data ) {
		if ( ! $this->wpdb )
			return null;

		return $this->wpdb->query( $this->wpdb->prepare( "REPLACE INTO `{$this->table}` VALUES ( %s, %s, %d );", $id, $data, time() ) );
	}

	/**
	 * Destroy a session.
	 *
	 * @param string $id Session id.
	 */
	public function destroy( $id ) {
		if ( ! $this->wpdb )
			return false;

		return (bool) $this->wpdb->query( $this->wpdb->prepare( "DELETE FROM `{$this->table}` WHERE `id` = %s;", $id ) );
	}

	/**
	 * Garbage collection.
	 */
	public function gc( $max ) {
		return true;
	}

	/**
	 * Compare versions and maybe run an upgrade routine.
	 */
	public function maybe_upgrade() {
		$current_version = (int) get_site_option( 'pj_wpdb_sessions_version', 0 );
		if ( version_compare( $this->version, $current_version, '>' ) )
			$this->do_upgrade( $current_version );
	}

	/**
	 * Perform an upgrade routine.
	 *
	 * @param int $current_version The version number from which to perform the upgrades.
	 */
	public function do_upgrade( $current_version ) {
		global $wpdb;

		if ( $current_version < 1 ) {
			$wpdb->query( "CREATE TABLE `{$this->table}` (
				`id` varchar(255) NOT NULL,
				`data` mediumtext NOT NULL,
				`timestamp` int(255) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

			$current_version = 1;
			update_site_option( 'pj_wpdb_sessions_version', $current_version );
		}
	}

	/**
	 * Cron-powered garbage collection.
	 */
	public function cron_gc() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->table}` WHERE `timestamp` < %d;", time() - HOUR_IN_SECONDS * 24 ) );
	}

	/**
	 * If we have a global configuration, try and read it.
	 *
	 * @param array $defaults The default settings.
	 */
	public static function maybe_user_config( $defaults ) {
		if ( ! function_exists( 'pj_user_config' ) )
			return $defaults;

		$pj_user_config = pj_user_config();
		if ( empty( $pj_user_config['wpdb_sessions'] ) || ! is_array( $pj_user_config['wpdb_sessions'] ) )
			return $defaults;

		return wp_parse_args( $pj_user_config['wpdb_sessions'], $defaults );
	}

	/**
	 * Runs at the end of this script.
	 */
	public static function init() {
		self::$config = self::maybe_user_config( array(
			'enable' => false,
		) );

		// Enable this plugin via a pj user config.
		if ( ! self::$config['enable'] )
			return null;

		if ( ! self::$instance ) {
			self::$instance = new Pj_WPDB_Session_Handler;
			self::$instance->wpdb = clone $GLOBALS['wpdb'];
			self::$instance->maybe_upgrade();

			session_set_save_handler(
				array( self::$instance, 'open' ),
				array( self::$instance, 'close' ),
				array( self::$instance, 'read' ),
				array( self::$instance, 'write' ),
				array( self::$instance, 'destroy' ),
				array( self::$instance, 'gc' )
			);

			register_shutdown_function( 'session_write_close' );

			if ( ! wp_next_scheduled( 'pj_wpdb_sessions_gc' ) )
				wp_schedule_event( time(), 'hourly', 'pj_wpdb_sessions_gc' );

			add_action( 'pj_wpdb_sessions_gc', array( self::$instance, 'cron_gc' ) );
		}

		return self::$instance;
	}

	// No outsiders.
	private function __construct() {}
}

Pj_WPDB_Session_Handler::init();

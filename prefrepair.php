<?php

/**
 * PrefRepair - tool to recreate or restore entries in the DesktopServer preferences file
 */

class DS_PrefRepair
{
	private $docdir = NULL;				// directory where websites are stored
	private $pref_file = NULL;			// full path to preferences file
	private $pref = NULL;				// instance of preferences data
	private $dirty = FALSE;				// when TRUE, need to re-write preferences file

	private $browser = 'Safari';		// browser
	private $force = FALSE;				// force update of site information
	private $tld = '.dev.cc';			// TLD
	private $verbose = TRUE;

	const PREF_FILE = 'com.serverpress.desktopserver.json';

	public function __construct( $args =  array() )
	{
		if ( ! defined( 'DS_OS_DARWIN' ) ) {
			// OS-specific defines
			define( 'DS_OS_DARWIN', 'Darwin' === PHP_OS );
			define( 'DS_OS_WINDOWS', !DS_OS_DARWIN && FALSE !== strcasecmp('win', PHP_OS ) );
			define( 'DS_OS_LINUX', FALSE === DS_OS_DARWIN && FALSE === DS_OS_WINDOWS );
		}

		foreach ( $args as $arg ) {
			if ( '-' === substr( $arg, 0, 1 ) ) {
				$param = NULL;
				if ( FALSE !== ( $pos = strpos( $arg, ':' ) ) ) {
					$param = substr( $arg, $pos );
					$arg = substr( $arg, 0, $pos );
				}

				switch ($arg) {
				case '-b':
					if ( in_array( $param, array( 'Safari', 'Chrome', 'Firefox' ) ) )
						$this->browser = $param;
					else
						die ( 'Unrecognized browser name specified: ' . $param );
					break;

				case '-f':
					$this->force = TRUE;
					break;

				case '-h':
					$this->help();
					break;

				case '-t':
					if ( NULL === $param ) {
						$this->msg( '-t parameter requires value', TRUE );
						$this->help();
					}
					if ( '.' !== substr( $param, 0, 1 ) ) {
						$this->msg( '-t parameter value must begin with "."', TRUE );
						$this->help();
					}
					$this->tld = $param;

				case '-v':
					$this->verbose = TRUE;
					break;
				}
			} else {
				if ( NULL === $this->docdir )
					$this->docdir = rtrim( $arg, '\\/' ) . DIRECTORY_SEPARATOR;
				else
					die( 'Document directory has already been specified.' );
			}
		}
	}

	public function run()
	{
		$this->msg( 'prefrepair:v1.0 - Copyright (C) 2018 ServerPress, LLC. All Rights Reserved.', TRUE );

		if ( $this->is_ds_running() ) {
			$this->msg( 'DesktopServer is currently running.' );
			die( 'Please exit the DesktopServer application before using this tool.' );
		}

		if ( DS_OS_WINDOWS ) {
			$this->pref_file = 'C:\\ProgramData\\DesktopServer\\' . self::PREF_FILE;
		} else if ( DS_OS_DARWIN ) {
			$this->pref_file = '/Users/Shared/.' . self::PREF_FILE;
		} else {
			die ( 'OS Dependency not implemented yet.' );
		}

		if ( NULL === $this->docdir ) {
			$this->msg( 'No document directory specfied. Assuming default location.' );
			$username = get_current_user();
//			$username = getenv( 'USERNAME' );
			// use default User's directory
			if ( DS_OS_WINDOWS ) {
				$this->docdir = "C:\\Users\\{$username}\\Documents\\Websites\\";
			} else if ( DS_OS_DARWIN ) {
				$this->docdir = "/Users/{$username}/Sites/";
			}
		}
		if ( !is_dir($this->docdir) ) {
			die( "Document directory {$this->docdir} does not exist." );
		}

		$this->msg( "Preferences: {$this->pref_file}", TRUE );
		$this->msg( "Rebuilding websites from directory: {$this->docdir}.", TRUE );

		$this->load_preferences();

		// update preferences based on parameters
		if ( $this->pref['browser'] !== $this->browser ) {
			$this->pref['browser'] = $this->browser;
			$this->dirty = TRUE;
			$this->msg( "Updating browser specification to: {$this->browser}." );
		}
		if ( $this->pref['tld'] !== $this->tld ) {
			$this->pref['tld'] = $this->tld;
			$this->dirty = TRUE;
			$this->msg( "Updating TLD specification to: {$this->tld}." );
		}

		$this->msg( 'Starting search...' );
		$dh = opendir( $this->docdir );
		if ( FALSE === $dh )
			die( 'Unable to access contents of document directory.' );
		while ( FALSE !== ( $file = readdir( $dh ) ) ) {
			if ( '.' === $file || '..' === $file )
				continue;
			$dir = $this->docdir . $file . DIRECTORY_SEPARATOR;
			if ( is_dir( $dir )) {
				// we have a directory entry, check it
				$this->process_dir( $dir );
			}
		}

		if ( $this->dirty ) {
			$this->msg( 'Rewriting preferences file.' );
			$this->save_preferences();
		}
	}

	private function process_dir( $dir )
	{
		$this->msg( "Working on directory {$dir}..." );
		if ( !file_exists( $dir . 'wp-config.php' ) ) {
			$this->msg( 'No wp-config file found, returning.' );
			return;
		}

		// load config file class if not already done
		if ( !class_exists( 'DS_ConfigFile', FALSE ) ) {
			$loc = dirname( __FILE__ ) . '/htdocs/classes/gstring.php';
			require_once( $loc );
			$loc = dirname( __FILE__ ) . '/htdocs/classes/class-ds-config-file.php';
			require_once( $loc );
		}

		$config = new DS_ConfigFile( $dir . 'wp-config.php' );
		$config->set_type( 'php-define' );
		$db_user = $config->get_key( 'DB_USER' );
		$db_pass = $config->get_key( 'DB_PASSWORD' );

//echo 'dir=', $dir, PHP_EOL;
		$site_name = basename( rtrim( $dir, '\\/' ) );
		$site_path = rtrim( $dir, '\\/' );
		$ip_address = '127.0.0.1';
		$site_entry = array();
		$site_entry['siteName'] = $site_name;
		$site_entry['sitePath'] = $site_path;
		$site_entry['ipAddress'] = $ip_address;
		$site_entry['dbName'] = $db_user;
		$site_entry['dbUser'] = $db_user;
		$site_entry['dbPass'] = $db_pass;
var_export($site_entry);

		if ( $this->force || !isset( $this->pref['sites'][$site_name] ) ) {
			if ( $this->force )
				$this->msg( "Forcing update of site information for {$site_name}." );
			else
				$this->msg( "Updating site information for {$site_name}." );
			$this->pref['sites'][$site_name] = $site_entry;
			$this->dirty = TRUE;
		}
	}

	private function is_ds_running()
	{
		if ( DS_OS_WINDOWS ) {
			$ret = exec( 'tasklist', $output );
			$output = implode( '', $output );
			if ( FALSE !== strpos( $output, 'DesktopServer.exe' ) )
				return TRUE;
		} else if ( DS_OS_DARWIN ) {
			$ret = exec( 'ps -ax', $output );
			$output = implode( '', $output );
			if ( FALSE !== strpos( $output, 'DesktopServer.app' ) )
				return TRUE;
		}

		return FALSE;
	}

	private function load_preferences()
	{
		if ( !file_exists( $this->pref_file ) ) {
			// file doesn't exist, create a new one
			$username = get_current_user();
			if ( DS_OS_WINDOWS )
				$desktop = "C:\\Users\\{$username}\\Desktop";
			else if ( DS_OS_DARWIN )
				$desktop = "/Users/{$username}/Desktop";

			$data = array(
				'version' => '3.9.2',						// TODO: detect
				'edition' => 'Premium',						// TODO: detect
				'webOwner' => '',
				'dbUser' => 'root',
				'dbPass' => '',
				'browser' => $this->browser,
				'tld' => $this->tld,
				'ds-plugins' => array(),
				'desktop' => $desktop,
				'documents' => rtrim( $this->docdir, '\\/' ),
				'sites' => array(),
			);
			$json = json_encode( $data, JSON_PRETTY_PRINT );
			file_put_contents( $this->pref_file, $json );
		}

		$contents = file_get_contents( $this->pref_file );
		$this->pref = json_decode( $contents, TRUE );
//echo 'preferences=', var_export($this->pref, TRUE), PHP_EOL;
	}

	private function save_preferences()
	{
		$json = json_encode( $this->pref, JSON_PRETTY_PRINT );
//echo 'preferences=', $json, PHP_EOL;
//die('stop');
		file_put_contents( $this->pref_file, $json );
	}

	private function msg( $msg, $output = FALSE )
	{
		if ( $this->verbose || $output )
			echo $msg, PHP_EOL;
	}

	private function help()
	{
		echo 'Usage: prefrepair [-options] [document-directory]', PHP_EOL;
		echo 'Options:', PHP_EOL;
		echo ' -b:browser The default browser to use. One of "Safari", "Chrome" or "Firefox".', PHP_EOL;
		echo ' -f         Forces rewrite of site data entry, even if already exists.', PHP_EOL;
		echo ' -h         Display this help page', PHP_EOL;
		echo ' -t:tld     Specify Top Level Domain for your local sites. Default ".dev.cc"', PHP_EOL;
		echo ' -v         Verbose output', PHP_EOL;
		echo PHP_EOL;

		$username = get_current_user();
		if ( DS_OS_WINDOWS )
			$exdir = "C:\\Users\\{$username}\\Documents\\Websites\\";
		else if ( DS_OS_DARWIN )
			$exdir = "/Users/{$username}/Sites/";
		echo "document-directory is the directory where websites are located. Example: {$exdir}", PHP_EOL;
		exit(0);
	}
}

if ( 'cli' !== PHP_SAPI )
	die( 'This tool is designed to be run from the command line.' );

array_shift($argv);
$app = new DS_PrefRepair($argv);
$app->run();
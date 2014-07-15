<?php

$bundle = 'com.spr.seo';

require_once( 'workflows.php' );
require_once( 'request.php' );
require_once( 'functions.php' );

$w = new Workflows;

$data = $_SERVER[ 'HOME' ] . '/Library/Application Support/Alfred 2/Workflow Data/com.spr.seo';
$cache = $w->cache();

if ( ! file_exists( $data ) )
	mkdir( $data );
if ( ! file_exists( $cache ) )
	mkdir( $cache );

// This should run only on the first run ( or if the user has deleted the config.json file )
if ( ! file_exists( "$data/config.json" ) ) {
	$config[ 'services' ] = getDefaultServices();

	foreach ( $config[ 'services' ] as $service => $values ) :
		$config[ 'services' ][ $service ][ 'enabled' ] = 0;
	endforeach;

	// Let's enable Alexa by default
	$config[ 'services' ][ 'Alexa' ][ 'enabled' ] = 1;

	file_put_contents( "$data/config.json", json_encode( $config ) );
}


if ( file_exists( "$data/config.json") ) {
	$config = json_decode( file_get_contents( "$data/config.json" ), TRUE );
} else {
	// Redundant error handling
	$config[ 'services' ] = getDefaultServices();

	foreach ( $config[ 'services' ] as $service => $values ) :
		$config[ 'services' ][ $service ][ 'enabled' ] = 0;
	endforeach;

	$config[ 'services' ][ 'Alexa' ][ 'enabled' ] = 1;

	file_put_contents( "$data/config.json", json_encode( $config ) );
}

// Check if the argument is a valid URL
if ( isset( $argv[1] ) ) {
	$arg = $argv[1];

	// Remove http
	if ( strpos( $argv[1], 'http://' ) === 0 ) {
		$argv[1] = str_replace( 'http://', '', $argv[1] );
	}
	if ( strpos( $argv[1], 'https://' ) === 0 ) {
		$argv[1] = str_replace( 'https://', '', $argv[1] );
	}


	$domain = explode( '.', $argv[1] );
	if ( count( $domain ) > 1 ) {
		if ( ! checkTLD( $domain[ count( $domain ) - 1 ] ) ) {
			unset( $argv[1] );
			unset( $domain );
		} else {
			$domain = implode( '.', $domain );
		}
	} else {
		if ( ! preg_match( '/^c(o|on|onf|onfi|config|configu|configur|configure)$/', $argv[1] ) ) {
			unset( $argv[1] );
			unset( $domain );			
		} else {
			$arg = 'configure';
			unset( $domain );
		}
	}
}

// Let's get the favicon — if one exists — for the page for a bit of bling
if ( isset( $domain ) ) {
	if ( ! file_exists( "$cache/$domain.png" ) ) {
		$favicon = file_get_contents( "https://www.google.com/s2/favicons?domain=$domain" );
		file_put_contents( "$cache/$domain.png", $favicon );
	}
	$w->result( '', $domain, "Check SEO for $domain", '', "$cache/$domain.png", 'yes', '' );
}

// Running with configure, so let's just display that result
if ( isset( $arg ) && ( $arg == 'configure' ) ) {
	$w->result( '', 'configure', 'Configure SEO', 'Enable / Disable services  ||  Add default URLs  ||  Set credentials', "", 'yes', "" );
	echo $w->toxml();
	die();
}

// This checks to make sure that we have at least one service enabled
foreach( $config[ 'services' ] as $service => $values ) :
	if ( $values[ 'enabled' ] == 1 ) {
		$enabled = TRUE;
		break;
	}
endforeach;

// There are no enabled services, so show the error message and the configure fallback
if ( ( ! isset( $enabled ) ) || ( $enabled === FALSE ) ) {
	$w->result( '', '', "There are no enabled services", "Please enable some services", "", "", "" );
	$w->result( '', 'configure', 'Configure SEO', 'Enable / Disable services  ||  Add default URLs  ||  Set credentials', "", 'yes', "" );
	echo $w->toxml();
	die();
}

if ( isset( $config[ 'urls' ] ) && ( count( $config[ 'urls' ] ) > 0 ) ) {
	foreach ( $config[ 'urls' ] as $url ) :
		if ( ! file_exists( "$cache/$url.png" ) ) {
			$favicon = file_get_contents( "https://www.google.com/s2/favicons?domain=$url" );
			file_put_contents( "$cache/$url.png", $favicon );
		}
		$w->result( '', $url, "Check SEO for $url", '', "$cache/$url.png", 'yes', $url );
	endforeach;
}

if ( count( $w->results() ) < 1 )
	$w->result( '', '', "Check SEO Rankings", 'Just start typing to check a website', '', 'no', '' );

$w->result( '', 'configure', 'Configure SEO', 'Enable / Disable services  ||  Add default URLs  ||  Set credentials', "", 'yes', "" );

echo $w->toxml();


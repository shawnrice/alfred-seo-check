<?php

require_once( 'functions.php' );

// We need an argument to continue
if ( ! isset( $argv[1] ) )
	die();

if ( $argv[1] == 'configure' ) {
	$script = 'tell application "Alfred 2" to run trigger "configure" in workflow "com.spr.seo"';
	exec( "osascript -e '$script'" );
	die();
}

if ( preg_match( '/(en|dis)able$/', $argv[1], $matches ) ) {
	if ( $matches[0] == 'enable' ) {
		$service = str_replace( '-enable', '', $argv[1] );
		setServiceValue( $service, 'enabled', 1 );
		echo "'$service' has been enabled. You may have to set the credentials for it to work.";
		die();
	} else if ( $matches[0] == 'disable' ) {
		$service = str_replace( '-disable', '', $argv[1] );
		setServiceValue( $service, 'enabled', 0 );
		echo "'$service' has been disabled.";
		die();
	}
}

if ( strpos( $argv[1], 'add-' ) === 0 ) {
	$url = str_replace( 'add-', '', $argv[1] );
	addURL( $url );
	echo "'$url' has been added to the defaults list";
	die();
} else if ( strpos( $argv[1], 'remove-' ) === 0 ) {
	$url = str_replace( 'remove-', '', $argv[1] );
	removeURL( $url );
	echo "'$url' has been removed from the defaults list";
	die();
}

if ( strpos( $argv[1], 'set-' ) === 0 ) {
	$arg     = str_replace( 'set-', '', $argv[1] );
	$service = substr( $arg, 0, strpos(	$arg, '-' ) );
	$arg     = str_replace( $service . '-', '', $arg );
	$key     = substr( $arg, 0, strpos(	$arg, '-' ) );
	$arg     = str_replace( $key . '-', '', $arg );
	$value   = "$arg";
	
	setServiceValue( $service, $key, $value );
	echo "Set $key for $service to $value";
	die();
}

$script = 'tell application "Alfred 2" to run trigger "display" in workflow "com.spr.seo" with argument "' . $argv[1] . '"';
exec( "osascript -e '$script'" );
die();
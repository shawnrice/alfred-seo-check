<?php

require_once( 'workflows.php' );
require_once( 'functions.php' );

$w = new Workflows;
$cache = $w->cache();
if ( empty( $argv[1] ) || ( ! isset( $argv[1] ) ) ) {
	$w->result( '', 'conf-services', 'Enable / Disable / Configure Services', '' , '', 'no', 'services');
	$w->result( '', 'conf-defaults', 'Add / Remove Default URLs', '' , '', 'no', 'url');
	echo $w->toxml();
	die();
}

$config = loadConfig();

$services = array_keys( $config[ 'services' ] );
foreach( $services as $k => $v ) :
	$services[ $k ] = str_replace( ' ', '_', $v );
endforeach;


$arg = $argv[1];

if ( isset( $argv[2] ) )
	$subArg = $argv[2];

if ( in_array( $arg, $services) ) {
	// To alter Google Pagerank
	$arg = str_replace( '_', ' ', $arg );
	if ( $config[ 'services' ][ $arg ][ 'enabled' ] )
		$w->result( '', $arg . '-disable', "Disable $arg", '', "icons/" . $config[ 'services' ][ $arg ][ 'icon' ], '', '');
	else
		$w->result( '', $arg . '-enable', "Enable $arg", '', "icons/" . $config[ 'services' ][ $arg ][ 'icon' ], '', '');

	if ( isset( $config[ 'services' ][ $arg ][ 'credentials' ] ) ) {
		foreach ( $config[ 'services' ][ $arg ][ 'credentials' ] as $key => $val ) :
			if ( ! empty( $val ) )
				$subtitle = "Current Value: $val";
		    else
		    	$subtitle = "Currently not set";

			$w->result( '', '', "Set $key ", $subtitle, "icons/" . $config[ 'services' ][ $arg ][ 'icon' ], 'no', "$arg-set-$key");
		endforeach;
	}

	echo $w->toxml();
	die();
}

if ( strpos( $arg, '-set-' ) ) {
	$service = substr( $arg, 0, strpos(	$arg, '-set-' ) );
	$key = substr( $arg, strpos( $arg, '-set-' ) + 5 );

	if ( ! isset( $subArg ) )
		$w->result( '', '', "Set '$key' for $service", 'Just start typing the value', "icons/" . $config[ 'services' ][ $service ][ 'icon' ], 'no', '');
	else
		$w->result( '', "set-$service-$key-$subArg", "Set '$key' for $service", "Set to: '$subArg'", "icons/" . $config[ 'services' ][ $service ][ 'icon' ], 'yes', '');
	echo $w->toxml();
	die();
}


if ( $argv[1] == 'services' ) {
	foreach( $config[ 'services' ] as $service => $values ) :

		if ( isset( $subArg ) && ( stripos( $service, $subArg ) === FALSE ) )
			continue;

		$title = $service;

		if ( $values[ 'enabled' ] === 1 ) {
			$arg = "disable";
		} else {
			$arg = "enable";
		}
		$w->result( '', '', "Configure $title", '', 'icons/' . $values[ 'icon' ], 'no', str_replace( ' ', '_', $service ) );
	endforeach;
}

if ( $argv[1] == 'url' ) {
	if ( ! ( isset( $subArg ) ) ) {
		if ( isset( $config[ 'urls' ] ) && ( count( $config[ 'urls' ] > 0 ) ) ) {
			$w->result( '', '', 'Add URL', 'Just start typing' , '', '', '');
			foreach ( $config[ 'urls' ] as $value ) :
				if ( ! file_exists( "$cache/$value.png" ) ) {
					$favicon = file_get_contents( "https://www.google.com/s2/favicons?domain=$value" );
					file_put_contents( "$cache/$value.png", $favicon );
				}
				$w->result( '', "remove-$value", "Remove '$value' from default URL list", $value , "$cache/$value.png", '', '');
			endforeach;
		} else {
			$w->result( '', '', 'Add URL', 'Just start typing' , '', '', '');
		}
	} else {
		if ( checkDomain( $subArg ) ) {
			if ( strpos( $subArg, 'http://' ) === 0 ) {
				$subArg = str_replace( 'http://', '', $subArg );
			}
			if ( strpos( $subArg, 'https://' ) === 0 ) {
				$subArg = str_replace( 'https://', '', $subArg );
			}
			if ( ! file_exists( "$cache/$subArg.png" ) ) {
				$favicon = file_get_contents( "https://www.google.com/s2/favicons?domain=$subArg" );
				file_put_contents( "$cache/$subArg.png", $favicon );
			}
			$w->result( '', "add-$subArg", "Add '$subArg' to default URL list", "$subArg is a valid domain", "$cache/$subArg.png", 'yes', '');
		} else {
			$w->result( '', '', "Add $subArg to default URL list", "$subArg is not a valid domain", '', 'no', '');
		}
	}
	echo $w->toxml();
	die();
}

if ( strpos( $argv[1], 'set-' ) === 0 ) {
	$args = str_replace( 'set-', '', $argv[1] );
	preg_match( '/^([a-zA-Z_]*)/', $args, $service );
	$service = $service[0];
	$args = str_replace( $service . '-', '', $args );
	preg_match( '/^([a-zA-Z_]*)/', $args, $key );
	$key = $key[0];
	$value = str_replace( $key, '', $args );
	if ( isset( $argv[2] ) )
		$value = $argv[2];
	$w->result( '', '', "$service: $key : $value ", '', '', '' );
	// echo $value;

}

echo $w->toxml();
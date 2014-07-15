<?php

require_once( 'workflows.php' );
require_once( 'request.php' );
require_once( 'functions.php' );

$w = new Workflows;
$cache = $w->cache();
if ( ! file_exists( $cache ) ) {
	mkdir( $cache );
}

$config = loadConfig();
$url = $argv[1];

if ( ! ( isset( $url ) && checkDomain( $url ) ) )
	die();

foreach ( $config[ 'services' ] as $service => $values ):
	if ( $values[ 'enabled' ] === 1 )
		$services[] = $service;
endforeach;

foreach ( $services as $service ) :
	$s = str_replace( ' ', '_', $service );
	$r = new $s;
	$r->doRequest( $url );
	if ( ! file_exists( "$cache/$url.png" ) ) {
		$favicon = file_get_contents( "https://www.google.com/s2/favicons?domain=$url" );
		file_put_contents( "$cache/$url.png", $favicon );
	}

	$w->result( '', '', "$service", $r->getRank(), 'icons/' . $config[ 'services' ][ $service ][ 'icon' ], '', '' );
endforeach;

echo $w->toxml();
die();
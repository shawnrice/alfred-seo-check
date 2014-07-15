<?php


class SEORequest
{
	private $url;
	private $results;

	private $salt;
	private $bundle;
	private $data;

	public function __construct() {
		$this->salt  = 'ereNDrpSgW';
		$this->bundle  = 'com.spr.seo';
		$this->data    = $_SERVER[ 'HOME' ] . '/Library/Application Support/Alfred 2/Workflow Data/' . $this->bundle;
	}

	public function setURL( $url ) {
		$this->url = $url;
	}

	public function doRequest( $page ) {
		$request = $this->url . "$page";
		$this->results = simplexml_load_string( file_get_contents( "$request" ) );
	}

	public function setValue( $service, $key, $value ) {
		$config = json_decode( file_get_contents( "$this->data" . '/config.json' ), TRUE );
		$config[ 'services' ][ $service ][ $key ] = $value;
		file_put_contents( "$data/config.json", json_ecode( $config ) );
	}

	public function getValue( $service, $key ) {
		$config = json_decode( file_get_contents( "$this->data" . '/config.json' ), TRUE );
		if ( isset( $config[ 'services' ][ $service ][ $key ] ) )
			return $config[ 'services' ][ $service ][ $key ];
		else
			return FALSE;
	}

	/**
	 * Encrypts a string with a salted ssl base64 encoding
	 * @param  string $string a string, here, we use a password string
	 * @return string         an encoded password string
	 */
	public function encrypt_string( $string ) {
		
		$string  = $this->salt . $string . $salt;
		$cmd = 'out=$(echo "' . $string . '" | openssl base64 -e); echo "${out}"';
		return exec( $cmd );
	}

	/**
	 * Decrypts a salted ssl base64 encoding string
	 * @param  string $string encrypted password string
	 * @return string         decrypted password string
	 */
	public function decrypt_string( $string ) {
		
		$cmd   = 'out=$(echo "' . $string . '" | openssl base64 -d); echo "${out}"';
		return str_replace( $this->salt, '', exec( $cmd ) );
		
	}
}

class Ahrefs extends SEORequest
{
	private $service = 'ahrefs';
	public function doRequest( $page ) {
		$this->bundle  = 'com.spr.seo';
		$this->data    = $_SERVER[ 'HOME' ] . '/Library/Application Support/Alfred 2/Workflow Data/' . $this->bundle;
		$config        = json_decode( file_get_contents( "$this->data" . '/config.json' ), TRUE );
		$token         = $config[ 'services' ][ 'ahrefs' ][ 'credentials' ][ 'token' ];
		$url           = "http://apiv2.ahrefs.com/?from=ahrefs_rank&target=";
		$url          .= $page;
		$url          .= "&mode=domain&limit=1&order_by=ahrefs_rank%3Adesc&output=json&token=";
		$url          .= $token;
		$this->results = json_decode( file_get_contents( $url ), TRUE );
	}

	public function getRank() {
		if ( isset( $this->results[ 'error' ] ) )
			return "Error: " . $this->results[ 'error' ];
		return 'Ahrefs Rank: ' . $this->results[ 'pages' ][0][ 'ahrefs_rank' ];
	}
}

class Mozscape extends SEORequest
{
	private $service = 'Mozscape';

	public function doRequest( $page ) {
		$this->bundle = 'com.spr.seo';
		$this->data   = $_SERVER[ 'HOME' ] . '/Library/Application Support/Alfred 2/Workflow Data/' . $this->bundle;
		$config       = json_decode( file_get_contents( "$this->data" . '/config.json' ), TRUE );
		$accessID     = $config[ 'services' ][ 'Mozscape' ][ 'credentials' ][ 'accessID' ];
		$secretKey    = $config[ 'services' ][ 'Mozscape' ][ 'credentials' ][ 'secretKey' ];

		$cols = 34359738368 + 68719476736;
		// upa - 34359738368 -- the are the cols
		// pda - 68719476736 -- the are the cols

		// Expires in five minutes
		$expires = time() + 300;

		// Create signature
		$signature = urlencode( base64_encode( hash_hmac( 'sha1', $accessID . "\n" . $expires, $secretKey, TRUE ) ) );

		$url = "http://lsapi.seomoz.com/linkscape/url-metrics/" . 
			urlencode( $page ) . 
			'?Cols=' 	 	. $cols . 
			'&AccessID=' 	. $accessID . 
			'&Expires=' 	. $expires . 
			'&Signature=' 	. $signature;
		$this->results = json_decode( file_get_contents( $url ), TRUE );
	}

	public function getRank() {
		if ( isset( $this->results[ 'status' ] ) )
			return "Error: Check your credentials and try again.";
		return "DA: " . round( $this->results[ 'pda' ] ) . " | PA: " . round( $this->results[ 'upa' ] );
	}

}

class Google_Pagerank extends SEORequest
{
    // Most code from here is a slight modification of ...
    // http://www.ewhathow.com/2013/09/how-to-get-google-page-rank-from-php/

    private $host;
    private $agent;
     
    //return the pagerank figure
    public function doRequest( $url ) {
        $host  = 'toolbarqueries.google.com';
        $agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.6) Gecko/20060728 Firefox/1.5';

        $hash = $this->hashURL( $url );
        $check = $this->checkHash( $hash );

        $fp = fsockopen( $host, 80, $errno, $errstr, 30 );
        if ( $fp ) {
           $out  = "GET /tbr?client=navclient-auto&ch=$check&features=Rank&q=info:$url HTTP/1.1\r\n";
           $out .= "User-Agent: " . $agent . "\r\n";
           $out .= "Host: " . $host . "\r\n";
           $out .= "Connection: Close\r\n\r\n";
         
           fwrite( $fp, $out );
            
           //$pagerank = substr( fgets( $fp, 128 ), 4 ); //debug only
           while ( ! feof( $fp ) ) :
                $data = fgets( $fp, 128 );
                $pos = strpos( $data, "Rank_" );

                if( $pos !== FALSE ) {
                    $pr = substr( $data, $pos + 9 );
                    $pr = trim( $pr );
                    $pr = str_replace( "\n",'',$pr );
                    $this->result = $pr;
                    break;
                }
           endwhile;
           fclose( $fp );
        }
    }
     
    //PageRank Lookup v1.1 by HM2K
    //convert a string to a 32-bit integer
    public function strToNum( $str, $check, $magic ) {
        $int = 4294967296;  // 2^32
     
        $length = strlen( $str );
        for ( $i = 0; $i < $length; $i++) {
            $check *= $magic;   
            //If the float is beyond the boundaries of integer ( usually +/- 2.15e+9 = 2^31 ), 
            //  the result of converting to integer is undefined
            //  refer to http://www.php.net/manual/en/language.types.integer.php
            if ( $check >= $int ) {
                $check = ( $check - $int * ( int ) ( $check / $int ) );
                //if the check less than -2^31
                $check = ( $check < -2147483648 ) ? ( $check + $int ) : $check;
            }
            $check += ord( $str{$i}); 
        }
        return $check;
    }
     
    //genearate a hash for a url
    public function hashURL( $url ) {
        $check = $this->strToNum( $url, 0x1505, 0x21 );
        $recheck = $this->strToNum( $url, 0, 0x1003F );
     
        $check >>= 2;    
        $check = ( ( $check >> 4 ) & 0x3FFFFC0 ) | ( $check & 0x3F );
        $check = ( ( $check >> 4 ) & 0x3FFC00 )  | ( $check & 0x3FF );
        $check = ( ( $check >> 4 ) & 0x3C000 )   | ( $check & 0x3FFF );   
         
        $T1 = ( ( ( ( $check & 0x3C0 ) << 4 ) | ( $check & 0x3C ) ) << 2 ) | ( $recheck & 0xF0F );
        $T2 = ( ( ( ( $check & 0xFFFFC000 ) << 4 ) | ( $check & 0x3C00 ) ) << 0xA ) | ( $recheck & 0xF0F0000 );
         
        return ( $T1 | $T2 );
    }
     
    //genearate a checksum for the hash string
    public function checkHash( $hash ) {
        $check = 0;
        $flag = 0;
     
        $hash = sprintf( '%u', $hash ) ;
        $length = strlen( $hash );
         
        for ( $i = $length - 1;  $i >= 0;  $i --) :
            $Re = $hash{$i};
            if ( 1 === ( $flag % 2 ) ) {              
                $Re += $Re;     
                $Re = ( int )( $Re / 10 ) + ( $Re % 10 );
            }
            $check += $Re;
            $flag ++;   
        endfor;
     
        $check %= 10;
        if ( 0 !== $check ) {
            $check = 10 - $check;
            if ( 1 === ( $flag % 2 ) ) {
                if ( 1 === ( $check % 2 ) ) {
                    $check += 9;
                }
                $check >>= 1;
            }
        }
     
        return '7'.$check.$hash;
    }

    public function getRank() {
    	return 'Pagerank: ' . $this->result;
    }
}


class Alexa extends SEORequest
{

	public function doRequest( $page ) {
		$this->url = "http://data.alexa.com/data?cli=10&dat=s&url=";
		$request = $this->url . "$page";
		$this->results = simplexml_load_string( file_get_contents( "$request" ) );
	}

	public function getRank() {
		return 'Alexa Pagerank: ' . ( int ) $this->results->SD[1]->POPULARITY->attributes()->TEXT;
	}
}
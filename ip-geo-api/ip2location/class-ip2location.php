<?php
if ( class_exists( 'IP_Geo_Block_API' ) ) :

/**
 * URL and Path for IP2Location database
 *
 */
define( 'IP_GEO_BLOCK_IP2LOC_IPV4_DAT', 'IP2LOCATION-LITE-DB1.BIN' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV6_DAT', 'IP2LOCATION-LITE-DB1.IPV6.BIN' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV4_ZIP', 'http://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV6_ZIP', 'http://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.IPV6.BIN.ZIP' );

/**
 * Class for IP2Location
 *
 * URL         : http://www.ip2location.com/
 * Term of use : http://www.ip2location.com/terms
 * Licence fee : Creative Commons Attribution-ShareAlike 4.0 Unported License
 * Input type  : IP address (IPv4)
 * Output type : array
 */
class IP_Geo_Block_API_IP2Location extends IP_Geo_Block_API {
	protected $transform_table = array(
		'countryCode' => 'countryCode',
		'countryName' => 'countryName',
		'regionName'  => 'regionName',
		'cityName'    => 'cityName',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);

	public function get_location( $ip, $args = array() ) {
		require_once( 'IP2Location.php' );

		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$file = apply_filters(
				IP_Geo_Block::PLUGIN_SLUG . 'ip2location-path',
				dirname( __FILE__ ) . '/' . IP_GEO_BLOCK_IP2LOC_IPV4_DAT
			 );
			$type = IP_GEO_BLOCK_API_TYPE_IPV4;
		}
		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$file = dirname( __FILE__ ) .  '/' . IP_GEO_BLOCK_IP2LOC_IPV6_DAT;
			$type = IP_GEO_BLOCK_API_TYPE_IPV6;
		}
		else {
			return array( 'errorMessage' => 'illegal format' );
		}

		try {
			$geo = new IP2Location( $file );
			if ( $geo && ( $geo->get_database_type() & $type ) ) {
				$res = array();
				$data = $geo->lookup( $ip );
				foreach ( $this->transform_table as $key => $val ) {
					if ( ! empty( $val ) && ! empty( $data->$val ) )
						$res[ $key ] = $data->$val;
				}

				if ( isset( $res['countryCode'] ) && strlen( $res['countryCode'] ) === 2 ) {
					if ( is_string( $res['latitude' ] ) ) unset( $res['latitude' ] );
					if ( is_string( $res['longitude'] ) ) unset( $res['longitude'] );
					return $res;
				}
			}
		}

		catch (Exception $e) {
			return array( 'errorMessage' => $e->getMessage() );
		}

		return array( 'errorMessage' => 'Not supported' );
	}

	public function download( &$db, $args ) {
		require_once( IP_GEO_BLOCK_PATH . 'includes/download.php' );

		$dir = trailingslashit( apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-ip2location-dir', dirname( __FILE__ )
		) );

		$res['ipv4'] = ip_geo_block_download_zip(
			apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-ip2location-zip-ipv4', IP_GEO_BLOCK_IP2LOC_IPV4_ZIP ),
			$args,
			$dir . IP_GEO_BLOCK_IP2LOC_IPV4_DAT,
			$db['ipv4_last']
		);

		$db['ipv4_path'] = ! empty( $res['ipv4']['filename'] ) ? $res['ipv4']['filename'] : 0;
		$db['ipv4_last'] = ! empty( $res['ipv4']['modified'] ) ? $res['ipv4']['modified'] : 0;

		$res['ipv6'] = ip_geo_block_download_zip(
			apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-ip2location-zip-ipv6', IP_GEO_BLOCK_IP2LOC_IPV6_ZIP ),
			$args,
			$dir . IP_GEO_BLOCK_IP2LOC_IPV6_DAT,
			$db['ipv6_last']
		);

		$db['ipv6_path'] = ! empty( $res['ipv6']['filename'] ) ? $res['ipv6']['filename'] : 0;
		$db['ipv6_last'] = ! empty( $res['ipv6']['modified'] ) ? $res['ipv6']['modified'] : 0;

		return $res;
	}
}

/**
 * Register API
 *
 */
IP_Geo_Block_Provider::register_addon( array(
	'IP2Location' => array(
		'key'  => NULL,
		'type' => 'IPv4 / free, need an attribution link',
		'link' => '<a class="ip-geo-block-link" href="http://www.ip2location.com/free/plugins" title="Free Plugins | IP2Location.com" rel=noreferrer target=_blank>http://www.ip2location.com/</a>&nbsp;(IPv4 / free, need an attribution link)',
	),
) );

endif;
?>
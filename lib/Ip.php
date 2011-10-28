<?php

/**
* @author "Ashar Voultoiz" <hashar@altern.org>
* @author "Tim Rupp" <caphrim007@gmail.com>
* @license GPL v2 or later
*
* This work is taken directly from Mediawiki
* Many code additions added by Tim Rupp
*/

// Some regex definition to "play" with IP address and IP address blocks

// An IP is made of 4 bytes from x00 to xFF which is d0 to d255
define( 'RE_IP_BYTE', '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])');
define( 'RE_IP_ADD' , RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE . '\.' . RE_IP_BYTE );

// An IPv4 block is an IP address and a prefix (d1 to d32)
define( 'RE_IP_PREFIX', '(3[0-2]|[12]?\d)');
define( 'RE_IP_BLOCK', RE_IP_ADD . '\/' . RE_IP_PREFIX);

// For IPv6 canonicalization (NOT for strict validation; these are quite lax!)
define( 'RE_IPV6_WORD', '([0-9A-Fa-f]{1,4})' );
define( 'RE_IPV6_GAP', ':(?:0+:)*(?::(?:0+:)*)?' );
define( 'RE_IPV6_V4_PREFIX', '0*' . RE_IPV6_GAP . '(?:ffff:)?' );

// An IPv6 block is an IP address and a prefix (d1 to d128)
define( 'RE_IPV6_PREFIX', '(12[0-8]|1[01][0-9]|[1-9]?\d)');

// An IPv6 IP is made up of 8 octets. However abbreviations like "::" can be used. This is lax!
define( 'RE_IPV6_ADD', '(:(:' . RE_IPV6_WORD . '){1,7}|' . RE_IPV6_WORD . '(:{1,2}' . RE_IPV6_WORD . '|::$){1,7})' );
define( 'RE_IPV6_BLOCK', RE_IPV6_ADD . '\/' . RE_IPV6_PREFIX );

// This might be useful for regexps used elsewhere, matches any IPv6 or IPv6 address or network
define( 'IP_ADDRESS_STRING', RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)|' . RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)');

/**
* A collection of public static functions to play with IP address
* and IP blocks.
*/
class Ip {
	const IDENT = __CLASS__;

	private static $patterns;

	public static function getPatterns() {
		return self::$patterns;
	}

	/**
	 * Given a string, determine if it as valid IP
	 * Unlike isValid(), this looks for networks too
	 * @param $ip IP address.
	 * @return string
	 */
	public static function isIpAddress($ip) {
		if ( !$ip ) return false;
		if ( is_array( $ip ) ) {
			return false;
		}

		if (self::isIPv4($ip)) {
			return true;
		} else if (self::isIPv6($ip)) {
			return true;
		} else {
			return false;
		}
		// IPv6 IPs with two "::" strings are ambiguous and thus invalid
		//return preg_match( '/^' . IP_ADDRESS_STRING . '$/', $ip) && ( substr_count($ip, '::') < 2 );
	}
	
	public static function isIPv6($ip) {
		if (!$ip) {
			return false;
		}

		if( is_array( $ip ) ) {
			return false;
		}

		if ($ip == '::' || $ip == '::/0') {
			return true;
		}

		// IPv6 IPs with two "::" strings are ambiguous and thus invalid
		$pattern = '/^' . RE_IPV6_ADD . '(\/' . RE_IPV6_PREFIX . '|)$/';
		self::$patterns[] = $pattern;
		$result = preg_match( $pattern, $ip) && ( substr_count($ip, '::') < 2);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function isIPv4($ip) {
		if ( !$ip ) {
			return false;
		}

		$pattern = '/^' . RE_IP_ADD . '(\/' . RE_IP_PREFIX . '|)$/';
		self::$patterns[] = $pattern;

		$result = preg_match($pattern, $ip);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function isCidr($cidr) {
		if (!$cidr) {
			return false;
		}

		$result = preg_match('/^' . RE_IP_BLOCK . '|' . RE_IPV6_BLOCK . '$/', $cidr);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function isRange($range) {
		if (!$range) {
			return false;
		}

		$range = str_replace(' ','',$range);
		$parts = explode('-',$range);

		if (count($parts) != 2) {
			return false;
		}

		if (!self::isIpAddress($parts[0]) || !self::isIpAddress($parts[1])) {
			return false;
		}
		$result = preg_match('/^'. RE_IP_ADD . '|' . RE_IPV6_ADD . '-' . RE_IP_ADD . '|' . RE_IPV6_ADD . '$/', $range);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function isIPv6Address($ip) {
		if ( !$ip ) return false;
		if( is_array( $ip ) ) {
			return false;
		}

		// IPv6 IPs with two "::" strings are ambiguous and thus invalid
		$result = preg_match( '/^' . RE_IPV6_ADD . '$/', $ip) && ( substr_count($ip, '::') < 2);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function isIPv6AddressHex($ip) {
		$hasPrefix = false;
		if (!$ip) return false;
		if (substr($ip, 0, 3) == 'v6-') {
			$hasPrefix = true;
		}

		if (preg_match('/[^0-9A-H]+/i', $ip) && !$hasPrefix) {
			return false;
		}

		// Because someone could specify a raw hex
		// v6 address to this function even though
		// the rest of the class assumes a v6- prefix
		$ip = 'v6-'.$ip;

		if (self::isIPv6Address(self::fromHex($ip))) {
			return true;
		} else {
			return false;
		}
	}

	public static function isIPv6Net($ip) {
		if (!$ip) return false;

		$result = preg_match('/^'. RE_IPV6_BLOCK .'$/', $ip);
		if ($result == 1) {
			return true;
		} else {
			return false;
		}
	}

	public static function isIPv6Netmask($ip) {
		if ( !$ip ) return false;
		$result = preg_match( '/^' . RE_IPV6_ADD . '\/' . RE_IPV6_ADD . '$/', $ip);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function stripIPv6Prefix($address) {
		if (substr($address, 0, 3) == 'v6-') {
			return substr($address, 3);
		} else {
			return $address;
		}
	}

	public static function isIPv4Address($ip) {
		if ( !$ip ) return false;
		$result = preg_match( '/^' . RE_IP_ADD .'$/', $ip);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	public static function isIPv4AddressHex($ip) {
		if (!$ip) return false;

		if (preg_match('/[^0-9A-H]+/i', $ip) ) {
			return false;
		}

		if (self::isIPv4Address(self::fromHex($ip))) {
			return true;
		} else {
			return false;
		}
	}

	public static function isIPv4Net($ip) {
		if (!$ip) return false;

		$result = preg_match('/^'. RE_IP_BLOCK .'$/', $ip);
		if ($result == 1) {
			return true;
		} else {
			return false;
		}
	}

	public static function isIPv4WLongNetmask($ip) {
		if ( !$ip ) return false;
		$result = preg_match( '/^' . RE_IP_ADD . '\/' . RE_IP_ADD . '$/', $ip);

		if ($result < 1) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Given an IP address in dotted-quad notation, returns an IPv6 octet.
	 * See http://www.answers.com/topic/ipv4-compatible-address
	 * IPs with the first 92 bits as zeros are reserved from IPv6
	 * @param $ip quad-dotted IP address.
	 * @return string 
	 */
	public static function IPv4toIPv6( $ip ) {
		if ( !$ip ) return null;
		if ( !self::isIPv4( $ip ) ) return null;
		// Convert only if needed
		if ( self::isIPv6( $ip ) ) return $ip;
		// IPv4 CIDRs
		if ( strpos( $ip, '/' ) !== false ) {
			$parts = explode( '/', $ip, 2 );
			if ( count( $parts ) != 2 ) {
				return false;
			}
			$network = self::toUnsigned( $parts[0] );
			if ( $network !== false && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 32 ) {
				$bits = $parts[1] + 96;
				return self::toOctet( $network ) . "/$bits";
			} else {
				return false;
			}
		}
		return self::toOctet( self::toUnsigned( $ip ) );
	}

	/**
	 * Given an IPv6 address in octet notation, returns an unsigned integer.
	 * @param $ip octet ipv6 IP address.
	 * @return string
	 */
	public static function toUnsigned6( $ip ) {
		if ( !$ip ) return null;

		$ip = explode(':', self::sanitizeIp( $ip ) );
		$r_ip = '';

		foreach ($ip as $v) {
			$r_ip .= str_pad( $v, 4, 0, STR_PAD_LEFT );
		}

		$r_ip = self::wfBaseConvert( $r_ip, 16, 10 );
		return $r_ip;
	}
	
	/**
	 * Given an IPv6 address in octet notation, returns the expanded octet.
	 * IPv4 IPs will be trimmed, thats it...
	 * @param $ip octet ipv6 IP address.
	 * @return string 
	 */	
	public static function sanitizeIp( $ip ) {
		if ( !$ip ) return null;
		if ( self::isIPv4($ip) ) return trim($ip);
		if ( !self::isIPv6($ip) ) return $ip;
		$ip = strtoupper( trim($ip) );
		// Expand zero abbreviations
		if ( strpos( $ip, '::' ) !== false ) {
			$ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')) . ':', $ip);
		}

		// For IPs that start with "::", correct the final IP so that it starts with '0' and not ':'
		if ( $ip[0] == ':' ) $ip = "0$ip";

		// Remove leading zereos from each bloc as needed
		$ip = preg_replace( '/(^|:)0+' . RE_IPV6_WORD . '/', '$1$2', $ip );
		return $ip;
	}
	
	/**
	 * Given an unsigned integer, returns an IPv6 address in octet notation
	 * @param $ip integer IP address.
	 * @return string 
	 */
	public static function toOctet( $ip_int ) {
		// Convert to padded uppercase hex
		$ip_hex = self::wfBaseConvert($ip_int, 10, 16, 32, false);

		// Seperate into 8 octets
		$ip_oct = substr( $ip_hex, 0, 4 );
		for ($n=1; $n < 8; $n++) {
			$ip_oct .= ':' . substr($ip_hex, 4*$n, 4);
		}

		// NO leading zeroes
		$ip_oct = preg_replace( '/(^|:)0+' . RE_IPV6_WORD . '/', '$1$2', $ip_oct );
		return $ip_oct;
	}

	/**
	 * Convert a network specification in IPv6 CIDR notation to an integer network and a number of bits
	 * @return array(string, int)
	 */
	public static function parseCidr6( $range ) {
		# Expand any IPv6 IP
		$parts = explode( '/', self::sanitizeIp( $range ), 2 );
		if ( count( $parts ) != 2 ) {
			return array( false, false );
		}
		$network = self::toUnsigned6( $parts[0] );
		if ( $network !== false && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 128 ) {
			$bits = $parts[1];
			if ( $bits == 0 ) {
				$network = 0;
			} else {
				# Native 32 bit functions WONT work here!!!
				# Convert to a padded binary number
				$network = self::wfBaseConvert( $network, 10, 2, 128 );

				# Truncate the last (128-$bits) bits and replace them with zeros
				$network = str_pad( substr( $network, 0, $bits ), 128, 0, STR_PAD_RIGHT );

				# Convert back to an integer
				$network = self::wfBaseConvert( $network, 2, 10 );
			}
		} else {
			$network = false;
			$bits = false;
		}
		return array( $network, $bits );
	}
	
	/**
	 * Given a string range in a number of formats, return the start and end of 
	 * the range in hexadecimal. For IPv6.
	 *
	 * Formats are:
	 *     2001:0db8:85a3::7344/96          			 CIDR
	 *     2001:0db8:85a3::7344 - 2001:0db8:85a3::7344   Explicit range
	 *     2001:0db8:85a3::7344/96             			 Single IP
	 * @return array(string, int)
	 */
	public static function parseRange6( $range ) {
		# Expand any IPv6 IP
		$range = self::sanitizeIp( $range );
		if ( strpos( $range, '/' ) !== false ) {
			# CIDR
			list( $network, $bits ) = self::parseCIDR6( $range );
			if ( $network === false ) {
				$start = $end = false;
			} else {
				$start = self::wfBaseConvert( $network, 10, 16, 32, false );
				# Turn network to binary (again)
				$end = self::wfBaseConvert( $network, 10, 2, 128 );
				# Truncate the last (128-$bits) bits and replace them with ones
				$end = str_pad( substr( $end, 0, $bits ), 128, 1, STR_PAD_RIGHT );
				# Convert to hex
				$end = self::wfBaseConvert( $end, 2, 16, 32, false );
				# see toHex() comment
				$start = "v6-$start"; $end = "v6-$end";
			}
		} elseif ( strpos( $range, '-' ) !== false ) {
			# Explicit range
			list( $start, $end ) = array_map( 'trim', explode( '-', $range, 2 ) );
			$start = self::toUnsigned6( $start ); $end = self::toUnsigned6( $end );
			if ( $start > $end ) {
				$start = $end = false;
			} else {
				$start = self::wfBaseConvert( $start, 10, 16, 32, false );
				$end = self::wfBaseConvert( $end, 10, 16, 32, false );
			}
			# see toHex() comment
			$start = "v6-$start"; $end = "v6-$end";
		} else {
			# Single IP
			$start = $end = self::toHex( $range );
		}
		if ( $start === false || $end === false ) {
			return array( false, false );
		} else {
			return array( $start, $end );
		}
	}
	
	/**
	 * Validate an IP address.
	 * @return boolean True if it is valid.
	 */
	public static function isValid( $ip ) {
		return ( preg_match( '/^' . RE_IP_ADD . '$/', $ip) || preg_match( '/^' . RE_IPV6_ADD . '$/', $ip) );
	}

	/**
	 * Validate an IP Block.
	 * @return boolean True if it is valid.
	 */
	public static function isValidBlock( $ipblock ) {
		return ( count(self::toArray($ipblock)) == 1 + 5 );
	}

	/**
	 * Determine if an IP address really is an IP address, and if it is public,
	 * i.e. not RFC 1918 or similar
	 * Comes from ProxyTools.php
	 */
	public static function isPublic( $ip ) {
		$n = self::toUnsigned( $ip );
		if ( !$n ) {
			return false;
		}

		// ip2long accepts incomplete addresses, as well as some addresses
		// followed by garbage characters. Check that it's really valid.
		if( $ip != long2ip( $n ) ) {
			return false;
		}

		static $privateRanges = false;
		if ( !$privateRanges ) {
			$privateRanges = array(
				array( '10.0.0.0',    '10.255.255.255' ),   # RFC 1918 (private)
				array( '172.16.0.0',  '172.31.255.255' ),   #     "
				array( '192.168.0.0', '192.168.255.255' ),  #     "
				array( '0.0.0.0',     '0.255.255.255' ),    # this network
				array( '127.0.0.0',   '127.255.255.255' ),  # loopback
			);
		}

		foreach ( $privateRanges as $r ) {
			$start = self::toUnsigned( $r[0] );
			$end = self::toUnsigned( $r[1] );
			if ( $n >= $start && $n <= $end ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Split out an IP block as an array of 4 bytes and a mask,
	 * return false if it can't be determined
	 *
	 * @param $ip string A quad dotted/octet IP address
	 * @return array
	 */
	public static function toArray( $ipblock ) {
		$matches = array();
		if( preg_match( '/^' . RE_IP_ADD . '(?:\/(?:'.RE_IP_PREFIX.'))?' . '$/', $ipblock, $matches ) ) {
			return $matches;
		} else if ( preg_match( '/^' . RE_IPV6_ADD . '(?:\/(?:'.RE_IPV6_PREFIX.'))?' . '$/', $ipblock, $matches ) ) {
			return $matches;
		} else {
			return false;
		}
	}

	/**
	 * Return a zero-padded hexadecimal representation of an IP address.
	 *
	 * Hexadecimal addresses are used because they can easily be extended to
	 * IPv6 support. To separate the ranges, the return value from this 
	 * function for an IPv6 address will be prefixed with "v6-", a non-
	 * hexadecimal string which sorts after the IPv4 addresses.
	 *
	 * @param $ip Quad dotted/octet IP address.
	 * @return hexidecimal
	 */
	public static function toHex( $ip ) {
		$n = self::toUnsigned( $ip );
		if ( $n !== false ) {
			if ( self::isIPv6($ip) ) {
				$n = "v6-" . self::wfBaseConvert( $n, 10, 16, 32, false );
			} else {
				$n = self::wfBaseConvert( $n, 10, 16, 8, false );
			}
		}
		return $n;
	}

	public static function fromHex($ip) {
		if (!$ip) return false;
		if (self::isIPAddress($ip)) return $ip;

		if (substr($ip, 0, 3) == 'v6-') {
			$ip = substr($ip, 3);
			$tmp = str_split($ip, 4);
			$result = strtolower(self::sanitizeIp(implode(':', $tmp)));
			return $result;
		} else {
			$tmp = explode('.', chunk_split($ip, 2, '.'));

			// Rebuild dotquad IP address
			return hexdec($tmp[0]). '.' . hexdec($tmp[1]) . '.' . hexdec($tmp[2]) . '.' . hexdec($tmp[3]);
		}
	}

	/**
	 * Given an IP address in dotted-quad/octet notation, returns an unsigned integer.
	 * Like ip2long() except that it actually works and has a consistent error return value.
	 * Comes from ProxyTools.php
	 * @param $ip Quad dotted IP address.
	 * @return integer
	 */
	public static function toUnsigned( $ip ) {
		// Use IPv6 functions if needed
		if ( self::isIPv6( $ip ) ) {
			return self::toUnsigned6( $ip );
		}
		if ( $ip == '255.255.255.255' ) {
			$n = -1;
		} else {
			$n = ip2long( $ip );
			if ( $n == -1 || $n === false ) { # Return value on error depends on PHP version
				$n = false;
			}
		}
		if ( $n < 0 ) {
			$n += pow( 2, 32 );
		}
		return $n;
	}

	/**
	 * Convert a dotted-quad IP to a signed integer
	 * Returns false on failure
	 */
	public static function toSigned( $ip ) {
		if ( $ip == '255.255.255.255' ) {
			$n = -1;
		} else {
			$n = ip2long( $ip );
			if ( $n == -1 ) {
				$n = false;
			}
		}
		return $n;
	}

	/**
	 * Convert a network specification in CIDR notation to an integer network and a number of bits
	 * @return array(string, int)
	 */
	public static function parseCidr( $range ) {
		$parts = explode( '/', $range, 2 );
		if ( count( $parts ) != 2 ) {
			return array( false, false );
		}
		$network = self::toSigned( $parts[0] );
		if ( $network !== false && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 32 ) {
			$bits = $parts[1];
			if ( $bits == 0 ) {
				$network = 0;
			} else {
				$network &= ~((1 << (32 - $bits)) - 1);
			}
			# Convert to unsigned
			if ( $network < 0 ) {
				$network += pow( 2, 32 );
			}
		} else {
			$network = false;
			$bits = false;
		}
		return array( $network, $bits );
	}

	/**
	 * Given a string range in a number of formats, return the start and end of 
	 * the range in hexadecimal.
	 *
	 * Formats are:
	 *     1.2.3.4/24          CIDR
	 *     1.2.3.4 - 1.2.3.5   Explicit range
	 *     1.2.3.4             Single IP
	 * 
	 *     2001:0db8:85a3::7344/96          			 CIDR
	 *     2001:0db8:85a3::7344 - 2001:0db8:85a3::7344   Explicit range
	 *     2001:0db8:85a3::7344             			 Single IP
	 * @return array(string, int)
	 */
	public static function parseRange( $range ) {
		// Use IPv6 functions if needed
		if ( self::isIPv6( $range ) ) {
			return self::parseRange6( $range );
		}
		if ( strpos( $range, '/' ) !== false ) {
			# CIDR
			list( $network, $bits ) = self::parseCIDR( $range );
			if ( $network === false ) {
				$start = $end = false;
			} else {
				$start = sprintf( '%08X', $network );
				$end = sprintf( '%08X', $network + pow( 2, (32 - $bits) ) - 1 );
			}
		} elseif ( strpos( $range, '-' ) !== false ) {
			# Explicit range
			list( $start, $end ) = array_map( 'trim', explode( '-', $range, 2 ) );
			$start = self::toUnsigned( $start );
			$end = self::toUnsigned( $end );
			if ( $start > $end ) {
				$start = $end = false;
			} else {
				$start = sprintf( '%08X', $start );
				$end = sprintf( '%08X', $end );
			}
		} else {
			# Single IP
			$start = $end = self::toHex( $range );
		}
		if ( $start === false || $end === false ) {
			return array( false, false );
		} else {				
			return array( $start, $end );
		}
	}

	/**
	* Determine if a given IPv4/IPv6 address is in a given CIDR network
	* @param $addr The address to check against the given range.
	* @param $range The range to check the given address against.
	* @return bool Whether or not the given address is in the given range.
	*/
	public static function isInRange($addr, $range) {
		// Convert to IPv6 if needed
		$unsignedIP = self::toHex( $addr );
		list( $start, $end ) = self::parseRange( $range );
		return (($unsignedIP >= $start) && ($unsignedIP <= $end));
	}

	public static function isInCidr($addr, $cidr) {
		if (self::isCidr($addr)) {
			$info = self::createIpInfoFromCidr($addr);
			if (self::isInCidr($info['first'], $cidr) && self::isInCidr($info['last'], $cidr)) {
				return true;
			} else {
				return false;
			}
		} else if (self::isRange($addr)) {
			$info = self::createIpInfoFromRange($addr);
			if (self::isInCidr($info['first'], $cidr) && self::isInCidr($info['last'], $cidr)) {
				return true;
			} else {
				return false;
			}
		} else {
			return self::isInRange($addr, $cidr);
		}
	}

	/**
	* Convert some unusual representations of IPv4 addresses to their
	* canonical dotted quad representation.
	*
	* This currently only checks a few IPV4-to-IPv6 related cases.  More
	* unusual representations may be added later.
	*
	* @param $addr something that might be an IP address
	* @return valid dotted quad IPv4 address or null
	*/
	public static function canonicalize( $addr ) {
		if ( self::isValid( $addr ) ) {
			return $addr;
		}

		// IPv6 loopback address
		$m = array();
		if ( preg_match( '/^0*' . RE_IPV6_GAP . '1$/', $addr, $m ) ) {
			return '127.0.0.1';
		}

		// IPv4-mapped and IPv4-compatible IPv6 addresses
		if ( preg_match( '/^' . RE_IPV6_V4_PREFIX . '(' . RE_IP_ADD . ')$/i', $addr, $m ) ) {
			return $m[1];
		}

		if ( preg_match( '/^' . RE_IPV6_V4_PREFIX . RE_IPV6_WORD . ':' . RE_IPV6_WORD . '$/i', $addr, $m ) ) {
			return long2ip( ( hexdec( $m[1] ) << 16 ) + hexdec( $m[2] ) );
		}

		return null;
	}

	/**
	* Convert a cidr (16) into a netmask (255.255.0.0).
	*
	* @param $cidr CIDR bits, without leading slash
	* @return a string containing the netmask notation
	* @see https://svn.airt.nl/svn/branches/20081031.1/source/lib/network.plib
	*/
	public static function cidr2netmask($cidr) {
		$bin = decbin(pow(2,$cidr)-1);
		$n = 32 - strlen($bin);
		for ($i=0; $i < $n; $i++) {
			$bin .= '0';
		}

		return long2ip(bindec($bin));
	}

	/**
	* Convert a netmask (255.255.0.0) to CIDR notation (16).
	* The leading slash in the CIDR notation will not be returned.
	*
	* @param $mask The netmask that needs to be converted
	* @return a string containing the CIDR notation of the netmask
	* @see https://svn.airt.nl/svn/branches/20081031.1/source/lib/network.plib
	*/
	public static function netmask2cidr($mask) {
		if ($mask == '0.0.0.0') {
			return 0;
		}

		$pos = strpos(decbin(ip2long($mask)), '0');
		if ($pos == false) {
			return 32;
		} else {
			return $pos;
		}
	}

	/**
	* method rangeToCIDRList.
	* Returns an array of CIDR blocks that fit into a specified range of
	* ip addresses.
	* Usage:
	*	CIDR::rangeToCIDRList("127.0.0.1","127.0.0.34");
	* Result:
	*	array(7) {
	*		[0]=> string(12) "127.0.0.1/32"
	*		[1]=> string(12) "127.0.0.2/31"
	*		[2]=> string(12) "127.0.0.4/30"
	*		[3]=> string(12) "127.0.0.8/29"
	*		[4]=> string(13) "127.0.0.16/28"
	*		[5]=> string(13) "127.0.0.32/31"
	*		[6]=> string(13) "127.0.0.34/32"
	*	}
	* @param $startIPinput String a IPv4 formatted ip address.
	* @param $startIPinput String a IPv4 formatted ip address.
	* @see http://null.pp.ru/src/php/Netmask.phps
	* @return Array CIDR blocks in a numbered array.
	*/
	public static function range2cidr($startIp, $endIp = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		if ((strpos($startIp, '-') !== false) && is_null($endIp)) {
			$tmp = explode('-', $startIp);
			$startIp = trim($tmp[0]);
			$endIp = trim($tmp[1]);
		}

		$cmd = sprintf('%s %s/bin/range2cidr.py --start="%s" --end="%s" 2>/dev/null',
			$config->python->path, _ABSPATH, $startIp, $endIp
		);
		$log->debug(sprintf('Running cmd %s', $cmd));

		$output = exec($cmd, $output, $returnVar);
		if ($returnVar > 0) {
			throw new Exception('An error ocurred while running the exclude script.');
		}

		$json = json_decode($output);
		return $json;
	}

	/**
	* Yes, I am just so awesome that I copied all the valid netmasks for IpV4
	*/
	public static function isIPv4Netmask($mask) {
		$tmp = self::toArray($mask);

		foreach($tmp as $key => $val) {
			$tmp[$key] = str_pad($val, 3, '0');
			$mask = implode('.', $tmp);
		}

		$valid = array(
			'255.255.255.255','000.000.000.000',

			'255.255.255.254', '255.255.255.252', '255.255.255.248', '255.255.255.240',
			'255.255.255.224', '255.255.255.192', '255.255.255.128', '255.255.255.000',

			'255.255.254.000', '255.255.252.000', '255.255.248.000', '255.255.240.000',
			'255.255.224.000', '255.255.192.000', '255.255.128.000', '255.255.000.000',

			'255.254.000.000', '255.252.000.000', '255.248.000.000', '255.240.000.000',
			'255.224.000.000', '255.192.000.000', '255.128.000.000', '255.000.000.000',

			'254.000.000.000', '252.000.000.000', '248.000.000.000', '240.000.000.000',
			'224.000.000.000', '192.000.000.000', '128.000.000.000'
		);

		if (in_array($mask, $valid)) {
			return true;
		} else {
			return false;
		}
	}

	public static function isVhost($vhost) {
		if (!$vhost) {
			return false;
		}

		$result = preg_match('/^(' . RE_IP_ADD . '|' . RE_IPV6_ADD . ')\[[a-zA-Z0-9 ._-]+\]$/', $vhost);
		if ($result > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Convert an arbitrarily-long digit string from one numeric base
	 * to another, optionally zero-padding to a minimum column width.
	 *
	 * Supports base 2 through 36; digit values 10-36 are represented
	 * as lowercase letters a-z. Input is case-insensitive.
	 *
	 * @param $input string of digits
	 * @param $sourceBase int 2-36
	 * @param $destBase int 2-36
	 * @param $pad int 1 or greater
	 * @param $lowercase bool
	 * @return string or false on invalid input
	 */
	public static function wfBaseConvert( $input, $sourceBase, $destBase, $pad=1, $lowercase=true ) {
		$input = strval( $input );
		if( $sourceBase < 2 ||
			$sourceBase > 36 ||
			$destBase < 2 ||
			$destBase > 36 ||
			$pad < 1 ||
			$sourceBase != intval( $sourceBase ) ||
			$destBase != intval( $destBase ) ||
			$pad != intval( $pad ) ||
			!is_string( $input ) ||
			$input == '' ) {
			return false;
		}

		$digitChars = ( $lowercase ) ?  '0123456789abcdefghijklmnopqrstuvwxyz' : '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$inDigits = array();
		$outChars = '';

		// Decode and validate input string
		$input = strtolower( $input );
		for( $i = 0; $i < strlen( $input ); $i++ ) {
			$n = strpos( $digitChars, $input{$i} );
			if( $n === false || $n > $sourceBase ) {
				return false;
			}

			$inDigits[] = $n;
		}

		// Iterate over the input, modulo-ing out an output digit
		// at a time until input is gone.
		while( count( $inDigits ) ) {
			$work = 0;
			$workDigits = array();

			// Long division...
			foreach( $inDigits as $digit ) {
				$work *= $sourceBase;
				$work += $digit;

				if( $work < $destBase ) {
					// Gonna need to pull another digit.
					if( count( $workDigits ) ) {
						// Avoid zero-padding; this lets us find
						// the end of the input very easily when
						// length drops to zero.
						$workDigits[] = 0;
					}
				} else {
					// Finally! Actual division!
					$workDigits[] = intval( $work / $destBase );
					$work = $work % $destBase;
				}
			}

			// All that division leaves us with a remainder,
			// which is conveniently our next output digit.
			$outChars .= $digitChars[$work];

			// And we continue!
			$inDigits = $workDigits;
		}

		while( strlen( $outChars ) < $pad ) {
			$outChars .= '0';
		}

		return strrev( $outChars );
	}

	public static function bin2ip($b) {
		$ip = "";
		$pieces = str_split($b, 8);
		foreach($pieces as $octet) {
		        $ip[] = bindec($octet);
		}

		return implode('.', $ip);
	}

	public static function enumerate($ip) {
		$results = array();

		if (self::isCidr($ip)) {
			$results = self::enumerateCidr($ip);
		} else if (self::isRange($ip)) {
			$cidrs = self::range2cidr($ip);
			foreach($cidrs as $cidr) {
				$results = array_merge($results, self::enumerateCidr($cidr));
			}
		} else {
			$results[] = $ip;
		}

		return $results;
	}

	public static function enumerateCidr($cidr) {
		$parts = explode("/", $cidr);
		$baseIP = sprintf("%032b",ip2long($parts[0]));
		$subnet = (int)$parts[1];
		$results = array();

		if ($subnet == 32) {
			$results[] = self::bin2ip($baseIP);
		} else {
			# for any other size subnet, print a list of IP addresses by concatenating
			# the prefix with each of the suffixes in the subnet
			$nets = 32 - $subnet;
			$ipPrefix = substr($baseIP, 0, $subnet);
			$range = pow(2, 32 - $subnet);

			for ($i = 0; $i < $range; $i++) {
				//echo $ipPrefix . sprintf("%0". $nets .'b', $i);
				$results[] = self::bin2ip($ipPrefix . sprintf("%0". $nets .'b', $i));
			}
		}

		return $results;
	}

	public function exclude($targets, $excludes) {
		$config = Ini_Config::getInstance();

		if (is_array($targets)) {
			$targets = implode(',', array_values($targets));
		}

		if (is_array($excludes)) {
			$excludes = implode(',', array_values($excludes));
		}

		$targets = str_replace(array('\r\n',"\r\n",'\r','\n', "\n", '\t',"\t"), ',', $targets);
		$excludes = str_replace(array('\r\n',"\r\n",'\r','\n', "\n", '\t',"\t"), ',', $excludes);
		$targets = preg_replace('/[^0-9.,-\/]/', '', $targets);
		$excludes = preg_replace('/[^0-9.,-\/]/', '', $excludes);

		$targets = preg_replace('|,,+|', ',', $targets);
		$excludes = preg_replace('|,,+|', ',', $excludes);

		$cmd = sprintf('%s %s/bin/exclude.py --target=%s --exclude=%s --format="json"',
			$config->python->path, _ABSPATH,
			escapeshellarg($targets), escapeshellarg($excludes)
		);

		$output = exec($cmd);
		$results = json_decode($output);

		return $results;
	}

	function cidr2range($net) {
		$final = null;
		$start=strtok($net,"/");
		$n=3-substr_count($net, ".");
		if ($n>0) { for ($i=$n;$i>0;$i--) $start.=".0"; }
		$bits1=str_pad(decbin(ip2long($start)),32,"0",STR_PAD_LEFT);
		$net=pow(2,(32-substr(strstr($net,"/"),1)))-1;
		$bits2=str_pad(decbin($net),32,"0",STR_PAD_LEFT);
		for ($i=0;$i<32;$i++) {
			if ($bits1[$i]==$bits2[$i]) $final.=$bits1[$i];
			if ($bits1[$i]==1 and $bits2[$i]==0) $final.=$bits1[$i];
			if ($bits1[$i]==0 and $bits2[$i]==1) $final.=$bits2[$i];
		}
		return $start." - ".long2ip(bindec($final));
	}
}

?>

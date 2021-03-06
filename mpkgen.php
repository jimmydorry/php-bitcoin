<?php
require_once('./config.php');
// bcmath module in php is very slow
// gmp module is much faster

if (extension_loaded('gmp') && !defined('USE_EXT')){
    define ('USE_EXT', 'GMP');
}
else if (extension_loaded('bcmath') && !defined('USE_EXT')){
    define ('USE_EXT', 'BCMATH');
}

// This loads the phpecc modules and selects best math library
require_once (dirname(__FILE__) . '/phpecc/classes/util/bcmath_Utils.php');
require_once (dirname(__FILE__) . '/phpecc/classes/util/gmp_Utils.php');
require_once (dirname(__FILE__) . '/phpecc/classes/interface/CurveFpInterface.php');
require_once (dirname(__FILE__) . '/phpecc/classes/CurveFp.php');
require_once (dirname(__FILE__) . '/phpecc/classes/interface/PointInterface.php');
require_once (dirname(__FILE__) . '/phpecc/classes/Point.php');
require_once (dirname(__FILE__) . '/phpecc/classes/NumberTheory.php');

//===========================================================================
function genBTCAddressFromMPK($master_public_key, $key_offset)
{
    if (USE_EXT != 'GMP' && USE_EXT != 'BCMATH')
        return false;

    /*
    if (USE_EXT == 'GMP')
    {
        $utils_class = 'gmp_Utils';
        $fn_bchexdec = 'gmp_hexdec';
        $fn_dec2base = 'gmp_dec2base';
        $fn_base2dec = 'gmp_base2dec';
    }
    else if (USE_EXT == 'BCMATH')
    {
        $utils_class = 'bcmath_Utils';
        $fn_bchexdec = 'bchexdec';
        $fn_dec2base = 'dec2base';
        $fn_base2dec = 'base2dec';
    }
    else
        return false;
*/

    // create the ecc curve
    if (USE_EXT == 'GMP')
    {	// GMP
        $_p  		= gmp_Utils::gmp_hexdec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F');
        $_r  		= gmp_Utils::gmp_hexdec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
        $_b  		= gmp_Utils::gmp_hexdec('0x0000000000000000000000000000000000000000000000000000000000000007');
        $_Gx 		= gmp_Utils::gmp_hexdec('0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798');
        $_Gy 		= gmp_Utils::gmp_hexdec('0x483ada7726a3c4655da4fbfc0e1108a8fd17b448a68554199c47d08ffb10d4b8');
    }
    else
    { // BCMATH
        $_p  		= bcmath_Utils::bchexdec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F');
        $_r  		= bcmath_Utils::bchexdec('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
        $_b  		= bcmath_Utils::bchexdec('0x0000000000000000000000000000000000000000000000000000000000000007');
        $_Gx 		= bcmath_Utils::bchexdec('0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798');
        $_Gy 		= bcmath_Utils::bchexdec('0x483ada7726a3c4655da4fbfc0e1108a8fd17b448a68554199c47d08ffb10d4b8');
    }
    $curve 	= new CurveFp($_p, 0, $_b);
    $gen 		= new Point( $curve, $_Gx, $_Gy, $_r );

    // prepare the input values
    if (USE_EXT == 'GMP')
    {	// GMP
        $x = gmp_Utils::gmp_hexdec('0x'.substr($master_public_key, 0, 64));
        $y = gmp_Utils::gmp_hexdec('0x'.substr($master_public_key, 64, 64));
        $z = gmp_Utils::gmp_hexdec('0x'.hash('sha256', hash('sha256', $key_offset.':0:'.pack('H*',$master_public_key), TRUE)));
    }
    else
    {	// BCMATH
        $x = bcmath_Utils::bchexdec('0x'.substr($master_public_key, 0, 64));
        $y = bcmath_Utils::bchexdec('0x'.substr($master_public_key, 64, 64));
        $z = bcmath_Utils::bchexdec('0x'.hash('sha256', hash('sha256', $key_offset.':0:'.pack('H*',$master_public_key), TRUE)));
    }

    // generate the new public key based off master and sequence points
    $pt = Point::add(new Point($curve, $x, $y), Point::mul($z, $gen) );
    if (USE_EXT == 'GMP')
    {	// GMP
        $keystr = "\x04" . str_pad(gmp_Utils::gmp_dec2base($pt->getX(), 256), 32, "\x0", STR_PAD_LEFT) . str_pad(gmp_Utils::gmp_dec2base($pt->getY(), 256), 32, "\x0", STR_PAD_LEFT);
    }
    else
    {	// BCMATH
        $keystr = "\x04" . str_pad(bcmath_Utils::dec2base($pt->getX(), 256), 32, "\x0", STR_PAD_LEFT) . str_pad(bcmath_Utils::dec2base($pt->getY(), 256), 32, "\x0", STR_PAD_LEFT);
    }

    $vh160 =  "\x0".hash('ripemd160', hash('sha256', $keystr, TRUE), TRUE);
    $addr = $vh160.substr(hash('sha256', hash('sha256', $vh160, TRUE), TRUE), 0, 4);

    // base58 conversion
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $encoded  = '';
    if (USE_EXT == 'GMP')
    {	// GMP
        $num = gmp_Utils::gmp_base2dec($addr, 256);
    }
    else
    { // BCMATH
        $num = bcmath_Utils::base2dec($addr, 256);
    }

    while (intval($num) >= 58)
    {
        $div = bcdiv($num, '58');
        $mod = bcmod($num, '58');
        $encoded = $alphabet[intval($mod)] . $encoded;
        $num = $div;
    }
    $encoded = $alphabet[intval($num)] . $encoded;
    $pad = '';
    $n = 0;
    while ($addr[$n++] == "\x0")
        $pad .= '1';

    return $pad.$encoded;
}
//===========================================================================

$start_time = microtime(1);
$mpk_offset = 0; //THE STARTING INDEX FOR THE ADDRESS GENERATION; INCREASING THIS MEANS STARTING THE SEQUENCE FURTHER ALONG THE CURVE
$num_rounds = 5;

echo 'Using: ' . USE_EXT . '<hr />';

for($i=0;$i < $num_rounds;$i++){
    $address = genBTCAddressFromMPK(MPK, $mpk_offset + $i);

    if($address){
        echo $address . '<br />';
    }
    else{
        echo 'Failed to get address' . '<br />';
    }

}

echo '<hr />Execution time: '.(microtime(1) - $start_time);

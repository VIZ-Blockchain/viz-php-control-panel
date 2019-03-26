<?php
if(!function_exists('base58_encode')){
	// Base58 encoding/decoding functions - all credits go to https://github.com/stephen-hill/base58php
	function base58_encode($string){
		$alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
		$base = strlen($alphabet);
		// Type validation
		if (is_string($string) === false) {
			return false;
		}
		// If the string is empty, then the encoded string is obviously empty
		if (strlen($string) === 0) {
			return '';
		}
		// Now we need to convert the byte array into an arbitrary-precision decimal
		// We basically do this by performing a base256 to base10 conversion
		$hex = unpack('H*', $string);
		$hex = reset($hex);
		$decimal = gmp_init($hex, 16);
		// This loop now performs base 10 to base 58 conversion
		// The remainder or modulo on each loop becomes a base 58 character
		$output = '';
		while (gmp_cmp($decimal, $base) >= 0) {
			list($decimal, $mod) = gmp_div_qr($decimal, $base);
			$output .= $alphabet[gmp_intval($mod)];
		}
		// If there's still a remainder, append it
		if (gmp_cmp($decimal, 0) > 0) {
			$output .= $alphabet[gmp_intval($decimal)];
		}
		// Now we need to reverse the encoded data
		$output = strrev($output);
		// Now we need to add leading zeros
		$bytes = str_split($string);
		foreach ($bytes as $byte) {
			if ($byte === "\x00") {
				$output = $alphabet[0].$output;
				continue;
			}
			break;
		}
		return (string)$output;
	}
}
if(!function_exists('base58_decode')){
	function base58_decode($base58){
		$alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
		$base = strlen($alphabet);
		// Type Validation
		if (is_string($base58) === false) {
			return false;
		}
		// If the string is empty, then the decoded string is obviously empty
		if (strlen($base58) === 0) {
			return '';
		}
		$indexes = array_flip(str_split($alphabet));
		$chars = str_split($base58);
		// Check for invalid characters in the supplied base58 string
		foreach ($chars as $char) {
			if (isset($indexes[$char]) === false) {
				return false;
			}
		}
		// Convert from base58 to base10
		$decimal = gmp_init($indexes[$chars[0]], 10);
		for ($i = 1, $l = count($chars); $i < $l; $i++) {
			$decimal = gmp_mul($decimal, $base);
			$decimal = gmp_add($decimal, $indexes[$chars[$i]]);
		}
		// Convert from base10 to base256 (8-bit byte array)
		$output = '';
		while (gmp_cmp($decimal, 0) > 0) {
			list($decimal, $byte) = gmp_div_qr($decimal, 256);
			$output = pack('C', gmp_intval($byte)).$output;
		}
		// Now we need to add leading zeros
		foreach ($chars as $char) {
			if ($indexes[$char] === 0) {
				$output = "\x00".$output;
				continue;
			}
			break;
		}
		return $output;
	}
}
if(!function_exists('ec_check_der')){
	function ec_check_der($data){
		return '30440220'==substr($data,0,8) && '0220'==substr($data,72,4);
	}
}
if(!function_exists('ec_compact2der')){
	function ec_compact2der($data){
		$x=substr($data,2,64);
		$y=substr($data,66,64);
		return '30440220'.$x.'0220'.$y;
	}
}
if(!function_exists('ec_der2compact')){
	function ec_der2compact($data){
		$x=substr($data,8,64);
		$y=substr($data,-64);
		return '1f'.$x.$y;
	}
}
class viz_keys{
	public $bin='';
	public $hex='';
	function viz_keys($data=''){
		if($data){
			if(preg_match('/^[0-9a-f]+$/i',$data)){
				$this->import_hex($data);
			}
			else{
				$this->import_bin($data);
			}
		}
	}
	function random_str($length,$keyspace='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ:#@^&*()!?.,;"[]{}'){
		$pieces=[];
		$max=mb_strlen($keyspace,'8bit')-1;
		for($i=0;$i<$length;++$i){
			$pieces[]=$keyspace[random_int(0,$max)];
		}
		return implode('',$pieces);
	}
	function gen($seed='',$salt=''){
		if(!$salt){
			$salt=$this->random_str(40);
		}
		$seed=$salt.$seed;
		$hex_key=hash('sha256',$seed);
		$this->import_hex($hex_key);
	}
	function gen_pair($seed='',$salt=''){
		if(!$salt){
			$salt=$this->random_str(40);
		}
		$seed=$salt.$seed;
		$hex_key=hash('sha256',$seed);
		$this->import_hex($hex_key);
		$wif=$this->wif();
		$this->to_public();
		$pub=$this->public_key();
		return [$wif,$pub];
	}
	function import_hex($hex){
		$this->bin=hex2bin($hex);
		$this->hex=$hex;
	}
	function import_bin($bin){
		$this->bin=$bin;
		$this->hex=bin2hex($bin);
	}
	function import_wif($wif){
		$wif_decoded=base58_decode($wif);
		$wif_checksum=substr($wif_decoded,-4);
		$wif_decoded_clear=substr($wif_decoded,0,-4);

		$checksum=hash('sha256',$wif_decoded_clear);
		$checksum=hash('sha256',hex2bin($checksum));
		$checksum=substr($checksum,0,8);
		if($checksum!=bin2hex($wif_checksum)){
			return false;
		}
		$check_version=substr($wif_decoded_clear,0,1);
		if('80'!=bin2hex($check_version)){
			return false;
		}
		$wif_decoded_clear=substr($wif_decoded_clear,1);
		$this->bin=$wif_decoded_clear;
		$this->hex=bin2hex($wif_decoded_clear);
		return true;
	}
	function import_public($key){
		$clear_key=substr($key,3);
		$key_decoded=base58_decode($clear_key);
		$key_checksum=substr($key_decoded,-4);
		$key_decoded_clear=substr($key_decoded,0,-4);
		$checksum=hash('ripemd160',$key_decoded_clear);
		$checksum=substr($checksum,0,8);
		if($checksum!=bin2hex($key_checksum)){
			return false;
		}
		$this->bin=$key_decoded_clear;
		$this->hex=bin2hex($key_decoded_clear);
		return true;
	}
	function to_public(){
		if(!function_exists('secp256k1_context_create')){
			return false;
		}
		$context=secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
		$public_key=null;
		$result=secp256k1_ec_pubkey_create($context, $public_key, $this->bin);
		if(1 === $result){
			$serialize_flags=SECP256K1_EC_COMPRESSED;
			$serialized='';
			if (1 !== secp256k1_ec_pubkey_serialize($context, $serialized, $public_key, $serialize_flags)) {
				return false;
			}
			$this->bin=$serialized;
			$this->hex=bin2hex($serialized);
			return true;
		}
		else{
			return false;
		}
	}
	function wif(){
		$key='80'.$this->hex;
		$checksum=hash('sha256',hex2bin($key));
		$checksum=hash('sha256',hex2bin($checksum));
		$key=$key.substr($checksum,0,8);
		return base58_encode(hex2bin($key));
	}
	function public_key($prefix='VIZ'){
		$key=$this->hex;
		$checksum=hash('ripemd160',hex2bin($key));
		$key=$key.substr($checksum,0,8);
		return $prefix.base58_encode(hex2bin($key));
	}
	function sign($data){
		if(!function_exists('secp256k1_context_create')){
			return false;
		}
		$context=secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
		$data_hash=hash('sha256',$data,true);
		$signature=null;
		if (1 !== secp256k1_ecdsa_sign($context,$signature,$data_hash,$this->bin)) {
			return false;
		}
		$serialized='';
		secp256k1_ecdsa_signature_serialize_der($context,$serialized,$signature);
		return bin2hex($serialized);
	}
	function sign_compact($data){
		if(!function_exists('secp256k1_context_create')){
			return false;
		}
		$context=secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
		$data_hash=hash('sha256',$data,true);
		$signature=null;
		if (1 !== secp256k1_ecdsa_sign($context,$signature,$data_hash,$this->bin)) {
			return false;
		}
		$serialized='';
		if(!function_exists('secp256k1_ecdsa_signature_serialize_compact')){
			return false;
		}
		secp256k1_ecdsa_signature_serialize_compact($context,$serialized,$signature);
		return dechex(4+27).bin2hex($serialized);
	}
	function sign_recoverable_compact($data){
		if(!function_exists('secp256k1_context_create')){
			return false;
		}
		$context=secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
		$data_hash=hash('sha256',$data,true);
		$signature_recoverable = null;
		if(!function_exists('secp256k1_ecdsa_sign_recoverable')){
			return false;
		}
		if(1 !== secp256k1_ecdsa_sign_recoverable($context,$signature_recoverable,$data_hash,$this->bin)){
			return false;
		}
		$serialized='';
		if(!function_exists('secp256k1_ecdsa_recoverable_signature_serialize_compact')){
			return false;
		}
		$recid=0;
		secp256k1_ecdsa_recoverable_signature_serialize_compact($context,$serialized,$recid,$signature_recoverable);
		return dechex($recid+4+27).bin2hex($serialized);
	}
	function verify($data,$signature){
		if(!function_exists('secp256k1_context_create')){
			return false;
		}
		$context=secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

		$public_key_point=null;
		if(1 !== secp256k1_ec_pubkey_parse($context,$public_key_point,$this->bin)){
			return false;
		}

		$signature_bin=hex2bin($signature);
		$signature_point=null;
		if(1 !== secp256k1_ecdsa_signature_parse_der($context,$signature_point,$signature_bin)){
			return false;
		}

		$data_hash=hash('sha256',$data,true);
		if(1 == secp256k1_ecdsa_verify($context,$signature_point,$data_hash,$public_key_point)){
			return true;
		}
		return false;
	}
	function verify_compact($data,$signature){
		if(!function_exists('secp256k1_context_create')){
			return false;
		}
		$context=secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

		$signature_bin=hex2bin($signature);
		$signature_point=null;
		$recid=substr($signature_bin,0,1);
		$recid=bin2hex($recid);
		$recid=hexdec($recid);
		$recid-=4;
		$recid-=27;
		$signature_bin=substr($signature_bin,1);
		if(1 !== secp256k1_ecdsa_recoverable_signature_parse_compact($context,$signature_point,$signature_bin,$recid)){
			return false;
		}

		$data_hash=hash('sha256',$data,true);
		$recovered_public_key_point=null;
		if(1 !== secp256k1_ecdsa_recover($context,$recovered_public_key_point,$signature_point,$data_hash)){
			return false;
		}

		$serialized = '';
		if (1 !== secp256k1_ec_pubkey_serialize($context,$serialized,$recovered_public_key_point,SECP256K1_EC_COMPRESSED)) {
			return false;
		}

		if($this->bin == $serialized){//public key equal recovered
			return true;
		}
		return false;
	}
}
/*
Example:
$key=new viz_keys('b9f3c242e5872ac828cf2ef411f4c7b2a710bd9643544d735cc115ee939b3aae');
print $key->hex.PHP_EOL;
print 'WIF from hex: '.$key->wif().PHP_EOL;
$signature=$key->sign('Hello VIZ.World!');
print $signature.PHP_EOL;
if($key->to_public()){
	print 'Public key: '.$key->public_key().PHP_EOL;
	print 'Verify signature status: '.$key->verify('Bye VIZ.World!',$signature).PHP_EOL;
	print 'Verify signature status: '.$key->verify('Hello VIZ.World!',$signature).PHP_EOL;
}
*/
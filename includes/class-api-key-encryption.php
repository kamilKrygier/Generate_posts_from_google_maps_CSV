<?php

class API_Key_Encryption {

	private $key;
	private $salt;

	public function __construct() {
		$this->key  = $this->get_default_key();
		$this->salt = $this->get_default_salt();
	}

	public function encrypt( $API_key ) {
		if ( ! extension_loaded( 'openssl' ) ) {
			Utils::debug_log('Could not load extenstion "openssl"!');
			return $API_key;
		}

		$method = 'aes-256-ctr';
		$ivlen  = openssl_cipher_iv_length( $method );
		$iv     = openssl_random_pseudo_bytes( $ivlen );

		$hashed_API_hey = openssl_encrypt( $API_key . $this->salt, $method, $this->key, 0, $iv );
		if ( ! $hashed_API_hey ) {
			Utils::debug_log('Unable to encrypt given key!');
			return false;
		}

		Utils::debug_log('Key encrypted!');
		return base64_encode( $iv . $hashed_API_hey );
	}

	public function decrypt( $hashed_API_hey ) {
		if ( ! extension_loaded( 'openssl' ) ) {
			Utils::debug_log('Could not load extenstion "openssl"!');
			return $hashed_API_hey;
		}

		$hashed_API_hey = base64_decode( $hashed_API_hey, true );

		$method = 'aes-256-ctr';
		$ivlen  = openssl_cipher_iv_length( $method );
		$iv     = substr( $hashed_API_hey, 0, $ivlen );

		$hashed_API_hey = substr( $hashed_API_hey, $ivlen );

		$API_key = openssl_decrypt( $hashed_API_hey, $method, $this->key, 0, $iv );
		if ( ! $API_key || substr( $API_key, - strlen( $this->salt ) ) !== $this->salt ) {
			Utils::debug_log('Unable to decrypt given key!');
			return false;
		}

		Utils::debug_log('Key decrypted!');
		return substr( $API_key, 0, - strlen( $this->salt ) );
	}

	private function get_default_key() {

		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) return LOGGED_IN_KEY;
		
		Utils::debug_log('This is a fallback key - BUT NOT SECURE');
		return false;

	}

	private function get_default_salt() {

		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) return LOGGED_IN_SALT;

		Utils::debug_log('This is a fallback salt - BUT NOT SECURE');
		return false;

	}
}
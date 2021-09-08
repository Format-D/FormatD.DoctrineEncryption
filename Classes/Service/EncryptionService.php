<?php
namespace FormatD\DoctrineEncryption\Service;

use FormatD\DoctrineEncryption\Exception;
use Neos\Flow\Annotations as Flow;

/**
 * Service for encrypting and decryption string values
 */
class EncryptionService
{
	/**
	 * The OpenSSL algorithm this implementation uses.
	 *
	 * 256-bit AES in Counter Mode is chosen. GCM mode would be preferable, but is only introduced in PHP7.
	 */
	const ALGORITHM = 'aes-256-ctr';

	/**
	 * The hashing algorithm used to create MACs.
	 */
	const HASH_ALGORITHM = 'sha256';

	/**
	 * The minimum key length we will accept.
	 */
	const MINIMUM_KEY_LENGTH = 32;

	/**
	 * @Flow\InjectConfiguration(path="secretKey", package="FormatD.DoctrineEncryption")
	 * @var string
	 */
	protected $secretKey;

	/**
	 * init object
	 */
	public function initializeObject()
	{
		if (!is_string($this->secretKey) || mb_strlen($this->secretKey) < self::MINIMUM_KEY_LENGTH) {
			throw new Exception('Configured secret key is too short!', 1601037499);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function encrypt($data)
	{
		$nonce = $this->generateNonce();
		$plaintext = serialize($data);

		$ciphertext = openssl_encrypt(
			$plaintext,
			self::ALGORITHM,
			$this->secretKey,
			OPENSSL_RAW_DATA,
			$nonce
		);

		//
		// The MAC is computed as H(ALGORITHM ⨁ C ⨁ K ⨁ IV), where C is the ciphertext and K is the encryption key.
		//
		$mac = hash(self::HASH_ALGORITHM, self::ALGORITHM.$ciphertext.$this->secretKey.$nonce, true);

		$encrypted = "<ENC>\0".base64_encode($ciphertext)."\0".base64_encode($mac)."\0".base64_encode($nonce);

		return $encrypted;
	}

	/**
	 * @inheritdoc
	 */
	public function decrypt($data)
	{
		if (mb_strpos($data, "<ENC>\0", 0) !== 0) {
			// string is not encrypted, so we return unencrypted string
			return $data;
			//throw new \RuntimeException("Could not validate ciphertext");
		}

		$parts = explode("\0", $data);

		if (count($parts) !== 4) {
			throw new \RuntimeException("Could not validate ciphertext");
		}

		list($_, $ciphertext, $mac, $nonce) = $parts;

		if (($ciphertext = base64_decode($ciphertext)) === false) {
			throw new \RuntimeException("Could not validate ciphertext");
		}
		if (($mac = base64_decode($mac)) === false) {
			throw new \RuntimeException("Could not validate ciphertext");
		}
		if (($nonce = base64_decode($nonce)) === false) {
			throw new \RuntimeException("Could not validate ciphertext");
		}

		$expected = hash(self::HASH_ALGORITHM, self::ALGORITHM.$ciphertext.$this->secretKey.$nonce, true);

		if (!hash_equals($expected, $mac)) {
			throw new \RuntimeException("Invalid MAC");
		}

		$plaintext = openssl_decrypt(
			$ciphertext,
			self::ALGORITHM,
			$this->secretKey,
			OPENSSL_RAW_DATA,
			$nonce
		);

		if ($plaintext === false) {
			throw new \RuntimeException("Could not decrypt ciphertext");
		}

		$decrypted = unserialize($plaintext);

		return $decrypted;
	}

	/**
	 * Generate a cryptographically-secure nonce.
	 *
	 * The terminology 'nonce' is used instead of IV because it strongly implies that this value should never be
	 * re-used.
	 *
	 * @return string
	 */
	protected function generateNonce()
	{
		$size = openssl_cipher_iv_length(self::ALGORITHM);
		$data = random_bytes($size);
		return $data;
	}
}

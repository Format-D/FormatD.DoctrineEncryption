<?php
namespace FormatD\DoctrineEncryption\Persistence\Doctrine\DataTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types;
use FormatD\DoctrineEncryption\Service\EncryptionService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;

/**
 * A datatype for storing an array encrypted in the persistence
 *
 * @Flow\Proxy(false)
 */
class ArrayEncryptedType extends Types\ArrayType
{
    /**
     * @var string
     */
    const ARRAY_ENCRYPTED = 'arrayEncrypted';

	/**
	 * @var EncryptionService
	 */
    protected $encryptionService;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::ARRAY_ENCRYPTED;
    }

	/**
	 * @inheritdoc
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		$this->initializeDependencies();
		$value = parent::convertToDatabaseValue($value, $platform);
		return $this->encryptionService->encrypt($value);
	}

	/**
	 * @inheritdoc
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		$this->initializeDependencies();
		$unencryptedSerializedValue = $this->encryptionService->decrypt($value);
		return parent::convertToPHPValue($unencryptedSerializedValue, $platform);
	}

    /**
     * Fetches dependencies from the static object manager.
     *
     * Injection cannot be used, since __construct on Types\Type is final.
     *
     * @return void
     */
    protected function initializeDependencies()
    {
        if ($this->encryptionService === null) {
			$this->encryptionService = Bootstrap::$staticObjectManager->get(EncryptionService::class);
		}
    }

}

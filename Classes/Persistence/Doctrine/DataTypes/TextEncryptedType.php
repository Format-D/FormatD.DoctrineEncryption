<?php
namespace FormatD\DoctrineEncryption\Persistence\Doctrine\DataTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types;
use FormatD\DoctrineEncryption\Service\EncryptionService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;

/**
 * A datatype for storing a text encrypted in the persistence
 *
 * @Flow\Proxy(false)
 */
class TextEncryptedType extends Types\TextType
{
    /**
     * @var string
     */
    const TEXT_ENCRYPTED = 'textEncrypted';

	/**
	 * @var EncryptionService
	 */
    protected $encryptionService;

    /**
	 * @inheritdoc
     */
    public function getName()
    {
        return self::TEXT_ENCRYPTED;
    }

	/**
	 * @inheritdoc
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		$this->initializeDependencies();
		$stringValue = parent::convertToDatabaseValue($value, $platform);
		return $this->encryptionService->encrypt($stringValue);
	}

	/**
	 * @inheritdoc
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		$this->initializeDependencies();
		$stringValue = parent::convertToPHPValue($value, $platform);
		return $this->encryptionService->decrypt($stringValue);
	}

    /**
	 * @inheritdoc
     */
    protected function initializeDependencies()
    {
        if ($this->encryptionService === null) {
			$this->encryptionService = Bootstrap::$staticObjectManager->get(EncryptionService::class);
		}
    }

}

<?php
namespace FormatD\DoctrineEncryption\Persistence\Doctrine\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use FormatD\DoctrineEncryption\Service\EncryptionService;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * EventListener that handles encryption / decryption if configured
 *
 * @Flow\Scope("singleton")
 */
class EncryptionEventListener {

	/**
	 * @Flow\Inject
	 * @var EncryptionService
	 */
	protected $encryptionService;

	/**
	 * @Flow\InjectConfiguration(type="Settings", package="FormatD.DoctrineEncryption", path="entities")
	 * @var string
	 */
	protected $entityConfiguration;

	/**
	 * @param OnFlushEventArgs $eventArgs
	 * @return void
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{

		$entityManager = $eventArgs->getEntityManager();
		$unitOfWork = $entityManager->getUnitOfWork();

		foreach ($unitOfWork->getScheduledEntityInsertions() AS $entity) {
			if (isset($this->entityConfiguration[get_class($entity)])) {
				$conf = $this->entityConfiguration[get_class($entity)];
				$meta = $entityManager->getClassMetadata(get_class($entity));
				foreach ($conf as $propertyPath => $propertyConf) {
					$this->encryptPropertyPath($entity, $propertyPath, $propertyConf, $entityManager);
				}
				$unitOfWork->recomputeSingleEntityChangeSet($meta, $entity);
				$unitOfWork->computeChangeSet($meta, $entity);
			}
		}

		foreach ($unitOfWork->getScheduledEntityUpdates() AS $entity) {
			if (isset($this->entityConfiguration[get_class($entity)])) {
				$conf = $this->entityConfiguration[get_class($entity)];
				$meta = $entityManager->getClassMetadata(get_class($entity));
				foreach ($conf as $propertyPath => $propertyConf) {
					$this->encryptPropertyPath($entity, $propertyPath, $propertyConf, $entityManager);
				}
				$unitOfWork->recomputeSingleEntityChangeSet($meta, $entity);
				$unitOfWork->computeChangeSet($meta, $entity);
			}
		}
	}

	/**
	 * @param LifecycleEventArgs $eventArgs
	 * @return void
	 */
	public function postLoad(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();

		if (isset($this->entityConfiguration[get_class($entity)])) {
			$conf = $this->entityConfiguration[get_class($entity)];
			foreach ($conf as $propertyPath => $propertyConf) {
				$this->decryptPropertyPath($entity, $propertyPath, $propertyConf);
			}
		}
	}

	/**
	 * @param object $entity
	 * @param string $propertyPath
	 * @param array $propertyConf
	 * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
	 */
	protected function decryptPropertyPath($entity, $propertyPath, $propertyConf) {
		if ($propertyConf['method'] === 'default') {
			$encryptedPropertyValue = ObjectAccess::getPropertyPath($entity, $propertyPath);
			$unencryptedPropertyValue = $this->encryptionService->decrypt($encryptedPropertyValue);
			//echo($propertyName . '=>' . $unencryptedPropertyValue . ', ');
			if (strpos($propertyPath, '.')) {
				$relationName = substr($propertyPath, 0, strpos($propertyPath, '.'));
				$propertyName = substr($propertyPath, strpos($propertyPath, '.') + 1);
				$relation = ObjectAccess::getProperty($entity, $relationName);
				ObjectAccess::setProperty($relation, $propertyName, $unencryptedPropertyValue, true);
			} else {
				ObjectAccess::setProperty($entity, $propertyPath, $unencryptedPropertyValue, true);
			}
		}
	}

	/**
	 * @param object $entity
	 * @param string $propertyPath
	 * @param array $propertyConf
	 * @param EntityManagerInterface $entityManager
	 * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
	 */
	protected function encryptPropertyPath($entity, $propertyPath, $propertyConf, $entityManager) {
		if ($propertyConf['method'] === 'default') {
			$unitOfWork = $entityManager->getUnitOfWork();
			$unencryptedPropertyValue = ObjectAccess::getPropertyPath($entity, $propertyPath);
			$encryptedPropertyValue = $this->encryptionService->encrypt($unencryptedPropertyValue);
			if (strpos($propertyPath, '.')) {
				$relationName = substr($propertyPath, 0, strpos($propertyPath, '.'));
				$propertyName = substr($propertyPath, strpos($propertyPath, '.') + 1);
				$relation = ObjectAccess::getProperty($entity, $relationName);
				ObjectAccess::setProperty($relation, $propertyName, $encryptedPropertyValue, true);
				$unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata(get_class($relation)), $relation);
			} else {
				ObjectAccess::setProperty($entity, $propertyPath, $encryptedPropertyValue, true);
			}
		}
	}

}

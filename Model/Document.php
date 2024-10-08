<?php
declare(strict_types=1);

/*
 * This file is part of the Stinger Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DoctrineEntitySearchBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use StingerSoft\EntitySearchBundle\Model\Document as BaseDocument;

/**
 * Backend independent document implementation
 */
abstract class Document implements BaseDocument {

	protected $id;

	/**
	 *
	 * @var string|null
	 */
	protected ?string $entityClass = null;

	/**
	 *
	 * @var string|null
	 */
	protected ?string $entityType = null;

	/**
	 *
	 * @var mixed
	 */
	protected $entityId = null;

	/**
	 *
	 * @var Field[]
	 */
	protected $internalFields;

	public function __construct() {
		$this->internalFields = new ArrayCollection();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::addField()
	 */
	public function addField(string $fieldName, $value): void {
		foreach($this->internalFields as $field) {
			if($field->getFieldName() === $fieldName) {
				$field->setFieldValue($value);
				return;
			}
		}
		$field = $this->newFieldInstance();
		$field->setFieldValue($value);
		$field->setFieldName($fieldName);
		$this->internalFields[] = $field;
		$field->setDocument($this);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFields()
	 */
	public function getFields(): array {
		$result = array();
		foreach($this->internalFields as $field) {
			if(isset($result[$field->getFieldName()])) {
				$oldValue = $result[$field->getFieldName()];
				if(is_array($oldValue)) {
					$result[$field->getFieldName()][] = $field->getFieldValue();
				} else if(is_scalar($oldValue)) {
					$result[$field->getFieldName()] = array(
						$oldValue,
						$field->getFieldValue()
					);
				}
			} else {
				$result[$field->getFieldName()] = $field->getFieldValue();
			}
		}
		return $result;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFieldValue()
	 */
	public function getFieldValue(string $fieldName) {
		$result = array();
		foreach($this->internalFields as $field) {
			if($field->getFieldName() === $fieldName) {
				$result[] = $field->getFieldValue();
			}
		}
		switch(count($result)) {
			case 0:
				return null;
			case 1:
				return $result[0];
			default:
				return $result;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::addMultiValueField()
	 */
	public function addMultiValueField(string $fieldName, $value): void {
		$field = $this->newFieldInstance();
		$field->setFieldValue($value);
		$field->setFieldName($fieldName);
		$this->internalFields[] = $field;
		$field->setDocument($this);
	}

	/**
	 * Returns the internal field representation
	 *
	 * @return Field[]
	 */
	public function getInternalFields(): array {
		return \is_array($this->internalFields) ? $this->internalFields : $this->internalFields->toArray();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setFile()
	 */
	public function setFile(string $path): void {
		// Not supported
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFile()
	 */
	public function getFile(): ?string {
		// Not supported
		return null;
	}

	/**
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getEntityClass()
	 */
	public function getEntityClass(): string {
		return $this->entityClass;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setEntityClass()
	 */
	public function setEntityClass(string $entityClass): void {
		$this->entityClass = $entityClass;
		if(!$this->entityType) {
			$this->entityType = $entityClass;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getEntityType()
	 */
	public function getEntityType(): string {
		return $this->entityType ?: $this->getEntityClass();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setEntityType()
	 */
	public function setEntityType(string $type): void {
		$this->entityType = $type;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getEntityId()
	 */
	public function getEntityId() {
		return \json_decode($this->entityId);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setEntityId()
	 */
	public function setEntityId($entityId): void {
		$this->entityId = json_encode($entityId);
	}

	public function getInternalEntityId() {
		return $this->entityId;
	}

	public function __get(string $name) {
		return $this->getFieldValue($name);
	}

	public function __isset(string $name): bool {
		foreach($this->internalFields as $field) {
			if($field->getFieldName() === $name) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Creates a new field instance
	 *
	 * @return Field
	 */
	protected abstract function newFieldInstance(): Field;
}

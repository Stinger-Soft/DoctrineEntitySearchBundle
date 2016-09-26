<?php

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

use StingerSoft\EntitySearchBundle\Model\Document as BaseDocument;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Backend independent document implementation
 */
abstract class Document implements BaseDocument {

	protected $id;

	/**
	 *
	 * @var string
	 */
	protected $entityClass = null;

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
	public function addField($fieldname, $value) {
		foreach($this->internalFields as $field) {
			if($field->getFieldName() == $fieldname) {
				$field->setFieldValue($value);
				return;
			}
		}
		$field = $this->newFieldInstance();
		$field->setFieldValue($value);
		$field->setFieldName($fieldname);
		$this->internalFields[] = $field;
		$field->setDocument($this);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFields()
	 */
	public function getFields() {
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
	public function getFieldValue($fieldName) {
		$result = array();
		foreach($this->internalFields as $field) {
			if($field->getFieldName() == $fieldName) {
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
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::addMultiValueField()
	 */
	public function addMultiValueField($fieldName, $value) {
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
	public function getInternalFields() {
		return $this->internalFields;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setFile()
	 */
	public function setFile($path) {
		// Not supported
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
	public function getEntityClass() {
		return $this->entityClass;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setEntityClass()
	 */
	public function setEntityClass($entityClass) {
		$this->entityClass = $entityClass;
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getEntityId()
	 */
	public function getEntityId() {
		return json_decode($this->entityId);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setEntityId()
	 */
	public function setEntityId($entityId) {
		$this->entityId = json_encode($entityId);
		return $this;
	}

	public function getInternalEntityId() {
		return $this->entityId;
	}

	public function __get($name) {
		return $this->getFieldValue($name);
	}

	public function __isset($name) {
		return $this->getFieldValue($name) !== null;
	}

	/**
	 * Creates a new field instance
	 *
	 * @return Field
	 */
	protected abstract function newFieldInstance();
}
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
	 * {@inheritDoc}
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
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFields()
	 */
	public function getFields() {
		$result = array();
		foreach($this->internalFields as $field) {
			$result[$field->getFieldName()] = $field->getFieldValue();
		}
		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getFieldValue()
	 */
	public function getFieldValue($fieldName) {
		foreach($this->internalFields as $field) {
			if($field->getFieldName() == $fieldName) {
				return $field->getFieldValue();
			}
		}
		return null;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::addMultiValueField()
	 */
	public function addMultiValueField($field, $value) {
		$field = $this->newFieldInstance();
		$field->setFieldValue($value);
		$field->setFieldName($field);
		$this->internalFields[] = $field;
		$field->setDocument($this);
	}

	/**
	 *
	 * {@inheritDoc}
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
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getEntityClass()
	 */
	public function getEntityClass() {
		return $this->entityClass;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::setEntityClass()
	 */
	public function setEntityClass($entityClass) {
		$this->entityClass = $entityClass;
		return $this;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\Document::getEntityId()
	 */
	public function getEntityId() {
		return json_decode($this->entityId);
	}

	/**
	 *
	 * {@inheritDoc}
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

	/**
	 * Creates a new field instance
	 *
	 * @return Field
	 */
	protected abstract function newFieldInstance();
}
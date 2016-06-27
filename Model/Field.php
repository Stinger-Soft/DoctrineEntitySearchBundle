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

abstract class Field {

	protected $id;

	protected $fieldName;

	protected $internalFieldValue;
	
	protected $serialized;
	
	/**
	 * @var Document
	 */
	protected $document;

	public function getId() {
		return $this->id;
	}

	public function getFieldName() {
		return $this->fieldName;
	}

	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
		return $this;
	}

	public function getFieldValue() {
		return $this->serialized ? unserialize($this->internalFieldValue) : $this->internalFieldValue;
	}

	public function setFieldValue($fieldValue) {
		if(is_scalar($fieldValue)){
			$this->internalFieldValue = $fieldValue;
			$this->serialized = false;
		}else{
			$this->internalFieldValue = serialize($fieldValue);
			$this->serialized = true;
		}
		return $this;
	}

	public function getDocument() {
		return $this->document;
	}

	public function setDocument(Document $document) {
		$this->document = $document;
		return $this;
	}

	public function getSerialized() {
		return $this->serialized;
	}

	public function setSerialized($serialized) {
		$this->serialized = $serialized;
		return $this;
	}
	
	
	
}
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

	protected $fieldValue;
	
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
		return $this->fieldValue;
	}

	public function setFieldValue($fieldValue) {
		$this->fieldValue = $fieldValue;
		return $this;
	}

	public function getDocument() {
		return $this->document;
	}

	public function setDocument(Document $document) {
		$this->document = $document;
		return $this;
	}
	
}
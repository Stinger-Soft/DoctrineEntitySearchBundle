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

abstract class Field {

	protected $id;

	protected ?string $fieldName = null;

	protected $internalFieldValue;
	
	protected bool $serialized = false;
	
	/**
	 * @var Document
	 */
	protected ?Document $document = null;

	public function getId() {
		return $this->id;
	}

	public function getFieldName() : string {
		return $this->fieldName;
	}

	public function setFieldName(string $fieldName) {
		$this->fieldName = $fieldName;
		return $this;
	}

	public function getFieldValue() {
		return $this->serialized ? unserialize($this->internalFieldValue) : $this->internalFieldValue;
	}

	public function setFieldValue($fieldValue): self {
		if(is_scalar($fieldValue)){
			$this->internalFieldValue = $fieldValue;
			$this->serialized = false;
		}else{
			$this->internalFieldValue = serialize($fieldValue);
			$this->serialized = true;
		}
		return $this;
	}

	public function getDocument(): ?Document {
		return $this->document;
	}

	public function setDocument(Document $document): self {
		$this->document = $document;
		return $this;
	}

	public function getSerialized(): bool {
		return $this->serialized;
	}

	public function setSerialized(bool $serialized): self {
		$this->serialized = $serialized;
		return $this;
	}
	
	
	
}

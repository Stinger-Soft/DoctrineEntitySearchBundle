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
namespace StingerSoft\DoctrineEntitySearchBundle\Entity;

use StingerSoft\DoctrineEntitySearchBundle\Model\Document as BaseDocument;

class Document extends BaseDocument {
	
	protected function newFieldInstance(){
		return new Field();
	}
}
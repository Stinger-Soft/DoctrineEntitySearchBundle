<?php

/*
 * This file is part of the Stinger Doctrine Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\DoctrineEntitySearchBundle\Tests\Model;

use StingerSoft\DoctrineEntitySearchBundle\Entity\Document;
use StingerSoft\EntitySearchBundle\Tests\Fixtures\ORM\Beer;

class DocumentTest extends \PHPUnit\Framework\TestCase {

	public function testAddFields() {
		$doc = new Document();
		
		$doc->setEntityClass(Beer::class);
		$doc->setEntityId(1);
		$doc->addField(Document::FIELD_AUTHOR, 'florian_meyer');
		$doc->addMultiValueField(Document::FIELD_EDITORS, 'florian_meyer');
		$doc->addMultiValueField(Document::FIELD_EDITORS, 'oliver_kotte');
		$doc->setFile('~/test.txt');
		
		$this->assertEquals(Beer::class, $doc->getEntityClass());
		$this->assertEquals(1, $doc->getEntityId());
		
		$fields = $doc->getFields();
		$this->assertArrayHasKey(Document::FIELD_AUTHOR, $fields);
		$this->assertNotNull($doc->getFieldValue(Document::FIELD_AUTHOR));
		$this->assertArrayHasKey(Document::FIELD_EDITORS, $fields);
		$this->assertNotNull($doc->getFieldValue(Document::FIELD_EDITORS));
		$this->assertContains('florian_meyer', $fields[Document::FIELD_EDITORS]);
		$this->assertNull($doc->getFieldValue(Document::FIELD_ROLES));
	}
}
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

namespace StingerSoft\DoctrineEntitySearchBundle\Tests\Services;

use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Knp\Component\Pager\Paginator;
use StingerSoft\DoctrineEntitySearchBundle\Entity\Document;
use StingerSoft\DoctrineEntitySearchBundle\Entity\Field;
use StingerSoft\EntitySearchBundle\Model\Query;
use StingerSoft\EntitySearchBundle\Model\Result\FacetSetAdapter;
use StingerSoft\EntitySearchBundle\Services\SearchService;
use StingerSoft\EntitySearchBundle\Tests\AbstractORMTestCase;
use StingerSoft\EntitySearchBundle\Tests\Fixtures\ORM\Beer;
use StingerSoft\EntitySearchBundle\Tests\Fixtures\ORM\Car;

class SearchServiceTest extends AbstractORMTestCase {

	protected int $indexCount = 0;

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	public function setUp(): void {
		parent::setUp();
		$this->getMockSqliteEntityManager();
		$this->indexCount = 0;
	}

	/**
	 *
	 * @return \StingerSoft\DoctrineEntitySearchBundle\Services\SearchService
	 */
	protected function getSearchService(EntityManager $em = null): SearchService {
		$dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
		$service = new \StingerSoft\DoctrineEntitySearchBundle\Services\SearchService(new Paginator($dispatcher));
		$service->setObjectManager($em ?? $this->getMockSqliteEntityManager());
		return $service;
	}


	protected function indexBeer(SearchService $service, $title = 'Hemelinger'): array {
		$beer = new Beer();
		$beer->setTitle($title);
		$this->em->persist($beer);
		$this->em->flush();

		$document = $service->createEmptyDocumentFromEntity($beer);
		$this->assertEquals($this->indexCount, $service->getIndexSize());
		$beer->indexEntity($document);
		$service->saveDocument($document);
		$this->em->flush();
		$this->assertEquals(++$this->indexCount, $service->getIndexSize());
		return array(
			$beer,
			$document
		);
	}

	public function testSaveDocument(): void {
		$service = $this->getSearchService($this->em);
		$this->indexBeer($service);
		$service->clearIndex();
		$this->assertEquals(0, $service->getIndexSize());
	}

	public function testSaveDocumentComposite(): void {
		$car = new Car('S500', 2016);
		$this->em->persist($car);
		$this->em->flush();

		$service = $this->getSearchService($this->em);
		$document = $service->createEmptyDocumentFromEntity($car);
		$this->assertEquals(0, $service->getIndexSize());
		$service->saveDocument($document);
		$this->em->flush();

		$this->assertEquals(1, $service->getIndexSize());

		$service->clearIndex();
		$this->assertEquals(0, $service->getIndexSize());
	}

	public function testRemoveDocument(): void {
		$service = $this->getSearchService($this->em);
		$result = $this->indexBeer($service);

		$service->removeDocument($result[1]);
		$this->em->flush();
		$this->assertEquals(0, $service->getIndexSize());
	}

	public function testAutocompletion(): void {
		$service = $this->getSearchService($this->em);
		$result = $this->indexBeer($service);

		$suggests = $service->autocomplete('He');
		$this->assertCount(1, $suggests);
		$this->assertContains($result[0]->getTitle(), $suggests);
	}

	public function testSearch(): void {
		$service = $this->getSearchService($this->em);
		$this->indexBeer($service);
		$this->indexBeer($service, 'Haake Beck');
		$this->indexBeer($service, 'Haake Beck');
		$this->indexBeer($service, 'Haake Beck Kräusen');
		$query = $this->getMockBuilder(Query::class)->setMethods(array(
			'getSearchTerm'
		))->getMock();
		$query->method('getSearchTerm')->will($this->returnValue('Beck'));
		$result = $service->search($query);
		$this->assertCount(3, $result->getResults());

		/**
		 * @var FacetSetAdapter $facets
		 */
		$facets = $result->getFacets();
		$titleFacets = $facets->getFacet(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE);
		$this->assertCount(2, $titleFacets);
		$this->assertArrayHasKey('Haake Beck', $titleFacets);
		$this->assertArrayHasKey('Haake Beck Kräusen', $titleFacets);
		$this->assertEquals(2, $titleFacets['Haake Beck']['count']);
		$this->assertEquals(1, $titleFacets['Haake Beck Kräusen']['count']);
		$typeFacets = $facets->getFacet(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TYPE);
		$this->assertCount(1, $typeFacets);

	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Tests\AbstractTestCase::getUsedEntityFixtures()
	 */
	protected function getUsedEntityFixtures(): array {
		return array(
			Beer::class,
			Car::class,
			Document::class,
			Field::class,
		);
	}

	protected function getMetadataDriverImplementation(): MappingDriverChain {
		$driver = new MappingDriverChain();
		$driver->setDefaultDriver(parent::getMetadataDriverImplementation());
		$namespaces = array(
			realpath(__DIR__ . '/../../Resources/config/doctrine/') => 'StingerSoft\DoctrineEntitySearchBundle\Entity',
		);

		$yamlDriver = new SimplifiedYamlDriver($namespaces);
		$driver->addDriver($yamlDriver, 'StingerSoft\DoctrineEntitySearchBundle');
		return $driver;
	}

	protected function getPaths(): array {
		return array(realpath(__DIR__ . '/../../Entity'));
	}
}

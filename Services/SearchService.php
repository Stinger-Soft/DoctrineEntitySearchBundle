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
namespace StingerSoft\DoctrineEntitySearchBundle\Services;

use Doctrine\ORM\EntityManager;
use StingerSoft\DoctrineEntitySearchBundle\Entity\Document;
use StingerSoft\DoctrineEntitySearchBundle\Entity\Field;
use StingerSoft\DoctrineEntitySearchBundle\Model\KnpResultSet;
use StingerSoft\EntitySearchBundle\Model\Query;
use StingerSoft\EntitySearchBundle\Services\AbstractSearchService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use StingerSoft\EntitySearchBundle\Model\Result\FacetSetAdapter;

class SearchService extends AbstractSearchService {
	
	use ContainerAwareTrait;

	const BATCH_SIZE = 50;

	protected $documentClazz = null;

	protected $fieldClazz = null;

	public static $searchableFields = array(
		\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE,
		\StingerSoft\EntitySearchBundle\Model\Document::FIELD_CONTENT 
	);

	public function __construct($documentClazz = Document::class, $fieldClazz = Field::class) {
		$this->documentClazz = $documentClazz;
		$this->fieldClazz = $fieldClazz;
	}

	protected function newDocumentInstance() {
		return new $this->documentClazz();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::clearIndex()
	 */
	public function clearIndex() {
		$docs = $this->getObjectManager()->getRepository($this->documentClazz)->findAll();
		$i = 0;
		foreach($docs as $doc) {
			$this->getObjectManager()->remove($doc);
			if(($i % self::BATCH_SIZE) === 0) {
				$this->getObjectManager()->flush();
				$this->getObjectManager()->clear();
			}
			++$i;
		}
		$this->getObjectManager()->flush();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::saveDocument()
	 */
	public function saveDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
		$this->getObjectManager()->persist($document);
		$this->getObjectManager()->flush();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::removeDocument()
	 */
	public function removeDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
		if(!($document instanceof \StingerSoft\DoctrineEntitySearchBundle\Model\Document)) {
			throw new \InvalidArgumentException(sprintf('Given document is of class %, expected %s'), get_class($document), $this->documentClazz);
		}
		$realDoc = $this->getObjectManager()->getRepository($this->documentClazz)->findOneBy(array(
			'entityId' => $document->getInternalEntityId(),
			'entityClass' => $document->getEntityClass() 
		));
		if($realDoc) {
			$this->getObjectManager()->remove($realDoc);
			$this->getObjectManager()->flush();
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::autocomplete()
	 */
	public function autocomplete($search, $maxResults = 10) {
		$om = $this->getObjectManager();
		if($om instanceof EntityManager) {
			return $this->autocompleteOrm($om, $search, $maxResults);
		}
		// ODM not supported yet
		return array();
	}

	public function autocompleteOrm(EntityManager $em, $search, $maxResults = 10) {
		$fieldRepos = $em->getRepository($this->fieldClazz);
		$qb = $fieldRepos->createQueryBuilder('field');
		$qb->select('field.fieldValue');
		$qb->orWhere('field.fieldName = :titleFieldName AND field.fieldValue LIKE :term');
		$qb->setParameter('titleFieldName', \StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE);
		$qb->orWhere('field.fieldName = :contentFieldName AND field.fieldValue LIKE :term');
		$qb->setParameter('contentFieldName', \StingerSoft\EntitySearchBundle\Model\Document::FIELD_CONTENT);
		$qb->setParameter('term', $search . '%');
		$iterator = $qb->getQuery()->iterate(null, \Doctrine\ORM\Query::HYDRATE_SCALAR);
		$suggestions = array();
		foreach($iterator as $res) {
			$suggestions = array_merge($suggestions, array_filter(explode(' ', $res[0]['fieldValue']), function ($word) use ($search) {
				return stripos($word, $search) === 0;
			}));
		}
		$suggestions = array_unique($suggestions);
		return array_slice($suggestions, 0, $maxResults);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::search()
	 */
	public function search(Query $query) {
		$om = $this->getObjectManager();
		if($om instanceof EntityManager) {
			return $this->searchOrm($query, $om);
		}
		// ODM not supported yet
		return null;
	}

	protected function searchOrm(Query $query, EntityManager $em) {
		$term = $query->getSearchTerm();
		$docRepos = $em->getRepository($this->documentClazz);
		$qb = $docRepos->createQueryBuilder('doc');
		$qb->leftJoin('doc.internalFields', 'field');
		foreach(self::$searchableFields as $field) {
			$qb->orWhere('field.fieldName = :' . $field . 'FieldName AND field.fieldValue LIKE :term');
			$qb->setParameter($field . 'FieldName', $field);
		}
		$qb->setParameter('term', '%' . $term . '%');
		$qb->distinct();
		
		$result = new KnpResultSet($qb);
		$result->setContainer($this->container);
		
		$facetQb = clone $qb;
		$facetQb->select('field.fieldName');
		$facetQb->addSelect('field.fieldValue');
		$facetQb->addSelect('COUNT(doc.id) as resultCount');
		$facetQb->addGroupBy('field.fieldName');
		$facetQb->addGroupBy('field.fieldValue');
		$facetQb->orderBy('resultCount', 'DESC');
		
		if($query->getUsedFacets() !== null){
			$facetQb->andWhere('field.fieldName IN (:fields)');
			$facetQb->setParameter('fields', $query->getUsedFacets());
		}
		
		$facetSet = new FacetSetAdapter();
		if($query->getUsedFacets() === null || count($query->getUsedFacets()) > 0){
			foreach($facetQb->getQuery()->getScalarResult() as $facetResult) {
				$facetSet->addFacetValue($facetResult['fieldName'], $facetResult['fieldValue'], $facetResult['resultCount']);
			}
		
			if($query->getUsedFacets() === null || in_array('type', $query->getUsedFacets())){
				$facetQb = clone $qb;
				$facetQb->select('doc.entityClass');
				$facetQb->addSelect('COUNT(doc.id) as resultCount');
				$facetQb->addGroupBy('doc.entityClass');
				$facetQb->orderBy('resultCount', 'DESC');
				
				foreach($facetQb->getQuery()->getScalarResult() as $facetResult) {
					$facetSet->addFacetValue('type', $facetResult['entityClass'], $facetResult['resultCount']);
				}
			}
		}
		
		$result->setFacets($facetSet);
		return $result;
	}

	public function getIndexSize() {
		$om = $this->getObjectManager();
		if($om instanceof EntityManager) {
			$docRepos = $om->getRepository($this->documentClazz);
			$qb = $docRepos->createQueryBuilder('doc');
			$qb->select('count(doc)');
			return $qb->getQuery()->getSingleScalarResult();
		}
		return 0;
	}
}
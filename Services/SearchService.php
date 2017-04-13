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
use Doctrine\ORM\QueryBuilder;
use StingerSoft\DoctrineEntitySearchBundle\Entity\Document;
use StingerSoft\DoctrineEntitySearchBundle\Entity\Field;
use StingerSoft\DoctrineEntitySearchBundle\Model\KnpResultSet;
use StingerSoft\EntitySearchBundle\Model\Query;
use StingerSoft\EntitySearchBundle\Model\Result\FacetSet;
use StingerSoft\EntitySearchBundle\Model\Result\FacetSetAdapter;
use StingerSoft\EntitySearchBundle\Services\AbstractSearchService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Doctrine\Common\Util\ClassUtils;

class SearchService extends AbstractSearchService {
	
	use ContainerAwareTrait;

	const BATCH_SIZE = 50;

	protected static $highlightStartTag = '<em>';

	protected static $highlightEndTag = '</em>';

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
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::clearIndex()
	 */
	public function clearIndex() {
		$em = $this->getObjectManager();
		$q = $em->createQuery('delete from ' . $this->fieldClazz);
		$q->execute();
		
		$q = $em->createQuery('delete from ' . $this->documentClazz);
		$q->execute();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Services\SearchService::saveDocument()
	 */
	public function saveDocument(\StingerSoft\EntitySearchBundle\Model\Document $document) {
		$this->removeDocument($document);
		/**
		 *
		 * @var EntityManager $om
		 */
		$om = $this->getObjectManager();
		$this->getObjectManager()->persist($document);
		$om->getUnitOfWork()->computeChangeSet($om->getClassMetadata(ClassUtils::getClass($document)), $document);
		foreach($document->getInternalFields() as $field) {
			$om->getUnitOfWork()->computeChangeSet($om->getClassMetadata(ClassUtils::getClass($field)), $field);
		}
	}

	/**
	 *
	 * {@inheritdoc}
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
		}
	}

	/**
	 *
	 * {@inheritdoc}
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
		$qb->select('field.internalFieldValue');
		$qb->orWhere('field.fieldName = :titleFieldName AND field.internalFieldValue LIKE :term');
		$qb->setParameter('titleFieldName', \StingerSoft\EntitySearchBundle\Model\Document::FIELD_TITLE);
		$qb->orWhere('field.fieldName = :contentFieldName AND field.internalFieldValue LIKE :term');
		$qb->setParameter('contentFieldName', \StingerSoft\EntitySearchBundle\Model\Document::FIELD_CONTENT);
		$qb->setParameter('term', '%' . $search . '%');
		$iterator = $qb->getQuery()->iterate(null, \Doctrine\ORM\Query::HYDRATE_SCALAR);
		$suggestions = array();
		foreach($iterator as $res) {
			//TODO remove whitespaces and some useless chars: .,;<>"+
			$suggestions = array_merge($suggestions, array_filter(explode(' ', strip_tags($res[0]['internalFieldValue'])), function ($word) use ($search) {
				return stripos($word, $search) === 0;
			}));
			//TODO use phpcommons Utils:removeDuplicatesByComparator -> Version 1.1
			$suggestions = array_unique($suggestions);
			if(count($suggestions) > $maxResults) {
				break;
			}
		}
		
		return array_slice($suggestions, 0, $maxResults);
	}

	/**
	 *
	 * {@inheritdoc}
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

	protected function createBasicSearchOrmQuery(Query $query, EntityManager $em) {
		$term = $query->getSearchTerm();
		$docRepos = $em->getRepository($this->documentClazz);
		$qb = $docRepos->createQueryBuilder('doc');
		$qb->leftJoin('doc.internalFields', 'field');
		foreach(self::$searchableFields as $field) {
			$qb->orWhere('field.fieldName = :' . $field . 'FieldName AND field.internalFieldValue LIKE :term');
			$qb->setParameter($field . 'FieldName', $field);
		}
		$qb->setParameter('term', '%' . $term . '%');
		$qb->distinct();
		return $qb;
	}

	protected function createFacetedOrmQuery(QueryBuilder $qb, Query $query, EntityManager $em) {
		$docRepos = $em->getRepository($this->documentClazz);
		$facetedQb = $docRepos->createQueryBuilder('facetDoc');
		$facetedQb->leftJoin('facetDoc.internalFields', 'facetField');
		if($query->getFacets() !== null && count($query->getFacets()) > 0) {
			foreach($query->getFacets() as $facetField => $facetValues) {
				if($facetValues === null || count($facetValues) == 0)
					continue;
				
				if($facetField == \StingerSoft\EntitySearchBundle\Model\Document::FIELD_TYPE) {
					$facetedQb->andWhere('facetDoc.entityType in (:clazzes)');
					$facetedQb->setParameter('clazzes', $facetValues);
				} else {
					$facetedQb->andWhere('(facetField.fieldName = :' . $facetField . 'FacetFieldName AND facetField.internalFieldValue IN (:' . $facetField . 'FacetFieldValues))');
					$facetedQb->setParameter($facetField . 'FacetFieldName', $facetField);
					$facetedQb->setParameter($facetField . 'FacetFieldValues', $facetValues);
				}
			}
		}
		$this->addResultIdInPart($facetedQb, $query, $em);
		return $facetedQb;
	}

	protected function fetchTypeFacetsFromOrmQuery(FacetSet $facets, Query $query, EntityManager $em) {
		if($query->getUsedFacets() === null || in_array(\StingerSoft\EntitySearchBundle\Model\Document::FIELD_TYPE, $query->getUsedFacets())) {
			$docRepos = $em->getRepository($this->documentClazz);
			$facetQb = $docRepos->createQueryBuilder('facetDoc');
			$facetQb->select('facetDoc.entityType');
			$facetQb->addSelect('COUNT(DISTINCT facetDoc.id) as resultCount');
			$facetQb->addGroupBy('facetDoc.entityType');
			$facetQb->orderBy('resultCount', 'DESC');
			$this->addResultIdInPart($facetQb, $query, $em);
			foreach($facetQb->getQuery()->getScalarResult() as $facetResult) {
				$facets->addFacetValue('type', $facetResult['entityType'], $facetResult['resultCount']);
			}
		}
	}

	protected function fetchCommonFacetsFromOrmQuery(FacetSet $facets, Query $query, EntityManager $em) {
		if($query->getUsedFacets() === null || count($query->getUsedFacets()) > 0) {
			$docRepos = $em->getRepository($this->documentClazz);
			$facetQb = $docRepos->createQueryBuilder('facetDoc');
			$facetQb->leftJoin('facetDoc.internalFields', 'facetField');
			$facetQb->select('facetField.fieldName');
			$facetQb->addSelect('facetField.internalFieldValue');
			$facetQb->addSelect('COUNT(facetDoc.id) as resultCount');
			$facetQb->addGroupBy('facetField.fieldName');
			$facetQb->addGroupBy('facetField.internalFieldValue');
			$facetQb->orderBy('resultCount', 'DESC');
			
			if($query->getUsedFacets() !== null) {
				$facetQb->andWhere('facetField.fieldName IN (:facetFields)');
				$facetQb->setParameter('facetFields', $query->getUsedFacets());
			}
			$this->addResultIdInPart($facetQb, $query, $em);
			foreach($facetQb->getQuery()->getScalarResult() as $facetResult) {
				$facets->addFacetValue($facetResult['fieldName'], $facetResult['internalFieldValue'], $facetResult['resultCount']);
			}
		}
	}

	protected function addResultIdInPart(QueryBuilder $queryToAdd, Query $query, EntityManager $em, $docAlias = 'facetDoc') {
		$inQuery = $this->createBasicSearchOrmQuery($query, $em);
		$inQuery->select('doc.id');
		$queryToAdd->andWhere($queryToAdd->expr()->in($docAlias . '.id', $inQuery->getQuery()->getDQL()));
		
		foreach(self::$searchableFields as $field) {
			$queryToAdd->setParameter($field . 'FieldName', $field);
		}
		$queryToAdd->setParameter('term', '%' . $query->getSearchTerm() . '%');
	}

	protected function searchOrm(Query $query, EntityManager $em) {
		// Simple Like query
		$qb = $this->createBasicSearchOrmQuery($query, $em);
		
		// Adds facets if available
		$facetedQb = $this->createFacetedOrmQuery($qb, $query, $em);
		
		$result = new KnpResultSet($facetedQb, $query->getSearchTerm());
		$result->setContainer($this->container);
		
		$facetSet = new FacetSetAdapter();
		$this->fetchTypeFacetsFromOrmQuery($facetSet, $query, $em);
		$this->fetchCommonFacetsFromOrmQuery($facetSet, $query, $em);
		
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
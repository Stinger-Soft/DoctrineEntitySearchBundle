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

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use StingerSoft\EntitySearchBundle\Model\PaginatableResultSet;
use StingerSoft\EntitySearchBundle\Model\ResultSetAdapter;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use StingerSoft\PhpCommons\String\Utils;
use StingerSoft\EntitySearchBundle\Model\Document as BaseDocument;

class KnpResultSet extends ResultSetAdapter implements PaginatableResultSet {
	
	use ContainerAwareTrait;

	protected $query = null;

	protected $term = null;

	/**
	 *
	 * @param Query|QueryBuilder $items        	
	 */
	public function __construct($items, $term) {
		$this->query = $items;
		$this->term = $term;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\PaginatableResultSet::paginate()
	 */
	public function paginate($page = 1, $limit = 10, array $options = array()) {
		$paginator = $this->container->get('knp_paginator');
		return $paginator->paginate($this->query, $page, $limit, $options);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSetAdapter::getResults()
	 */
	public function getResults($offset = 0, $limit = null) {
		$query = null;
		if($this->query instanceof QueryBuilder) {
			$query = $this->query->getQuery();
		} else {
			$query = clone $this->query;
		}
		$query->setFirstResult($offset);
		$query->setMaxResults($limit);
		return $query->getResult();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSet::getExcerpt()
	 */
	public function getExcerpt(BaseDocument $document) {
		$content = $document->getFieldValue(BaseDocument::FIELD_CONTENT);
		$content = !is_array($content) ? $content : implode(' ', $content);
		return Utils::highlight(Utils::excerpt($content, $this->term), $this->term);
	}
}
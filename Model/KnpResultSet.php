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

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use StingerSoft\EntitySearchBundle\Model\Document as BaseDocument;
use StingerSoft\EntitySearchBundle\Model\PaginatableResultSet;
use StingerSoft\EntitySearchBundle\Model\ResultSetAdapter;
use StingerSoft\PhpCommons\String\Utils;

class KnpResultSet extends ResultSetAdapter implements PaginatableResultSet {

	protected $query = null;

	protected $term = null;

	protected $paginator;

	/**
	 *
	 * @param Query|QueryBuilder $items
	 */
	public function __construct(PaginatorInterface $paginator, $items, $term) {
		$this->query = $items;
		$this->term = $term;
		$this->paginator = $paginator;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\PaginatableResultSet::paginate()
	 */
	public function paginate(int $page = 1, int $limit = 10, array $options = array()): PaginationInterface {
		return $this->paginator->paginate($this->query, $page, $limit, $options);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see \StingerSoft\EntitySearchBundle\Model\ResultSetAdapter::getResults()
	 */
	public function getResults(int $offset = 0, int $limit = null): array {
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
	public function getExcerpt(BaseDocument $document): ?string {
		$content = $document->getFieldValue(BaseDocument::FIELD_CONTENT);
		$content = !\is_array($content) ? $content : implode(' ', $content);
		if($content === null) {
			return null;
		}
		return Utils::highlight(Utils::excerpt($content, $this->term), $this->term);
	}
}
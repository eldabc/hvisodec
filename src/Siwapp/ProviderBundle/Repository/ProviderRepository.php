<?php

namespace Siwapp\ProviderBundle\Repository;

use Knp\Component\Pager\PaginatorInterface;
use Siwapp\ProviderBundle\Entity\Provider;
use Siwapp\ProviderBundle\Entity\InvoiceProvider;

/**
 * ProviderRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProviderRepository extends \Doctrine\ORM\EntityRepository
{
    public function findLike($term)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Provider::class, 'p')
            ->where('p.name LIKE :name')
            ->orWhere('p.identification LIKE :name')
            ->setParameter('name', '%'. $term .'%')
            ->getQuery()
            ->getResult();
    }

    public function paginatedSearch(array $params, $limit = 50, $page = 1)
    {
        if (!$this->paginator) {
            throw new \RuntimeException('You have to set a paginator first using setPaginator() method');
        }

        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Provider::class, 'p');
        foreach ($params as $field => $value) {
            if ($value === null) {
                continue;
            }
            if ($field == 'terms') {
                $terms = $qb->expr()->literal("%$value%");
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('p.name', $terms),
                    $qb->expr()->like('p.identification', $terms),
                    $qb->expr()->like('p.contactPerson', $terms)
                ));
            }
        }

        $qb->leftJoin('p.invoicesProvider', 'i');
        $qb->addSelect('SUM(CASE WHEN i.gross_amount IS NULL THEN 0 ELSE i.gross_amount END) AS amount');
        $qb->addSelect('SUM(CASE WHEN i.gross_amount IS NULL THEN 0 ELSE i.gross_amount END - CASE WHEN i.paid_amount IS NULL THEN 0 ELSE i.paid_amount END) AS due_amount');
        $qb->groupBy('p.id');

        return $this->paginator->paginate($qb->getQuery(), $page, $limit);
    }

    /**
     * There is no easy way to inject things into repositories yet.
     */
    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
    }
}

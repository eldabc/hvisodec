<?php

namespace Siwapp\ProviderBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Siwapp\CoreBundle\Entity\Serie;
use Siwapp\ProviderBundle\Entity\InvoiceProvider;
use Siwapp\CoreBundle\Repository\AbstractInvoiceRepository;


/**
 * InvoiceProviderRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InvoiceProviderRepository extends AbstractInvoiceRepository
{
	public function paginatedSearch(array $params, $limit = 50, $page = 1)
	{
		if (!$this->paginator) {
			throw new \RuntimeException('You have to set a paginator first using setPaginator() method');
		}
		
		$qb = $this->getEntityManager()
		->createQueryBuilder()
		->from($this->getEntityName(), 'i');
		
		$this->addPaginatedSearchSelects($qb);
		$this->applySearchParamsToQuery($params, $qb);
		
		return $this->paginator->paginate($qb->getQuery(), $page, $limit);
	}
	
	
	protected function applySearchParamsToQuery(array $params, QueryBuilder $qb)
	{
	    foreach ($params as $field => $value) {
	        if ($value === null) {
	            continue;
	        }elseif ($field == 'terms') {
	            $terms = $qb->expr()->literal("%$value%");
	            $qb->andWhere($qb->expr()->orX(
	                $qb->expr()->like('i.provider_name', $terms),
	                $qb->expr()->like('i.provider_identification', $terms)
	                ));
	        }elseif ($field == 'provider') {
	            $provider = $qb->expr()->literal("%$value%");
	            $qb->andWhere($qb->expr()->orX(
	                $qb->expr()->like('i.provider_name', $provider),
	                $qb->expr()->like('i.provider_identification', $provider)
	                ));
	        }elseif ($field == 'date_from'){
	            $qb->andWhere('i.issue_date >= :inicio');
	            if(is_object($params['date_from']))
	                $qb->setParameter('inicio', $params['date_from']->format('Y-m-d'));
	                else
	                    $qb->setParameter('inicio', $params['date_from']);
	        }elseif ($field == 'date_to'){
	            $qb->andWhere('i.issue_date <= :fin');
	            if(is_object($params['date_to']))
	                $qb->setParameter('fin', $params['date_to']->format('Y-m-d'));
	                else
	                    $qb->setParameter('fin', $params['date_to']);
	        }
	    }
	}
	
	protected function addPaginatedSearchSelects(QueryBuilder $qb)
	{
	    // Select everything by default.
	    $qb->select('i');
	}
	
	public function updateInvoiceDue(){
	    
	    $fecha = new \DateTime('now');
	    
	    $qb = $this->getEntityManager()
	    ->createQueryBuilder()
	    ->update($this->getEntityName(), 'i')
	    ->set('i.status', 3)
	    ->where('i.due_date <= :due')
	    ->andWhere('i.status != :borrador')
	    ->andWhere('i.status != :cerrada')
	    ->andWhere('i.status != :vencida')
	    ->setParameter('due', $fecha->format('Y-m-d'))
	    ->setParameter('borrador', InvoiceProvider::DRAFT)
	    ->setParameter('cerrada', InvoiceProvider::CLOSED)
	    ->setParameter('vencida', InvoiceProvider::OVERDUE)
	    ->getQuery();
	    
	    return $qb->execute();
	    
	}
	
	public function getItemInvoiceProvider($item, $invoice){
	    
	    $qb = $this->getEntityManager()
	    ->createQueryBuilder()
        ->select('i')
        ->from(InvoiceProvider::class, 'i')
	    ->innerJoin('i.item', 'it')
	    ->where('it.id = :item_id')
	    ->setParameter('item_id', $item)
	    ->getQuery();
	    
	    return $qb->execute();
	}
}
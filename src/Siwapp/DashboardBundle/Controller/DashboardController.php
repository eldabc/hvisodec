<?php

namespace Siwapp\DashboardBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\OrderBundle\Entity\Order;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard", name="dashboard_index")
     * @Template("SiwappDashboardBundle:Dashboard:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $invoiceRepo = $em->getRepository('SiwappInvoiceBundle:Invoice');
        $invoiceProviderRepo = $em->getRepository('SiwappProviderBundle:InvoiceProvider');
        $orderRepo = $em->getRepository('SiwappEstimateBundle:Estimate');
        $estimateRepo = $em->getRepository('SiwappOrderBundle:Order');
        $invoiceRepo->setPaginator($this->get('knp_paginator'));

        $taxRepo = $em->getRepository('SiwappCoreBundle:Tax');
        $taxes = $taxRepo->findAll();
        
        $unoDeEnero = date('Y')."-01-01";

        $form = $this->createForm('Siwapp\InvoiceBundle\Form\SearchInvoiceType', null, [
            'action' => $this->generateUrl('dashboard_index'),
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $params = $form->getData();
        } else {
        	$form->get('date_from')->setData(\DateTime::createFromFormat('Y-m-d', $unoDeEnero));
        	$params = ['date_from' => $unoDeEnero];
        }
        // Last invoices.
        // @todo Unhardcode this.
        $limit = 5;
        $pagination = $invoiceRepo->paginatedSearch($params, $limit, $request->query->getInt('page', 1));
        $totals = $invoiceRepo->getTotals($params);

        // Last overdue invoices.
        $overdueParams = $params;
        $overdueParams['status'] = Invoice::OVERDUE;
        // @todo Unhardcode this.
        $limit = 50;
        $paginationDue = $invoiceRepo->paginatedSearch($overdueParams, $limit, $request->query->getInt('page', 1));
        $totalsDue = $invoiceRepo->getTotals($overdueParams);
        $totals['overdue'] = $totalsDue['due'];
        
        $pendingParams = $params;
        $pendingParams['status'] = Order::PENDING;
        $totals_estimate = $estimateRepo->getTotals($pendingParams);
        $totals_order = $orderRepo->getTotals($pendingParams);
        $totals_provider = $invoiceProviderRepo->getTotals($params);

        // Tax totals.
        foreach ($taxes as $tax) {
            $taxId = $tax->getId();
            $params['tax'] = $taxId;
            $taxTotals = $invoiceRepo->getTotals($params);
            $totals['taxes'][$taxId] = $taxTotals['tax_' . $taxId];
        }

        return [
            'invoices' => $pagination,
            'overdue_invoices' => $paginationDue,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
            'search_form' => $form->createView(),
            'totals' => $totals,
        	    'totals_provider' => $totals_provider,
            'totals_order' => $totals_order,
            'totals_estimate' => $totals_estimate,
            'taxes' => $taxes,
            'paginatable' => false,
            'sortable' => false,
        ];
    }
}

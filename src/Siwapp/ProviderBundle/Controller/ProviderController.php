<?php

namespace Siwapp\ProviderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Siwapp\ProviderBundle\Entity\Provider;
use Siwapp\ProviderBundle\Entity\InvoiceProvider;
use Siwapp\ProviderBundle\Form\InvoiceProviderType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;
use Siwapp\ProviderBundle\Entity\Credit;

/**
 * @Route("/provider")
 */
class ProviderController extends Controller
{
    /**
     * @Route("/list", name="provider_index")
     * @Template("SiwappProviderBundle:Provider:index.html.twig")
     */
	public function indexAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$repo = $em->getRepository('SiwappProviderBundle:Provider');
    	$repo->setPaginator($this->get('knp_paginator'));
    	// @todo Unhardcode this.
    	$limit = 50;
    	
    	$form = $this->createForm('Siwapp\ProviderBundle\Form\SearchProviderType', null, [
    			'action' => $this->generateUrl('provider_index'),
    			'method' => 'GET',
    	]);
    	$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$pagination = $repo->paginatedSearch($form->getData(), $limit, $request->query->getInt('page', 1));
    	} else {
    		$pagination = $repo->paginatedSearch([], $limit, $request->query->getInt('page', 1));
    	}
    	
    	$providers = [];
    	foreach ($pagination->getItems() as $item) {
    		$providers[] = $item[0];
    	}
    	
    	
    	$listForm = $this->createForm('Siwapp\ProviderBundle\Form\ProviderListType', $providers, [
    			'action' => $this->generateUrl('provider_index'),
    	]);
    	$listForm->handleRequest($request);
    	if ($listForm->isSubmitted() && $listForm->isValid()) {
    		$data = $listForm->getData();
    		if ($request->request->has('delete')) {
    			if (empty($data['providers'])) {
    				$this->addTranslatedMessage('flash.nothing_selected', 'warning');
    			}
    			else {
    				foreach ($data['providers'] as $provider) {
    					$em->remove($provider);
    				}
    				$em->flush();
    				$this->addTranslatedMessage('flash.bulk_deleted');
    				
    				// Rebuild the query, since some objects are now missing.
    				return $this->redirect($this->generateUrl('provider_index'));
    			}
    		}
    	}
    	
    	return array(
    			'providers' => $pagination,
    			'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
    			'search_form' => $form->createView(),
    			'list_form' => $listForm->createView(),
    	);
    }
    
    /**
     * @Route("/autocomplete", name="provider_autocomplete")
     */
    public function autocompleteAction(Request $request)
    {
    	$entities = $this->getDoctrine()
    	->getRepository('SiwappProviderBundle:Provider')
    	->findLike($request->get('term'));
    	
    	return new JsonResponse($entities);
    }
    
    /**
     * @Route("/add", name="provider_add")
     * @Template("SiwappProviderBundle:Provider:edit.html.twig")
     */
    public function addAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$provider= new Provider();
    	
    	$form = $this->createForm('Siwapp\ProviderBundle\Form\ProviderType', $provider, [
    			'action' => $this->generateUrl('provider_add'),
    	]);
    	$form->handleRequest($request);
    	
    	if ($form->isSubmitted() && $form->isValid()) {
    		$em->persist($provider);
    		$em->flush();
    		$this->addTranslatedMessage('flash.added');
    		
    		return $this->redirect($this->generateUrl('provider_edit', array('id' => $provider->getId())));
    	}
    	
    	return array(
    			'form' => $form->createView(),
    			'entity' => $provider,
    	);
    }
    
    /**
     * @Route("/{id}/edit", name="provider_edit")
     * @Template("SiwappProviderBundle:Provider:edit.html.twig")
     */
    public function editAction(Request $request, $id)
    {
    	$em = $this->getDoctrine()->getManager();
    	$provider = $em->getRepository('SiwappProviderBundle:Provider')->find($id);
    	if (!$provider) {
    		throw $this->createNotFoundException('Unable to find Customer entity.');
    	}
    	
    	$form = $this->createForm('Siwapp\ProviderBundle\Form\ProviderType', $provider, [
    			'action' => $this->generateUrl('provider_edit', ['id' => $id]),
    	]);
    	$form->handleRequest($request);
    	
    	if ($form->isSubmitted() && $form->isValid()) {
    		$em->persist($provider);
    		$em->flush();
    		$this->addTranslatedMessage('flash.updated');
    		
    		return $this->redirect($this->generateUrl('provider_edit', array('id' => $provider->getId())));
    	}
    	
    	return array(
    			'form' => $form->createView(),
    			'entity' => $provider,
    	);
    }
    
    /**
     * @Route("/{id}/delete", name="provider_delete")
     */
    public function deleteAction($id)
    {
    	$em = $this->getDoctrine()->getManager();
    	$provider= $em->getRepository('SiwappProviderBundle:Provider')->find($id);
    	if (!$provider) {
    		throw $this->createNotFoundException('Unable to find Provider entity.');
    	}
    	$em->remove($provider);
    	$em->flush();
    	$this->addTranslatedMessage('flash.deleted');
    	
    	return $this->redirect($this->generateUrl('provider_index'));
    }
    
    protected function addTranslatedMessage($message, $status = 'success')
    {
    	$translator = $this->get('translator');
    	$this->get('session')
    	->getFlashBag()
    	->add($status, $translator->trans($message, [], 'SiwappProviderBundle'));
    }
    
    protected function bulkDelete(array $invoices)
    {
    	$em = $this->getDoctrine()->getManager();
    	foreach ($invoices as $invoice) {
    		$em->remove($invoice);
    	}
    	$em->flush();
    	$this->addTranslatedMessage('flash.bulk_deleted');
    	
    	return $this->redirect($this->generateUrl('invoprovider_list'));
    }
    
    protected function bulkInvoicesDownload(array $invoices)
    {
        
    	$em = $this->getDoctrine()->getManager();
    	$pages = [];
    	
    	$uploads = $this->get('kernel')->getRootDir().'/../web/uploads/';
    	
    	foreach ($invoices as $invoice) {
    		//$entity = $em->getRepository('SiwappProviderBundle:InvoiceProvider')->find($invoice->getId());

    		//if ($entity) {
    		    $pages[] = $uploads.$invoice->getInvoice();
    		//}
    	}
    
    	$zipname = 'invoices_providers.zip';
    	unlink($uploads.$zipname);
    	$zip = new ZipArchive;
    	$zip->open($uploads.$zipname, ZipArchive::CREATE);

    	foreach ($pages as $page) {
    		$zip->addFile($page, basename($page));
    	}
    	$zip->close(); 	
    	
    	header('Content-Type: application/zip');
    	header('Content-disposition: attachment; filename='.$zipname);
    	header('Content-Length: ' . filesize($uploads.$zipname));
    	readfile($uploads.$zipname);
    }
}

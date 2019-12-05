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
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\ProviderBundle\Form\InvoiceProviderType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use ZipArchive;
use Siwapp\ProviderBundle\Entity\Credit;
use Siwapp\CoreBundle\Controller\AbstractInvoiceController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Siwapp\ProductBundle\Entity\Product;

/**
 * @Route("/provider")
 */
class InvoiceProviderController extends AbstractInvoiceController
{    
    
    /**
     * @Route("/invoice", name="invoprovider_list")
     * @Template("SiwappProviderBundle:InvoiceProvider:index.html.twig")
     */
    public function indexAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$repo = $em->getRepository('SiwappProviderBundle:InvoiceProvider');
    	$repo->setPaginator($this->get('knp_paginator'));
    	// @todo Unhardcode this.
    	$limit = 50;
    	
    	$unoDeEnero = date('Y')."-01-01";
    	
    	$form = $this->createForm('Siwapp\ProviderBundle\Form\SearchInvoiceProviderType', null, [
    			'action' => $this->generateUrl('invoprovider_list'),
    			'method' => 'GET',
    	]);

    	$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$pagination = $repo->paginatedSearch($form->getData(), $limit, $request->query->getInt('page', 1));
    	} else {
    		$form->get('date_from')->setData(\DateTime::createFromFormat('Y-m-d', $unoDeEnero));
    		$params = ['date_from' => $unoDeEnero];
    		$pagination = $repo->paginatedSearch($params, $limit, $request->query->getInt('page', 1));
    	}

    	$invoices = [];
    	foreach ($pagination->getItems() as $item) {
    		$invoices[] = $item;
    	}
    	
    	$listForm = $this->createForm('Siwapp\ProviderBundle\Form\InvoiceProviderListType', $invoices, [
    			'action' => $this->generateUrl('invoprovider_list'),
    	]);

    	$listForm->handleRequest($request);
    	if ($listForm->isSubmitted()) {
    		$data = $listForm->getData();
    		
    		if (empty($data['invoices'])) {
    			$this->addTranslatedMessage('flash.nothing_selected', 'warning');
    		}
    		else {
    			if ($request->request->has('delete')) {
    				return $this->bulkDelete($data['invoices']);
    			} elseif ($request->request->has('pdf')) {
    				return $this->bulkInvoicesDownload($data['invoices']);
    			} 
    		}
    	}
    	
    	return array(
    			'invoices' => $pagination,
    			'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
    			'search_form' => $form->createView(),
    			'list_form' => $listForm->createView(),
    	);
    }
    
    /**
     * @Route("/invoice/new", name="invoprovider_add")
     * @Template("SiwappProviderBundle:InvoiceProvider:edit.html.twig")
     */
    public function newAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$invoice = new InvoiceProvider();
    	$newItem = new Item($em->getRepository('SiwappCoreBundle:Tax')->findBy(['is_default' => 1]));
    	$invoice->addItem($newItem);
    	
    	$defaultInvoiceSerie = $terms = $em->getRepository('SiwappCoreBundle:Series')->findOneBy(array('default_invoice' => true, 'enabled' => true));
    	
    	$customer = $em->getRepository('SiwappCustomerBundle:Customer')->find(1);
    	
    	$invoice->setCustomer($customer);
    	$invoice->setCustomerName("Softcode");
    	$invoice->setCustomerEmail("info@softcode.es");
    	$invoice->setSeries($defaultInvoiceSerie);
    	
    	$form = $this->createForm('Siwapp\ProviderBundle\Form\InvoiceProviderType', $invoice, [
    			'action' => $this->generateUrl('invoprovider_add'),
			   'default_invoice' => $defaultInvoiceSerie,
		]);

		$form->handleRequest($request);
		

    	if ($form->isSubmitted()) {

    		$data = $form->getData();

    		if (!empty($data->getInvoice())) {
    			/** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
    			$file = $data->getInvoice();

    			// Move the file to the uploads directory.
    			$uploadsDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
    			
    			try {
    				$newFile = $file->move($uploadsDir, $file->getClientOriginalName());
    				// Update the property to the new file name.
    				$data->setInvoice($newFile->getFileName());
    			}
    			catch (FileException $e) {
    				$msg = $translator->trans('flash.logo_upload_error', [], 'SiwappConfigBundle');
    				$this->get('session')->getFlashBag()->add('warning', $msg);
    			}
    		}
    		
    		$providerEntity = $em->getRepository('SiwappProviderBundle:Provider')->findOneBy(array('identification' => $invoice->getProviderIdentification()));
    		
    		// Si no se encontró el cliente, se le notifica al usuario que debe darlo de alta
    		if (!$providerEntity) {
    			
    			$this->addTranslatedMessage('flash.unregistered_provider', 'danger');
    			
    		} else {
    			
    			$invoice->setProvider($providerEntity);
    			
    			$em->persist($invoice);
    			$em->flush();
    			$this->addTranslatedMessage('flash.invo_added');
    			
    			return $this->redirect($this->generateUrl('invoprovider_edit', array('id' => $invoice->getId())));
    			
    		}		
    		
    	}
    	
    	return array(
    			'form' => $form->remove('expirationDate')->createView(),
    			'entity' => $invoice,
    			'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
    	);
    }
    
    /**
     * @Route("/invoice/{id}/edit", name="invoprovider_edit")
     * @Template("SiwappProviderBundle:InvoiceProvider:edit.html.twig")
     */
    public function editInvoiceAction(Request $request, $id)
    {
    	$em = $this->getDoctrine()->getManager();
    	$entity = $em->getRepository('SiwappProviderBundle:InvoiceProvider')->find($id);
    	if (!$entity) {
    		throw $this->createNotFoundException('Unable to find Invoice Provider entity.');
    	}
    	
    	$old_invoice = $entity->getInvoice();
    	
    	$form = $this->createForm(InvoiceProviderType::class, $entity, [
    			'action' => $this->generateUrl('invoprovider_edit', ['id' => $id]),
    			'editing' => false,
    	]);
    	$form->handleRequest($request);
    	
    	if ($form->isSubmitted()) {
    		
    		$data = $form->getData();
    		
    		if (!empty($data->getInvoice())) {

    			$file = $data->getInvoice();
    			$fileName = $file->getClientOriginalName();
    			
    			$uploadsDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
    			try {
    				$newFile = $file->move($uploadsDir, $fileName);
    				$data->setInvoice($newFile->getFileName());
    				
    				if (is_file($uploadsDir."/".$old_invoice)){
    					unlink($uploadsDir."/".$old_invoice);
    				}
    			}
    			catch (FileException $e) {
    				$msg = $translator->trans('flash.logo_upload_error', [], 'SiwappConfigBundle');
    				$this->get('session')->getFlashBag()->add('warning', $msg);
    			}
    		}else{
    			$entity->setInvoice($old_invoice);
    		}
    		
    		$providerEntity = $em->getRepository('SiwappProviderBundle:Provider')->findOneBy(array('identification' => $entity->getProviderIdentification()));
    		
    		// Si no se encontró el cliente, se le notifica al usuario que debe darlo de alta
    		if (!$providerEntity) {
    			$this->addTranslatedMessage('flash.unregistered_provider', 'danger');
    		} else {

    			$entity->setProvider($providerEntity);

    			$uow = $em->getUnitOfWork();
    			$uow->computeChangeSets();

    			foreach ($entity->getItems() as $item){
    			    $changeset = $uow->getEntityChangeSet($item);
    			    
    			    $product = $item->getProduct();
    			    
    			    if($product instanceof Product){
        			    if($changeset['quantity'][0] != $changeset['quantity'][1]){
        			        
        			        if ($changeset['quantity'][0] < $changeset['quantity'][1]){
        			            $diff = ($changeset['quantity'][1] - $changeset['quantity'][0]);
        			            $product->setStock(intval($product->getStock() + $diff));
        			        }else{
        			            $diff = ($changeset['quantity'][0] - $changeset['quantity'][1]);
        			            $product->setStock($product->getStock() - $diff);
        			        }
        			        $em->persist($product);
        			    }
    			    }
    			}    			

    			$em->persist($entity);
    			$em->flush();
    			$this->addTranslatedMessage('flash.invo_added');
    			
    			return $this->redirect($this->generateUrl('invoprovider_edit', array('id' => $entity->getId())));
    			
    		}
    	}
    	
    	return array(
    			'entity' => $entity,
    			'form' => $form->createView(),
    			'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
    	);
    }
    
    /**
     * @Route("/invoice/download/{name}", name="download_file")
     */
    public function downloadFileAction($name){
    	
    	$path = $this->container->getParameter('kernel.root_dir').'/../web/uploads/';
    	$path .= $name;
    	
    	$response = new BinaryFileResponse($path);
    	$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name);
    	return $response;
    }
    
    /**
     * @Route("/invoice/{id}/delete", name="invoprovider_delete")
     */
    public function deleteInvoiceAction($id)
    {
    	$em = $this->getDoctrine()->getManager();
    	$invoice = $em->getRepository('SiwappProviderBundle:InvoiceProvider')->find($id);
    	if (!$invoice) {
    		throw $this->createNotFoundException('Unable to find Invoice Provider entity.');
    	}
    	$em->remove($invoice);
    	$em->flush();
    	$this->addTranslatedMessage('flash.invo_deleted');
    	
    	return $this->redirect($this->generateUrl('invoprovider_list'));
    }
    
    /**
     * @Route("/{invoiceId}/credits", name="invoice_credits")
     * @Template("SiwappProviderBundle:Credit:list.html.twig")
     */
    public function creditsAction(Request $request, $invoiceId)
    {
        // Return all payments
        $em = $this->getDoctrine()->getManager();
        $invoice = $em->getRepository('SiwappProviderBundle:InvoiceProvider')->find($invoiceId);
        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        
        $credit = new Credit();
        $addForm = $this->createForm('Siwapp\ProviderBundle\Form\CreditType', $credit, [
            'action' => $this->generateUrl('invoice_credits', ['invoiceId' => $invoiceId]),
        ]);
        $addForm->handleRequest($request);
        if ($addForm->isSubmitted() && $addForm->isValid()) {
            $invoice->addCredit($credit);
            $em->persist($invoice);
            $em->flush();
            $this->addTranslatedMessage('payment.flash.added');
            
            // Rebuild the query, since we have new objects now.
            return $this->redirect($this->generateUrl('invoprovider_list'));
        }
        
        $listForm = $this->createForm('Siwapp\ProviderBundle\Form\InvoiceCreditListType', $invoice->getCredits()->getValues(), [
            'action' => $this->generateUrl('invoice_credits', ['invoiceId' => $invoiceId]),
        ]);
        $listForm->handleRequest($request);
        
        if ($listForm->isSubmitted() && $listForm->isValid()) {
            $data = $listForm->getData();
            foreach ($data['payments'] as $credit) {
                $invoice->removeCredit($credit);
                $em->persist($invoice);
                $em->flush();
            }
            $this->addTranslatedMessage('payment.flash.bulk_deleted');
            
            // Rebuild the query, since some objects are now missing.
            return $this->redirect($this->generateUrl('invoprovider_list'));
        }
        
        return [
            'invoiceId' => $invoiceId,
            'add_form' => $addForm->createView(),
            'list_form' => $listForm->createView(),
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        ];
    }
    
    /**
     * @Route("/form-totals", name="invoice_provider_form_totals")
     */
    public function getInvoiceFormTotals(Request $request)
    {
        $post = $request->request->get('invoice_provider');
        if (!$post) {
            throw new NotFoundHttpException;
        }
        
        $response = $this->getInvoiceTotalsFromPost($post, new InvoiceProvider, $request->getLocale());
        
        return new JsonResponse($response);
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

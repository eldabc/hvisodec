<?php

namespace Siwapp\InvoiceBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Siwapp\CoreBundle\Controller\AbstractInvoiceController;
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\InvoiceBundle\Entity\Payment;
use Siwapp\InvoiceBundle\Form\InvoiceType;
use Siwapp\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @Route("/invoice")
 */
class InvoiceController extends AbstractInvoiceController
{
    const STR_WHITE = " ";
    
    /**
     * @Route("", name="invoice_index")
     * @Template("SiwappInvoiceBundle:Invoice:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('SiwappInvoiceBundle:Invoice');
        $repo->setPaginator($this->get('knp_paginator'));
        // @todo Unhardcode this.
        $limit = 50;
        
        $unoDeEnero = date('Y')."-01-01";

        $form = $this->createForm('Siwapp\InvoiceBundle\Form\SearchInvoiceType', null, [
            'action' => $this->generateUrl('invoice_index'),
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
            $invoices[] = $item[0];
        }
        $listForm = $this->createForm('Siwapp\InvoiceBundle\Form\InvoiceListType', $invoices, [
            'action' => $this->generateUrl('invoice_index'),
        ]);

        $listForm->handleRequest($request);

        if ($listForm->isSubmitted()) {
        	$data = $request->request->get('invoice_list');
            if (empty($data['invoices'])) {
                $this->addTranslatedMessage('flash.nothing_selected', 'warning');
            }
            else {
            	
            	$invos = array();
            	
            	foreach ($data['invoices'] as $value){
            		$invos['invoices'][] = $em->getRepository('SiwappInvoiceBundle:Invoice')->find($value);
            	}
            	
                if ($request->request->has('delete')) {
                	return $this->bulkDelete($invos['invoices']);
                } elseif ($request->request->has('pdf')) {
                	return $this->bulkPdf($invos['invoices']);
                } elseif ($request->request->has('print')) {
                	return $this->bulkPrint($invos['invoices']);
                } elseif ($request->request->has('email')) {
                	return $this->bulkEmail($invos['invoices']);
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
     * @Route("/{id}/show", name="invoice_show")
     * @Template("SiwappInvoiceBundle:Invoice:show.html.twig")
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('SiwappInvoiceBundle:Invoice')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        if (!$entity->isClosed()) {
            // When the invoice is open send to the edit form by default.
            return $this->redirect($this->generateUrl('invoice_edit', array('id' => $id)));
        }

        return array(
            'entity' => $entity,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        );
    }

    /**
     * @Route("/{id}/show/print", name="invoice_show_print")
     */
    public function showPrintAction($id)
    {
        $invoice = $this->getDoctrine()
            ->getRepository('SiwappInvoiceBundle:Invoice')
            ->find($id);
        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        return new Response($this->getInvoicePrintPdfHtml($invoice, true));
    }

    /**
     * @Route("/{id}/show/pdf", name="invoice_show_pdf")
     */
    public function showPdfAction($id)
    {
        $invoice = $this->getDoctrine()
            ->getRepository('SiwappInvoiceBundle:Invoice')
            ->find($id);
        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        $html = $this->getInvoicePrintPdfHtml($invoice);
        $pdf = $this->getPdf($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoice-' . $invoice->label() . '.pdf"'
        ]);
    }

    /**
     * @Route("/new", name="invoice_add")
     * @Template("SiwappInvoiceBundle:Invoice:edit.html.twig")
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $invoice = new Invoice();
        $newItem = new Item($em->getRepository('SiwappCoreBundle:Tax')->findBy(['is_default' => 1]));
        $invoice->addItem($newItem);
        $terms = $em->getRepository('SiwappConfigBundle:Property')->get('legal_terms');
        if ($terms) {
            $invoice->setTerms($terms);
        }
        
        $defaultInvoiceSerie = $terms = $em->getRepository('SiwappCoreBundle:Series')->findOneBy(array('default_invoice' => true, 'enabled' => true));

        $form = $this->createForm('Siwapp\InvoiceBundle\Form\InvoiceType', $invoice, [
            'action' => $this->generateUrl('invoice_add'),
        	'default_invoice' => $defaultInvoiceSerie,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            if ($request->request->has('save_draft')) {
                $invoice->setStatus(Invoice::DRAFT);
            } else {
                // Any save action transforms this to opened.
                $invoice->setStatus(Invoice::OPENED);
            }
            
            //Validation stock
            foreach($invoice->getItems() as $item){
                if(!$item->getProduct() instanceof Product){
                    continue;
                }
                
                if($item->getProduct()->getStock() < $item->getQuantity()){
                    
                    $this->addTranslatedMessage('Solo quedan '.$item->getProduct()->getStock().' unidades del producto '.$item->getProduct()->getReference().' en stock', 'warning');
                    
                    return array(
                        'form' => $form->createView(),
                        'entity' => $invoice,
                        'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
                    );
                    
                }
            }
            
            
            $em->persist($invoice);
            $em->flush();
            $this->addTranslatedMessage('flash.added');

            // Send the email after the invoice is updated.
            if ($request->request->has('save_email')) {
                $message = $this->getEmailMessage($invoice);
                $result = $this->get('mailer')->send($message);
                if ($result) {
                    $this->addTranslatedMessage('flash.emailed');
                    if (!$invoice->isSentByEmail()) {
                        $invoice->setSentByEmail(true);
                        $em->persist($invoice);
                        $em->flush();
                    }
                }
            }

            return $this->redirect($this->generateUrl('invoice_edit', array('id' => $invoice->getId())));
        }

        return array(
            'form' => $form->createView(),
            'entity' => $invoice,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        );
    }

    /**
     * @Route("/{id}/edit", name="invoice_edit")
     * @Template("SiwappInvoiceBundle:Invoice:edit.html.twig")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('SiwappInvoiceBundle:Invoice')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $form = $this->createForm(InvoiceType::class, $entity, [
            'action' => $this->generateUrl('invoice_edit', ['id' => $id]),
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirectRoute = 'invoice_edit';
            if ($request->request->has('save_draft')) {
                $entity->setStatus(Invoice::DRAFT);
            } elseif ($request->request->has('save_close')) {
                $entity->setForcefullyClosed(true);
            } elseif ($entity->isDraft()) {
                // Any save action transforms this to opened.
                $entity->setStatus(Invoice::OPENED);
            }

            // See if one of PDF/Print buttons was clicked.
            if ($request->request->has('save_pdf')) {
                $redirectRoute = 'invoice_show_pdf';
            } elseif ($request->request->has('save_print')) {
                $this->get('session')->set('invoice_auto_print', $id);
            }
            
            $uow = $em->getUnitOfWork();
            $uow->computeChangeSets();

            foreach ($entity->getItems() as $item){
                $changeset = $uow->getEntityChangeSet($item);

                $product = $item->getProduct();
                
                if($product instanceof Product){
                    
                    if(($item->getProduct()->getStock()+$changeset['quantity'][0]) >= abs($item->getQuantity())){
                    

                        if($changeset['quantity'][0] != $changeset['quantity'][1]){
                            
                            if ($changeset['quantity'][0] < $changeset['quantity'][1]){
                                $diff = ($changeset['quantity'][1] - $changeset['quantity'][0]);
                                $product->setStock(intval($product->getStock() - $diff));
                            }else{
                                $diff = ($changeset['quantity'][0] - $changeset['quantity'][1]);
                                $product->setStock($product->getStock() + $diff);
                            }
                            
                            $em->persist($product);
                            
                        }else {
                            $change_state = $uow->getEntityChangeSet($entity);
            
                            if (isset($change_state['status'][0]) && isset($change_state['status'][1])){
                                if ($change_state['status'][0] == 0 && $change_state['status'][1] > 0){
                                    
                                    if ($changeset['quantity'][0] < $changeset['quantity'][1]){
                                        $diff = ($changeset['quantity'][1] - $changeset['quantity'][0]);
                                        $product->setStock(intval($product->getStock() - $diff));
                                    }elseif($changeset['quantity'][0] > $changeset['quantity'][1]){
                                        $diff = ($changeset['quantity'][0] - $changeset['quantity'][1]);
                                        $product->setStock($product->getStock() + $diff);
                                    }else{
                                        $product->setStock($product->getStock() - $changeset['quantity'][0]);
                                    }
                                    
                                    $em->persist($product);
                                }
                            }
                        }
                        
                    }else{
                        $this->addTranslatedMessage('Solo quedan '.($item->getProduct()->getStock()+$changeset['quantity'][0]).' unidades del producto '.$item->getProduct()->getReference().' en stock', 'warning');
                        
                        return array(
                            'form' => $form->createView(),
                            'entity' => $entity,
                            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
                        );
                    }
                }
            }
            
            // Save.
            $em->persist($entity);
            $em->flush();
            $this->addTranslatedMessage('flash.updated');

            // Send the email after the invoice is updated.
            if ($request->request->has('save_email')) {
                $message = $this->getEmailMessage($entity);
                $result = $this->get('mailer')->send($message);
                if ($result) {
                    $this->addTranslatedMessage('flash.emailed');
                    if (!$entity->isSentByEmail()) {
                        $entity->setSentByEmail(true);
                        $em->persist($entity);
                        $em->flush();
                    }
                }
            }

            return $this->redirect($this->generateUrl($redirectRoute, array('id' => $id)));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        );
    }

    /**
     * @Route("/{id}/email", name="invoice_email")
     * @Method({"POST"})
     */
    public function emailAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $invoice = $em->getRepository('SiwappInvoiceBundle:Invoice')->find($id);
        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        $message = $this->getEmailMessage($invoice);
        $result = $this->get('mailer')->send($message);
        if ($result) {
            $invoice->setSentByEmail(true);
            $em->persist($invoice);
            $em->flush();
            $this->addTranslatedMessage('flash.emailed');
        }

        return $this->redirect($this->generateUrl('invoice_index'));
    }

    /**
     * @Route("/{id}/delete", name="invoice_delete")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $invoice = $em->getRepository('SiwappInvoiceBundle:Invoice')->find($id);
        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }
        $em->remove($invoice);
        $em->flush();
        $this->addTranslatedMessage('flash.deleted');

        return $this->redirect($this->generateUrl('invoice_index'));
    }

    /**
     * @Route("/{invoiceId}/payments", name="invoice_payments")
     * @Template("SiwappInvoiceBundle:Payment:list.html.twig")
     */
    public function paymentsAction(Request $request, $invoiceId)
    {
        // Return all payments
        $em = $this->getDoctrine()->getManager();
        $invoice = $em->getRepository('SiwappInvoiceBundle:Invoice')->find($invoiceId);
        if (!$invoice) {
            throw $this->createNotFoundException('Unable to find Invoice entity.');
        }

        $payment = new Payment;
        $addForm = $this->createForm('Siwapp\InvoiceBundle\Form\PaymentType', $payment, [
            'action' => $this->generateUrl('invoice_payments', ['invoiceId' => $invoiceId]),
        ]);
        $addForm->handleRequest($request);
        if ($addForm->isSubmitted() && $addForm->isValid()) {
            $invoice->addPayment($payment);
            $em->persist($invoice);
            $em->flush();
            $this->addTranslatedMessage('payment.flash.added');

            // Rebuild the query, since we have new objects now.
            return $this->redirect($this->generateUrl('invoice_index'));
        }

        $listForm = $this->createForm('Siwapp\InvoiceBundle\Form\InvoicePaymentListType', $invoice->getPayments()->getValues(), [
            'action' => $this->generateUrl('invoice_payments', ['invoiceId' => $invoiceId]),
        ]);
        $listForm->handleRequest($request);

        if ($listForm->isSubmitted() && $listForm->isValid()) {
            $data = $listForm->getData();
            foreach ($data['payments'] as $payment) {
                $invoice->removePayment($payment);
                $em->persist($invoice);
                $em->flush();
            }
            $this->addTranslatedMessage('payment.flash.bulk_deleted');

            // Rebuild the query, since some objects are now missing.
            return $this->redirect($this->generateUrl('invoice_index'));
        }

        return [
            'invoiceId' => $invoiceId,
            'add_form' => $addForm->createView(),
            'list_form' => $listForm->createView(),
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        ];
    }
    
    /**
     * @Route("/sepa", name="invoice_sepa")
     * @Template("SiwappInvoiceBundle:Invoice:sepa.html.twig")
     */
    public function sepaAction(Request $request)
    {
        
        $form = $this->createForm('Siwapp\InvoiceBundle\Form\SepaType', null, [
            'action' => $this->generateUrl('invoice_sepa'),
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $params = $form->getData();
            
            $em = $this->getDoctrine()->getManager();
            $receipts = $em->getRepository('SiwappInvoiceBundle:Invoice')->searchReceipts($params);
            
            if ($receipts){
                $property_repository = $em->getRepository('SiwappConfigBundle:Property');
                $data = $property_repository->getAll();
                
                $dir = $this->container->getParameter('kernel.root_dir')."/../web/recibos";
                $filename = $dir."/remtest.txt";
                
                if (!file_exists($dir)) {
                    
                    if (!mkdir($dir)){
                        $this->addTranslatedMessage('flash.no_dir');
                        
                        return $this->redirect($this->generateUrl('invoice_sepa'));
                    }
                }
            }
            
            
            $gestor = fopen($filename, "w+");
            
            if (!$gestor){
                $this->addTranslatedMessage('flash.permission_less');
                
                return $this->redirect($this->generateUrl('invoice_sepa'));
            }
            
            //Cabecera presentador
            $reg01 = array(
                "01",
                "19445",
                "001",
                str_pad($data['sepa_id_creditor'], 35, self::STR_WHITE, STR_PAD_RIGHT),
                str_pad( substr($data['company_name'], 0, 70) , 70, self::STR_WHITE, STR_PAD_RIGHT),
                $params['date_creation']->format('Ymd'),
                str_pad("PRE".$params['date_creation']->format('YmdHi')."00000000018600301122", 35, self::STR_WHITE, STR_PAD_RIGHT),
                $data['sepa_entity'],
                $data['sepa_subsidiary'],
                str_pad(self::STR_WHITE, 434, self::STR_WHITE, STR_PAD_RIGHT)
            );
            fwrite($gestor, implode("", $reg01) . "\r\n");
            
            //Cabecera acreedor
            $reg02 = array(
                "02",
                "19445",
                "002",
                str_pad($data['sepa_id_creditor'], 35, self::STR_WHITE, STR_PAD_RIGHT),
                $params['date_payment']->format('Ymd'),
                str_pad( substr($data['company_name'], 0, 70) , 70, self::STR_WHITE, STR_PAD_RIGHT),
                str_pad( substr($data['company_address'], 0, 50) , 50, self::STR_WHITE, STR_PAD_RIGHT),
                str_pad( substr($data['company_cp']." ".$data['company_location'], 0, 50) , 50, self::STR_WHITE, STR_PAD_RIGHT),
                str_pad( substr($data['company_province'], 0, 40) , 40, self::STR_WHITE, STR_PAD_RIGHT),
                "ES",
                str_pad($data['sepa_iban'], 34, self::STR_WHITE, STR_PAD_RIGHT),
                str_pad(self::STR_WHITE, 301, self::STR_WHITE, STR_PAD_RIGHT)
            );
            fwrite($gestor, implode("", $reg02) . "\r\n");
            
            $reg = 0;
            $total = 0;
            foreach ($receipts as $value){
                
                //Individual de adeudo
                $reg03 = array(
                    "03",
                    "19445",
                    "003",
                    str_pad($value->getId(), 35, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad($value->getMandato(), 35, self::STR_WHITE, STR_PAD_RIGHT),
                    "RCUR",
                    "SUPP",
                    str_pad( str_replace('.', '', number_format($value->getGrossAmount(), 2, "." , "")), 11, "0", STR_PAD_LEFT),
                    str_pad($value->getIssueDate()->format('Ymd'), 8, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad( $value->getBic() , 11, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad( substr($value->getCustomerName(), 0, 70) , 70, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad( substr($value->getInvoicingAddress(), 0, 50) , 50, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad( substr($value->getPostalCode()." ".$value->getLocation(), 0, 50) , 50, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad( substr($value->getProvince(), 0, 40) , 40, self::STR_WHITE, STR_PAD_RIGHT),
                    "ES",
                    "1",
                    str_pad( "A".$value->getBic(), 36, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad( self::STR_WHITE , 35, self::STR_WHITE, STR_PAD_RIGHT),
                    "A",
                    str_pad( substr($value->getIban(), 0, 34) , 34, self::STR_WHITE, STR_PAD_RIGHT),
                    "RCPT",
                    str_pad( $value->getId() , 140, self::STR_WHITE, STR_PAD_RIGHT),
                    str_pad(self::STR_WHITE, 19, self::STR_WHITE, STR_PAD_RIGHT)
                );
                fwrite($gestor, implode("", $reg03) . "\r\n");
                
                $reg++;
                $total += $value->getGrossAmount();
                
            }
            
            //Total acreedor por fecha
            $reg04 = array(
                "04",
                str_pad($data['sepa_id_creditor'], 35, self::STR_WHITE, STR_PAD_RIGHT),
                $params['date_payment']->format('Ymd'),
                str_pad( str_replace('.', '', number_format($total, 2, "." , "")), 17, "0", STR_PAD_LEFT),
                str_pad( $reg, 8, "0", STR_PAD_LEFT),
                str_pad( ($reg+2), 10, "0", STR_PAD_LEFT),
                str_pad(self::STR_WHITE, 520, self::STR_WHITE, STR_PAD_RIGHT)
            );
            fwrite($gestor, implode("", $reg04) . "\r\n");
            
            //Total acreedor
            $reg05 = array(
                "05",
                str_pad($data['sepa_id_creditor'], 35, self::STR_WHITE, STR_PAD_RIGHT),
                str_pad( str_replace('.', '', number_format($total, 2, "." , "")), 17, "0", STR_PAD_LEFT),
                str_pad( $reg, 8, "0", STR_PAD_LEFT),
                str_pad( ($reg+3), 10, "0", STR_PAD_LEFT),
                str_pad(self::STR_WHITE, 528, self::STR_WHITE, STR_PAD_RIGHT)
            );
            fwrite($gestor, implode("", $reg05) . "\r\n");
            
            
            //Total acreedor
            $reg99 = array(
                "99",
                str_pad( str_replace('.', '', number_format($total, 2, "." , "")), 17, "0", STR_PAD_LEFT),
                str_pad( $reg, 8, "0", STR_PAD_LEFT),
                str_pad( ($reg+5), 10, "0", STR_PAD_LEFT),
                str_pad(self::STR_WHITE, 563, self::STR_WHITE, STR_PAD_RIGHT)
            );
            fwrite($gestor, implode("", $reg99) . "\r\n");
            
            fclose($gestor);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);
            header("Content-Type: text/plain");
            header("Content-Disposition: attachment; filename=".basename($filename));
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($filename));
            
            ob_clean();
            flush();
            readfile( $filename );
            exit();

        }
        
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/form-totals", name="invoice_form_totals")
     */
    public function getInvoiceFormTotals(Request $request)
    {
        $post = $request->request->get('invoice');
        if (!$post) {
            throw new NotFoundHttpException;
        }
        // print_r($post);
        $response = $this->getInvoiceTotalsFromPost($post, new Invoice, $request->getLocale());

        return new JsonResponse($response);
    }

    protected function addTranslatedMessage($message, $status = 'success')
    {
        $translator = $this->get('translator');
        $this->get('session')
            ->getFlashBag()
            ->add($status, $translator->trans($message, [], 'SiwappInvoiceBundle'));
    }

    protected function getInvoicePrintPdfHtml(Invoice $invoice, $print = false)
    {
        $settings = $this->getDoctrine()
            ->getRepository('SiwappConfigBundle:Property')
            ->getAll();

        return $this->renderView('SiwappInvoiceBundle:Invoice:print.html.twig', [
            'invoice'  => $invoice,
            'settings' => $settings,
            'print' => $print,
        ]);
    }

    protected function bulkDelete(array $invoices)
    {
        $em = $this->getDoctrine()->getManager();
        foreach ($invoices as $invoice) {
            $em->remove($invoice);
        }
        $em->flush();
        $this->addTranslatedMessage('flash.bulk_deleted');

        return $this->redirect($this->generateUrl('invoice_index'));
    }

    protected function bulkPdf(array $invoices)
    {
        $pages = [];
        foreach ($invoices as $invoice) {
            $pages[] = $this->getInvoicePrintPdfHtml($invoice);
        }

        $html = $this->get('siwapp_core.html_page_merger')->merge($pages, '<div class="pagebreak"> </div>');
        $pdf = $this->getPdf($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoices.pdf"'
        ]);
    }

    protected function bulkPrint(array $invoices)
    {
        $pages = [];
        foreach ($invoices as $invoice) {
            $pages[] = $this->getInvoicePrintPdfHtml($invoice, true);
        }

        $html = $this->get('siwapp_core.html_page_merger')->merge($pages, '<div class="pagebreak"> </div>');

        return new Response($html);
    }

    protected function bulkEmail(array $invoices)
    {
        $em = $this->getDoctrine()->getManager();
        foreach ($invoices as $invoice) {
            $message = $this->getEmailMessage($invoice);
            $result = $this->get('mailer')->send($message);
            if ($result) {
                $invoice->setSentByEmail(true);
                $em->persist($invoice);
            }
        }
        $em->flush();
        $this->addTranslatedMessage('flash.bulk_emailed');

        return $this->redirect($this->generateUrl('invoice_index'));
    }

    protected function getEmailMessage($invoice)
    {
        $em = $this->getDoctrine()->getManager();
        $configRepo = $em->getRepository('SiwappConfigBundle:Property');

        $html = $this->renderView('SiwappInvoiceBundle:Invoice:email.html.twig', array(
            'invoice'  => $invoice,
            'settings' => $em->getRepository('SiwappConfigBundle:Property')->getAll(),
        ));
        $pdf = $this->getPdf($html);
        $attachment = new \Swift_Attachment($pdf, $invoice->getId().'.pdf', 'application/pdf');
        $subject = '[' . $this->get('translator')->trans('invoice.invoice', [], 'SiwappInvoiceBundle') . ': ' . $invoice->label() . ']';
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($configRepo->get('company_email'), $configRepo->get('company_name'))
            ->setTo($invoice->getCustomerEmail(), $invoice->getCustomerName())
            ->setBody($html, 'text/html')
            ->attach($attachment);

        return $message;
    }
}

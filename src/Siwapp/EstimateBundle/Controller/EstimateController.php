<?php

namespace Siwapp\EstimateBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Siwapp\CoreBundle\Controller\AbstractInvoiceController;
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\EstimateBundle\Entity\Estimate;
use Siwapp\EstimateBundle\Form\EstimateType;

/**
 * @Route("/order")
 */
class EstimateController extends AbstractInvoiceController
{
    /**
     * @Route("", name="estimate_index")
     * @Template("SiwappEstimateBundle:Estimate:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('SiwappEstimateBundle:Estimate');
        $repo->setPaginator($this->get('knp_paginator'));
        // @todo Unhardcode this.
        $limit = 50;
        
        $unoDeEnero = date('Y')."-01-01";

        $form = $this->createForm('Siwapp\EstimateBundle\Form\SearchEstimateType', null, [
            'action' => $this->generateUrl('estimate_index'),
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

        $listForm = $this->createForm('Siwapp\EstimateBundle\Form\EstimateListType', $pagination->getItems(), [
            'action' => $this->generateUrl('estimate_index'),
        ]);
        $listForm->handleRequest($request);
        if ($listForm->isSubmitted()) {
        	$data = $request->request->get('estimate_list');
            if (empty($data['estimates'])) {
                $this->addTranslatedMessage('flash.nothing_selected', 'warning');
            }
            else {
            	
            	$invos = array();
            	
            	foreach ($data['estimates'] as $value){
            		$invos['estimates'][] = $em->getRepository('SiwappEstimateBundle:Estimate')->find($value);
            	}
            	
                if ($request->request->has('delete')) {
                	return $this->bulkDelete($invos['estimates']);
                } elseif ($request->request->has('pdf')) {
                	return $this->bulkPdf($invos['estimates']);
                } elseif ($request->request->has('print')) {
                	return $this->bulkPrint($invos['estimates']);
                } elseif ($request->request->has('email')) {
                	return $this->bulkEmail($invos['estimates']);
                } elseif ($request->request->has('pdf-deliverynote-estimate')) {
                	return $this->bulkDeliveryNotePdf($invos['estimates']);
                } elseif ($request->request->has('print-deliverynote-estimate')) {
                	return $this->bulkDeliveryNotePrint($invos['estimates']);
                } elseif ($request->request->has('email-deliverynote-estimate')) {
                	return $this->bulkDeliveryNoteEmail($invos['estimates']);
                }
            }
        }

        return array(
            'estimates' => $pagination,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
            'search_form' => $form->createView(),
            'list_form' => $listForm->createView(),
        );
    }

    /**
     * @Route("/{id}/show", name="estimate_show")
     * @Template("SiwappEstimateBundle:Estimate:show.html.twig")
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('SiwappEstimateBundle:Estimate')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        if ($entity->isDraft() || $entity->isPending()) {
            return $this->redirect($this->generateUrl('estimate_edit', ['id' => $id]));
        }

        return array(
            'entity' => $entity,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        );
    }

    /**
     * @Route("/{id}/show/print", name="estimate_show_print")
     */
    public function showPrintAction($id)
    {
        $estimate = $this->getDoctrine()
            ->getRepository('SiwappEstimateBundle:Estimate')
            ->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        return new Response($this->getEstimatePrintPdfHtml($estimate, true));
    }

    /**
     * @Route("/{id}/show/pdf", name="estimate_show_pdf")
     */
    public function showPdfAction($id)
    {
        $estimate = $this->getDoctrine()
            ->getRepository('SiwappEstimateBundle:Estimate')
            ->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        $html = $this->getEstimatePrintPdfHtml($estimate);

        return new Response(
            $this->getPdf($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="Order-' . $estimate->label() . '.pdf"'
            )
        );
    }

    /**
     * @Route("/{id}/show/deliverynote/print", name="estimate_deliverynote_show_print")
     */
    public function showPrintDeliveryNoteAction($id)
    {
        $estimate = $this->getDoctrine()
            ->getRepository('SiwappEstimateBundle:Estimate')
            ->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        return new Response($this->getDeliveryNotePrintPdfHtml($estimate, true));
    }

    /**
     * @Route("/{id}/show/deliverynote/pdf", name="estimate_deliverynote_show_pdf")
     */
    public function showDeliveryNotePdfAction($id)
    {
        $estimate = $this->getDoctrine()
            ->getRepository('SiwappEstimateBundle:Estimate')
            ->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        $html = $this->getDeliveryNotePrintPdfHtml($estimate);
        $pdf = $this->getPdf($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoice-' . $estimate->label() . '.pdf"'
        ]);
    }

    /**
     * @Route("/add", name="estimate_add")
     * @Template("SiwappEstimateBundle:Estimate:edit.html.twig")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $estimate = new Estimate();
        $estimate->addItem(new Item());
        $terms = $em->getRepository('SiwappConfigBundle:Property')->get('legal_terms');
        if ($terms) {
            $estimate->setTerms($terms);
        }

        $form = $this->createForm(EstimateType::class, $estimate, [
            'action' => $this->generateUrl('estimate_add'),
            'quantity_item_zero' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($estimate);
            $em->flush();
            $this->addTranslatedMessage('flash.added');

            return $this->redirect($this->generateUrl('estimate_edit', array('id' => $estimate->getId())));
        }

        return array(
            'form' => $form->createView(),
            'entity' => $estimate,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
        );
    }

    /**
     * @Route("/{id}/edit", name="estimate_edit")
     * @Template("SiwappEstimateBundle:Estimate:edit.html.twig")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('SiwappEstimateBundle:Estimate')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }
        $form = $this->createForm(EstimateType::class, $entity, [
            'action' => $this->generateUrl('estimate_edit', ['id' => $id]),
            'editing' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $redirectRoute = 'estimate_edit';
            if ($request->request->has('save_draft')) {
                $entity->setStatus(Estimate::DRAFT);
            }/* elseif ($request->request->has('save_close')) {
                $entity->setStatus(Estimate::REJECTED);
            } elseif ($entity->isDraft()) {
                $entity->setStatus(Estimate::PENDING);
            }*/
            // See if one of PDF/Print buttons was clicked.
            if ($request->request->has('save_pdf')) {
                $redirectRoute = 'estimate_show_pdf';
            } elseif ($request->request->has('save_print')) {
                $this->get('session')->set('estimate_auto_print', $id);
            }
            $em->persist($entity);
            $em->flush();
            $this->addTranslatedMessage('flash.updated');

            if ($request->request->has('save_close')) {
                return $this->redirect($this->generateUrl('estimate_index'));
            }

            // Send the email after the estimate is updated.
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
            // Generate the invoice.
            if ($request->request->has('save_generate')) {
                $invoice = $this->get('siwapp_estimate.invoice_generator')->generate($entity);
                if ($invoice) {
                    $this->addTranslatedMessage('flash.invoice_generated');
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
     * @Route("/{id}/email", name="estimate_email")
     * @Method({"POST"})
     */
    public function emailAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $estimate = $em->getRepository('SiwappEstimateBundle:Estimate')->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        $message = $this->getEmailMessage($estimate);
        $result = $this->get('mailer')->send($message);
        if ($result) {
            $estimate->setSentByEmail(true);
            $em->persist($estimate);
            $em->flush();
            $this->addTranslatedMessage('flash.emailed');
        }

        return $this->redirect($this->generateUrl('estimate_index'));
    }

    /**
     * @Route("/{id}/generate-invoice", name="estimate_generate_invoice")
     * @Method({"POST"})
     */
    public function generateInvoiceAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $estimate = $em->getRepository('SiwappEstimateBundle:Estimate')->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }

        $invoice = $this->get('siwapp_estimate.invoice_generator')->generate($estimate);
        if ($invoice) {
            $this->addTranslatedMessage('flash.invoice_generated');

            return $this->redirect($this->generateUrl('invoice_edit', ['id' => $invoice->getId()]));
        }

        return $this->redirect($this->generateUrl('estimate_index'));
    }

    /**
     * @Route("/{id}/delete", name="estimate_delete")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $estimate = $em->getRepository('SiwappEstimateBundle:Estimate')->find($id);
        if (!$estimate) {
            throw $this->createNotFoundException('Unable to find Estimate entity.');
        }
        $em->remove($estimate);
        $em->flush();
        $this->addTranslatedMessage('flash.deleted');

        return $this->redirect($this->generateUrl('estimate_index'));
    }

    /**
     * @Route("/form-totals", name="estimate_form_totals")
     */
    public function getInvoiceFormTotals(Request $request)
    {
        $post = $request->request->get('estimate');
        if (!$post) {
            throw new NotFoundHttpException;
        }

        $response = $this->getInvoiceTotalsFromPost($post, new Estimate, $request->getLocale());

        return new JsonResponse($response);
    }

    protected function addTranslatedMessage($message, $status = 'success')
    {
        $translator = $this->get('translator');
        $this->get('session')
            ->getFlashBag()
            ->add($status, $translator->trans($message, [], 'SiwappEstimateBundle'));
    }

    protected function getEstimatePrintPdfHtml(Estimate $estimate, $print = false)
    {
        $settings = $this->getDoctrine()
            ->getRepository('SiwappConfigBundle:Property')
            ->getAll();

        $factor = !$print ? self::ITEMS_FACTOR : 0;

        return $this->renderView('SiwappEstimateBundle:Estimate:print.html.twig', [
            'estimate'  => $estimate,
            'settings' => $settings,
            'print' => $print,
            'itemsxPage' => self::MAX_ITEMS_X_PAGE * ( 1 + $factor ),
        ]);
    }

    protected function getDeliveryNotePrintPdfHtml(Estimate $estimate, $print = false)
    {
        $settings = $this->getDoctrine()
            ->getRepository('SiwappConfigBundle:Property')
            ->getAll();

        $factor = !$print ? self::ITEMS_FACTOR : 0;

        return $this->renderView('SiwappEstimateBundle:Estimate:print.deliverynote.html.twig', [
            'estimate'  => $estimate,
            'settings' => $settings,
            'print' => $print,
            'itemsxPage' => self::MAX_ITEMS_X_PAGE * ( 1 + $factor )
        ]);
    }

    protected function bulkDelete(array $estimates)
    {
        $em = $this->getDoctrine()->getManager();
        foreach ($estimates as $estimate) {
            $em->remove($estimate);
        }
        $em->flush();
        $this->addTranslatedMessage('flash.bulk_deleted');

        return $this->redirect($this->generateUrl('estimate_index'));
    }

    protected function bulkPdf(array $estimates)
    {
        $pages = [];
        foreach ($estimates as $estimate) {
            $pages[] = $this->getEstimatePrintPdfHtml($estimate);
        }

        $html = $this->get('siwapp_core.html_page_merger')->merge($pages, '<div class="pagebreak"> </div>');
        $pdf = $this->getPdf($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Orders.pdf"'
        ]);
    }

    protected function bulkPrint(array $estimates)
    {
        $pages = [];
        foreach ($estimates as $estimate) {
            $pages[] = $this->getEstimatePrintPdfHtml($estimate, true);
        }

        $html = $this->get('siwapp_core.html_page_merger')->merge($pages, '<div class="pagebreak"> </div>');

        return new Response($html);
    }

    protected function bulkEmail(array $estimates)
    {
        $em = $this->getDoctrine()->getManager();
        foreach ($estimates as $estimate) {
            $message = $this->getEmailMessage($estimate);
            $result = $this->get('mailer')->send($message);
            if ($result) {
                $estimate->setSentByEmail(true);
                $em->persist($estimate);
            }
        }
        $em->flush();
        $this->addTranslatedMessage('flash.bulk_emailed');

        return $this->redirect($this->generateUrl('estimate_index'));
    }

    protected function bulkDeliveryNotePdf(array $estimates)
    {
        $pages = [];
        foreach ($estimates as $estimate) {
            $pages[] = $this->getDeliveryNotePrintPdfHtml($estimate);
        }

        $html = $this->get('siwapp_core.html_page_merger')->merge($pages, '<div class="pagebreak"> </div>');
        $pdf = $this->getPdf($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="DeliveryNotes.pdf"'
        ]);
    }

    protected function bulkDeliveryNotePrint(array $estimates)
    {
        $pages = [];
        foreach ($estimates as $estimate) {
            $pages[] = $this->getDeliveryNotePrintPdfHtml($estimate, true);
        }

        $html = $this->get('siwapp_core.html_page_merger')->merge($pages, '<div class="pagebreak"> </div>');

        return new Response($html);
    }

    protected function bulkDeliveryNoteEmail(array $estimates)
    {
        $em = $this->getDoctrine()->getManager();
        foreach ($estimates as $estimate) {
            $message = $this->getDeliveryNoteEmailMessage($estimate);
            $result = $this->get('mailer')->send($message);
            if ($result) {
                $estimate->setSentByEmail(true);
                $em->persist($estimate);
            }
        }
        $em->flush();
        $this->addTranslatedMessage('flash.bulk_emailed');

        return $this->redirect($this->generateUrl('estimate_index'));
    }

    protected function getDeliveryNoteEmailMessage($estimate)
    {
        $em = $this->getDoctrine()->getManager();
        $configRepo = $em->getRepository('SiwappConfigBundle:Property');

        $html = $this->renderView('SiwappEstimateBundle:Estimate:email.deliverynote.html.twig', array(
            'estimate'  => $estimate,
            'settings' => $em->getRepository('SiwappConfigBundle:Property')->getAll(),
            'itemsxPage' => self::MAX_ITEMS_X_PAGE * ( 999 )
        ));
        $pdf = $this->getPdf($html);
        $attachment = new \Swift_Attachment($pdf, $estimate->getId().'.pdf', 'application/pdf');
        $subject = '[' . $this->get('translator')->trans('estimate.estimate', [], 'SiwappEstimateBundle') . ': ' . $estimate->label() . ']';
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($configRepo->get('company_email'), $configRepo->get('company_name'))
            ->setTo($estimate->getCustomerEmail(), $estimate->getCustomerName())
            ->setBody($html, 'text/html')
            ->attach($attachment);

        return $message;
    }

    protected function getEmailMessage($estimate)
    {
        $em = $this->getDoctrine()->getManager();
        $configRepo = $em->getRepository('SiwappConfigBundle:Property');

        $html = $this->renderView('SiwappEstimateBundle:Estimate:email.html.twig', array(
            'estimate'  => $estimate,
            'settings' => $em->getRepository('SiwappConfigBundle:Property')->getAll(),
            'itemsxPage' => self::MAX_ITEMS_X_PAGE * ( 999 )
        ));
        $pdf = $this->getPdf($html);
        $attachment = new \Swift_Attachment($pdf, $estimate->getId().'.pdf', 'application/pdf');
        $subject = '[' . $this->get('translator')->trans('estimate.estimate', [], 'SiwappEstimateBundle') . ': ' . $estimate->label() . ']';
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($configRepo->get('company_email'), $configRepo->get('company_name'))
            ->setTo($estimate->getCustomerEmail(), $estimate->getCustomerName())
            ->setBody($html, 'text/html')
            ->attach($attachment);

        return $message;
    }
}

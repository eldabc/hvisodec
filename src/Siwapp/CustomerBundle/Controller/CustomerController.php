<?php

namespace Siwapp\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Siwapp\CustomerBundle\Entity\Customer;

/**
 * @Route("/customer")
 */
class CustomerController extends Controller
{
    /**
     * @Route("", name="customer_index")
     * @Template("SiwappCustomerBundle:Customer:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('SiwappCustomerBundle:Customer');
        $repo->setPaginator($this->get('knp_paginator'));
        // @todo Unhardcode this.
        $limit = 50;

        $form = $this->createForm('Siwapp\CustomerBundle\Form\SearchCustomerType', null, [
            'action' => $this->generateUrl('customer_index'),
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pagination = $repo->paginatedSearch($form->getData(), $limit, $request->query->getInt('page', 1));
        } else {
            $pagination = $repo->paginatedSearch([], $limit, $request->query->getInt('page', 1));
        }

        $customers = [];
        foreach ($pagination->getItems() as $item) {
            $customers[] = $item[0];
        }

        $listForm = $this->createForm('Siwapp\CustomerBundle\Form\CustomerListType', $customers, [
            'action' => $this->generateUrl('customer_index'),
        ]);
        $listForm->handleRequest($request);
        if ($listForm->isSubmitted() && $listForm->isValid()) {
            $data = $listForm->getData();
            if ($request->request->has('delete')) {
                if (empty($data['customers'])) {
                    $this->addTranslatedMessage('flash.nothing_selected', 'warning');
                }
                else {
                    foreach ($data['customers'] as $customer) {
                        $em->remove($customer);
                    }
                    $em->flush();
                    $this->addTranslatedMessage('flash.bulk_deleted');

                    // Rebuild the query, since some objects are now missing.
                    return $this->redirect($this->generateUrl('customer_index'));
                }
            }
        }

        return array(
            'customers' => $pagination,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
            'search_form' => $form->createView(),
            'list_form' => $listForm->createView(),
        );
    }

    /**
     * @Route("/autocomplete", name="customer_autocomplete")
     */
    public function autocompleteAction(Request $request)
    {
        $entities = $this->getDoctrine()
            ->getRepository('SiwappCustomerBundle:Customer')
            ->findLike($request->get('term'));

        return new JsonResponse($entities);
    }

    /**
     * @Route("/add", name="customer_add")
     * @Template("SiwappCustomerBundle:Customer:edit.html.twig")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = new Customer();

        $form = $this->createForm('Siwapp\CustomerBundle\Form\CustomerType', $customer, [
            'action' => $this->generateUrl('customer_add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($customer);
            $em->flush();
            $this->addTranslatedMessage('flash.added');

            return $this->redirect($this->generateUrl('customer_edit', array('id' => $customer->getId())));
        }

        return array(
            'form' => $form->createView(),
            'entity' => $customer,
        );
    }

    /**
     * @Route("/{id}/edit", name="customer_edit")
     * @Template("SiwappCustomerBundle:Customer:edit.html.twig")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository('SiwappCustomerBundle:Customer')->find($id);
        if (!$customer) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }

        $form = $this->createForm('Siwapp\CustomerBundle\Form\CustomerType', $customer, [
            'action' => $this->generateUrl('customer_edit', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($customer);
            $em->flush();
            $this->addTranslatedMessage('flash.updated');

            return $this->redirect($this->generateUrl('customer_edit', array('id' => $customer->getId())));
        }

        return array(
            'form' => $form->createView(),
            'entity' => $customer,
        );
    }

    /**
     * @Route("/{id}/delete", name="customer_delete")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository('SiwappCustomerBundle:Customer')->find($id);
        if (!$customer) {
            throw $this->createNotFoundException('Unable to find Customer entity.');
        }
        $em->remove($customer);
        $em->flush();
        $this->addTranslatedMessage('flash.deleted');

        return $this->redirect($this->generateUrl('customer_index'));
    }

    protected function addTranslatedMessage($message, $status = 'success')
    {
        $translator = $this->get('translator');
        $this->get('session')
            ->getFlashBag()
            ->add($status, $translator->trans($message, [], 'SiwappCustomerBundle'));
    }
}

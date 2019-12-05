<?php

namespace Siwapp\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Siwapp\UserBundle\Entity\User;

/**
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * @Route("", name="user_index")
     * @Template("SiwappUserBundle:User:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('SiwappUserBundle:User');
        $repo->setPaginator($this->get('knp_paginator'));
        // @todo Unhardcode this.
        $limit = 50;
        
        $form = $this->createForm('Siwapp\UserBundle\Form\SearchUserType', null, [
            'action' => $this->generateUrl('user_index'),
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pagination = $repo->paginatedSearch($form->getData(), $limit, $request->query->getInt('page', 1));
        } else {
            $pagination = $repo->paginatedSearch([], $limit, $request->query->getInt('page', 1));
        }
        
        $users = [];
        foreach ($pagination->getItems() as $item) {
            $users[] = $item;
        }

        $listForm = $this->createForm('Siwapp\UserBundle\Form\UserListType', $users, [
            'action' => $this->generateUrl('user_index'),
        ]);
        $listForm->handleRequest($request);
        if ($listForm->isSubmitted() && $listForm->isValid()) {
            $data = $listForm->getData();
            if ($request->request->has('delete')) {
                if (empty($data['users'])) {
                    $this->addTranslatedMessage('flash.nothing_selected', 'warning');
                }
                else {
                    foreach ($data['users'] as $product) {
                        $em->remove($product);
                    }
                    $em->flush();
                    $this->addTranslatedMessage('flash.bulk_deleted');
                    
                    // Rebuild the query, since some objects are now missing.
                    return $this->redirect($this->generateUrl('user_index'));
                }
            }
        }
        
        return array(
            'users' => $pagination,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
            'search_form' => $form->createView(),
            'list_form' => $listForm->createView(),
        );
    }
    
    /**
     * @Route("/add", name="user_add")
     * @Template("SiwappUserBundle:User:edit.html.twig")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        
        $form = $this->createForm('Siwapp\UserBundle\Form\RegistrationType', $user, [
            'action' => $this->generateUrl('user_add'),
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addTranslatedMessage('flash.added');
            
            return $this->redirect($this->generateUrl('user_edit', array('id' => $user->getId())));
        }
        
        return array(
            'form' => $form->createView(),
            'entity' => $user,
        );
    }
    
    
    
    
    /**
     * @Route("/{id}/edit", name="user_edit")
     * @Template("SiwappUserBundle:User:edit.html.twig")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('SiwappUserBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }
        
        $form = $this->createForm('Siwapp\UserBundle\Form\RegistrationType', $user, [
            'action' => $this->generateUrl('user_edit', ['id' => $id]),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
     
            $this->addTranslatedMessage('flash.updated');
            
            return $this->redirect($this->generateUrl('user_edit', array('id' => $user->getId())));
        }
        
        return array(
            'form' => $form->createView(),
            'entity' => $user,
        );
    }
    
    /**
     * @Route("/{id}/delete", name="user_delete")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('SiwappUserBundle:User')->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }
        $em->remove($user);
        $em->flush();
        $this->addTranslatedMessage('flash.deleted');
        
        return $this->redirect($this->generateUrl('user_index'));
    }
    
    protected function addTranslatedMessage($message, $status = 'success')
    {
        $translator = $this->get('translator');
        $this->get('session')
        ->getFlashBag()
        ->add($status, $translator->trans($message, [], 'SiwappCustomerBundle'));
    }
}
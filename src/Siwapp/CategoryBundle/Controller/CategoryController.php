<?php

namespace Siwapp\CategoryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Siwapp\CategoryBundle\Entity\Category;

/**
 * @Route("/category")
 */
class CategoryController extends Controller
{
    /**
     * @Route("", name="category_index")
     * @Template("SiwappCategoryBundle:Category:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('SiwappCategoryBundle:Category');
        $repo->setPaginator($this->get('knp_paginator'));
        // @todo Unhardcode this.
        $limit = 50;

        $form = $this->createForm('Siwapp\CategoryBundle\Form\SearchCategoryType', null, [
            'action' => $this->generateUrl('category_index'),
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pagination = $repo->paginatedSearch($form->getData(), $limit, $request->query->getInt('page', 1));
            // echo  "aqui";
        } else {
            // echo  "aca";
            $pagination = $repo->paginatedSearch([], $limit, $request->query->getInt('page', 1));
        }
       
        $categorys = [];
        foreach ($pagination->getItems() as $item) {
            // print_r($item);
            $categorys[] = $item;
        }
       
        $listForm = $this->createForm('Siwapp\CategoryBundle\Form\CategoryListType', $categorys, [
            'action' => $this->generateUrl('category_index'),
        ]);
        $listForm->handleRequest($request);
        if ($listForm->isSubmitted() && $listForm->isValid()) {
            $data = $listForm->getData();
            if ($request->request->has('delete')) {
                if (empty($data['categorys'])) {
                    $this->addTranslatedMessage('flash.nothing_selected', 'warning');
                }
                else {
                    foreach ($data['categorys'] as $category) {
                        $em->remove($category);
                    }
                    $em->flush();
                    $this->addTranslatedMessage('flash.bulk_deleted');

                    // Rebuild the query, since some objects are now missing.
                    return $this->redirect($this->generateUrl('category_index'));
                }
            }
        }

        return array(
            'categorys' => $pagination,
            'currency' => $em->getRepository('SiwappConfigBundle:Property')->get('currency', 'EUR'),
            'search_form' => $form->createView(),
            'list_form' => $listForm->createView(),
        );
    }

    /**
     * @Route("/autocomplete-reference", name="product_autocomplete_reference")
     */
    public function autocompleteReferenceAction(Request $request)
    {
        $entities = $this->getDoctrine()
            ->getRepository('SiwappProductBundle:Product')
            ->findLikeReference($request->get('term'));

        return new JsonResponse($entities);
    }

    /**
     * @Route("/add", name="category_add")
     * @Template("SiwappCategoryBundle:Category:edit.html.twig")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $category = new Category();

        $form = $this->createForm('Siwapp\CategoryBundle\Form\CategoryType', $category, [
            'action' => $this->generateUrl('category_add'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addTranslatedMessage('flash.added');

            return $this->redirect($this->generateUrl('category_edit', array('id' => $category->getId())));
        }

        return array(
            'form' => $form->createView(),
            'entity' => $category,
        );
    }

    /**
     * @Route("/{id}/edit", name="category_edit")
     * @Template("SiwappCategoryBundle:Category:edit.html.twig")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('SiwappCategoryBundle:Category')->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }

        $form = $this->createForm('Siwapp\CategoryBundle\Form\CategoryType', $category, [
            'action' => $this->generateUrl('category_edit', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addTranslatedMessage('flash.updated');

            return $this->redirect($this->generateUrl('category_edit', array('id' => $category->getId())));
        }

        return array(
            'form' => $form->createView(),
            'entity' => $category,
        );
    }

    /**
     * @Route("/{id}/delete", name="category_delete")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('SiwappCategoryBundle:Category')->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Unable to find Category entity.');
        }
        $em->remove($category);
        $em->flush();
        $this->addTranslatedMessage('flash.deleted');

        return $this->redirect($this->generateUrl('category_index'));
    }

    protected function addTranslatedMessage($message, $status = 'success')
    {
        $translator = $this->get('translator');
        $this->get('session')
            ->getFlashBag()
            ->add($status, $translator->trans($message, [], 'SiwappCategoryBundle'));
    }
    
    // /**
    //  * @Route("/validate-stock", name="product_validate_stock")
    //  */
    // public function validateStockAction(Request $request)
    // {
    //     $entities = $this->getDoctrine()
    //     ->getRepository('SiwappProductBundle:Product')
    //     ->validateStock($request->get('product'));
        
    //     return new JsonResponse($entities);
    // }
}

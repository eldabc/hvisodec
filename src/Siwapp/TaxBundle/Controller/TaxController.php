<?php

namespace Siwapp\TaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/tax")
 */
class TaxController extends Controller
{
    /**
     * @Route("", name="tax_index")
     * @Template("SiwappTaxBundle:Tax:index.html.twig")
     */
    public function indexAction()
    {
        //return $this->render('TaxBundle:Default:index.html.twig');
    }
}

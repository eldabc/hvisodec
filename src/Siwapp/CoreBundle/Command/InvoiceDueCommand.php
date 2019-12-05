<?php

namespace Siwapp\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Siwapp\ConfigBundle\Entity\Property;
use Siwapp\CoreBundle\Entity\AbstractInvoice;
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\CoreBundle\Entity\Series;
use Siwapp\CoreBundle\Entity\Tax;
use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\InvoiceBundle\Entity\Payment;
use Siwapp\CustomerBundle\Entity\Customer;
use Siwapp\EstimateBundle\Entity\Estimate;
use Siwapp\ProductBundle\Entity\Product;
use Siwapp\RecurringInvoiceBundle\Entity\RecurringInvoice;
use Siwapp\ProviderBundle\Entity\InvoiceProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InvoiceDueCommand extends ContainerAwareCommand
{

    /**
     * Array mapping old ids to new.
     */
    protected $mapping;

    protected function configure()
    {
        $this
            ->setName('siwapp:invoice-due')
            ->setDescription('Cambiar estado de facturas vencidas.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Actualizando estado de facturas.");
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $repo = $em->getRepository('SiwappInvoiceBundle:Invoice');
        
        $repo->updateInvoiceDue();
        
        $output->writeln([
            'Estados de facturas actualizados correctamente!',// A line
            '============',// Another line
            'Actualizando estados de factura de proveedores',// Empty line
            '',// Empty line
        ]);
        
        $repoProvider = $em->getRepository('SiwappProviderBundle:InvoiceProvider');
        
        $repoProvider->updateInvoiceDue();
        
        $output->writeln("Estados de facturas de proveedores actualizados correctamente!");

    }
}

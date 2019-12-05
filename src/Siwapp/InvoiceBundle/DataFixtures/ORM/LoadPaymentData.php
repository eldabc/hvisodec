<?php
namespace Siwapp\InvoiceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\InvoiceBundle\Entity\Payment;
use Symfony\Component\Yaml\Parser;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPaymentData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $yaml = new Parser();
        // TODO: find a way of obtainin Bundle's path with the help of $this->container
        $bpath = './src/Siwapp/InvoiceBundle';
        $value = $yaml->parse(file_get_contents($bpath.'/DataFixtures/payments.yml'));
        foreach ($value['Payment'] as $ref => $values) {
            $payment = new Payment();
            foreach ($values as $fname => $fvalue) {
                $method = Inflector::camelize('set_' . $fname);
                if (is_callable(array($payment, $method))) {
                    call_user_func(array($payment, $method), $fvalue);
                }
            }
            $manager->persist($payment);
            $manager->flush();
            $this->addReference($ref, $payment);
        }
    }

    public function getOrder()
    {
        return '1';
    }
}

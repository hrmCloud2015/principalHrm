<?php
/*
 * STANDAR DE NISSI MENUES
 * 
 */
namespace Principal\Alertas;
 
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
 
class Alertas implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $db = $serviceLocator->get('Zend\Db\Adapter\DbAdapter');
        //$table = new \YourModule\Model\MyTableModel();
        //$table->setDbAdapter($db);
 
        return 'dos';
    }
}

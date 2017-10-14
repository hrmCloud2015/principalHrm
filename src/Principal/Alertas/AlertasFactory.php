<?php
/*
 * STANDAR DE NISSI MENUES
 * 
 */
namespace Principal\Alertas;
 
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
 
class CommonControlAppAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $locator, $name, $requestedName)
    {
        if (class_exists($requestedName.'Controller')){
            return true;
        }
 
        return false;
    }
 
    public function createServiceWithName(ServiceLocatorInterface $locator, $name, $requestedName)
    {
        $class = $requestedName.'Controller';
        return new $class;
    }
}
?>

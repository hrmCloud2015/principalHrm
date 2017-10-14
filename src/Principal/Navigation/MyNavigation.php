<?php
/*
 * STANDAR DE NISSI MENUES
 * 
 */
namespace Principal\Navigation;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Navigation\Service\DefaultNavigationFactory;

class MyNavigation extends DefaultNavigationFactory
{
protected function getPages(ServiceLocatorInterface $serviceLocator)
    {
        $navigation = array();
        $nivel      = array();// Menu prinicpal
        
        if (null === $this->pages) {
            //Menu principal para recorrer
            $fetchMenu = $serviceLocator->get('c_mu')->getMenu();            
            foreach($fetchMenu as $key=>$row)// NIVEL PRINCIPAL
            {            
               $id = $row['id'];
               $nivel['label']   = $row['nombre'];
               $nivel['uri']     = '#';
               //$op['pages']   = array(array( 'label'=> 'Telefoonboek','route'=>'nomina/default','controller'=>'gnomina','action'=>'list'));
               // Armar primer nivel 
               $con    = array();// Contenedor nivel principal
               $nivel1 = array();// Menu nivel 1

               $Menu1 = $serviceLocator->get('c_mu')->getMenu1($id);            

               $sw=0;               
               foreach($Menu1 as $key1=>$row1) // NIVEL 1
               {                 
                 $id1 =  $row1['id']; 
                  
                 $nivel1['label'] = $row1['nombre'];
                 $nivel1['uri']   = '#';
                 
                 // Armar segundo nivel  
                 $con1   = array();// Contenedor nivel 1
                 $nivel2 = array();// Menu nivel 2
                 $Menu2 = $serviceLocator->get('c_mu')->getMenu2($id1);           
                 foreach($Menu2 as $key2=>$row2)// NIVEL 2
                 {                
                    $id2 =  $row2['id']; 
                    $nivel2['label'] = $row2['nombre'].'_2'; // Esto es importante para identifiacr el segundo nivel
                    $nivel2['uri']   = '#';                     
                    
                    // Armar tercer nivel  
                    $con2   = array();// Contenedor nivel 2
                    $nivel3 = array();// Menu nivel 2
                    $Menu3 = $serviceLocator->get('c_mu')->getMenu3($id2);     
                    //print_r($Menu3);
                    foreach($Menu3 as $key3=>$row3)// NIVEL 3
                    {                       
                       if ($row3['controlador']=='info') 
                           $nivel3['label']      = $row3['nombre'].'('.$row3['id'];
                       else
                           $nivel3['label']      = $row3['nombre'];    
                           
                       $nivel3['route']      = $row3['modelo'].'/default';
                       $nivel3['controller'] = $row3['controlador'];
                       $nivel3['action']     = $row3['vista'];
                       
                       array_push( $con2 , $nivel3 ); // NIVEL 3
                    }
                    $nivel2['pages'] = $con2;   
                    array_push( $con1 , $nivel2 ); // MENU NIVEL 2
                 }
                 $nivel1['pages'] = $con1;   
                 
                 $sw=1;  
                 array_push( $con , $nivel1 ); // MENU NIVEL 1
               }               
               //if ($sw==1)
                  $nivel['pages'] = $con; 
               // Fin niveles 
               array_push( $navigation , $nivel ); // MENU PRINCIPAL
            }

            
            //$navigation[] = array (
            //    'label' => 'Jaapsblog.nl',
            //    'uri'   => '#',
            //    'pages' => array(                   
            //     array( //<-- this NOW works too
            //         'label'         => 'Telefoonboek',
            //         'route'         => 'nomina/default',
            //         'controller'    => 'gnomina',
            //         'action'        => 'list',                     
            //     ),
            //         ),
            //);            
            

            $mvcEvent = $serviceLocator->get('Application')
                      ->getMvcEvent();

            $routeMatch = $mvcEvent->getRouteMatch();
            $router     = $mvcEvent->getRouter();
            $pages      = $this->getPagesFromConfig($navigation);

            $this->pages = $this->injectComponents(
                $pages,
                $routeMatch,
                $router
            );
        }

        return $this->pages;
    }
}
?>

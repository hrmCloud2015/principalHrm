<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Principal\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Principal\Model\AlbumTable;  // Libreria de datos
use Principal\Model\NominaFunc;   

class ActualController extends AbstractActionController
{
    public function indexAction()
    {        
        // Procedimiento de actualizaciones
        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $d=new AlbumTable($this->dbAdapter);        

        $datos2  = $d->getGeneral("select id, year(fecUlVac) as fecha from a_empleados");
         foreach ($datos2 as $dat)
         {
             $id = $dat['id'];
             // Armar cuadro de vacaciones para todos
             $fecha = $dat['fecha'];
               ini_set('max_execution_time', 300); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )  

               $d ->modGeneral("update n_libvacaciones set estado= 0 where idEmp=".$dat['id']." and year(fechaF) > ".$fecha);


             // Fin armar vacaciones             
         }        

        
        return new ViewModel();
    }    
    
    public function indexdAction()
    {        
        // Procedimiento de actualizaciones
        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $u=new AlbumTable($this->dbAdapter);
        $datos = $u ->getGeneral("select * from a_empleados");
        $i=1;  
//        foreach ($datos as $dat)
//        {
//            $datos2 = $u ->getGeneral1("select id from t_fondos where tipo=4 and nit='".$dat['t_ces']."'");
//            $id = $datos2['id'];
//            if ($dat['t_ces']=='1')
//               $id = 1;
            
//            $u ->modGeneral("update a_empleados set idFces=".$id." where id = ".$dat['id']);
            //$i++;
//        }        
        
        return new ViewModel();
    }        

    
//    public function indexAction()
//    {        
//        // Procedimiento de actualizaciones
//        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
//        $d=new AlbumTable($this->dbAdapter);        
//
//        $datos2  = $d->getGeneral("select a.id,  a.fecIng, TIMESTAMPDIFF(YEAR, a.fecIng, CURDATE()) as ano   
//                                   from a_empleados a where a.gen =0 limit 80");
//         foreach ($datos2 as $dat){
//             $id = $dat['id'];
//             // Armar cuadro de vacaciones para todos
//             $f = new NominaFunc($this->dbAdapter);      
//             $fecha = $dat['fecIng'];
//             $sw=0;
//             for ($i=0;$i<=$dat['ano'];$i++)
//             {
//               ini_set('max_execution_time', 300); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )  
//               $dat2 =  $f->getPeriodo($fecha,365);
//               //echo $dat['fechaI'].' - '.$dat['fechaF'].'<br />';
//               $fecha  = $dat2['fechaF'];
//               $fechai = $dat2['fechaI'];
//               $fechaf = $dat2['fechaF'];
//               if ($sw==1)
//               {
//                 $d ->modGeneral("insert into n_libvacaciones (idEmp, fechaI, fechaF,diasP, estado) 
//                                values(".$id.",'".$fechai."','".$fechaf."',15,1)");
//               }
//               $sw=1;
//             }
//             $d ->modGeneral("update a_empleados set gen=1 where id = ".$dat['id']);               
//             // Fin armar vacaciones             
//         }        

        
//        return new ViewModel();
//    }
    
    
}

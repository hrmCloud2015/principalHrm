<?php
/** STANDAR MAESTROS NISSI  */
// (C): Cambiar en el controlador 
namespace Principal\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Form\Annotation\AnnotationBuilder;

use Principal\Form\Formulario;         // Componentes generales de todos los formularios
use Principal\Model\AlbumTable;        // Libreria de datos

use Principal\Model\GraficosTable;     // Libreria de datos graficos

use Principal\Model\ExcelFunc; // Funciones de excel 

class DausentismosController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    private $lin  = "/principal/dausentismos/list"; // Variable lin de acceso  0 (C)
    private $tlis = "Dashboard ausentismos"; // Titulo listado
    private $tfor = "Dashboard ausentismos"; // Titulo formulario
    
   // Editar y nuevos datos *********************************************************************************************
   public function listAction() 
   { 
      $form = new Formulario("form");
      //  valores iniciales formulario   (C)
      $id = (int) $this->params()->fromRoute('id', 0);
      $id = 4;
      $form->get("id")->setAttribute("value",$id); 
      $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
      $d=new AlbumTable($this->dbAdapter);
      $g = new GraficosTable($this->dbAdapter);
      // Empleados
      $arreglo='';
      $datos = $d->getEmp(''); 
      foreach ($datos as $dat){
         $idc=$dat['id'];$nom = $dat['CedEmp'].' - '.$dat['nombre'].' '.$dat['apellido'];
         $arreglo[$idc]= $nom;
      }              
      $form->get("idEmp")->setValueOptions($arreglo);                         
      $valores=array
      (
           "titulo"  => $this->tfor,
           "form"    => $form,
           'url'       => $this->getRequest()->getBaseUrl(),
           "datAus"    => $g->getGeneral1("select count(id) as num , sum(DATEDIFF( fechaf , fechai ) + 1  ) as numDias
                                 from n_ausentismos where estado=1"), // Incapa
           "datSexo"   => $g->getAusSexo(), // Sexo 
           "datEdades" => $g->getAusEdades(), // Edades 
           "datIncap"  => $g->getAus(), // Incapacidades
           "datIncAno" => $g->getAusAnos(), // Linea de incapaciades
           "datIncSex" => $g->getAusTipSexo(),
           "datIncCcos" => $g->getAusCcos(), // Inca por centro de costos
           "lin"       => $this->lin
      );       
      // ------------------------ Fin valores del formulario      
      return new ViewModel($valores);        

   } // Fin listar datos   

   // NOMINA A EXCEL 
   public function listexcelAction() 
   { 
      if($this->getRequest()->isPost()) // Actulizar datos
      {
        $request = $this->getRequest();
        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $d = new AlbumTable($this->dbAdapter);  
        // CONSULTA DE DEVENGADOS CON CODIGOS

        $datos = $d->getGeneral("select d.nombre as nomCcos, a.fecDoc, a.fechai, a.fechaf, datediff( a.fechaf , a.fechai ) + 1 as diasAus, b.nombre as nomTaus,
             c.CedEmp, c.nombre, c.apellido, case when c.SexEmp = 1 then 'Hombre' else 'Mujer' end as sexo, a.comen  
                 from n_ausentismos a 
                    inner join n_tip_aus b on b.id = a.idTaus 
                    inner join a_empleados c on c.id = a.idEmp 
                    inner join n_cencostos d on d.id = c.idCcos 
                 where a.estado = 1    
                   order by d.nombre , c.nombre ");
        $c = new ExcelFunc();
        //print_r($datos);
        $c->listexcel($datos, "Ausentismos");

        $valores = array("datos" => $datos );      
        $view = new ViewModel($valores);              
        return $view;                         
      }
    }// FIN NOMINA A EXCEL   

}

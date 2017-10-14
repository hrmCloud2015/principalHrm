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

class DincapacidadesController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    private $lin  = "/principal/dincapacidades/list"; // Variable lin de acceso  0 (C)
        private $tlis = "Dashboard incapacidades"; // Titulo listado
    private $tfor = "Dashboard incapacidades"; // Titulo formulario
    
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
           "datInc"    => $g->getGeneral1("select count(id) as num , sum(DATEDIFF( fechaf , fechai ) + 1  ) as numDias
                                 from n_incapacidades where estado=1"), // Incapa
           "datPro"    => $g->getGeneral1("select count(id) as num , sum(DATEDIFF( fechaf , fechai ) + 1  ) as numDias
                                 from n_incapacidades_pro"), // Prorrogas
           "datSexo"   => $g->getIncSexo(), // Sexo 
           "datEdades" => $g->getIncEdades(), // Edades 
           "datIncap"  => $g->getIncap(), // Incapacidades
           "datIncAno" => $g->getIncapAnos(), // Linea de incapaciades
           "datIncSex" => $g->getIncTipSexo(),
           "datIncCcos" => $g->getIncCcos(), // Inca por centro de costos
           "lin"       => $this->lin
      );       
      // ------------------------ Fin valores del formulario      
      return new ViewModel($valores);        

   } // Fin listar datos   

}

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

class DastalentoController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    private $lin  = "/principal/dastalento/list"; // Variable lin de acceso  0 (C)
        private $tlis = "Dashboard talento humano"; // Titulo listado
    private $tfor = "Dashboard talento humano"; // Titulo formulario
    private $ttab = "Tipo de nomina, Periodo, Tipo de calendario, Grupo ,M,E"; // Titulo de las columnas de la tabla
//    private $mod  = "Nivel de aspecto ,A,E"; // Funcion del modelo
    
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
           'url'     => $this->getRequest()->getBaseUrl(),
            "datGit"    => $g->getEvaluacionItems(4),
            "datGge"    => $g->getEvaluacionGeneral($id),
            "datGde"    => $g->getEvaluacionDetallada($id),
            "datSexo"   => $g->getSexo(), // Sexo compañia 
            "datEdades" => $g->getEdades(), // Edades de la compañia
            "datSexEdad" => $g->getEdadesSexo(), // Sexo y edades
            "datEva"    => $g->getGeneral1("select b.nombre as nomTeva, a.fecha  
                                    from t_evaluacion a
                                       inner join t_tipo_eva b on b.id = a.idTeva 
                                       where a.id=".$id),
            "datPeva"   => $d->getPorcEval($id),           
           "lin"     => $this->lin
      );       
      // ------------------------ Fin valores del formulario      
      return new ViewModel($valores);        

   } // Fin actualizar datos 
   
   public function listagAction() 
   { 
      $form = new Formulario("form");             
      $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
      $d=new AlbumTable($this->dbAdapter);
      if($this->getRequest()->isPost()) // Actualizar 
      {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->request->getPost();              
        }        
      }  
      $idEmp = $data->idEmp;
           
      
      $valores=array
      (
           "titulo"  => $this->tfor,
           "form"    => $form,
           "datBas"  => $d->getEmpMG(" and a.id = ".$idEmp), // Datos basico del empleado
           "datEmp"  => $d->getGeneral1("select CedEmp, nombre, apellido  
                                         from a_empleados where id = ".$idEmp),          
           "datVac"  => $d->getGeneral("select b.*, c.dias, d.id as idVac, d.fecDoc, d.valor 
                                           from a_empleados a 
                                           inner join n_libvacaciones b on b.idEmp = a.id 
                                           left join n_vacaciones_p c on c.idPvac = b.id  
                                           left join n_vacaciones d on d.id = c.idVac 
                                           where a.id = ".$idEmp." order by b.fechaF desc  "),
           "datAus"  => $d->getGeneral("select b.*, c.nombre as nomTaus  from a_empleados a 
                                            inner join n_ausentismos b on b.idEmp = a.id 
                                            inner join n_tip_aus c on c.id = b.idTaus   
                                            where a.id = ".$idEmp." order by b.fechaf desc  "),          
           "datInc"  => $d->getGeneral("select b.*, c.nombre as nomTinc  from a_empleados a 
                                            inner join n_incapacidades b on b.idEmp = a.id 
                                            inner join n_tipinc c on c.id = b.idInc
                                            where a.id = ".$idEmp." order by b.fechaf desc  "),                    
           "datEmb"  => $d->getGeneral("select b.*, c.nombre as nomTemb  
                                            from a_empleados a 
                                            inner join n_embargos  b on b.idEmp = a.id 
                                            inner join n_tip_emb c on c.id = b.idTemb
                                            where a.id = ".$idEmp." order by b.fecDoc desc  "),                              
           "datGdot"  => $d->getGeneral("select b.*, c.nombre as nomGdot  from a_empleados a 
                                            inner join t_dotaciones  b on b.idEmp = a.id 
                                            inner join t_grup_dota c on c.id = b.idGdot 
                                            where a.id = ".$idEmp." order by b.fecDoc desc  "),                                        
           "datDesc"  => $d->getGeneral("select b.*, c.nombre as nomTdesc, b.suceso   
                                            from a_empleados a 
                                            inner join t_descargos b on b.idEmp = a.id 
                                            inner join t_tipo_descar c on c.id = b.idTdes
                                            where a.id = ".$idEmp." order by b.fecDoc desc  "),                                                  
           "datCap"  => $d->getGeneral("select b.*, c.nombre as nomArea from a_empleados a 
                                            inner join t_sol_cap b on b.idCcos = a.idCcos 
                                            inner join t_areas_capa c on c.id = b.idArea 
                                            where a.id = ".$idEmp." order by b.fecDoc desc  "),                                                            
           "datEve"  => $d->getGeneral("select b.*, c.nombre as nomTeve  
                                            from t_eventos b  
                                            inner join t_tipo_eventos c on c.id = b.idTev 
                                            order by b.fecDoc desc  "),                                                                      
           "datPres"  => $d->getGeneral("select b.*, c.nombre as nomTpres from a_empleados a
                                            inner join n_prestamos b on b.idEmp = a.id 
                                            inner join n_tip_prestamo c on c.id = b.idTpres 
                                            where a.id = ".$idEmp." order by b.fecDoc desc  "),                                                            
           'url'     => $this->getRequest()->getBaseUrl(),
           'idEmp'   => $data->idEmp,
           "lin"     => $this->lin
      );      
      $view = new ViewModel($valores);        
      $this->layout('layout/blancoB'); // Layout del login
      return $view;        
   }
}

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


class BitacoraController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    private $lin  = "/principal/bitacora/list"; // Variable lin de acceso  0 (C)
    private $tlis = "Bitacora de empleado"; // Titulo listado
    private $tfor = "Bitacora de empleado"; // Titulo formulario
    private $ttab = "Tipo de nomina, Periodo, Tipo de calendario, Grupo ,M,E"; // Titulo de las columnas de la tabla
//    private $mod  = "Nivel de aspecto ,A,E"; // Funcion del modelo
    
   // Editar y nuevos datos *********************************************************************************************
   public function listAction() 
   { 
      $form = new Formulario("form");
      //  valores iniciales formulario   (C)
      $id = (int) $this->params()->fromRoute('id', 0);
      $form->get("id")->setAttribute("value",$id); 
      $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
      $d=new AlbumTable($this->dbAdapter);
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
           "datEmp"  => $d->getGeneral1("select CedEmp, nombre, apellido, foto   
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
           "datGdot"  => $d->getGeneral("select b.*, c.nombre as nomGdot, d.fecha   
                                            from a_empleados a 
                                            inner join t_dota_i b on b.idEmp = a.id 
                                            inner join t_dota d on d.id = b.idDot 
                                            inner join t_grup_dota c on c.id = b.idGdot 
                                            where a.id = ".$idEmp." order by d.fecha desc  "),                                        
           "datDesc"  => $d->getGeneral("select b.*, c.nombre as nomTdesc, b.suceso   
                                            from a_empleados a 
                                            inner join t_descargos b on b.idEmp = a.id 
                                            inner join t_tipo_descar c on c.id = b.idTdes
                                            where a.id = ".$idEmp." order by b.fecDoc desc  "),                                                  
           "datEve"  => $d->getGeneral("select b.*, c.nombre as nomArea, e.nombre as nomTeve 
                                         from a_empleados a 
                                            inner join t_sol_cap_i_e d on d.idEmp = a.id
                                            inner join t_sol_cap b on d.idSol = b.id                                            
                                            inner join t_areas_capa c on c.id = b.idArea     
                                            inner join t_tipo_capa e on e.id = b.idTcap                                                                                   
                                            where a.id = ".$idEmp." order by b.fecDoc desc"),                                                            
           "datPres"  => $d->getGeneral("select b.id, b.fecDoc, b.docRef, c.nombre as nomTpres, 
                                         e.nombre as nomTnom, d.valor , d.valCuota, d.cuotas
                                         , d.pagado, d.saldoIni             
                                         from a_empleados a
                                            inner join n_prestamos b on b.idEmp = a.id 
                                            inner join n_prestamos_tn d on d.idPres = b.id 
                                            inner join n_tip_prestamo c on c.id = b.idTpres
                                            inner join n_tip_nom e on e.id = d.idTnom 
                                            where a.id = ".$idEmp."                           
                               order by b.id desc  "),                                                            
           "datCont"  => $d->getGeneral("select a.*, b.nombre  
                                 from n_emp_contratos a 
                                     inner join a_tipcon b on b.id = a.idTcon 
                                      where idEmp = ".$idEmp." 
                                         order by a.id desc  "),                                                                       
           'url'     => $this->getRequest()->getBaseUrl(),
           'idEmp'   => $data->idEmp,
           "lin"     => $this->lin
      );      
      $view = new ViewModel($valores);        
      $this->layout('layout/blancoB'); // Layout del login
      return $view;        
   }
}

<?php
/*
 * FUNCIONES DE NOMINA NISSI
 * 
 */
namespace Principal\Model;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

use Zend\Authentication\Result;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;

use Principal\Model\AlbumTable; 
//
/// INDICE

//// FUNCIONES BASICAS ------------------------------------------
// 0. DATOS DEL PC Y USUARIO ACTIVO

class LogFunc extends AbstractTableGateway
{      
   protected $table  = '';  
   public $adapter; 
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
   }             
   // Variables para guardar datos en log
   public function getDatLog()
   {
       // Usuario activo
       $auth     = new AuthenticationService();
       $identity = $auth->getIdentity();       
       $usu      = ltrim($identity->usr_name);              
       $du       = array("usu"  => $usu); // Toca hacer esta vuelta para extraer sin escapes la variabel identidad
       $usu =  $du['usu'];
       // Buscar rol 
       $d = new AlbumTable($this->adapter);
       $datos = $d->getRolUsu($usu); // Buscar datos del rol del usuario
       // Ip del pc
       $ip     =  $_SERVER['REMOTE_ADDR']; 
       // Fecha del sistema
       $date   = new \DateTime(); 
       $fecSis = $date->format('Y-m-d H:i');        

       
       $datos=array("idUsu"  => $datos['id'],
                    "ipSer"  => $ip,
                    "fecSis" => $fecSis,
                    "idRol"  => $datos['idRol'],
                    "idEmp"  => $datos['idEmp'],
                    "admin"  => $datos['admin']
           );
       return $datos;       
   }
   // Guardar en tabla de log 
   public function getDatLogG($idUsu, $ipSer, $fecha, $idRol, $tabla, $cambio, $accion)
   {
     $pos = strpos($tabla, "i_constructor");  
     if ($pos==0)
        $pos = strpos($tabla, "i_cabecera");          
     if ($pos==0)
        $pos = strpos($tabla, "n_nomina");          
     if ($pos==0)
        $pos = strpos($tabla, "n_nomina_e");          
     if ($pos==0)
        $pos = strpos($tabla, "n_nomina_e_d");          
     if ($pos==0)
        $pos = strpos($tabla, "i_pie");          
     
     if ($pos==0)
        $this->adapter->query("insert into h_logmaestros (idUsu, ipSer, fechaNis, idRol, tabla, cambios, accion ) 
             values (".$idUsu.",'".$ipSer."','".$fecha."',".$idRol.",'".$tabla."','".$cambio."','".$accion."' )",Adapter::QUERY_MODE_EXECUTE);
   }
   
   // Url actual
   public function getUrl()
   {
       //$url = $_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];       
       
       $ruta = array("server" => $_SERVER['HTTP_HOST'],
                     "puerto" => $_SERVER['SERVER_PORT'],
                     "puerto" => $_SERVER['REQUEST_URI'],);
       return $ruta ;
   }   
    
}
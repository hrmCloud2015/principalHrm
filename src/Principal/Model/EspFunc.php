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

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport\SmtpOptions;

//
/// INDICE

//// FUNCIONES BASICAS ------------------------------------------
// 0. DATOS DEL PC Y USUARIO ACTIVO

class EspFunc extends AbstractTableGateway
{      
   protected $table  = '';  
   // Url actual
   public function getUrl()
   {
       $url = $_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];       
       
       // Obtener el id de la cadena , que sera el codigo del id del informe
       //echo $_SERVER['REQUEST_URI'].'<br />';
       $pIdr = strripos( $_SERVER['REQUEST_URI'] , '/');
       $idRepor = substr( $_SERVER['REQUEST_URI'] , $pIdr+1 ,1000 ) ; // Obtener el id del reporte              
       
       // Buscar la ubicacion del public 
       $host = strripos( $_SERVER['REQUEST_URI'] , 'public/');
       $rutaContr = substr( $_SERVER['REQUEST_URI'] , $host+7 ,1000 ) ; // Obtener el id del reporte              

       $host = strripos( $rutaContr , '/list');
       $rutaContr2 = substr( $rutaContr , 0, $host ) ; // Obtener el id del reporte              

       $ruta = array("server" => $_SERVER['HTTP_HOST'],
                     "puerto" => $_SERVER['SERVER_PORT'],
                     "rutaC"  => $_SERVER['REQUEST_URI'],
                     "ruta"   => $_SERVER['REQUEST_URI'],
                     "rutaControl" => $rutaContr,
                     "rutaControl2" => $rutaContr2,  // Solo controlador y modelo                   
                     "para"   => $idRepor,
                    );       
       return $ruta ;
   }      

  // Redondear
   public function getRedondear($valor, $tipo)
   {
      $valor = round($valor,0);
      $lon = strlen($valor);    
      // Validar paa subir unidades      
      $sumar = 0;
      if ( $tipo == 1 ) // UNIDADES 000 MIL
      {
         if ( substr( rtrim($valor) ,$lon-3, 3) > 500 ) // Mayor a 5 pasa a la sieguiente unidad
            $sumar = 1;
         $valor = ((substr( rtrim($valor) ,0, $lon-3))+$sumar).'000' ;
      }
      if ( $tipo == 2 ) // UNIDADES 00 CENTENAS
      {         
         if ( substr( rtrim($valor) ,$lon-2, 2) > 50 ) // Mayor a 5 pasa a la sieguiente unidad
            $sumar = 1;
         $valor = ((substr( rtrim($valor) ,0, $lon-2))+$sumar).'00' ;
      }      

      if ( $tipo == 3 ) // UNIDADES 0 DECENAS
      {
         if ( substr( $valor,$lon-1, 1) > 5 ) // Mayor a 5 pasa a la sieguiente unidad
            $sumar = 1;
         $valor = ((substr( $valor,0, $lon-1))+$sumar).'0' ;
      }      

      if ( $tipo == 4 ) // UNIDADES 00 CENTENAS funcion arriba  
      {         
         if ( substr( rtrim($valor) ,$lon-2, 2) >= 50 ) // Mayor a 5 pasa a la sieguiente unidad
            $sumar = 1;
         $valor = ((substr( rtrim($valor) ,0, $lon-2))+$sumar).'00' ;
      }      
      
      return $valor ;
   }                         
               
}


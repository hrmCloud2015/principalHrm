<?php
/*
 * FUNCIONES DE NOMINA NISSI
 * 
 */
namespace Principal\Model;
 
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

use Zend\Authentication\Result;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

use Zend\Mail\Exception\ExceptionInterface as MailException;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;

use Principal\Model\AlbumTable; // Libro de consultas
use Principal\Model\EspFunc; // Funciones especiales 

// PHP Email 
//require './vendor/Classes/PHPMailer/class.phpmailer.php'; 
//use PHPMailer;

//require './vendor/Classes/PHPMailer/PHPMailerAutoload.php'; 
//use PHPMailer;

//require './vendor/Classes/PHPMailer/class.smtp.php'; 
//use SMTP;

//require './vendor/Classes/PHPMailer/PHPMailerAutoload.php'; 
//use PHPMailerAutoload;

// TCPDF PARA FORMATOS
require './vendor/Classes/tcpdf/tcpdf.php'; 
require './vendor/Classes/fpdi/fpdi.php'; 
use Tcpdf; 
use FPDI; 

/// INDICE

//// FUNCIONES BASICAS ------------------------------------------
// 0. FUNCION GENERAL PARA CALCULOS EN NOMINA
// 01. VALOR DE FORMULAS
     
//// FUNCIONES GENERALES ----------------------------------------
class EmailFunc extends AbstractTableGateway
{
   protected $table  = '';   
      
   public $dbAdapter;

   public $empresa;
   public $logo;
   public $nit;
   public $codigo;
   public $version;
   public $titulo;
   public $cabecera;
   public $pie;

   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
   }

   // Gestor de correos electronicos 
   public function gestorCorreos($textBody, $estado)
   {
      $f = new EspFunc($this->adapter);  
      $d = new AlbumTable($this->adapter);  
      // Configuracion general 
      $datCg = $d->getConfiguraG(""); // Datos de la configuracion general del entorno del programa
      $this->logo  = $datCg['logo'];
      $this->empresa = $datCg['empresa'];
      $this->nit    = $datCg['nit'];           
      $datUrl = $f->getUrl();
      $urlActual = $datUrl['rutaControl2'];        
      // Consulta de configuracoin de envio de correos 
      $dat = $d->getGestorCorreos($urlActual); // Consulta del reporte de la opcion      
      foreach ($dat as $datE) 
      {
         // Validacion estados         
         $textoEstado='';
         if ($datE['aprobar']==1) // aprobacion de documentos
             $textoEstado = '<strong>DOCUMENTO APROBADO</strong><br />';


         $subject  = ltrim($datE['nomMod']).' - '.ltrim($datE['nomOp']) ;
         $from = $this->empresa; 
         $to = ltrim($datE['email']);     
         // Contenido del correo  
         $htmlBody = '';

//echo $datE['aprobar'].'-'.$estado;
         if ( ( $datE['aprobar']==1 ) and ($estado==1) )// Si el docu es aprobado y hay gstor envia
            $this->sendMailSimple($htmlBody, $textoEstado.$textBody, $subject, $from, $to);
      } // Fin recorrido 

   }// Fin validacion del gestor 

   public function envioMail($para)
   {
       $d = new AlbumTable($this->adapter);  
       $datGen = $d->getConfiguraG(''); //---------------- CONFIGURACIONES GENERALES (1)
       $correo = ltrim($datGen['emailPago']); 
       $clave = ltrim($datGen['clavePago']); 
       $smtp = ltrim($datGen['smtp']); 
       $ssl = ltrim($datGen['emailSsl']); 
       $port = ltrim($datGen['emailPort']); 
       $name = ltrim($datGen['emailName']); 
       $host = ltrim($datGen['emailHost']); 

       $subject='Comprobante de pago - Nomina';
       $enviado=true;
       try{
        ini_set('max_execution_time', 600); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        //$para = 'wilsonmet8@gmail.com';
        $message = new Message();
        $message->addTo//($to)
            ($para)
               ->addFrom//($from)
               ($correo)
               ->setSubject($subject);
    
    //echo $name.'<br />';
    //echo $host.'<br />';
    //echo $port.'<br />';
    //echo $smtp.'<br />';
    //echo $correo.'<br />';
    //echo $clave.'<br />';
    //echo $ssl.'<br />';
        // Setup SMTP transport using LOGIN authentication
        $transport = new SmtpTransport();
        $options   = new SmtpOptions(array(
           'name'              => $name,
           'host'              => $host,
           'port'              => $port, // Notice port change for TLS is 587
           'connection_class'  => $smtp,
           'connection_config' => array(
                 'username' => $correo,
                 'password' => $clave,
                'ssl'      => $ssl,
           ),
        ));             
        
        $textBody='Volante de nomina de la primera quincena de Mayo del 2016';
        $html = new MimePart($textBody);
        $html->type = "text/html";
        $body = new MimeMessage();
        $body->addPart($html);

        $contentPart = new MimePart($body->generateMessage());        
        $contentPart->type = 'multipart/alternative;' . PHP_EOL . ' boundary="' . $body->getMime()->boundary() . '"';

        $attachment = new MimePart(fopen( __DIR__.'archivo.pdf' , 'r'));
        $attachment->type = 'application/pdf';
        $attachment->encoding    = Mime::ENCODING_BASE64;
        $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
        
        $body = new MimeMessage();
        $body->setParts(array($contentPart, $attachment));
 
        $message->setBody($body);
        $enviado = true;
        $transport->setOptions($options);
        $transport->send($message);
        } catch (MailException   $ex) {
            print_r($ex);
            $enviado= false;
            return false;
       }
            //2
       return $enviado;


   } // ENVIO DE EMAIL  

   // ENVIO DE EMAIL SIMPLES 
   public function envioMailSimple($para, $mensaje, $textBody)
   {
       $d = new AlbumTable($this->adapter);  
       $datGen = $d->getConfiguraG(''); //---------------- CONFIGURACIONES GENERALES (1)
       $correo = ltrim($datGen['emailPago']); 
       $clave = ltrim($datGen['clavePago']); 
       $smtp = ltrim($datGen['smtp']); 
       $ssl = ltrim($datGen['emailSsl']); 
       $port = ltrim($datGen['emailPort']); 
       $name = ltrim($datGen['emailName']); 
       $host = ltrim($datGen['emailHost']); 

       $subject = $mensaje ;
       $enviado=true;
       try{
        ini_set('max_execution_time', 600); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        //$para = 'wilsonmet8@gmail.com';
        $message = new Message();
        $message->addTo//($to)
            ($para)
               ->addFrom//($from)
               ($correo)
               ->setSubject($subject);
        // Setup SMTP transport using LOGIN authentication
        $transport = new SmtpTransport();
        $options   = new SmtpOptions(array(
           'name'              => $name,
           'host'              => $host,
           'port'              => $port, // Notice port change for TLS is 587
           'connection_class'  => $smtp,
           'connection_config' => array(
                 'username' => $correo,
                 'password' => $clave,
                 'ssl'      => $ssl,
           ),
        ));                    
        
        $html = new MimePart($textBody);
        $html->type = "text/html";
        $body = new MimeMessage();
        $body->addPart($html);

        $message->setBody($body);
        $enviado = true;
        $transport->setOptions($options);
        $transport->send($message);
        } catch (MailException   $ex) {
            echo ($ex);
            $enviado= false;
            //return false;
       }
            //2
       return $enviado;


   } // ENVIO DE EMAIL SIMPLES

   // ENVIO DE EMAIL SIMPLES CON ADJUNTOS 
   public function envioMailSimpleAdjunto($para, $mensaje, $textBody)
   {
       $d = new AlbumTable($this->adapter);  
       $datGen = $d->getConfiguraG(''); //---------------- CONFIGURACIONES GENERALES (1)
       $correo = ltrim($datGen['emailPago']); 
       $clave = ltrim($datGen['clavePago']); 
       $smtp = ltrim($datGen['smtp']); 
       $ssl = ltrim($datGen['emailSsl']); 
       $port = ltrim($datGen['emailPort']); 
       $name = ltrim($datGen['emailName']); 
       $host = ltrim($datGen['emailHost']); 

       $subject = $mensaje ;
       $enviado=true;
       try{
        ini_set('max_execution_time', 600); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        //$para = 'wilsonmet8@gmail.com';
        $message = new Message();
        $message->addTo//($to)
            ($para)
               ->addFrom//($from)
               ($correo)
               ->setSubject($subject);
        // Setup SMTP transport using LOGIN authentication
        $transport = new SmtpTransport();
        $options   = new SmtpOptions(array(
           'name'              => $name,
           'host'              => $host,
           'port'              => $port, // Notice port change for TLS is 587
           'connection_class'  => $smtp,
           'connection_config' => array(
                 'username' => $correo,
                 'password' => $clave,
                 'ssl'      => $ssl,
           ),
        ));                    

        $textBody = 'A continuacion adjuntamos del detalle de pago de su nomina de acuerdo al periodo correspondiente';

        $html = new MimePart($textBody);
        $html->type = "text/html";
        $body = new MimeMessage();
        $body->addPart($html);

        $contentPart = new MimePart($body->generateMessage());        
        $contentPart->type = 'multipart/alternative;' . PHP_EOL . ' boundary="' . $body->getMime()->boundary() . '"';

        $attachment = new MimePart(fopen( __DIR__.'\archivoNom.pdf' , 'r'));
        $attachment->type = 'application/pdf';
        $attachment->encoding    = Mime::ENCODING_BASE64;
        $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;       


        $body = new MimeMessage($textBody);
        $body->setParts(array($contentPart, $attachment));        

        $message->setBody($body);
        $enviado = true;
        $transport->setOptions($options);
        $transport->send($message);
        } catch (MailException   $ex) {
            echo ($ex);
            $enviado= false;
            //return false;
       }
            //2
       return $enviado;

   } // ENVIO DE EMAIL SIMPLES CON ADJUNTOS

   // ESPECIAL: Enviar correo sin adjunto 
   public function sendMailSimple($htmlBody, $textBody, $subject, $from, $to)
   {
       $enviado=true;
       try{
        ini_set('max_execution_time', 600); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
        $message = new Message();
        $message->addTo( 'wilsonmet8@gmail.com' )
            //('n.rivera792@gmail.com')
               ->addFrom//($from)
               ('wilsonmet8@gmail.com')
               ->setSubject($subject);
    
        // Setup SMTP transport using LOGIN authentication
        $transport = new SmtpTransport();
        /*$options   = new SmtpOptions(array(
           'name' => 'localhost.localdomain',
           'host' => 'localhost',
           'port' => 25,
        ));*/
        $options = new SmtpOptions(array(
            'host' => 'smtp.gmail.com',
            'connection_class' => 'login',
            'port' => '465',
            //587,
        'connection_config' => array(
        'ssl' => 'ssl', /* Page would hang without this line being added */
             'username' => 'wilsonmet8@gmail.com',
            'password' => '',
        ),
        ));     
               
        
        $html = new MimePart($textBody);
        $html->type = "text/html";
        $body = new MimeMessage();
        $body->addPart($html);

        $contentPart = new MimePart($body->generateMessage());        
        $contentPart->type = 'multipart/alternative;' . PHP_EOL . ' boundary="' . $body->getMime()->boundary() . '"';

        //$attachment = new MimePart(fopen( __DIR__.'archivo.pdf' , 'r'));
        //$attachment->type = 'application/pdf';
        //$attachment->encoding    = Mime::ENCODING_BASE64;
        //$attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
        
        //$body = new MimeMessage();
        //$body->setParts(array($contentPart, $attachment));
 
        $message->setBody($body);
 
        $transport->setOptions($options);
        $transport->send($message);
        } catch (MailException   $ex) {
               echo '<br>correo del reponsable: '.$to.'<br>' . get_class($ex) . ' says: ' . $ex->getMessage();  
            //print_r($ex->getMessage());
            return $to;
            //return false;
       }
            
       return $enviado;
    }  

   // ESPECIAL: Enviar correo
    public function sendMail($htmlBody, $textBody, $subject, $from, $to)
    {

      $to = 'wilsonmet8@gmail.com';
$subject = 'test';
$message = 'hello world';
$mail = mail($to,$subject,$message, "From: XXX@gmail.com");

        $message = new \Zend\Mail\Message();
        $message->setBody("hola");
        $message->setFrom("hola 2");
        $message->setSubject("dols");
        $message->addTo("wilsonmet8@gmail.com");

        $smtpOptions = new \Zend\Mail\Transport\SmtpOptions(); 

        $smtpOptions->setHost('smtp.gmail.com')
                    ->setConnectionClass('login')
                    ->setName('smtp.gmail.com')
                    ->setPort('465')
                    ->setConnectionConfig( array(
                       'username' => 'wilsonmet8@gmail.com',
                       'password' => '',
                       'ssl' => 'ssl'
                    ) );   
        $transport = new \Zend\Mail\Transport\Smtp($smtpOptions);  
        $transport->send($message);          
    }

   // Crear pdf para adjunto de la tabla i_formatos
   public function creadAdjunto($idR, $idC) 
   {
      $d = new AlbumTable($this->adapter);  
      // Configuracion general 
      $datCg = $d->getConfiguraG(""); // Datos de la configuracion general del entorno del programa
      $this->logo = $datCg['logo'];
      $this->empresa = $datCg['empresa'];
      $this->nit    = $datCg['nit'];     
      $this->codigo   = "";     
      $this->version  = "";     
      
      // CONSULTA REPORTE SOLICITADO
      $dat = $d->getGeneral1("select a.*,b.cabecera, b.varCab, c.pie 
                              from i_formatos a 
                                left join i_cabecera b on b.id=a.idCab  
                                left join i_pie c on c.id=a.idPie
                              where a.id=".$idR); // Consulta del reporte de la opcion   
         // print_r($dat);                       
      $this->titulo   = $dat['nombre'];
      $this->cabecera = $dat['cabecera'];        
      $this->pie      = $dat['pie'];             
      $codigo         = $dat['codigo'];
      $version        = $dat['version'];     
      
      $varCab    = $dat['varCab'];
      if ($dat['sentido']==0)
         $sentido = "P";
      else
         $sentido = "L";
       
      // ********************************
      // *---------------------------------- INICIO FORMATO ---------------------------------------------------*
      // ********************************      
      // (0) Datos ------------ 
      $con="";                 

      $columnas='';
      $consulta = '"'.$dat['consulta'].'"'; 
      //echo $dat['consulta'];
      if (ltrim($dat['consulta'])!='')
      {
         eval("\$consulta =$consulta;");            
         $datos = $d->getGeneral($consulta);                   
         $columnas = '';  
      }       
    
      // Consulta variables adicionales para datos en cabecera
      $v = ltrim($varCab);
      if ($v!='')
      {
         //eval("\$str =$varCab;"); 
        foreach ($datos as $resultado)
          {  
            $varCab1 =  $resultado['titulo'];  
         }
      }     
     
      //$this->FPDF($sentido, "mm", "A4"); // Llamar esta funcion es vital (P: Vertical, L:Horizontal )
      //$this->AddPage();       
      $pdf = new Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
      $pdf->SetCreator(PDF_CREATOR);
      $datGen = $d->getConfiguraG(" where id=1"); // Obtener datos de configuracion general        
      $rutaP = $datGen['ruta']; // Ruta padre      
      $logo   = $rutaP."/Datos/General/".$this->logo; // Linux
        $pdf->AddPage();      
      //echo $logo;
      // (3) INICIO DETALLE------------------------------------
      $v = ltrim($dat['detalle']);
      if ($v!='')  
        {         
          $detalle = $dat['detalle'];          
          //echo $detalle;
          eval("\$str =$detalle;");    
             
        }

          //          $pdf->Ln();        
      //   $pdf->SetFont("times","","9");
      //   $pdf->Cell(40,5,'TOTALES DEL EMPLEADO:',1,0,'L',true);  
      // (3) FIN DETALLE-------------------------------------------
//        echo 'Ruta '.__DIR__;
      $pdf->Output( __DIR__.'\archivoNom.pdf'  ,"F");
       //$pdf->Output("listado","I");
                                
      // *******************************************************************************************************
      // *---------------------------------- FIN FORMATOS PDF *******-----------------------------------------------*
      // *******************************************************************************************************                       
   }        
   /// Configuraciones del reporte PDF ///         
   function Header() 
   { 
    $empresa= $this->empresa;
    $nit    = $this->nit;        
    $titulo = $this->titulo;
    $codigo = $this->codigo;        
    $version = $this->version;
    
    $date   = new \DateTime(); 
    $fecha  = substr( $date->format('Y-m-d H:i'),0,11);  

    
    $varCab1 = $this->varCab1 ;
    $varCab2 = $this->varCab2 ;
    $varCab3 = $this->varCab3 ;

    $f = new AlbumTable($this->dbAdapter);
    $datGen = $f->getConfiguraG(" where id=1"); // Obtener datos de configuracion general        
    $rutaP = $datGen['ruta']; // Ruta padre
    
    $logo   = $rutaP."/Datos/General/".$this->logo; // Linux
    
    $cab    = $this->cabecera;
    eval("\$str =$cab;");    
    $pdf->Ln(); 
    $columnas = $this->columnas;
    if (ltrim($columnas )!='')
        eval("\$str =$columnas;");     
    
    if ($this->PageNo()==2)
        $pdf->Ln();      
        $pdf->Ln();
   }      


   // Adjunto ingresos y retenciones
   public function creadAdjuntoCert($idEmp, $ano) 
   {
      $d = new AlbumTable($this->adapter);  
      // Create a new PDF document
      $pdf = new FPDI();
      $datGen = $d->getConfiguraG(" where id=1"); // Obtener datos de configuracion general        
      $rutaP = $datGen['ruta']; // Ruta padre      
      $ruta   = $rutaP."/Datos/General/rete.pdf"; // Linux
      $pos = 16; // Posicion de numeros en campos de valores
      // Datos del empleado
      $con = '';
      if ( $idEmp > 0)  
          $con = ' and a.id = '.$idEm; 

      $datEmp = $d->getGeneral("select a.id 
                                 from a_empleados a 
                                     inner join n_tipemp b on b.id = a.idTemp 
                                where a.estado = 0 and b.tipo = 0  ".$con );
      foreach ($datEmp as $datE) 
      {
          $idEmp = $datE['id'];
          $datCon = $d->getGeneral("select b.nit, b.empresa,  
a.CedEmp, a.nombre1, a.nombre2 , a.apellido1, a.apellido2 ,
 concat( year(now()) ,'-' ,lpad( month(now()),2,'0')  ,'-', lpad( day(now()),2,'0')  ) as fecha, 
c.nombre as ciudad, substring( c.codigo,1,2 ) as codDep, substring( c.codigo,3,100 ) as codCiu,                 
 # ------------------------------------------------------------- Procesos que hacen parte de certificados de ingresos y retenciones (1) 
lpad( ltrim(format( ( Select round(sum( dd.devengado ),0)
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
        inner join n_conceptos_pr gg on gg.idConc = ee.id # Procesos ingreso y retenciones 
     where gg.idProc = 10 and dd.idConc != 250 and dd.idConc not in (195,213) and dd.devengado > 0 and year(ff.fechaF) = ".$ano." and aa.idEmp = a.id ) ,0 ) ) ,".$pos.",'.') as pagosEmp, 
( Select round(sum( dd.devengado ),0)
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
        inner join n_conceptos_pr gg on gg.idConc = ee.id # Procesos ingreso y retenciones 
     where gg.idProc = 10 and dd.idConc != 250 and dd.idConc not in (195,213) and dd.devengado > 0 and year(ff.fechaF) = ".$ano." and aa.idEmp = a.id )  as pagosEmp2,      
 # -----------------------------------------------------------------------------------------------------------------------Cesantias (2)
lpad( ltrim(format( ( Select round(sum( dd.devengado),0) + 
( case when bb.id = 71 then 4186000 else 
     case when bb.id = 41 then 6175800 else 
       case when bb.id = 72 then 31320000 else 
         case when bb.id = 75 then 48073500 else 
           case when bb.id = 48 then 5343000 else 
              0 
           end  
         end   
       end  
     end 
   end ) 
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where dd.devengado > 0 and year(ff.fecha) = ".$ano." and dd.idConc in (289,195,213) and ff.idTnom != 10 and aa.idEmp = a.id ),0 ) ) ,".$pos.",'.') as cesantias,

( Select round(sum( dd.devengado ),0) + 
( case when bb.id = 71 then 4186000 else 
     case when bb.id = 41 then 6175800 else 
       case when bb.id = 72 then 31320000 else 
         case when bb.id = 75 then 48073500 else 
           case when bb.id = 48 then 5343000 else 
              0 
           end  
         end   
       end  
     end 
   end ) 
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where dd.devengado > 0 and ff.idTnom != 10 and year(ff.fecha) = ".$ano." and dd.idConc in (289,195,213) and aa.idEmp = a.id ) as cesantias3,    

( Select round(sum( dd.devengado ),0)
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where dd.devengado > 0 and ff.idTnom != 10 and year(ff.fechaF) = ".$ano." and dd.idConc in (289,195,213) and aa.idEmp = a.id ) as cesantias2,     
 # ------------------------------------------------------------------------------------------------------------- Gastos de representacion (3)
lpad( format( ( 0  ) ,0  ) ,".$pos.",'.') as gastRepre,     
 # -------------------------------------------------------------------------------------------------------------- Pension por vejez (4)
lpad( format( ( 0  ) ,0  ) ,".$pos.",'.') as pensionVejez,     
 # ------------------------------------------------------------------------- Procesos que hacen parte de los ingresos pero no de certificados (5)
lpad( format( ( Select sum( dd.devengado )  
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where  dd.devengado > 0  and dd.idConc not in (289, 250) and ff.idTnom != 10 and year(ff.fechaF) = ".$ano." and aa.idEmp = a.id ),0  ) ,".$pos.",'.') as pagosOtrEmp,      
( Select sum( dd.devengado )  
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where  dd.devengado > 0 and dd.idConc not in (289, 250) and ff.idTnom != 10 and year(ff.fechaF) = ".$ano." and aa.idEmp = a.id )as pagosOtrEmp2,           
 # --------------------------------------------------------------------------------------------------------------------- Salud (6)
lpad( format( ( Select sum( dd.deducido )
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where year(ff.fechaF) = ".$ano." and dd.idConc in (15) and aa.idEmp = a.id  ),0  ) ,".$pos.",'.') as salud, 
( Select sum( dd.deducido )
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where year(ff.fechaF) = ".$ano." and dd.idConc in (15) and aa.idEmp = a.id  ) as salud2,      
 # ---------------------------------------------------------------------------------------------------------------------- Pension (7)
lpad( format( ( Select sum( dd.deducido )
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where year(ff.fechaF) = ".$ano." and dd.idConc in (11,21) and aa.idEmp = a.id  ),0  ) ,".$pos.",'.') as pension,
( Select sum( dd.deducido )
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where year(ff.fechaF) = ".$ano." and dd.idConc in (11,21) and aa.idEmp = a.id  ) as pension2,     
 # ----------------------------------------------------------------------------------------------------------------- Aportes voluntarios (8)
lpad( format( ( 0  ) ,0  ) ,".$pos.",'.') as aportesVol,          
 # ------------------------------------------------------------------------------------------------------------------------ Rete fuente (9)
lpad( format( ( Select sum( dd.deducido )
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where year(ff.fechaF) = ".$ano." and dd.idConc != 250 and dd.idConc in (10) and aa.idEmp = a.id  ) ,0  ) ,".$pos.",'.') as reteFuente,
( Select sum( dd.deducido )
     from n_nomina_e aa 
        inner join n_nomina ff on ff.id = aa.idNom 
        inner join a_empleados bb on bb.id = aa.idEmp 
        inner join n_cencostos cc on cc.id = bb.idCcos 
        inner join n_nomina_e_d dd on dd.idInom = aa.id 
        inner join n_conceptos ee on ee.id = dd.idConc 
     where year(ff.fechaF) = ".$ano." and dd.idConc != 250 and dd.idConc in (10) and aa.idEmp = a.id  )  as reteFuente2           , a.id as idEmp   
# Fin consultas ------------------------------------------------------------------------------------------------------     
                              from a_empleados a 
                               left join c_general b on b.id = 1 
                               left join n_ciudades c on c.id = a.idCiu 
                            where a.estado = 0 and a.id = ".$idEmp." 
                              order by a.nombre "); 
      $fechaI = $ano.'01-01';
      $fechaF = $ano.'12-31';
      foreach ($datCon as $dat) 
      {
         $pageCount = $pdf->setSourceFile($ruta);
         $pageNo = 1; 
         // import a page
         $templateId = $pdf->importPage($pageNo);
         // get the size of the imported page
         $size = $pdf->getTemplateSize($templateId);
         // create a page (landscape or portrait depending on the imported page size)
         if ($size['w'] > $size['h']) 
         {
           $pdf->AddPage('L', array($size['w'], $size['h']));
         } else {
           $pdf->AddPage('P', array($size['w'], $size['h']));
         }
         $pdf->useTemplate($templateId);         
         // DATOS DE EMPLEADOR --------------------- (1)
         $pdf->SetFont('Courier','B');
         $pdf->SetXY(13, 32);
         $pdf->Write(6, $dat['nit'] );
         $pdf->SetXY(13, 41);
         $pdf->Write(6, $dat['empresa'] );

         // FIN DATOS DEL EMPLEADOR --------------------- (1)         
         // DATOS DEL EMPLEADO --------------------- (2)
         $pdf->SetXY(28, 48);
         $pdf->Write(6, $dat['CedEmp'] );
         $pdf->SetXY(80, 48);
         $pdf->Write(6, $dat['apellido1'] );
         $pdf->SetXY(112, 48);
         $pdf->Write(6, $dat['apellido2'] );         
         $pdf->SetXY(144, 48);
         $pdf->Write(6, $dat['nombre1'] );                  
         $pdf->SetXY(175, 48);
         $pdf->Write(6, $dat['nombre2'] );                           
         // FIN DATOS DEL EMPLEADO --------------------- (2)
         // FECHAS, LUGAR, MUNICIPIO --------------------- (3)         
         $pdf->SetXY(20, 58);
         $pdf->Write(6, $fechaI );
         $pdf->SetXY(59, 58);
         $pdf->Write(6, $fechaF );
         $pdf->SetXY(91, 58);
         $pdf->Write(6, $dat['fecha'] );
         $pdf->SetXY(91, 58);
         $pdf->Write(6, $dat['fecha'] );         
         $pdf->SetXY(120, 58);
         $pdf->Write(6, $dat['ciudad'] );                  
         $pdf->SetXY(184, 58);
         $pdf->Write(6, $dat['codDep'] );                           
         $pdf->SetXY(195, 58);
         $pdf->Write(6, $dat['codCiu'] );                                    
         // FIN FECHAS, LUGAR, MUNICIPIO --------------------- (3)

         // CONCEPTO DE LOS INGRESOS  --------------------------------- (4) 
         $posNum = 169; 
         $pdf->SetFont('Courier','B',10);
         $pdf->SetXY( $posNum , 71);
         $pdf->Write(6, $dat['pagosEmp'] ); //-- 37 
//echo $dat['pagosEmp'].'<br />';

         $pdf->SetXY( $posNum , 76);         
         $cesa = $dat['cesantias'];

         if ($idEmp == 75 )
             $cesa = '......52,266,246';
         if ($idEmp == 48 )
             $cesa = '......5,343,000';          
           
         $pdf->Write(6, $cesa ); //-- 38
//echo $dat['cesantias'];
         $pdf->SetXY( $posNum , 80);
         $pdf->Write(6,  $dat['gastRepre'] ); //-- 39

         $pdf->SetXY( $posNum , 84);
         $pdf->Write(6, $dat['pensionVejez'] ); //-- 40

         $pdf->SetXY( $posNum , 88);

         $cesa2 = $dat['cesantias2'];
if ($idEmp == 75 )         
         $cesa2 = 0;
if ($idEmp == 48 )
       $cesa2 = 0;

         $cesantiasPagos = ( $cesa2+$dat['pagosEmp2'] );
         
         $pagoOtrEmp = $dat['pagosOtrEmp2'] - $cesantiasPagos;

         $datTot = $d->getGeneral1("select lpad( format( ( ".$pagoOtrEmp." ) ,0  ) ,".$pos.",'.') as valor"); // Totales truco de formateo     
         $pdf->Write(6, $datTot['valor'] ); //-- 41

$cesa3 = $dat['cesantias3'];
if ($idEmp == 75 )         
         $cesa3 = 52266246;
if ($idEmp == 48 )         
         $cesa3 = 5343000;
       

         $datTot = $d->getGeneral1("select lpad( format( ( ".( $dat['pagosEmp2'] + $pagoOtrEmp + $cesa3 )." ) ,0  ) ,".$pos.",'.') as valor"); // Totales truco de formateo     
         $pdf->SetXY( $posNum , 92);
         $pdf->Write(6, $datTot['valor']  ); //-- total 

         // FIN CONCEPTO DE LOS INGRESOS  ----------------------------- (4)


         // CONCEPTO DE LOS APORTES  --------------------------------- (5)
         $pdf->SetXY( $posNum , 101);
         $pdf->Write(6,  $dat['salud'] ); //-- 43 
         $pdf->SetXY( $posNum , 105);
         $pdf->Write(6,  $dat['pension'] ); //-- 44 
         $pdf->SetXY( $posNum , 109);
         $pdf->Write(6,  $dat['aportesVol'] ); //-- 45          
         
         $pdf->SetXY( $posNum , 113);
         $pdf->Write(6,  $dat['reteFuente'] ); //-- 45          
         // FIN CONCEPTO DE LOS APORTES  ----------------------------- (5)

        }// Fin recorrido conceptos de empleado 
       # code...
      }// Fin recorrido empleados       
      $pdf->Output( '/var/www/nomina.cajamag.com.co/public/rete2.pdf'  ,"F");      
     //$pdf->Output("listado","I");
     //$pdf->Output('newpdf.pdf', 'D');                        

   }        
}




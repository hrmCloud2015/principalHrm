<?php
/*
 * STANDAR DE NISSI CONSULTAS
 * 
 */
namespace Principal\Model;
 
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport\SmtpOptions;

use Principal\Model\LogFunc;
use Principal\Model\EmailFunc;

class Alertas extends AbstractTableGateway
{
   protected $table  = 't_nivelasp';   
   
   public $dbAdapter;
    
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
   }

    // Alertas vencimiento de contratos
    public function getVencimientoContratos()
    { 
      $con = "select a.id, c.CedEmp, c.nombre , c.apellido , a.fechaI,  a.fechaF ,
(  a.fechaF between  now() and (date_add( now() , interval b.alertaFinCont day) )   ) as diasVenc ,
d.nombre as nomTcon, e.nombre as nomCar, f.nombre as nomCcos  
from n_emp_contratos a 
inner join c_general b on b.id = 1 
inner join a_empleados c on c.id = a.idEmp 
inner join a_tipcon d on d.id = a.idTcon 
inner join t_cargos e on e.id = a.idCar  
inner join n_cencostos f on f.id = a.idCcos 
where ( a.fechaF between  now() and (date_add( now() , interval b.alertaFinCont day) ) )
order by a.fechaF";  
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;                        
    }
    // Alertas vencimiento de contratos numero
    public function getVencimientoContratosN()
    { 
      $con = "select count(a.id) as num 
from n_emp_contratos a 
inner join c_general b on b.id = 1 
inner join a_empleados c on c.id = a.idEmp 
where a.estado=0 and (  a.fechaF between  now() and (date_add( now() , interval b.alertaFinCont day) )   )
order by a.fechaF ";  
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;                        
    }    
    // Alertas por documentos controlados vencidos 
    public function getDocusControlados()
    { 
      $con = "select datediff( f.fechaF, now() ), f.id, d.email as email, 
      d.nombre as nomRec, d.apellido as apeRec, h.nombre as nomDoc ,f.fechaF, g.CedEmp, g.nombre, g.apellido  
from t_docu_control a 
   inner join t_tip_docontrol b on b.id = a.idTdoc  
   inner join t_tip_docontrol_e c on c.idTdoc = b.id
   inner join a_empleados d on d.id = c.idEmp # empleados programdos para recibir alertas 
   inner join t_docu_control_e e on e.idDoc = a.id # documentos adjuntos 
   inner join t_docu_control_e_a f on f.idIdoc = e.id 
   inner join a_empleados g on g.id = e.idEmp # Empleados de los documentos especiales 
   inner join t_tip_docontrol_i h on h.id = f.idTdoc 
  where b.diasAlerta > 0 and d.email!=''  
    and f.fechaF != '0000-00-00' and datediff( f.fechaF, now() ) <= b.diasAlerta  and f.aviso=0"; 
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      $f = New EmailFunc($this->adapter);      
      $mensaje = 'Alerta ! Documentos de control vencidos';
      foreach ($datos as $dat) 
      {
         $textBody = "<strong> Sr(a) ".$dat['nomRec']." ".$dat['apeRec']." </strong> <br />";
         $textBody .= " Los siguientes documentos estan proximos a vencer: <br /><hr /><br />";  

         $textBody .= "1. Documento <strong>".$dat['nomDoc']."</strong> vence el <strong>".$dat['fechaF']."</strong> <br /><br />";  

         $textBody .= " del sr(a) ".$dat['nombre']." ".$dat['apellido']." C.C. ".$dat['CedEmp']." <br />";  

         $textBody .= "<hr /> Mensaje HrmCloud";  

         $f->envioMailSimple($dat['email'], $mensaje, $textBody);   
         $con = "update t_docu_control_e_a set aviso=1 where id =".$dat['id'];          
         $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);          
      }      

      return $datos;                        
    }

    // Alertas pendientes por contrato
    public function getPendientesContratos()
    { 
      $con = "select c.CedEmp, c.nombre , c.apellido , a.fechaI,  a.fechaF  
from n_emp_contratos a 
inner join c_general b on b.id = 1 
inner join a_empleados c on c.id = a.idEmp 
where a.estado=0 and (  a.fechaF between  now() and (date_add( now() , interval b.alertaFinCont day) )   )
order by a.fechaF ";  
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;                        
    }
    // Alertas pendientes por contratar
    public function getPendientesContratosN()
    { 
      $con = "select count(a.id) as num 
from n_emp_contratos a 
inner join c_general b on b.id = 1 
inner join a_empleados c on c.id = a.idEmp 
where a.estado=0 and (  a.fechaF between  now() and (date_add( now() , interval b.alertaFinCont day) )   )
order by a.fechaF ";  
         
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;                        
    }


    // Alertas por constructor 
    public function getConstructor($idAler)
    { 
      $con = '';
      if ($idAler>0)
          $con = " and a.id = ".$idAler ; 

      $result=$this->adapter->query("select a.consulta, a.cuerpo , a.id, 
                     a.nombre, a.cabecera 
                from i_constructor a 
                  where a.salida = 3 and a.estado = 1 and a.idMod = 2 
                  and not exists ( select null from c_alerta_sol_d_hist aa where aa.idCon = a.id and year(aa.fecha) = year( now() ) and month( aa.fecha ) = month( now() ) 
                        and day( aa.fecha ) = day( now() ) ) ".$con,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      $num = 0; 
      foreach ($datos as $dat) 
      {
        $num = 1; 
      }  
      if ( $num > 0 )
      {  

      $f = New EmailFunc($this->adapter);      
      $mensaje = 'Alerta ! '.$dat['nombre'];
      // Recorrido alertas configuradas en el consructor-------- (1)
      foreach ($datos as $dat) 
      {
         $consulta = '"'.$dat['consulta'].'"'; // Consulta dinamica 
         $cabecera = $dat['cabecera'];
         $textBody = "";         
         $idCon = $dat['id'];
         //echo $cuerpo;
         // Fin validacion consulta -------- (2)
         if (ltrim($dat['consulta'])!='')
         {
            eval("\$consulta =$consulta;");            
            $result = $this->adapter->query($consulta,Adapter::QUERY_MODE_EXECUTE);
            $datRep = $result->toArray();
          //print_r( $datRep );
            // Recrrida onsulta dinamica -------- (3)
            foreach ($datRep as $datR) 
            {
               $textB = $dat['cuerpo'];
               // Buscar campos pa reemplazar
               $result=$this->adapter->query("select * 
                  from i_constructor_ce where idCons = ".$idCon,Adapter::QUERY_MODE_EXECUTE);
               $datRe = $result->toArray();            
               foreach ($datRe as $datree) 
               {
                  $campo = "(".$datree['campo'].")";
                  $valor = $datree['valor'];
                  $textB = str_replace( $campo, $datR[ $valor], $textB);
                  //echo ltrim($textB).'<br />';               
               }         
                  $textBody = $textBody.$textB;
                //echo $textBody;  
             }// Recrrida onsulta dinamica -------- (3)
                  
             $cuerpo = $cabecera.'<hr />'.$textBody;     
             $con = '';  
             if ($idAler==0) // Alerta se realiza directamente la consutlta
                $con = " and ( select count( aa.id ) from c_alerta_sol_d_hist aa where aa.idAler = b.idAler and aa.idCon =  a.id and year(aa.fecha)=year(now()) 
                                     and month(aa.fecha)=month(now()) and day(aa.fecha) = day( now() )  ) = 0"; 

             $result=$this->adapter->query( "select b.id, b.idAler, ltrim(c.emailCor)  as email    
                        from i_constructor a 
                           inner join c_alerta_sol_d b on b.idAler = a.idAler 
                           inner join a_empleados c on c.id = b.idEmp 
                      where a.id = ".$idCon.$con ,Adapter::QUERY_MODE_EXECUTE);
               $datEm = $result->toArray();  
               // Recorrido empleados email -------- (4)  
               foreach ($datEm as $datE) 
               {
                   $idAler = $datE['idAler'];
                   $idDaler = $datE['id'];
                   $enviado = $f->envioMailSimple( $datE['email'], $mensaje, $cuerpo); 
                   if ($enviado)
                   {
                       $result = $this->adapter->query("insert into c_alerta_sol_d_hist (idAler, idDaler, idCon) 
                           values(".$idAler.",".$idDaler.",".$idCon.") ",Adapter::QUERY_MODE_EXECUTE);
                       //$result->toArray();                      
                   }  
              
               }// Recorrido empleados email -------- (4)                                      
             
         }  // Fin validacion consulta -------- (2)          
      } // Recorrido de alertas configuradas en el constructor (1)    
     } // Validacion 
      //return $datos;                        
    }
}

   
 

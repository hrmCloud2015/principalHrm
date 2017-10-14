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
use Principal\Model\Paranomina; // Parametros de nomina

/// INDICE

// Update general
// Consulta general
// Consulta general publica
// Consulta mostrar borrado o no 

// ESPECIAL: Datos de acceso a la opcion actual 

// Lista de roles
// Rol usuario
// rol de un usuario 
// Niveles de aspectos 
// Listado de lista de chqueos o niveles de cargos 
// Etapas de contratacion para lista de chequeo 
// Etapas de contratacion en tipo de contratacion
// Listado de cargos
// Listado de listas para cargos
// Listado de aspirantea de chqueos o niveles de cargos 
// Listado de departamentos
// Listado de sedes
// Listado de documentos aprobados para contratacion de cargos
// Listado de vacantes de cargos
// Cargos hoja de vida 
// Etapas de la lista de chequeo
// Listado de aspirantes cabecera lista de chequeo y resultados
// Listado de formularios
// Listado de etapas items y formularios
// Grupo de nomina
// Sub grupo de nomina
// Calendario
// Centros de costos
// Tipos de nomina
// Conceptos de nomina
// 
// Tipos de automaticos
// Prefijo contable
// Tipos de contratacion
// Tipos de automaticos en nominas aplicadas
// Movimiento de calendario en nomina
// Tipos de empleado
// Tipos de conceptos automaticos en nominas aplicadas
// Conceptos de nominas hijas 
// Lista de procesos
// Procesos del concepto
// Lista de empleados activos
// Listado maestro de empleados activos
// Lista de ausentismos
// Lista de incapacidades
// Calendario por tipo de nominas
// Periodos del tipo de calendario    
// Conceptos aplicados a una matriz
// Configuraciones generales
// Listado de cabeceras
// Promedio pago vacaciones
// Listado de vacaciones
// Listado de tipos de prestamos
// Consulta dias no habiles 
// Consulta empleados en nomina 
// Conceptos aplicados a tipos de incapacidades
// Conceptos aplicados a tipos de ausentismos
// Lista de salarios
// Escalas salariales 
// Escalas salariales en el cargo
// Listado de tipos de embargos
// Listado de bancos


//// LISTADOS MAESTROS Y DOCUMENTOS ///

// Documento novedades antes de nomina
// Listado maestro de conceptos activos
// Listado maestro de empleados activos
// Listado maestro de tipos de automaticos 
// Listado maestro de otros automaticos 


//// LISTADOS TALENTO HUMANO ///

// Listado de nivel de estudios
// Filtro de hojas de vidas por cargos
// Datos de la solictud de contratacion 
// Inventarios de dotaciones
// Listado grupos de dotaciones
// Listado areas
// Listado de tipos de descargas
// Listado de tipos de eventos
// Listado de descargos
// Listado lineas de dotaciones

class AlbumTable extends AbstractTableGateway
{
   protected $table  = 't_nivelasp';
   protected $table2 = 't_etapas_con';
   protected $table3 = 't_etapas_con';
   protected $table4 = 't_cargos';
   public $salarioMinimo;   
   
   public $dbAdapter;
    
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();

        // Parametros de nomina para funciones de consulta 
        $pn = new Paranomina($this->adapter);
        $dp = $pn->getGeneral1(1);
        $this->salarioMinimo=$dp['valorNum'];// Salario minimo

   }
   
   // Update general
   public function modGeneral($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);

   }

   // Update general con id 
   public function modGeneralId($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $id = $this->adapter->getDriver()->getLastGeneratedValue(); 
      return $id;
   }  


   // Consulta general
   public function getGeneral($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
   // Consulta general publica
   public function getGeneralP($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }   
   // Consulta general 1
   public function getGeneral1($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      //$datos=$result->toArray();
      $datos = $result->current();
      return $datos;
   }   
   // Consulta mostrar borrado o no
   public function getBregistro($tabla,$campo,$id)
   {
      $result=$this->adapter->query("select id as bloquear from ".$tabla." where ".$campo." =".$id,Adapter::QUERY_MODE_EXECUTE);
      //$datos=$result->toArray();
      $datos = $result->current();
      return $datos;
   }   
    
   // ESPECIAL: Datos de acceso a la opcion actual  
   public function getPermisos($lin)
   {
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog();      

      if ($dt['admin']==1)
      {
         $con = "select 1 as nuevo, 1 as modificar, 1 as eliminar, 1 as aprobar, 0 as vista,
            0 as idGrupNom 
          from c_mu2 limit 1";      
      }
      else // Usuario no administrador
      {
         $con = "select b.nuevo, b.modificar, b.eliminar, b.aprobar, b.vista,
               b.idGrupNom 
                 from c_mu3 a 
                   inner join c_roles_o b on b.idM3 = a.id 
                   inner join c_roles c on c.id = b.idRol 
                   inner join users d on d.idRol = c.id 
                   where d.id=".$dt['idUsu']." 
                   and concat('/',a.modelo , '/' , a.controlador, '/' ,  a.vista)='$lin'";        
      }
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();

//      if ( ($datos['nuevo']==0) and ($datos['modificar']==0) and ($datos['eliminar']==0) )
//     {
//          $datos='';
//      }
      return $datos;
   }
   // ESPECIAL: Datos de acceso a la opcion actual  
   public function getPermisosAcceso($lin)
   {
      $t = new LogFunc($this->adapter);
      $dt = $t->getDatLog();      

      if ($dt['admin']==1)
      {
         return 1; 
      }
      else // Usuario no administrador
      {
         $con = "select count(a.id) as num 
                 from c_mu3 a 
                   inner join c_roles_o b on b.idM3 = a.id 
                   inner join c_roles c on c.id = b.idRol 
                   inner join users d on d.idRol = c.id 
                   where d.id=".$dt['idUsu']." 
                   and concat(a.modelo , '/' , a.controlador, '/' ,  a.vista)='$lin'";
         $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
         $datos = $result->current();
         return $datos['num'];                           
      }
   }   
   // ESPECIAL: Enviar correo
   public function sendMail($htmlBody, $textBody, $subject, $from, $to)
   {
       ini_set('max_execution_time', 300); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
       $message = new Message();
       $message->addTo('wilsonmet8@gmail.com')
               ->addFrom('wilsonmet8@gmail.com')
               ->setSubject('Nissi web Invitación capacitación ');
    
        // Setup SMTP transport using LOGIN authentication
        $transport = new SmtpTransport();
      $options   = new SmtpOptions(array(
    'name' => 'localhost.localdomain',
    'host' => 'localhost',
    'port' => 25,
));
//        $options   = new SmtpOptions(array(
  //           'host'              => 'smtp.gmail.com',
    //         'connection_class'  => 'login',
      //       'connection_config' => array(
        //     'ssl'       => 'ssl', // tls
          //   'username' => 'wilsonmet8@gmail.com',
        // 'password' => 'junior19247'
       // ),
      //  'port' => 587,
     //  ));
 // 587 
       $html = new MimePart('<strong>Empresa de prueba<strong><br /> '
               . 'Ref: Invitación capacitación <hr /> '
               . 'Sr(a) DIANA ORTEGA FLEREZ <br /><br /><br /> '
               . 'Le hacemos participe de la capacitacion proxima a dictarse en nuestras instalaciones.<hr /><br /> '
               . 'Area : Informatica <br /> '
               . 'Tipo : Conferencia <br />  '
               . 'Tematica : Tecnologías de la información <hr />'
               . '<br /><br />'
               . '');
       $html->type = "text/html";
 
       $body = new MimeMessage();
       $body->addPart($html);
 
       $message->setBody($body);
 
       $transport->setOptions($options);
       $transport->send($message);
    }   
                
    // CONSULTAS FIJAS *-----------------------------------------------****

   // Lista de roles
   public function getRoles($con)
   {
      $result=$this->adapter->query("select * from c_roles where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Consulta de mnues 
   public function getMenuRoles($id, $idRol)
   {
      $result=$this->adapter->query("select a.id, a.nombre, case when b.idM3 is null then 0 else b.id end as idM3,
                                         case when b.nuevo is null then 0 else b.nuevo end as nuevo,
                                         case when b.modificar is null then 0 else b.modificar end as modificar,
                                         case when b.eliminar is null then 0 else b.eliminar end as eliminar, 
                                         case when b.aprobar is null then 0 else b.aprobar end as aprobar, 
                                         case when b.vista is null then 0 else b.vista end as vista, 
                            c.idM1 , a.idM2, d.idM  , case when c.grupoNom > 0
                                  then b.idGrupNom else -9 end as idGrup # Manejo de grupo de nomina   
                                         from c_mu3 a
                                         inner join c_mu2 c on c.id = a.idM2 
                                         inner join c_mu1 d on d.id = c.idM1 
                                         left join c_roles_o b on b.idM3 = a.id and b.idRol = ".$idRol." 
                                         where  a.idM2 = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              
   // Rol usuario
   public function getRolUsu($usu)
   {
      $result=$this->adapter->query("select * 
        from users where usr_name='".$usu."'" ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                                             
   // Permisos especiales por usuario 
   public function getUsuEspe($id)
   {
      $result=$this->adapter->query("select * 
        from users where id=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                                                
   // Listado de aspectos de cargos
   public function getAsp2($idNcar, $idCar )
   {
      $result=$this->adapter->query("select b.*,( select count(a.id)
           from t_cargos_a a 
                    where a.idAsp = b.id and a.idCar = ".$idCar." and a.texto!='' ) as items 
                                 from t_asp_cargo b where b.idNasp = ".$idNcar ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }   
   // Listado de aspectos de cargos
   public function getAsp($con)
   {
      $result=$this->adapter->query("select * from t_asp_cargo $con order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }       
   // Niveles de aspectos o nivel de cargos   
   public function getNasp()
   {
      //$result=$this->adapter->query("select * from t_nivelasp order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $result=$this->adapter->query("select * from t_nivel_cargo order by nombre ",Adapter::QUERY_MODE_EXECUTE);      
      $datos=$result->toArray();
      return $datos;
    }

//---------------------------------------------------------------
// CONSUTAS PARA ETAPAS DE CONTRATACION -------------------------
//---------------------------------------------------------------

   // Listado de documentos aprobados para promocion de cargos
   public function getSolprom($con, $verReq, $idUsu)
   {
      $conVer = ' and a.idUsu = '.$idUsu;
      if ($verReq==1) // Puede ver todas las requisiciones de personal 
          $conVer = ' ';

      $result=$this->adapter->query("select a.*, b.nombre as nomCar,h.nombre as nomNivel, 
                                            c.nombre as nomSed, d.nombre as nomCcos , 
                                            case when b.id is null then 0 else b.id end as idCheq,
                                            ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id ) as numAsp,
                                            lower( concat( case when g.nombre is null then '' else g.nombre end , '', 
                                case when g.apellido is null then '' else g.apellido end , '' ) ) as usuario  ,
                                       (  select sum( case when aa.empleado>0 then 1 else 0 end ) from t_lista_cheq aa where aa.idSol = a.id ) as numCont, g.nombre, g.apellido, a.justificacion as justificacion , a.comen as comen,
                                        lower( concat( case when j.nombre is null then '' else j.nombre end , '', 
                                case when j.apellido is null then '' else j.apellido end, '' ) ) as nomUsuA,  
                                        lower( concat( case when l.nombre is null then '' else l.nombre end , '', 
                                case when l.apellido is null then '' else l.apellido end, '' ) ) as nomUsuA1 ,
        # Datos de procesos
       ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id and aa.contratado = 0 and aa.empleado =  0 ) as pendienteContra,
       ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id and aa.contratado = 1 and aa.empleado =  0 ) as pendienteRegistro,       
       ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id and aa.contratado = 1 and aa.empleado =  1 and aa.estado != 3 ) as pendienteCierre ,
       concat( m.CedEmp , ' - ', m.nombre, ' ', m.apellido ) as promocion 
                                       from t_sol_prom a 
                                            inner join t_cargos b on b.id=a.idCar 
                                            inner join t_sedes c on c.id=a.idSed
                                            inner join n_cencostos d on d.id = a.idCcos 
                                            inner join users f on f.id = a.idUsu 
                                            left join t_nivel_req h on h.id = a.idNivel 
                                            left join a_empleados g on g.id = f.idEmp                                             
                                            left join users i on i.id = a.idUsuA 
                                            left join a_empleados j on j.id = i.idEmp                                             
                                            left join users k on k.id = a.idUsuA1 
                                            left join a_empleados l on l.id = k.idEmp                                
                                            left join a_empleados m on m.id = a.idEmp 
                                        where ".$con." ".$conVer."  
                                            group by a.id     
                                            order by a.id desc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                        

   // Listado de documentos aprobados para contratacion de cargos
   public function getSolcon($con, $verReq, $idUsu)
   {
      $conVer = ' and a.idUsu = '.$idUsu;
      if ($verReq==1) // Puede ver todas las requisiciones de personal 
          $conVer = ' ';

      $result=$this->adapter->query("select a.*, b.nombre as nomCar,h.nombre as nomNivel, 
                                            c.nombre as nomSed, d.nombre as nomCcos , 
                                            case when b.id is null then 0 else b.id end as idCheq,
                                            ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id ) as numAsp,
                                            lower( concat( case when g.nombre is null then '' else g.nombre end , '', 
                                case when g.apellido is null then '' else g.apellido end , '' ) ) as usuario  ,
                                       (  select sum( case when aa.empleado>0 then 1 else 0 end ) from t_lista_cheq aa where aa.idSol = a.id ) as numCont,
                                  (  select sum( case when aa.estado=1 and aa.empleado = 0 then 1 else 0 end ) from t_lista_cheq aa where aa.idSol = a.id ) as numApro,
                                        g.nombre, g.apellido, a.justificacion as justificacion , a.comen as comen,
                                        lower( concat( case when j.nombre is null then '' else j.nombre end , '', 
                                case when j.apellido is null then '' else j.apellido end, '' ) ) as nomUsuA,  
                                        lower( concat( case when l.nombre is null then '' else l.nombre end , '', 
                                case when l.apellido is null then '' else l.apellido end, '' ) ) as nomUsuA1 ,
        # Datos de procesos
       ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id and aa.contratado = 0 and aa.empleado =  0 ) as pendienteContra,
       ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id and aa.contratado = 1 and aa.empleado =  0 ) as pendienteRegistro,       
       ( select count(aa.id) from t_lista_cheq aa where aa.idSol = a.id and aa.contratado = 1 and aa.empleado =  1 and aa.estado != 3 ) as pendienteCierre 
                                       from t_sol_con a 
                                            inner join t_cargos b on b.id=a.idCar 
                                            inner join t_sedes c on c.id=a.idSed
                                            inner join n_cencostos d on d.id = a.idCcos 
                                            inner join users f on f.id = a.idUsu 
                                            left join t_nivel_req h on h.id = a.idNivel 
                                            left join a_empleados g on g.id = f.idEmp                                             
                                            left join users i on i.id = a.idUsuA 
                                            left join a_empleados j on j.id = i.idEmp                                             
                                            left join users k on k.id = a.idUsuA1 
                                            left join a_empleados l on l.id = k.idEmp                                
                                        where ".$con." ".$conVer."  
                                            group by a.id     
                                            order by a.id desc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                        
   // Listado de documentos aprobados para contratacion de cargos
   public function getSolconG($con)
   {
      $result=$this->adapter->query("select a.*, concat(b.nombre,' (', b.deno,')' ) as nomCar, c.nombre as nomSed, d.nombre as nomCcos  
                                            from t_sol_con a 
                                            inner join t_cargos b on b.id=a.idCar 
                                            inner join t_sedes c on c.id=b.idSed
                                            inner join n_cencostos d on d.id = a.idCcos 
                                            where ".$con."  
                                            order by a.id desc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->Current();
      return $datos;
    }                                    
   // Lista de chequeo y solictud contratacion
   public function getSolCheq($id)
   {
      $result=$this->adapter->query("select b.id, b.cedula, e.nombre, e.apellido, a.idSol,
                     concat( 'C.C.',b.cedula, ' - ', b.nombre1, ' ', b.nombre2, 
                b.apellido1, ' ', b.apellido2 ) as aspirante, ltrim(e.email) as email, 
               f.nombre as nomCar, a.fechaA as fecApr, g.nombre as nomCcos,
               i.nombre as nomApr, i.apellido as apeApr, j.nombre as nomPues , e.DirEmp as direccion, e.TelEmp, b.email as emailEmp 

                                            from t_lista_cheq a 
                                               inner join t_hoja_vida b on b.id = a.idHoj 
                                               inner join t_sol_con c on c.id = a.idSol
                                               inner join users d on d.id = c.idUsu 
                                               inner join a_empleados e on e.id = d.idEmp 
                                               inner join t_cargos f on f.id = c.idCar 
                                               inner join n_cencostos g on g.id = c.idCcos 
                                               left join users h on h.id = a.idUsuA 
                                               left join a_empleados i on i.id = h.idEmp 
                                               left join n_proyectos_p j on j.id = c.idPues 
                                              where a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->Current();
      return $datos;    
   }
   // Etapas de contratacion para lista de chequeo  
   public function getLcheq($id)
   {
      $id  = (int) $id; 
      $result=$this->adapter->query("select a.id, a.nombre
         from t_etapas_con a 
       left join t_nivel_cargo_o b on a.id=b.idEtapa and b.idNcar=$id
         where b.id is null order by a.nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    
   // Etapas de contratacion en tipo de contratacion
   public function getLcheqTcon($id)
   {
      $id  = (int) $id;
      $result=$this->adapter->query("select a.id, a.nombre, b.orden  
                                       from t_etapas_con a 
                                        inner join t_nivel_cargo_o b on a.id=b.idEtapa where b.idNcar=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
    }   

   // Datos cabecera proceso de contratacion lista de chequeo 
   public function getDatPos($id)
   {
      $id  = (int) $id; 
      $result=$this->adapter->query("select a.*,b.fecDoc,d.nombre as nomCar, e.nombre as nomSede
                                            ,c.cedula, concat(c.nombre1, ' ', c.nombre2) as nombre, 
                              concat(c.apellido1, ' ', c.apellido2) as apellido, a.contratado, a.empleado, b.vacantes    
                                            from t_lista_cheq a 
                                              inner join t_sol_con b on b.id = a.idSol
                                              inner join t_hoja_vida c on c.id=a.idHoj           
                                              inner join t_cargos d on d.id=b.idCar  
                                              inner join t_sedes e on e.id=d.idSed
                                            where a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }        
   // Responsable de procesos de contratacion
   public function getRespContra($con)
   {
      $result=$this->adapter->query("select a.idEtacon , b.nombre as nomCar , c.nombre as nomEmp, c.apellido as apeEmp, d.tipo, c.email     
                                  from t_etapas_con_c a 
                                     inner join t_cargos b on b.id = a.idCar
                                     inner join a_empleados c on c.idCar = b.id 
                                     inner join t_etapas_con d on d.id = a.idEtacon 
                                     where c.estado = 0 ".$con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    

   // Insertar items de lista de chequeo 
   public function getInsertCheque($con, $id)
   {

      $idCheq = $this->modGeneralId("insert into t_lista_cheq_d (idCheq, idEtaI, etapa ) (
                  select a.id, g.id, f.tipo 
                  from t_lista_cheq a 
                    inner join t_sol_con b on a.idSol=b.id 
                    inner join t_cargos c on b.idCar=c.id
                    inner join t_nivel_cargo d on d.id=c.idCheq 
                    inner join t_nivel_cargo_o e on e.idNcar=d.id
                    inner join t_etapas_con f on e.idEtapa=f.id
                    inner join t_etapas_con_i g on g.idEtacon=f.id 
                  WHERE 
                  not exists (SELECT null from t_lista_cheq_d 
                      where a.idHoj=idHoj and a.id=idCheq and g.id=idEtaI and f.tipo=etapa ) and ".$con." )");
    // MIGRAR DE LA SELECCION CONTINUA LOS ITEMS  
      $sw=0;
      if ($sw==1)
      {
    $result=$this->adapter->query("update t_lista_cheq aa 
             inner join t_lista_cheq_d bb on bb.idCheq = aa.id 
           set bb.descripcion = 
               (  select d.descripcion 
                  from t_lista_cheq a 
                    inner join t_hoja_vida b on b.id = a.idHoj 
                    inner join t_lista_sel c on c.idHoj = b.id 
                    inner join t_lista_sel_d d on d.idCheq = c.id 
                  where a.id = aa.id and d.idEtaI = bb.idEtaI  ),
            bb.estado = ( select d.estado 
                  from t_lista_cheq a 
                    inner join t_hoja_vida b on b.id = a.idHoj 
                    inner join t_lista_sel c on c.idHoj = b.id 
                    inner join t_lista_sel_d d on d.idCheq = c.id 
                  where a.id = aa.id and d.idEtaI = bb.idEtaI  ),
            bb.idRefSel = ( select d.estado   
                  from t_lista_cheq a 
                    inner join t_hoja_vida b on b.id = a.idHoj 
                    inner join t_lista_sel c on c.idHoj = b.id 
                    inner join t_lista_sel_d d on d.idCheq = c.id 
                  where a.id = aa.id and d.idEtaI = bb.idEtaI  )                  
         where aa.id = ".$id);
      }
   }    
   // Consultar lista de chqueo en proceso
   public function getProcContra($id)
   {
      $result=$this->adapter->query("Select a.idSol, a.estado, a.contratado, b.idCar, count(c.CedEmp) as emp, b.idCiu, a.empleado  
                                  from t_lista_cheq a
                                    inner join t_sol_con b on b.id=a.idSol 
                                    left join a_empleados c on c.IdHoj=a.idHoj and c.estado=0 
                                  where a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
    }    
   // Datos de solictud de contratacion
   public function getSolContra($id)
   {
      $result=$this->adapter->query("select a.vacantes, sum(b.contratado) as contra, a.fechaIni, 
                           a.salario, c.id, c.tipo, c.meses, c.nombre as nomTcon, a.idEsal, 
                           case when a.salario > 0 then a.salario else d.salario end as salario     
                             from t_sol_con a 
                                 inner join t_lista_cheq b on b.idSol = a.id 
                                 inner join a_tipcon c on c.id = a.idTcon 
                                 left join n_salarios d on d.id = a.idEsal  
                              where a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      //b.contratado>0 and
      $datos=$result->current();
      return $datos;
    }    

   // Lista de chequeo referencias laborales 
   public function getCheqlaboral($id)
   {
      $result=$this->adapter->query("select b.* from t_lista_cheq_d a 
                                            inner join t_lista_cheq_hl b on b.idDcheq = a.id   
                                            where a.idCheq =".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    
   // Lista de chequeo referencias laborales 
   public function getCheqAspCargos($id)
   {
      $result=$this->adapter->query("select b.* from t_lista_cheq_d a 
                                            inner join t_lista_cheq_vc b on b.idDcheq = a.id   
                                            where a.idCheq =".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    
   // Lista de niveles de solictud de contratacion 
   public function getNivelRequerimiento($id)
   {
      $result=$this->adapter->query("select * from t_nivel_req " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    
   //  SELECCION CONTINUA -----------------------------------------------
   // Listado de empleados para seleccion continua
   public function getSelcon($con)
   {
      $result=$this->adapter->query("select a.*, concat(b.nombre,' (', b.deno,')' ) as nomCar,
                                            c.nombre as nomSed, d.nombre as nomCcos , 
                                            case when b.id is null then 0 else b.id end as idCheq,count(b.id) as numAsp    
                                          from t_sol_con a 
                                            inner join t_cargos b on b.id=a.idCar 
                                            inner join t_sedes c on c.id=b.idSed
                                            inner join n_cencostos d on d.id = a.idCcos 
                                            left join t_lista_cheq e on e.idSol = a.id 
                                            where ".$con." 
                                            group by a.id     
                                            order by a.id desc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                        
   // Insertar items de lista de chequeo procso de seleccion continua
   public function getInsertChequeContinua($con)
   {
      $result=$this->adapter->query("insert into t_lista_sel_d (idCheq, idEtaI, etapa ) 
        (select a.id, g.id, f.tipo 
                  from t_lista_sel a 
                    inner join t_hoja_vida_c b on b.idHoj = a.idHoj and b.idCar = a.idCar  
                    inner join t_cargos c on b.idCar=c.id
                    inner join t_nivel_cargo d on d.id=c.idCheq 
                    inner join t_nivel_cargo_o e on e.idNcar=d.id
                    inner join t_etapas_con f on e.idEtapa=f.id
                    inner join t_etapas_con_i g on g.idEtacon=f.id 
                  WHERE 
                  not exists (SELECT null from t_lista_sel_d h 
                      where a.id=h.idCheq and g.id=h.idEtaI and f.tipo=h.etapa ) and ".$con." )" ,Adapter::QUERY_MODE_EXECUTE);    
    }    
   // Etapas de la lista de chqueo para seleccion continua
   public function getEtcehqSelCon($con)
   {
      $result=$this->adapter->query("select h.id as idDlchq, a.idHoj, e.id,e.idNcar,e.idEtapa,
        f.nombre,g.id as idItem, g.nombre as nomItem, g.idForm, f.id as idEtcon,  
        i.nombre as nomForm, h.id , h.descripcion , h.estado, i.tipo, a.id as idCheq, g.orden, f.tipoCal , g.calMin, g.calMax, f.tipoTot ,
 #  Totales
 case f.tipoTot 
  when 0 then # ------------------- Promedio (1)  
    ( select count(aa.estado) 
       from t_lista_sel_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id and aa.estado = 1 
              order by aa.id desc limit 1 )  
  when 1 then # ------------------- Sumatoria (2)          
    ( select round( sum(aa.estado) ,2 )
       from t_lista_sel_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id order by aa.id desc limit 1 )  
  when 2 then # ------------------- Promedio (2)          
    ( select round( sum(aa.estado) / count(aa.id) ,2 ) 
       from t_lista_sel_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id order by aa.id desc limit 1 ) 
end as puntaje                        
      from t_lista_sel a 
        inner join t_hoja_vida_c b on b.idHoj = a.idHoj and b.idCar = a.idCar 
        inner join t_cargos c on b.idCar=c.id
        inner join t_nivel_cargo d on d.id=c.idCheq 
        inner join t_nivel_cargo_o e on e.idNcar=d.id
        inner join t_etapas_con f on e.idEtapa=f.id
        inner join t_etapas_con_i g on g.idEtacon=f.id
        left join t_lista_sel_d h on h.idEtaI=g.id and h.idCheq=a.id 
        left join t_form i on g.idForm=i.id 
      where g.estado = 0 and $con 
         group by h.id  
       order by e.orden, g.orden",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                       
   // Datos cabecera proceso de contratacion
   public function getDatPosSelCon($id)
   {
      $id  = (int) $id; 
      $result=$this->adapter->query("select distinct a.*,a.fecDoc,d.nombre as nomCar, e.nombre as nomSede
                                            ,f.cedula, concat(f.nombre1,' ',f.nombre2) as nombre, 
                                            concat( f.apellido1, ' ',f.apellido2 ) as apellido ,

     # -------------------------------------------------------------------------- Calificacion                                                    
( select sum( e.estado ) 
          from t_lista_sel d 
          inner join t_lista_sel_d e on e.idCheq = d.id   
            where d.idHoj = c.idHoj and d.idCar = c.idCar ) as numCal, h.calMin , h.calMax   

                                        from t_lista_sel a 
                                              inner join t_hoja_vida_c c on c.idHoj=a.idHoj and c.idCar = a.idCar           
                                              inner join t_cargos d on d.id=c.idCar  
                                              inner join t_sedes e on e.id=d.idSed
                                              inner join t_hoja_vida f on f.id = c.idHoj  
                                              left join t_nivel_cargo h on h.id = d.idCheq  
                                        left join t_lista_sel_d g on g.idCheq=a.id 
                                            where a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }        
   // Adjuntos lista de chequeo por seleccion 
   public function getAdjCheqSelCon($id)
   {
      $result=$this->adapter->query("Select a.* 
                                        from t_lista_sel_d_a a 
                                           inner join t_lista_sel b on b.id = a.idCheq 
                               where b.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                               
   // Listado de etapas items, formularios e items
   public function getIformISelCon($con, $idCheq)
   {
      $result=$this->adapter->query("select a.*,b.nombre as nomform, c.idForm, c.id as idIform, 
                                     c.nombre as nomIform, c.lista, c.tipo, c.ubi, d.lista as lisForm, 
                                       d.texto as texForm, d.casilla as casForm , d.fecha as fechaF, b.tipoCal, c.calMin, c.calMax, d.estado               
                              from t_etapas_con_i a 
                                 inner join t_form b on a.idForm=b.id
                                 inner join t_form_i c on c.idForm=b.id 
                                 left join t_lista_sel_f d on d.idIform=c.id 
                                     and d.idDcheq=".$idCheq." 
                              where c.estado = 0 and a.id>0".$con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                          

   // Insertar items de lista de chequeo procso de desvinculacion
   public function getInsertChequeDesvin($con)
   {
      $result=$this->adapter->query("insert into t_desvinculacion_e_i (idDoc, idEdoc, idEtaI, etapa ) 
        (select a.idDoc, a.id, g.id, f.tipo   
                  from t_desvinculacion_e a 
                    inner join a_empleados b on b.id = a.idEmp 
                    inner join t_cargos c on c.id = b.idCar 
                    inner join t_nivel_cargo d on d.id=c.idCheq 
                    inner join t_nivel_cargo_o e on e.idNcar=d.id
                    inner join t_etapas_con f on e.idEtapa=f.id
                    inner join t_etapas_con_i g on g.idEtacon=f.id 
                  WHERE not exists (SELECT null from t_desvinculacion_e_i h 
                      where h.idDoc = a.idDoc and h.idEdoc = a.id and h.idEtaI = g.id and f.tipo=h.etapa ) and f.tipo = 5 ".$con.")" ,Adapter::QUERY_MODE_EXECUTE);    
    }    
   // Etapas de la lista de chqueo para desvinculacion 
   public function getEtcehqDesv($id)
   {
      $result=$this->adapter->query("select h.id as idDlchq, a.idEmp, e.id,e.idNcar,e.idEtapa,
        f.nombre,g.id as idItem, g.nombre as nomItem, g.idForm, f.id as idEtcon,  
        i.nombre as nomForm, h.id , h.descripcion , h.estado, i.tipo, a.id as idCheq, g.orden, f.tipoCal , g.calMin, g.calMax, f.tipoTot ,
 #  Totales
0 as puntaje                        
      from t_desvinculacion_e a 
        inner join t_desvinculacion_e_i aa on aa.idEdoc = a.id 
        inner join a_empleados b on b.id = a.idEmp 
        inner join t_cargos c on b.idCar=c.id
        inner join t_nivel_cargo d on d.id=c.idCheq 
        inner join t_nivel_cargo_o e on e.idNcar=d.id
        inner join t_etapas_con f on e.idEtapa=f.id
        inner join t_etapas_con_i g on g.idEtacon=f.id
        left join t_desvinculacion_e_i h on h.idEtaI = g.id and h.idEdoc = a.id 
        left join t_form i on g.idForm=i.id 
      where f.tipo = 5 and  a.id = ".$id." order by e.orden, g.orden",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }        
   // Datos cabecera proceso de desvinculacion
   public function getDatPosDesvi($id)
   {
      $id  = (int) $id; 
      $result=$this->adapter->query("select distinct a.*,f.fecDoc, d.nombre as nomCar, e.nombre as nomSede
                                              , c.CedEmp as cedula ,  c.nombre, c.apellido 
                                            from t_desvinculacion_e a 
                                              inner join a_empleados c on c.id = a.idEmp          
                                              inner join t_cargos d on d.id=c.idCar  
                                              left join t_sedes e on e.id=c.idSed
                                              inner join t_desvinculacion f on f.id = a.idDoc 
                                            where a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }     
   // Dotaciones en seleccion 
   public function getAspiDot($idHoj, $id)
   {
      $result=$this->adapter->query("select a.id as idGdot, 4 ,c.id, c.nombre as nomGdot,
  e.nombre as nomDota , case when g.valor is null then 0 else g.valor end as valor , e.id as idDot,  gg.valor as valorDot, h.nombre as nomLin, 
  ( select aa.id from a_empleados aa where aa.CedEmp = f.cedula ) as idEmp     
from t_sol_con a
  inner join t_cargos_d b on b.idCar = a.idCar 
  inner join t_grup_dota c on c.id = b.idGdot 
  inner join t_grup_dota_m d on d.idGdot = c.id 
  inner join t_mat_dota e on e.id = d.idDot 
    inner join t_lineas_dot h on h.id = e.idLin 
  inner join t_hoja_vida f on (f.SexEmp = e.tipo or e.tipo = 3 ) and f.id = ".$idHoj."  
    left join t_sol_con_dota g on g.idSol = a.id and g.idGdot = c.id 
    left join t_sol_con_dota_m gg on gg.idSol = a.id and gg.idGdot = c.id and gg.idDot = e.id 
where a.id = ".$id." order by c.nombre,h.nombre ,e.nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                          
//---------------------------------------------------------------
// FIN CONSUTAS PARA ETAPAS DE CONTRATACION -------------------------
//---------------------------------------------------------------

   // Listado de cargos
   public function getCargos()
   {
      $result=$this->adapter->query("select * , nombre as nomFiltro 
                                       from t_cargos
                                          order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }            
   // Listado niveles de cargos (Confia esta es la tabla que guarda los niveles de cargos)
   public function getNcargos()
   {
      $result=$this->adapter->query("select * from t_nivelasp order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                    
   // Listado de departamentos
   public function getDepar()
   {
      $result=$this->adapter->query("select * from t_departamentos order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                    

//---------------------------------------------------------------
// CONSUTAS PARA DOTACIONES -------------------------
//---------------------------------------------------------------

   // Listado grupos de dotaciones 
   public function getGrupDot($id)
   {
      $result=$this->adapter->query("select * from t_grup_dota".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                    

   //FIltro para tipos de documentos.      

   public function getTipDocFill()
   {
       $result=$this->adapter->query("select *, nombre as nomFiltro 
                                       from t_tip_doc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    

   // Listado de grupo de dotaciones
   public function getGdot()
   {
      $result=$this->adapter->query("select * from t_grup_dota order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    

   // Empleados con dotacion para entregar en documento
   public function getDatGdot($id, $idCcos )
   {
      $con = '';
      if ($idCcos>0) 
         $con = ' and c.idCcos='.$idCcos;

      $result=$this->adapter->query("select b.id as idDcat, c.id as idEmp, c.id as idEmp , c.CedEmp, c.nombre, c.apellido, d.id as idCar, d.nombre as nomCar, 0 as valor, c.SexEmp, '' as nomGdot, 0 as idGdot     
                               from t_dota a
                                 inner join t_dota_e b on b.idDot = a.id 
                                 inner join a_empleados c on c.id = b.idEmp 
                                 inner join t_cargos d on d.id = c.idCar 
                               where a.id=".$id."  order by c.id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
   // Cabecera de dotaciones
   public function getDocDotItem($id)
   {
      $result=$this->adapter->query("select * from t_dota_i a where a.idDot=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }       
   // Items documento entrega de dotacion 
   public function getDocDotCab($id)
   {
      $result=$this->adapter->query("select a.*,  '' as nomGdot, a.idCcos, case when c.nombre is null then '' 
  else concat( '( Centro de costos : ',c.nombre,')' ) end as nomCcos, a.idCcos, 
        case when a.tipo = 0 then '' else 'Reposición' end as tipoDota     
                                       from t_dota a 
                             
                                         left join n_cencostos c on c.id = a.idCcos 
                                     where a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }    
   // Dotaciones dentro del grupo de dotaciones en documento de dotacion 
   public function getGrupDotI($id)
   {
      $result=$this->adapter->query("select a.*,c.idEmp , d.id as idMdot, b.nombre as nomGdot, b.id as idGdot,
                         lower(d.nombre) as nomDot , d.uniAno as unidades, d.tipo, d.imagen , d.foto    
                           from t_dota a 
                         inner join t_dota_d c on c.idDot = a.id  
                         inner join t_mat_dota d on d.id = c.idMdot 
                         inner join t_grup_dota b on b.id = c.idGdot                           
                     where a.id = ".$id." 
                      order by c.idEmp,c.idGdot, d.id  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }         
   // Unidades entregadas en documento de dotaciones
   public function getDotUni($id)
   {
      $result=$this->adapter->query("select * from t_dota_d a where a.idDot =".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;

   }            
//---------------------------------------------------------------
// FIN CONSUTAS PARA DOTACIONES -------------------------
//---------------------------------------------------------------

//---------------------------------------------------------------
//---------------- HOJAS DE VIDA -------------------------
//---------------------------------------------------------------
   // Listado de vacantes para cargos
   public function getVaca($con, $selCon, $id)
   {
      $cam = '';
      if ( $selCon == 1 )// Manejo de seleccion continua 
           $cam = ' and e.estado = 1';

      $result=$this->adapter->query("select distinct a.id,a.cedula,
         concat( a.nombre1, ' ',a.nombre2 ) as nombre,
         concat( a.apellido1, ' ', a.apellido2) as apellido,
          b.idCar, a.estado, a.fecReg as fec_reg, c.nombre as nomCar, a.estado as estHoj,  
          
       # Solictud en lista de chequeo ---------------------------------------------------------   
          case when d.idSol is null then 0 else d.idSol end as idSol, 
       # Seleccion continua -----------------------------------------------------------------            
(
  select sum(aa.estado) as califica 
     from t_lista_sel_d aa 
       inner join t_lista_sel bb on bb.id = aa.idCheq
      where bb.idHoj = a.id and bb.idCar = b.idCar  
 ) as numCal,           
(
  select count(aa.estado)*5 as total 
     from t_lista_sel_d aa 
       inner join t_lista_sel bb on bb.id = aa.idCheq
      where bb.idHoj = a.id and bb.idCar = b.idCar               
 ) as total ,
case when (
  select bb.id 
     from t_lista_sel bb 
      where bb.idHoj = a.id and bb.idCar = b.idCar order by id desc limit 1        
  ) is null then 
     -9  else
(
  select bb.id 
     from t_lista_sel bb 
      where bb.idHoj = a.id and bb.idCar = b.idCar order by id desc limit 1 
  ) end as idSel,  
  (
  select sum(bb.estado) 
     from t_lista_sel bb 
      where bb.idHoj = a.id and bb.idCar = b.idCar        
 ) as estadoSel, f.calMin , f.calMax , d.anulada , c.id as idCar,         
     d.idPues, ( select aa.nombre from n_proyectos_p aa where aa.id = d.idPues ) as nomPues,
       ( case when d.idSol is null then '.' else ' ' end ) as postulado,
     gg.nombre as nomPues,  
     ( select count(aa.id) from t_lista_cheq aa where aa.idSol = d.idSol and aa.idHoj = a.id ) as numProc  

       from t_hoja_vida a 
           inner join t_hoja_vida_c b on a.id = b.idHoj 
           inner join t_cargos c on c.id = b.idCar 
           left join t_nivel_cargo f on f.id = c.idCheq # Lista de 
           left join t_lista_cheq d on d.idHoj = a.id and d.idSol = ".$id." # Solo listas activas 
           left join t_lista_sel e on e.idHoj = a.id and e.estado = 1 and e.idCar = c.id     
           left join t_sol_con ff on ff.id = d.idSol  
           left join n_proyectos_p gg on gg.id = ff.idPues          
       where a.id > 0 ".$con." 
order by ( case when d.idSol is null then 9 else 1 end ) 
         " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                            
    
   // Cargos hoja de vida
   public function getCarHoj($con)
   {
      $result=$this->adapter->query("select * from t_hoja_vida_c where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                              
   // Criterios educativos cargos
   public function getCriHoj($con, $hoja)
   {
      if ( $hoja == 2 )
         $result=$this->adapter->query("select * from t_hoja_vida_rt where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);

      if ( $hoja == 1 )
         $result=$this->adapter->query("select * from t_hoja_vida_re where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);

      $datos=$result->toArray();
      return $datos;
   }                                 
   // Sectores 
   public function getSectores($con)
   {
      $result=$this->adapter->query("select *
                                          from t_sector_laboral 
                                            where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                     
   // Datos seleccion hoja de vida  
   public function getSelHoj($idHoj, $idCar)
   {
      $result=$this->adapter->query("select count( c.id ) as num  
                                    from t_hoja_vida_c a 
                                        inner join t_lista_cheq b on b.idHoj = a.idHoj  
                                        inner join t_sol_con c on c.id = b.idSol and c.idCar = a.idCar 
                                 where a.idhoj = ".$idHoj." and c.idCar = ".$idCar ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                
   // Datos seleccion hoja de vida  
   public function getSelConHoj($idHoj, $idCar)
   {
      $result=$this->adapter->query("select count( b.id ) as num 
                                    from t_hoja_vida_c a 
                                        inner join t_lista_sel b on b.idHoj = a.idHoj and b.idCar = a.idCar  
                                where a.idhoj = ".$idHoj." and b.idCar = ".$idCar ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                


//---------------------------------------------------------------
//---------------- FIN HOJAS DE VIDA -------------------------
//---------------------------------------------------------------                

   // Listado de aspirantes cabecera lista de chequeo 
   public function getAspi($con)
   {
      $result=$this->adapter->query("select distinct a.*,b.fecDoc, b.fecApr, d.nombre as nomCar, d.plazas, ( select count( aa.id ) 
                  from t_cargos aa 
                    inner join a_empleados bb on bb.idCar = aa.id 
                  where bb.estado = 0 and aa.id = d.id ) as plazasDispo, 
    e.nombre as nomSede, j.id as idEmp 
          ,c.cedula,  c.nombre1, c.nombre2, c.apellido1, c.apellido2, a.contratado, a.empleado, b.vacantes,
       case when g.idEmp > 0 then    
          lower( concat( h.nombre, ' ',h.apellido,'(', g.usuario, ')') ) 
       else 
          g.usuario end  
        as usuario, a.idUsuA, i.nombre as nomCheq, i.calMin, i.calMax, 
  # -------------------------------------------------------------------------- Calificacion                                                    
( select sum( ee.estado ) 
          from t_lista_sel dd 
          inner join t_lista_sel_d ee on ee.idCheq = dd.id   
            where dd.idHoj = c.id and dd.idCar = d.id ) as numCal , j.id as tiempo ,
  a.idUsuC , k.usuario as usuarioC,             
# Limites de seleccion y contratacion ---------------------------            
  b.diasSel as diasSelL, b.diasCon as diasConL,  
  (date_add( b.fecApr , interval b.diasSel day) ) as fecLimSel,           
  (date_add( (date_add( b.fecApr , interval b.diasSel day) ) , interval b.diasCon day) ) as fecLimCon,  
  datediff( now(), b.fecApr ) + 1 as diasTran, 
  
# Dias transcurridos en contratacion ----------------------------  
  datediff(  a.fechaA, b.fecApr ) + 1 as diasSel, 
  datediff(  a.fechaContra, ( (date_add( (date_add( b.fecApr , interval b.diasSel day) ) , interval b.diasCon day) ) ) ) + 1 as diasCon ,
    datediff(  a.fechaContra, a.fechaA ) + 1 as diasConReal ,
    datediff(  a.fechaFproc, a.fechaA ) + 1 as diasFproc  ,
  a.fechaContra, a.fechaFproc, upper(l.nombre) as nomNivel, 
  (  select aa.fecha  
         from t_lista_sel_h aa 
             inner join t_lista_sel bb on bb.id = aa.idCheq 
            where bb.idHoj = a.idHoj and aa.estado = 0 order by aa.id desc limit 1) as fechaSelCon , a.anulada , concat( m.usuario , '-', case when n.nombre is null then ' ' else concat(n.nombre,' ',n.apellido) end  ) as nomAnula , 
( select aa.nombre from n_proyectos_p aa where aa.id = b.idPues ) as nomPues,
 ( select bb.nombre  
       from n_proyectos_p aa 
         inner join n_proyectos bb on bb.id = aa.idProy 
         inner join n_clientes cc on cc.id = bb.idCli 
           where aa.id = b.idPues ) as nomProy   
      from t_lista_cheq a           
          inner join t_sol_con b on b.id = a.idSol 
          inner join t_hoja_vida c on c.id=a.idHoj           
          inner join t_cargos d on d.id=b.idCar  
          inner join t_nivel_cargo i on i.id = d.idCheq           
          inner join t_sedes e on e.id=d.idSed
          inner join t_hoja_vida_c f on f.idHoj=c.id and f.idCar=b.idCar  
          left join users g on g.id = a.idUsuA 
          left join a_empleados h on h.id = g.idEmp           
          left join a_empleados j on j.CedEmp = c.cedula 
          left join users k on k.id = a.idUsuC  
          left join users m on m.id = a.idUsuAnula   
          left join a_empleados n on n.id = m.idEmp # Empleado que anulo   
          left join t_nivel_req l on l.id = b.idNivel 
          left join t_tip_doc p on p.idTcon = a.id # Ubicar tipo de contrato 
      where a.anulada=0 and $con group by a.id",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                    
   // Etapas de la lista de chqueo
   public function getEtcehq($con)
   {
      $result=$this->adapter->query("
select h.id as idDlchq, a.contratado, a.idHoj, a.empleado, e.id,e.idNcar,e.idEtapa,
        f.nombre,g.id as idItem, g.nombre as nomItem, g.idForm, f.id as idEtcon,  
      i.nombre as nomForm, h.id , h.descripcion , h.estado, i.tipo, a.id as idCheq, g.orden , f.tipoCal , g.calMin, g.calMax, f.tipoTot , 
 #  Totales
 case f.tipoTot 
  when 0 then # ------------------- Promedio (1)  
    ( select count(aa.estado) 
       from t_lista_cheq_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id and aa.estado = 1)  
  when 1 then # ------------------- Sumatoria (2)          
    ( select round( sum(aa.estado) ,2 )
       from t_lista_cheq_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id )  
  when 2 then # ------------------- Promedio (2)          
    ( select round( sum(aa.estado) / count(aa.id) ,2 ) 
       from t_lista_cheq_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id ) 
end as puntaje, f.contrata , h.id, c.id as idCar                                         
      from t_lista_cheq a 
        inner join t_sol_con b on a.idSol=b.id 
        inner join t_cargos c on b.idCar=c.id
        inner join t_nivel_cargo d on d.id=c.idCheq 
        inner join t_nivel_cargo_o e on e.idNcar=d.id
        inner join t_etapas_con f on e.idEtapa=f.id
        inner join t_etapas_con_i g on g.idEtacon=f.id 
        left join t_lista_cheq_d h on h.idEtaI=g.id and h.idCheq=a.id 
        left join t_form i on g.idForm=i.id 
      where g.estado = 0 and $con order by e.orden, g.orden",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                          

      // Etapas de la lista de chqueo por permisos 
   public function getEtcehq2($con)
   {
      $result=$this->adapter->query("
select h.id as idDlchq, a.contratado, a.idHoj, a.empleado, e.id,e.idNcar,e.idEtapa,
        f.nombre,g.id as idItem, g.nombre as nomItem, g.idForm, f.id as idEtcon,  
      i.nombre as nomForm, h.id , h.descripcion , h.estado, i.tipo, a.id as idCheq, g.orden , f.tipoCal , g.calMin, g.calMax, f.tipoTot , 
 #  Totales
 case f.tipoTot 
  when 0 then # ------------------- Promedio (1)  
    ( select count(aa.estado) 
       from t_lista_cheq_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id and aa.estado = 1)  
  when 1 then # ------------------- Sumatoria (2)          
    ( select round( sum(aa.estado) ,2 )
       from t_lista_cheq_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id )  
  when 2 then # ------------------- Promedio (2)          
    ( select round( sum(aa.estado) / count(aa.id) ,2 ) 
       from t_lista_cheq_d aa 
        inner join t_etapas_con_i bb on bb.id = aa.idEtaI 
           where aa.idCheq = a.id and bb.idEtacon = f.id ) 
end as puntaje, f.contrata , h.id, c.id as idCar                                         
      from t_lista_cheq a 
        inner join t_sol_con b on a.idSol=b.id 
        inner join t_cargos c on b.idCar=c.id
        inner join t_nivel_cargo d on d.id=c.idCheq 
        inner join t_nivel_cargo_o e on e.idNcar=d.id
        inner join t_etapas_con f on e.idEtapa=f.id
        inner join t_etapas_con_i g on g.idEtacon=f.id 
        inner join t_etapas_con_c aa on aa.idEtacon = f.id # Validar permiso a la etapa 
        inner join a_empleados bb on bb.idCar = aa.idCar 
        inner join users cc on cc.idEmp = bb.id         
        left join t_lista_cheq_d h on h.idEtaI=g.id and h.idCheq=a.id 
        left join t_form i on g.idForm=i.id 
      where g.estado = 0 and $con order by e.orden, g.orden",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                          
   // Etapas de la lista de chqueo sum total
   public function getEtcehqSt($con)
   {
      $result=$this->adapter->query("select a.id, count(b.estado) as estado from t_lista_cheq a 
                inner join t_lista_cheq_d b on b.idCheq=a.id 
                where $con group by a.id ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                          
   // Etapas de la lista de chqueo sum total calificados
   public function getEtcehqStc($con)
   {
      $result=$this->adapter->query("select a.id, count(b.estado) as estado from t_lista_cheq a 
                inner join t_lista_cheq_d b on b.idCheq=a.id 
                where $con group by a.id ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                          
    
   // Listado de formularios                                       
   public function getForm()
   {
      $result=$this->adapter->query("select * from t_form order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                               
   // Listado de etapas items y formularios
   public function getIform($con)
   {
      $result=$this->adapter->query("select a.*,b.nombre as nomform,
       case when c.id is null then '' else concat( 'CONTRA:',c.nombre ) end as nomTcon, 
 case when d.id is null then '' else concat( 'DOCUMENTO:',d.nombre ) end as nomTdoc   
                           from t_etapas_con_i a 
                               left join t_form b on a.idForm=b.id 
                               left join a_tipcon c on c.id = a.idTcon 
                               left join t_tip_doc d on d.id = a.idTdoc 
                               ".$con."
                                order by a.orden " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                   
   // Listado de etapas items, formularios e items
   public function getIformI($con, $idCheq)
   {
      $result=$this->adapter->query("select a.*,b.nombre as nomform, c.idForm, c.id as idIform, 
                                     c.nombre as nomIform, c.lista, c.tipo, c.ubi, d.lista as lisForm, 
               d.texto as texForm, d.casilla as casForm, d.fecha, d.valor      
                                     from t_etapas_con_i a 
                   inner join t_form b on a.idForm=b.id
                   inner join t_form_i c on c.idForm=b.id 
                   left join t_lista_cheq_f d on d.idIform=c.id 
                                     and d.idDcheq=".$idCheq." where a.id>0".$con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                       
   // Listado de aspectos de cargo en nivel de aspectos 
   public function getNaspN($con, $idC)
   {
      $result=$this->adapter->query("select a.*,b.nombre as nomAsp,                                     
                d.texto as texRes, d.lista as listRes, # datos guardados directamente en los items del aspecto
                                    d.a as aR, d.b as bR, d.c as cR, d.d as dR, d.e as eR, 
            c.a as aC, c.b as bC, c.c as cC, c.d as dC, c.e as eC,# Datos guardados en el cargo cuando el item del aspecto lo pide
            d.texto as textCar, d.estado  
                                    from t_asp_cargo_i a 
                                    inner join t_asp_cargo b on a.idAsp=b.id
                                    left join t_cargos_a c on c.idIasp = a.id 
                                    left join t_lista_cheq_vc d on d.idAspI = a.id and d.idDcheq=".$idC." 
                                    where b.tipo=2 ".$con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                
   // Adjuntos lista de chequeo
   public function getAdjCheq($id)
   {
      $result=$this->adapter->query("Select * from t_lista_cheq_d_a where idCheq=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                   
   
   // Fondos de prestacion social 
   public function getFondos($con)
   {
      $result=$this->adapter->query("select * from t_fondos where tipo=".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                           
   // Grupo de nomina
   public function getGrupo()
   {
      $result=$this->adapter->query("select * from n_grupos where activa = 0 order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                               
   // Grupo de nomina
   public function getGrupoNom($con)
   {
      $result=$this->adapter->query("select * from n_grupos
                      where activa = 0  ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                   
   // Grupo de nomina 2
   public function getGrupo2()
   {
      $result=$this->adapter->query("select * , nombre as nomFiltro 
                    from n_grupos order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                   
   // Sub grupo de nomina
   public function getSgrupo()
   {
      $result=$this->adapter->query("select * from n_subgrupos order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                   
   // Calendario
   public function getCalen($con)
   {    
      $result=$this->adapter->query("select * from n_tip_calendario where activo=0 $con order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                       
   // Centros doe costos
   public function getCencos()
   {
      $result=$this->adapter->query("select *, nombre as nomFiltro  
                                       from n_cencostos 
                                         where estado=0 order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
//---------------------------------------------------------------
// CONSUTAS DE PROYECTOS -------------- -------------------------
//---------------------------------------------------------------

 public function getProyectosFilt($con)
       {
          $result=$this->adapter->query("select * , nombre as nomFiltro 
                                       from n_proyectos_p ",Adapter::QUERY_MODE_EXECUTE);
           $datos=$result->toArray();
          return $datos;
       }

   // Listado de sedes
   public function getPuestos()
   {
      $result=$this->adapter->query("select * 
                                       from n_proyectos_p 
                                     order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                            
   // Listado de puestos 
   public function getPuestosCon($con)
   {
      $result=$this->adapter->query("select a.id, a.nombre, b.nombre as nomCiu,
                                               c.nombre as nomSed, d.nombre as nomProy ,
                    ( select count( aa.id ) 
                           from n_supervisores_p aa  
                                 where aa.idPues = a.id ) as numSup                                                    
                                            from n_proyectos_p a                
                                               inner join n_ciudades b on b.id = a.idCiu
                                               inner join t_sedes c on c.id = a.idSed 
                                               inner join n_proyectos d on d.id = a.idProy 
                                            where a.id > 0 ".$con." 
                                              order by c.nombre, b.nombre, a.nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                               
   // Listado de puestos
   public function getSedes()
   {
      $result=$this->adapter->query("select * from t_sedes order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                
   // Listado de zonas
   public function getZonas()
   {
      $result=$this->adapter->query("select * from n_zonas order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                   
   // Zonas por sedes
   public function getZonasSedes()
   {
      $result=$this->adapter->query("select * from n_zonas order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                       
   // Proyectos
   public function getProyectos()
   {
      $result=$this->adapter->query("select a.id, a.nombre  
             from n_proyectos a                
                where a.estado=0 " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                
   // Listado de modalidades
   public function getModalidad()
   {
      $result=$this->adapter->query("select * from n_modalidad order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                                             
   

   // LIstado de puestos
   public function getPuesSuper($con)
   {
      $result=$this->adapter->query("select a.id, a.nombre, b.nombre as nomCiu,
                                               c.nombre as nomSed, d.nombre as nomProy     
                                            from n_proyectos_p a                
                                               inner join n_ciudades b on b.id = a.idCiu
                                               inner join t_sedes c on c.id = a.idSed 
                                               inner join n_proyectos d on d.id = a.idProy 
                                               inner join n_supervisores_p e on e.idPues = a.id 
                                               inner join n_supervisores f on f.id = e.idSup 
                                            where a.estado = 0 ".$con  ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }   

   // Clientes
   public function getClientes()
   {
      $result=$this->adapter->query("select a.id, a.nombre  
             from n_clientes a                
                where a.estado=0 " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                                    
   // Proyectos de empleados
   public function getProyectosEmp($idEmp)
   {
      $result=$this->adapter->query("select a.id, a.nombre  
             from n_proyectos a
                inner join n_proyectos_e b on b.idProy = a.id 
                where a.estado=0 and b.idEmp = ".$idEmp." order by a.nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                              
   // Proyectos de empleados 
   public function getProyectoEmpleados($id)
   {
      $result=$this->adapter->query("select a.id, a.nombre, c.id as idEmp,
             c.CedEmp, c.nombre, c.apellido,    
             case when d.id is null then (b.horas/8)/2 else d.dias end as dias,
               d.domingo      
             from n_proyectos a
                inner join n_proyectos_e b on b.idProy = a.id 
                inner join a_empleados c on c.id = b.idEmp  
                left join n_novedades_pr d on d.idProy = a.id and d.idEmp = b.idEmp 
                where c.estado=0 and c.finContrato=0 and a.estado=0 and a.id=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }               
   // Puestos de trabajo por proyectos
   public function getProyectosPuestos($id)
   {
      $result=$this->adapter->query("select a.id, a.nombre, b.nombre as nomCiu,
         c.nombre as nomSed, d.nombre as nomProy     
             from n_proyectos_p a                
                inner join n_ciudades b on b.id = a.idCiu
                inner join t_sedes c on c.id = a.idSed 
                inner join n_proyectos d on d.id = a.idProy 
                where a.estado=0 and a.idProy=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                 

   // Listado arbol de sedes grupos zonas puestos
   public function getArbolProy($con)
   {
      $result=$this->adapter->query("select a.id , a.nombre, # Sedes
   c.id as idM1, c.nombre as nomMod1, # Proyectos 
   # Manejo de zonas 
      case when d.id is null then 1 else d.id end as idM2,   # id de la zona, caso especial por tabla de union entre sede y zonas 
           case when f.nombre is null then 'SIN ZONA' else f.nombre end as nomMod2 # Nombre de la zona       
       
                                  from t_sedes a 
                                    inner join n_proyectos_p b on b.idSed = a.id  # Puestos 
                                    inner join n_proyectos c on c.id = b.idProy # Proyectos 
                                    left join n_proyectos_p_z d on d.idPtra = b.id # Zonas 
                                    left join t_sedes_z e on e.idSed = a.id and e.id =d.idSzon 
                                    left join n_zonas f on f.id = e.idZon 
     group by a.nombre # Sede 
            , c.nombre  # Proyecto 
               , (case when f.nombre is null then 'SIN ZONA' else f.nombre end) # Zona
                                   
     order by a.nombre # Sede 
            , c.nombre  # Proyecto 
               , (case when f.nombre is null then 'SIN ZONA' else f.nombre end) # Zona" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                      
   // Consulta de puestos por proyectos
   public function getPuestosPorProyectos($idProy, $idZon)
   {
      // Cuando la zona es 1 , es porque no tiene asignada a la sede 
      $result=$this->adapter->query("select b.id, b.nombre 

                                  from t_sedes a 
                                    inner join n_proyectos_p b on b.idSed = a.id  # Puestos 
                                    inner join n_proyectos c on c.id = b.idProy # Proyectos 
                                    left join n_proyectos_p_z d on d.idPtra = b.id # Zonas 
                                    left join t_sedes_z e on e.idSed = a.id and e.id =d.idSzon 
                                    left join n_zonas f on f.id = e.idZon 
                                    
where c.id = ".$idProy." and (  case when d.id is null then 1 else d.id end  ) = ".$idZon."                                     
     group by a.nombre # Sede 
            , c.nombre  # Proyecto 
               , (case when f.nombre is null then 'SIN ZONA' else f.nombre end) # Zona
               , b.nombre " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              
   // Consulta de acciones del proceso 
   public function getSuperPuestosItem($idZon, $idSup)
   {
      $result=$this->adapter->query("select * 
                                       from n_supervisores_p a 
                                          where a.idSup = ".$idSup." and a.idZon = ".$idZon ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              
//---------------------------------------------------------------
// FIN CONSULTA DE PROYECTOS  -----------------------------------
//---------------------------------------------------------------                                                     
   // Tipos de nomina
   public function getTnom($con)
   {
      $result=$this->adapter->query("select *, nombre as nomFiltro 
                                       from n_tip_nom 
                                          where estado=0 ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;

   }                                                           
   // Conceptos de nomina 
   public function getConnom()
   {
      $result=$this->adapter->query("select *, case when valor=1 then 'HORAS' else 'VALOR' end as tipVal from n_conceptos order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                   
   // Conceptos de nomina  2
   public function getConnom2($con)
   {
      $result=$this->adapter->query("select *, case when valor=1 then 'HORAS' else 'VALOR' end as tipVal 
                                    from n_conceptos where id > 0 ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Conceptos de nomina excluyendo los conceptos de pretamos 
   public function getConPres($con)
   {
      $result=$this->adapter->query("select a.id, a.codigo, a.nombre, case when a.valor=1 then 'HORAS' else 'VALOR' end as tipVal 
                                    from n_conceptos a where a.id > 0 ".$con." 
                                      and ( select count(aa.id) from n_tip_prestamo aa where aa.idConE = a.id )  = 0
                    order by a.nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Variables de nomina 
   public function getFormulas()
   {
      $result=$this->adapter->query("select * from n_formulas order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Tipos de automaticos
   public function getTautoma()
   {
      $result=$this->adapter->query("select * from n_tip_auto order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Prefijos contables
   public function getPrefcont()
   {
      $result=$this->adapter->query("select * from n_pref_con order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                            
   // Tipos de contratacion
   public function getTipcont()
   {
      $result=$this->adapter->query("select * from a_tipcon order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                               
   // Tipos de automaticos en nominas aplicadas
   public function getTipaNapl($con)
   {
      $result=$this->adapter->query("select * from n_tip_auto_tn where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                
   // Tipos de prestamos en nominas aplicadas
   public function getTippNapl($con)
   {
      $result=$this->adapter->query("select * from n_tip_prestamo_tn where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
   // Prestamos del empleados
   public function getEmpPrestamos($con)
   {
      $result=$this->adapter->query("select a.id, a.fecDoc, a.docRef, fecApr,b.nombre,b.apellido,b.CedEmp, 
                                            c.nombre as nomcar, d.nombre as nomccos, a.estado
                                            , e.nombre as nomTpres, sum(f.saldoIni) + sum(f.pagado) as pagado,
                                            sum(f.valor) as valor, sum(a.abonosExtra) as abonosExtra,
                                               f.valCuota, f.cuotas    
                                            from n_prestamos a 
                                            inner join a_empleados b on a.idEmp=b.id 
                                            left join t_cargos c on c.id=b.idCar
                                            inner join n_cencostos d on d.id=b.idCcos
                                            inner join n_tip_prestamo e on e.id = a.idTpres 
                                            inner join n_prestamos_tn f on f.idPres = a.id 
                                            where ".$con." 
                                            group by a.id
                                            order by a.fecDoc desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   // Prestamos del empleados asldos cero
   public function getEmpPrestamosCero($con)
   {
      $result=$this->adapter->query("select f.id, a.id as idPres, a.fecDoc, a.docRef, fecApr,b.nombre,b.apellido,b.CedEmp, 
                                            c.nombre as nomcar, d.nombre as nomccos, a.estado
                                            , e.nombre as nomTpres, sum(f.saldoIni) + sum(f.pagado) as pagado,
                                            sum(f.valor) as valor, sum(a.abonosExtra) as abonosExtra,
                                               f.valCuota, f.cuotas, 
                       case when ( select g.valor from n_presta_primas g where g.idEmp = b.id and g.idIpres = f.id  ) is null then 
                          0 else ( select g.valor 
                            from n_presta_primas g 
                              where g.estado=0 and g.idEmp = b.id and g.idIpres = f.id  ) end 
                       as valorAbonar                                                      
                                        from n_prestamos a 
                                            inner join a_empleados b on a.idEmp=b.id 
                                            left join t_cargos c on c.id=b.idCar
                                            inner join n_cencostos d on d.id=b.idCcos
                                            inner join n_tip_prestamo e on e.id = a.idTpres 
                                            inner join n_prestamos_tn f on f.idPres = a.id 
                                        where ".$con." 
                                            group by a.id
                                            order by a.fecDoc desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
   // Movimiento de calendario en nomina
   public function getMcalen($con)
   {
      $result=$this->adapter->query("select * from n_tip_calendario_d where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                     
  // Tipos de empleado
   public function getTemp($con)
   {
      $result=$this->adapter->query("select * from n_tipemp where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }           
     // Tipos de empleado
   public function getTempFill($con)
   {
      $result=$this->adapter->query("select  * , nombre as nomFiltro  from n_tipemp where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
   // Listado de conceptos 
   public function getConcetos($con)
   {
      $result=$this->adapter->query("select * , nombre as nomFiltro 
                                       from n_conceptos where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Tipos de conceptos automaticos en nominas aplicadas
   public function getConaNapl($con)
   {
      $result=$this->adapter->query("select * from n_conceptos_tn where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                    
   // Conceptos de nominas hijas 
   public function getConNhij($con)
   {
      $result=$this->adapter->query("select * from n_conceptos_th where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Conceptos a tipos de empleados
   public function getConNtemp($con)
   {
      $result=$this->adapter->query("select * from n_conceptos_te where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Lista de procesos
   public function getProcesos($con)
   {
      $result=$this->adapter->query("select * from n_procesos where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Procesos del concepto
   public function getConPro($con)
   {
      $result=$this->adapter->query("select * from n_conceptos_pr where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Lista de empleados activos
   public function getEmp($con)
   {
      $result=$this->adapter->query("select * , 
                 concat( a.CedEmp, ' ', a.nombre, ' ' ,a.apellido  ) as nomFiltro   
                                      from a_empleados a
                                    where a.estado=0 ".$con." order by a.nombre,a.apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }       
   // Lista de empleados generales
   public function getEmpG($con)
   {
      $result=$this->adapter->query("select * , nombre as nomFiltro 
                                      from a_empleados 
                                     order by nombre,apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }   
   // Lista de empleados generales
   public function getEmpG2($con)
   {
      $result=$this->adapter->query("select id, CedEmp , nombre, apellido , nombre as nomFiltro 
                                      from a_empleados 
                                     order by nombre,apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
   // Lista de empleados inactivos
   public function getEmpInactivos($con)
   {
      $result=$this->adapter->query("select * 
                                      from a_empleados 
                                    where estado=1 ".$con." order by nombre,apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
    // Lista de empleados activos
   public function getEmpActivos($con)
   {
      $result=$this->adapter->query("select * 
                                      from a_empleados 
                                    where estado=0 ".$con." order by nombre,apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }        
   // Listado maestro de empleados activos
   public function getEmpM($con)
   {
      $result=$this->adapter->query("select a.id, a.CedEmp, a.activo, a.nombre, a.apellido, b.codigo as codCar, 
                                b.nombre as nomCar, c.id as idCcos, c.nombre as nomCcos,
                                idVac, vacAct, idInc, a.SexEmp, a.SexEmp, 
                                d.id as idGdot, d.nombre as nomGdot, e.fecDoc as FecUdot, 
        a.estado , count(e.id) as numDot, d.numero as numDotP, f.porc, a.sueldo              
                                from a_empleados a 
        left join t_cargos b on a.idCar=b.id
                                inner join n_cencostos c on a.idCcos=c.id 
                                left join t_grup_dota d on d.id=b.idGdot 
                                left join t_dotaciones e on e.idEmp=a.id and year(e.fecDoc) = year(now()) 
                                left join n_tarifas f on f.id = a.idRies 
                                where a.activo=0 ".$con." 
                                group by a.id     
                                order by a.nombre,a.apellido, e.fecDoc desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   // Listado maestro de empleados activos con sueldos
   public function getEmpMSalario($con)
   {
      $result=$this->adapter->query("select a.id, a.CedEmp, a.activo, a.nombre, a.apellido, b.codigo as codCar, 
                                b.nombre as nomCar, c.id as idCcos, c.nombre as nomCcos,
                                idVac, vacAct, idInc, a.SexEmp, a.SexEmp, 
                                d.id as idGdot, d.nombre as nomGdot, e.fecDoc as FecUdot, 
        a.estado , count(e.id) as numDot, d.numero as numDotP, f.porc, a.sueldo              
                          from a_empleados a 
                                 inner join t_cargos b on a.idCar=b.id
                                 inner join n_tipemp g on g.id = a.idTemp 
                                 inner join n_cencostos c on a.idCcos=c.id 
                                left join t_grup_dota d on d.id=b.idGdot 
                                left join t_dotaciones e on e.idEmp=a.id and year(e.fecDoc) = year(now()) 
                                left join n_tarifas f on f.id = a.idRies                                 
                          where a.estado=0 and a.finContrato=0 and g.tipo = 0 and a.sueldo !=  ".$this->salarioMinimo."   
                             ".$con."  
                                group by a.id     
                                order by a.sueldo " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    

   // Listado de empleados sueldos 
   public function getEmpMSalarioR($con)
   {
      $result=$this->adapter->query("select distinct round(a.sueldo) as sueldo             
                          from a_empleados a 
                                 inner join t_cargos b on a.idCar=b.id
                                 inner join n_tipemp g on g.id = a.idTemp 
                                 inner join n_cencostos c on a.idCcos=c.id 
                                left join t_grup_dota d on d.id=b.idGdot 
                                left join t_dotaciones e on e.idEmp=a.id and year(e.fecDoc) = year(now()) 
                                left join n_tarifas f on f.id = a.idRies                                 
                          where a.estado=0 and a.finContrato=0 and g.tipo = 0 and a.sueldo !=  ".$this->salarioMinimo."   
                             ".$con."  
                                group by a.id     
                                order by a.sueldo " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    

   // Listado maestro de empleados totales 
   public function getEmpMtotales($con)
   {
      $result=$this->adapter->query("select a.* , b.codigo as codCar, 
                                b.nombre as nomCar, c.id as idCcos, c.nombre as nomCcos,
                                idVac, vacAct, idInc, a.SexEmp, a.SexEmp, 
                                d.id as idGdot, d.nombre as nomGdot, e.fecDoc as FecUdot, 
        a.estado , count(e.id) as numDot, d.numero as numDotP, f.porc, a.sueldo              
                          from a_empleados a 
                                left join t_cargos b on a.idCar=b.id
                                inner join n_cencostos c on a.idCcos=c.id 
                                left join t_grup_dota d on d.id=b.idGdot 
                                left join t_dotaciones e on e.idEmp=a.id and year(e.fecDoc) = year(now()) 
                                left join n_tarifas f on f.id = a.idRies 
                            where a.id> 0 ".$con." 
                                group by a.id     
                                order by a.nombre,a.apellido, e.fecDoc desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
   // Listado maestro de empleados totales
   public function getEmpBusqueda($con)
   {
      $result=$this->adapter->query("select a.id, a.CedEmp, a.activo, a.nombre, a.apellido, b.codigo as codCar, 
                                b.nombre as nomCar, c.id as idCcos, c.nombre as nomCcos,
                                idVac, vacAct, idInc, a.SexEmp, a.SexEmp, 
                                d.id as idGdot, d.nombre as nomGdot, e.fecDoc as FecUdot, 
        a.estado , count(e.id) as numDot, d.numero as numDotP, f.porc, a.sueldo              
                                from a_empleados a 
        left join t_cargos b on a.idCar=b.id
                                inner join n_cencostos c on a.idCcos=c.id 
                                left join t_grup_dota d on d.id=b.idGdot 
                                left join t_dotaciones e on e.idEmp=a.id and year(e.fecDoc) = year(now()) 
                                left join n_tarifas f on f.id = a.idRies 
                                where ".$con." 
                                group by a.id     
                                order by a.nombre,a.apellido, e.fecDoc desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
   // Lista de empleados por sucursales
   public function getEmpSucursales($con)
   {
      $result=$this->adapter->query("select a.*  
                                      from a_empleados a 
                                        
                                    where a.activo=0 and a.estado=0  
                         and ( select count( b.id ) from n_sucursal_e b 
                               where b.idEmp = a.id ) = 0 
                          order by a.nombre, a.apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }       
   // Lista de empleados por sub grupos
   public function getEmpSubgrupos($con)
   {
      $result=$this->adapter->query("select a.*  
                                      from a_empleados a 
                                        
                                    where a.activo=0 and a.estado=0  
                         and a.idSgrup = 0 
                          order by a.nombre, a.apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }          
   // Listado maestro de conceptos activos
   public function getConM($con)
   {
      $result=$this->adapter->query("select a.*,case a.tipo when 1 then 'DEVENGADO' 
                          when 2 then 'DEDUCIDO' end as nomTipo,
        case a.valor when 1 then 'HORAS' 
        when 2 then 'VALOR' end as nomVal   
                          from n_conceptos a                            
                          order by a.valor, a.nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   // Listado maestro de tipos de automaticos 
   public function getTauM($con)
   {
      $result=$this->adapter->query("select a.*,c.nombre as nomTnom, e.codigo, e.nombre as nomCon 
           from n_tip_auto a 
           left join n_tip_auto_tn b on b.idTauto=a.id 
           left join n_tip_nom c on c.id=b.idTnom
           left join n_tip_auto_i d on d.idTauto=a.id
           left join n_conceptos e on e.id=d.idCon" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
   // Listado maestro de otros automaticos 
   public function getOtauM($con)
   {
      $result=$this->adapter->query("select distinct a.id,a.CedEmp, a.nombre as nomEmp,
                  a.apellido,b.nombre as nomCar,
                  c.nombre as nomGrup, d.nombre as nomtau, 
                  f.codigo, f.nombre as nomCon, e.valor, h.nombre as nomTnom   
                  from a_empleados a 
      inner join t_cargos b on a.idCar=b.id 
      inner join n_grupos c on a.idGrup=c.id 
      inner join n_tip_auto d on d.id=a.idTau 
      inner join n_emp_conc e on e.idEmp=a.id 
      inner join n_conceptos f on f.id=e.idCon
                  inner join n_emp_conc_tn g on g.idEmCon=e.id 
                  inner join n_tip_nom h on h.id=g.idTnom" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }       
   // Listado maestro de empleados activos general 
   public function getEmpMG($con)
   {
      $result=$this->adapter->query('select a.CedEmp, a.activo, a.id, a.nombre, a.apellido, b.codigo as codCar, 
                                b.nombre as nomCar, c.id as idCcos, c.nombre as nomCcos,
                                idVac, vacAct, idInc, d.nombre as nomSal, e.nombre as nomPen,
        f.nombre as nomCes, g.nombre as nomArp, concat(r.nombre," (",r.porc,")") as nomRiesgo, r.porc as porRiesgo, r.tipo as tipRiesgo,
         g.nombre as nomFav, h.nombre as nomFafc, ii.nombre as nomCaja , 
        j.nombre as nomTcon, k.nombre as nomTemp, m.nombre as nomGrup,
        n.nombre as nomTau1, o.nombre as nomTau2, p.nombre as nomTau3, 
                                q.nombre as nomTau4, a.sueldo, a.FecNac, a.fecIng, a.DirEmp , a.TelEmp, 
                                a.email, 
( select con.fechaI from n_emp_contratos con where con.idEmp=a.id order by id limit 1 ) as fecIng,                                 
        ( DATEDIFF( now() , a.fecIng  ) )  as dias,
        round( ( DATEDIFF( now() , a.fecIng ) ) / 365 , 0 )  as anos, s.nombre as nomBanco, a.numCuenta, 
        
        #Demas datos de talento humano 
        a.estatura, case a.sangre 
                      when 0 then "O-"
                when 1 then "O+" 
                when 2 then "A-"
                when 7 then "A+"
                when 4 then "B-"
                when 3 then "B+"
                when 5 then "AB-"
                when 6 then "AB+"               
                end as sangre,                
                
      a.operaciones, a.enfermedades , a.lentes, t.nombre as nomCiu,
        u.nombres as nomFami, 
             case a.sangre 
                      when 1 then "Mama"
                      when 2 then "Papa"
                      when 3 then "Esposo"
                      when 4 then "Hijo"
                      when 5 then "Abuelo" end parente, a.imagen , rr.nombre as nomSed 
        
                                from a_empleados a  
        inner join t_cargos b on a.idCar=b.id
                                inner join n_cencostos c on a.idCcos=c.id 
                                inner join t_fondos d on d.id=a.idFsal
                                inner join t_fondos e on e.id=a.idFpen
                                left  join t_fondos f on f.id=a.idFces
                                left join t_fondos g on g.id=a.idFarp 
                                left join t_fondos h on h.id=a.idFav 
                                left join t_fondos i on i.id=a.idFafc
                                left join t_fondos ii on ii.id=a.idCaja 
                                left join a_tipcon j on j.id=a.IdTcon 
                                left join n_tipemp k on k.id=a.idTemp 
                                left join n_grupos m on m.id=a.idGrup 
                                left join n_tip_auto n on n.id=a.idTau
                                left join n_tip_auto o on o.id=a.idTau2
                                left join n_tip_auto p on p.id=a.idTau3
                                left join n_tip_auto q on q.id=a.idTau4 
                                left join n_tarifas r on r.id = a.idRies
                                left join n_bancos s on s.id = a.idBanco 
                                left join n_ciudades t on t.id = a.idCiu 
                                left join a_empleados_f u on u.idEmp = a.id 
                                left join t_sedes rr on rr.id = a.idSed 
        where a.id > 0 '.$con." group by a.id order by a.nombre,a.apellido" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }     
   // Listado de familiares por empleados 
   public function getEmpFamiliares($id)
   {
      $result=$this->adapter->query("select *,(YEAR(CURRENT_DATE) - YEAR(fechaNac)) - (RIGHT(CURRENT_DATE,5) < RIGHT(fechaNac,5)) AS edad  
                                       from a_empleados_f 
                                  where idEmp=".$id." "
                  . "                  order by (YEAR(CURRENT_DATE) - YEAR(fechaNac)) - (RIGHT(CURRENT_DATE,5) < RIGHT(fechaNac,5))" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   
   // Lista de ausentismos
   public function getAusentismos($con)
   {
      $result=$this->adapter->query("select *, nombre as nomFiltro 
                           from n_tip_aus where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                        
   // Lista de incapacidades
   public function getIncapacidades($con)
   {
      $result=$this->adapter->query("select *, nombre as nomFiltro  
                          from n_tipinc where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                   
   // Calendario por tipo de nominas
   public function getCalendario($con)
   {
      $result=$this->adapter->query("Select b.valor,a.idTcal, a.tipo    
                             from n_tip_nom a 
                             inner join n_tip_calendario b on a.idTcal=b.id 
                             where a.id=".$con ,Adapter::QUERY_MODE_EXECUTE);
      //$datos=$result->toArray();
      $datos = $result->current();
      return $datos;
   }                
   // Periodos del tipo de calendario    
   public function getCalenIniFin($idGrupo, $idCal, $idTnom)
   {
      $result=$this->adapter->query("select * 
         from n_tip_calendario_d 
            where idGrupo = $idGrupo and idCal = $idCal and idTnom =".$idTnom,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }              
   // Periodos del tipo de calendario   2 
   public function getCalenIniFin2($idGrupo, $idCal, $idTnom)
   {
      $result=$this->adapter->query("select a.*, case when b.id is null then 0 else b.id end as idNom 
         from n_tip_calendario_d a
         left join n_nomina b on b.idTnom = a.idTnom and b.idGrupo = a.idGrupo 
   and b.idCal = a.idCal and b.fechaI=a.fechaI and b.fechaF = a.fechaF
         where a.estado=0 and a.idGrupo = ".$idGrupo." 
           and a.idCal = ".$idCal." and a.idTnom =".$idTnom." order by fechaI",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }      
   // Periodos del tipo de calendario   2 
   public function getPeriodoCerrado($con)
   {
      $result=$this->adapter->query("select a.id, a.fechaI, a.fechaF, 
                               b.nombre as nomTnom, c.nombre as nomGrupo, 
      concat( '(', c.nombre , ' - ', b.nombre , ' )  ', fechaI, ' - ' , fechaF ) as nomFiltro                                 
                             from n_nomina a 
                               inner join n_grupos b on b.id = a.idGrupo  
                               inner join n_tip_nom c on c.id = a.idTnom
                             where a.estado=2 and year(a.fechaF)=year(now()) 
                                order by a.fechaF desc",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }      

   // Periodos del tipo de calendario   2 
   public function getPeriodoTodos($con)
   {
      $result=$this->adapter->query("select a.id, a.fechaI, a.fechaF, 
                               b.nombre as nomTnom, c.nombre as nomGrupo, 
      concat( a.id, '(', c.nombre , ' - ', b.nombre , ' )  ', fechaI, ' - ' , fechaF ) as nomFiltro                                 
                             from n_nomina a 
                               inner join n_grupos b on b.id = a.idGrupo  
                               inner join n_tip_nom c on c.id = a.idTnom
                             where a.estado=2  ".$con."
                                order by a.fechaF desc",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }         

   // Periodos abiertos y cerrados de nomina y orden
   public function getCalendarios($idGrupo, $idCal, $estado, $orden )
   {
      if ($orden == 1)
         $orden = "desc";
      else
         $orden = "asc";
      $result=$this->adapter->query("Select * from n_tip_calendario_d 
                                       where idCal=".$idCal." and idGrupo=".$idGrupo."
                                        and estado=".$estado." order by fechaI ".$orden ,Adapter::QUERY_MODE_EXECUTE);


      $datos=$result->toArray();
      return $datos;
   }                 
   // Periodos abiertos y cerrados de nomina y orden ano actual 
   public function getCalendariosAnoAct($idGrupo, $idCal, $estado, $orden )
   {
      if ($orden == 1)
         $orden = "desc";
      else
         $orden = "asc";
      $result=$this->adapter->query("Select * from n_tip_calendario_d 
                                       where year(fechaI)=year(now()) and 
                                        idCal=".$idCal." and idGrupo=".$idGrupo."
                                        and estado=".$estado." order by fechaI ".$orden ,Adapter::QUERY_MODE_EXECUTE);


      $datos=$result->toArray();
      return $datos;
   }                    
   // Listado de matrices
   public function getMatz($con)
   {
      $result=$this->adapter->query("select * from n_tip_matriz where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                           
   // Conceptos aplicados a una matriz
   public function getConaMatz($con)
   {
      $result=$this->adapter->query("select * from n_tip_matriz_tnv where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                        
   // Configuraciones generales
   public function getConfiguraG($con)
   {
      $result=$this->adapter->query("select * from c_general ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                                           
   // Listado de cabeceras
   public function getCabInf($con)
   {
      $result=$this->adapter->query("select * from i_cabecera ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;
   }                                              
   // Listado de pies de documentos
   public function getPieInf($con)
   {
      $result=$this->adapter->query("select * from i_pie ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;
   }                                              
   
   // Promedio pago vacaciones pagas en diner 
   public function getVacaP($idEmp, $fecSal, $fecIni, $proceso)
   {
      // 6 id proceso de vacaciones sum( round( ( (c.devengado) / 360  ) * 30, 0 ) ) as promedio  
      $result=$this->adapter->query("select       
 case when sum( c.devengado )  is null then 
   0 
 else   
   round( ( sum( c.devengado ) / 360 ) * 30 , 0 )   
 end as promedio   
from n_nomina a 
   inner join n_nomina_e b on b.idNom=a.id
   inner join n_nomina_e_d c on c.idInom=b.id
   inner join n_conceptos d on d.id=c.idConc
   inner join n_conceptos_pr e on e.idConc=d.id 
   inner join a_empleados f on f.id=b.idEmp 
where c.idConc != 122 and e.idProc = ".$proceso." # Procesos para liquidacion final o vacas pagadas 

and a.fechaI >= '".$fecIni."' and a.fechaF <= '".$fecSal."'  

 and b.idEmp=".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;      
   }                   

   // Detallado promedio de vacaciones
   public function getVacaPd($idEmp, $fecSal,$fecIni, $proceso)
   {
      // 6 id proceso de vacaciones sum( round( ( (c.devengado) / 360  ) * 30, 0 ) ) as promedio  
      $result=$this->adapter->query("select       
            d.nombre, c.devengado , a.fechaI, a.fechaF 
from n_nomina a 
   inner join n_nomina_e b on b.idNom=a.id
   inner join n_nomina_e_d c on c.idInom=b.id
   inner join n_conceptos d on d.id=c.idConc
   inner join n_conceptos_pr e on e.idConc=d.id 
   inner join a_empleados f on f.id=b.idEmp 
where c.idConc != 122 and e.idProc = ".$proceso." # Procesos para liquidacion final o vacas pagadas 

and a.fechaI >= '".$fecIni."' and a.fechaF <= '".$fecSal."'  

 and b.idEmp=".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;      
   }                   
   // Listado de vacaciones
   public function getSovac($con)
   {
      $result=$this->adapter->query("select a.*,b.CedEmp,b.nombre,b.apellido,
                      concat(c.nombre,' (', c.deno,')' ) as nomCar, d.nombre as nomTnom 
                                            from n_vacaciones a 
                                            inner join a_empleados b on b.id=a.idEmp
                                            inner join t_cargos c on c.id=b.idCar 
                                            inner join n_tip_nom d on d.id = a.idTnom 
                                            where ".$con."  
                                            order by a.fecDoc desc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              
   // Listado de tipos de prestamos
   public function getTpres($con)
   {
      $result=$this->adapter->query("select * from n_tip_prestamo order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                  
    // Consulta dias no habiles 
   public function getConfHn($fecha)
   {
      $result=$this->adapter->query("select * from c_general_dnh where fecha='".$fecha."'",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                      
   // Consulta empleados en nomina     
   public function getNomEmp($con)
   {
      $result=$this->adapter->query("select * from n_nomina_e ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;

   }                                     
   // Conceptos aplicados a tipos de incapacidades   
   public function getConTinc($con)
   {
      $result=$this->adapter->query("select * from n_tipinc_c where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                           
   // Conceptos aplicados a tipos de ausentismos
   public function getConTaus($con)
   {
      $result=$this->adapter->query("select * from n_tip_aus_c where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                              
   // Lista de salarios
   public function getSalarios($con)
   {
      $result=$this->adapter->query("select * from n_salarios where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                 
   // Escalas salariales
   public function getSalCargos($con)
   {
      $result=$this->adapter->query("select * from t_cargos_sa where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Escalas salariales en el cargo
   public function getEsalCargo($con)
   {
      $result=$this->adapter->query("select b.*  
                                     from t_cargos_sa a
                                    inner join n_salarios b on b.id = a.idSal 
                                     where b.estado = 0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Estudios en cargos
   public function getEstuCargo($id)
   {
      $result=$this->adapter->query("select b.*  
                                     from t_cargos_sa a
                                    inner join n_salarios b on b.id = a.idSal 
                                     where a.idCar = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Nivel de estudios en cargos
   public function getNestudiosCargos($con)
   {
      $result=$this->adapter->query("select * from t_cargos_t where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                
   // Listado de tipos de embargos
   public function getTemb($con)
   {
      $result=$this->adapter->query("select * from n_tip_emb order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }               
   // Documento novedades antes de nomina    
   public function getDnovedades($con)
   {
      $result=$this->adapter->query("select a.id, b.nombre as nomConc, c.nombre as nomEmp, c.apellido as apeEmp, c.CedEmp,   
                          a.devengado, a.deducido, a.horas, d.fechaI , d.fechaF, c.sueldo, a.fechaEje,
                          e.nombre as nomProy, e.id as idProy ,
                          case when f.cuotas is null then 0 else f.cuotas end as cuotas     
                  from n_novedades a 
                          inner join n_conceptos b on b.id=a.idConc
                          inner join a_empleados c on c.id=a.idEmp 
                          inner join n_tip_calendario_d d on d.id=a.idCal 
                          left join n_proyectos e on e.id = a.idProy 
                          left join n_novedades_cuotas f on f.idInov = a.id 
                          where a.estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                   
   // Listado de nivel de estudios
   public function getNestudios($con)
   {
      $result=$this->adapter->query("select * , nombre as nomFiltro 
             from t_nivel_estudios where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }           
   // Filtro de hojas de vidas por cargos
   public function getHojasVida($con)
   {
      $result=$this->adapter->query("select distinct b.*, case when c.id is null then 0 else c.id end as idEmp, c.finContrato, c.estado as estEmp, c.id as idEmp   
                                  from t_hoja_vida b 
                                     left join t_hoja_vida_c a on a.id=a.idHoj
                                     left join a_empleados c on c.CedEmp = b.cedula 
                                     where b.estado in ('0',1, '2') ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                 
   // Datos de la solictud de contratacion 
   public function getDatSol($id)
   {
      $result=$this->adapter->query("select distinct c.nombre as nomCar, e.nombre as nomSed,b.vacantes,b.fecDoc,
                            f.cedula, f.nombre,  f.apellido , b.estado,
                            e.nombre as nomSed, b.salario       
                        from t_lista_cheq a 
                            inner join t_lista_cheq_d d on a.id=d.idCheq
                            inner join t_sol_con b on a.idSol=b.id
                            inner join t_cargos c on b.idCar=c.id
                            inner join t_sedes e on e.id=c.idSed 
                            inner join t_hoja_vida f on f.id=a.idHoj 
                            where a.id=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                    
   // 
   // Inventario de dotaciones
   public function getInvDot($id)
   {
      $result=$this->adapter->query("select *, case tipo when 1 then 'Hombre' 
                                                  when 2 then 'Mujer'
              when 3 then 'Unisex' end as tipNom   
              from t_mat_dota order by nombre, tipNom ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                    
   // Listado areas
   public function getAreas($con)
   {
      $result=$this->adapter->query("select * , nombre as nomFiltro 
                               from t_areas_capa ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                       
   // Listado areas de la compañia
   public function getAreasComp($con)
   {
      $result=$this->adapter->query("select * from t_areas ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                          
   // Listado lineas de dotaciones
   public function getLinDot($con)
   {
      $result=$this->adapter->query("select * from t_lineas_dot ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                          
   // Listado tallas
   public function getTallasDot($con)
   {
      $result=$this->adapter->query("select * from t_tallas ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                             
   // Listado de tipos de descargas   
   public function getTdescargos($con)
   {
      $result=$this->adapter->query("select * from t_tipo_descar ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                          
   // Listado de descargos
// Listado de descargos
   public function getDescargos($con)
   {
      $result=$this->adapter->query("select a.* , b.nombre as nomTdes, c.CedEmp, c.nombre, c.apellido, d.usuario, e.usuario as usuCierre,
      f.nombre as nomArchivo,f.id as idDocDes, count(g.id) as conPre

                                    from t_descargos a 
                                        inner join t_tipo_descar b on b.id = a.idTdes 
                                        left join a_empleados c on c.id = a.idEmp
                           left join users  d on d.id  = a.idUsu
                           left join users  e on e.id  = a.idUsuCierre
                           left join t_descargos_doc  f on f.idDes  = a.id
                           left join t_descargos_a    g on g.idDes  =  a.id
                                    where ".$con." 
                                     group by a.id 
                                        order by a.id desc ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }       

   // Listado de tipos de evalaucioens
   public function getTeva($con)
   {
      $result=$this->adapter->query("select  * 
                                            from t_tipo_eva   
                                            order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                  
   // Listado de bancos    
   public function getBancosPlantilla($con)
   {
      $result=$this->adapter->query("select  * 
                                            from c_bancos ".$con." 
                                            order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                  
   // Listado de bancos de la empresa   
   public function getBancos($con)
   {
      $result=$this->adapter->query("select  * 
                                            from n_bancos ".$con." 
                                            order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                               
   // Hojas de vidas para excel
   public function getHojasVidaE($con)
   {
      $result=$this->adapter->query("select a.*, c.nombre as nomCar from t_hoja_vida a 
           inner join t_hoja_vida_c b on b.idHoj=a.id  
           inner join t_cargos c on c.id = b.idCar 
           where a.estado=0".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                     
   // Hojas de vidas para excel
   public function getPresCuotas($id, $idPres)
   {
      //$result=$this->adapter->query("select c.idTnom, d.nombre as nomTnom,
        //                            e.valor, e.cuotas, e.valCuota,
          //                          e.saldoIni + e.pagado as pagado, d.prestamo  # 0: cuota fija, 1: cuota programada    
            //        from n_prestamos a
              //            inner join n_tip_prestamo b on b.id = a.idTpres
                //          inner join n_tip_prestamo_tn c on c.idTpresta = b.id 
                  //        inner join n_tip_nom d on d.id = c.idTnom 
                    //      left join n_prestamos_tn e on e.idPres = a.id and e.idTnom = d.id 
                      //    where a.id = ".$idPres  ,Adapter::QUERY_MODE_EXECUTE);

      $result=$this->adapter->query("select a.idTnom, b.nombre as nomTnom, c.valor, 
                         c.valCuota, c.cuotas, (c.saldoIni + c.pagado) as pagado, b.prestamo   
                         from n_tip_prestamo_tn a  
                              inner join n_tip_nom b on b.id = a.idTnom 
                              left join n_prestamos_tn c on c.idTnom = a.idTnom and c.idPres = ".$idPres." 
                              group by a.idTnom "  ,Adapter::QUERY_MODE_EXECUTE);


      $datos=$result->toArray();
      return $datos;
   }                     
   // Listado de entidaes
   public function getEntidades()
   {
      $result=$this->adapter->query("select *, nombre as nomFiltro 
                                         from a_entidades ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }               
   // Listado de funcionarios de la entidad 
   public function getEntidadesPersonal()
   {
      $result=$this->adapter->query("select b.id, a.nombre as nomEnt, b.nombres, b.apellidos 
          from a_entidades a
             inner join a_entidades_p b on b.idEnt = a.id
          order by a.nombre, b.nombres     ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                   
   // Primas de antiguedad
   public function getPrimaAnt()
   {
      $result=$this->adapter->query("select a.*, b.id as idCon, b.tipo, a.anual 
                                    from n_prima_anti a 
                                    inner join n_conceptos b on b.id = a.idConc 
                                    where a.estado=0 ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }               
   // Tipos de liquidacion
   public function getLiquidacion()
   {
      $result=$this->adapter->query("select * from n_tip_liqu ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }               
   // Motivos de retiro
   public function getMotRetiro()
   {
      $result=$this->adapter->query("select * from n_mot_ret order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                   
   // Lista de motivos de final de contrato
   public function getTipLiqui($con)
   {
      $result=$this->adapter->query("select * 
              from n_tip_liquida" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                 
   // Consulta ultimo fecha ultimo aumento salaria empleado
   public function getAsalariaF($id, $fecha)
   {

      $result = $this->adapter->query("select 
case when a.variable = 0 then 
  # Buscar si tiene un aumento de sueldo antes de 3 meses 
  case when a.idSal = 1 then 
    (  select (round( ( DATEDIFF( '".$fecha."' , bb.fecDoc ) ) / 30, 0 ) ) as numMes 
        from n_asalarial_emp aa 
           inner join n_asalarial bb on bb.id = aa.idAsal where bb.estado = 2 and aa.idEmp = a.id
        order by bb.fecDoc desc limit 1   )
  else 
    (  select (round( ( DATEDIFF( '".$fecha."' , bb.fecDoc ) ) / 30, 0 ) ) 
        from n_asalarial_d aa  
           inner join n_asalarial bb on bb.id = aa.idAsal  
        where bb.estado = 2 and aa.idEsal = a.idSal  
          order by bb.fecDoc desc limit 1  )
  end 
      else a.variable end as variable             
                from a_empleados a 
                  where a.id=".$id ,Adapter::QUERY_MODE_EXECUTE);    
      //$result = $this->adapter->query("select count(a.id) as numAum ,  
      //    case when (round( ( DATEDIFF( '".$fecha."' , b.fecDoc ) ) / 30, 0 ) ) is null 
             //then 
        //        0
      //       else  
     //            round( ( DATEDIFF( '".$fecha."' , b.fecDoc ) ) / 30, 0 )
    //        end as meses 
     //           from a_empleados a 
       //            inner join n_asalarial_d c on c.idEsal = a.idSal  
         //          inner join n_asalarial b on b.id = c.idAsal and b.idGrup = a.id//Grup 
//                where a.id=".$id." order by b.fecDoc desc limit 1 " ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                
   // Consulta periodo de nomina
   public function getPerNomina($id)
   {
      $result = $this->adapter->query("Select a.idTnom, a.idIcal, a.idGrupo,a.fechaI,a.fechaF, a.idCal ,
                     a.fechaIp, a.fechaIc,  a.fechaIcAnt, a.idTnomL, 
                     case when c.periodo is null then 0 else c.periodo end as periodo,
                     year( a.fechaIp ) as anoIp, month(a.fechaIp) as mesIp, # Ano y mes Primas 
                     year( a.fechaIc ) as anoIc, month(a.fechaIc) as mesIc, #Ano y mes cesantias    
          case when year( a.fechaF ) > year( a.fechaIc ) then # Validar cambio de ano en cesantias 
            datediff( a.fechaF , concat( year( a.fechaF ) ,'-', '01' ,'-', '01' ) ) + 1
          else 
             0  
             end as diasCesantiaNuevo,  # Diferencia de dias entre un año nuevo para caso de cesantias sin cerrar                           
                     month( a.fechaF ) as mesF,
                     year( a.fechaI ) as ano, month( a.fechaI ) as mes,
                   datediff( a.fechaF, a.fechaI ) +1  as diasNom, b.anticipo 
              from n_nomina a 
                      left join n_tip_calendario_p c on c.idCal = a.idCal and substr(a.fechaI,6,2) = c.mesI                        
             and LPAD(trim(LEFT( (substr(a.fechaI,9,2)),10)),2,'0') = LPAD(trim(LEFT( (c.diaI),10)),2,'0')             
                  left join n_grupos b on b.id = a.idGrupo                    
                      where a.id=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                   
   // Consulta periodo de nomina anteriores ya cerrados
   public function getPerNominaCerrados($con)
   {
      $result = $this->adapter->query("select id, fechaI, fechaF
                               from n_nomina where $con order by fechaF desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;
   }                      
   // Listado de motivos de contratacion
   public function getMotivosContra()
   {
      $result=$this->adapter->query("select * from t_motivos_contra order by nombre",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }

//---------------------------------------------------------------
// CONSUTAS EVENTOS Y CAPACITACIONES -------------------------
//---------------------------------------------------------------
   // Listado de tipos de eventos
   public function getTeventos($con)
   {
      $result=$this->adapter->query("select  * 
                                            from t_tipo_eventos                                               
                                            order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                         
   // Modalidad de eventos
   public function getModalEvento($con)
   {
      $result=$this->adapter->query("select  *, nombre as nomFiltro  
                                            from t_modalidad_evento                  where id > 0 ".$con." 
                                            order by id ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                             
   // Listado de tipos de capacitaciones
   public function getTcapa($con)
   {
      $result=$this->adapter->query("select  * , nombre as nomFiltro 
                                            from t_tipo_capa 
                                             where id > 0 ".$con."   
                                            order by nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                               
   // Invitados capacitacion
   public function getInvCap($con)
   {
      $result=$this->adapter->query("select * from t_sol_cap_i where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Programacion
   public function getPrograma($con)
   {
      $result=$this->adapter->query("select *, year(fecha) as ano,month(fecha) as mes,day(fecha) as dia 
             from t_programa".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   public function getProgramaPeriodo()
   {
      $result=$this->adapter->query("select concat( year(now()) ,'-', lpad( mes,2,'0' ), '-01'  ) as fecha, 
  case mes when 1 then 'Enero' 
           when 2 then 'Febrero' 
           when 3 then 'Marzo' 
           when 4 then 'Abril' 
           when 5 then 'Mayo'            
           when 6 then 'Junio' 
           when 7 then 'Julio' 
           when 8 then 'Agosto' 
           when 9 then 'Septiembre'               
           when 10 then 'Octubre' 
           when 11 then 'Noviembre' 
           when 12 then 'Diciembre'                                                    
  end as mes ,   ano,  
   mes as mesNumero 
from n_nov_prog_p " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                          
   // Buscar dias programados para el evento 
   public function getProgEvento($con)
   {
      $result=$this->adapter->query("select a.id, a.idEve, a.fechaI, a.horaI, a.horaF 
                                        from t_programa a
                                           inner join t_sol_cap b on b.id = a.idEve 
                                           where b.estado in (1) ".$con."  
                                           order by a.idEve ,a.fechaI" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Buscar valro guardado de asistencias 
   public function getProgEventoAsis($id)
   {
      $result=$this->adapter->query("select a.idIsol , a.idProg , a.asistio  
                                       from t_sol_cap_i_e_a a 
                                     where a.idSol = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                             
   // Grupos de empleados por eventos 
   public function getGrupoEventos($con)
   {
      $result=$this->adapter->query("select * from t_grupo_emp_eve ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                
//---------------------------------------------------------------
// FIN CONSUTAS EVENTOS Y CAPACITACIONES -------------------------
//---------------------------------------------------------------


   // Listado de evaluadores de descargos
   public function getEvaDescar($can)
   {
      $result=$this->adapter->query("Select a.id, b.id as idEmp , b.CedEmp, b.nombre, b.apellido, c.nombre as nomCar,
                                     d.nombre as nomCcos  
                                     from t_eva_descar a 
                                     inner join a_empleados b on b.id = a.idEmp 
                                     inner join t_cargos c on c.id = b.idCar
                                     inner join n_cencostos d on d.id = b.idCcos ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      //$datos = $result->current();
      return $datos;
   }   
   // Centros de costos a evaluar 
   public function getInvEva($con)
   {
      $result=$this->adapter->query("select * from t_evaluacion_c where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Evaluadores 
   public function getEnvaluadores($con)
   {
      $result=$this->adapter->query("Select b.id, b.CedEmp, b.nombre, b.apellido 
                                      from t_eva_comp a
                                        inner join a_empleados b on b.id = a.idEmp ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                             
   // Listado de terceros
   public function getTerceros($con)
   {
      $result=$this->adapter->query("select a.codigo, b.id, 
                   case when b.central = 1 then
                      concat( a.nombre, ' ', '(Principal)') else b.nombre end as nombre,
                   case when b.central = 1 then
                      concat( a.nombre, ' ', '(Principal)') else b.nombre end as nomFiltro 
                   from n_terceros a
                      inner join n_terceros_s b on b.idTer = a.id 
                      where a.id>0  ".$con." 
                      order by a.nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }               
   // Elementos de un reporte
   public function getEleRep($con)
   {
      $result=$this->adapter->query("select * from i_constructor_ele where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                              
   // Menu 3
   public function getMenRepor($con)
   {
      $result=$this->adapter->query("select a.*, b.nombre as nomC2, 
           c.nombre as nomC3, d.nombre as nomC4  
           from c_mu3 a 
           inner join c_mu2 b on b.id = a.idM2
           inner join c_mu1 c on c.id = b.idM1
           inner join c_mu d on d.id = c.idM 
           where a.repor = 1 
           order by d.nombre, c.nombre, b.nombre, a.nombre".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                 
//----------------------------------------
// ***-- CONTRUCTOR --------------------- 
//----------------------------------------  
   // Composicion del reporte
   public function getFilCont()
   {
      $result=$this->adapter->query("select * from i_constructor_f order by id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                 
   // Composicion del reporte
   public function getConRepor($con, $id)
   {
      $result = $this->adapter->query("select b.id, b.nombre, d.tipo, d.etiqueta, d.names, d.funcion      
                               from c_mu3 a 
                                      inner join i_constructor b on b.idOm = a.id 
                                      left join i_constructor_ele c on c.idCon = b.id 
                                      left join i_constructor_f d on d.id = c.tipo 
                                 where b.id = ".$id."  
                                      order by d.tipo desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                              
   // Composicion del reporte
   public function getConRepor1($con, $id)
   {
      $result = $this->adapter->query("select b.id, b.nombre, d.tipo, d.etiqueta, d.names, d.funcion      
                               from c_mu3 a 
                                      inner join i_constructor b on b.idOm = a.id 
                                      left join i_constructor_ele c on c.idCon = b.id 
                                      left join i_constructor_f d on d.id = c.tipo 
                                 where b.id = ".$id."  
                                      order by d.tipo desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                                 
   // Campos para mostrar en el reporte
   public function getCamposReport($id)
   {
      $result=$this->adapter->query("select c.id, c.alias 
                               from c_mu3 a 
                                      inner join i_constructor b on b.idOm = a.id 
                                      inner join i_constructor_ca c on c.idCon = b.id 
                                 where b.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                           
   // Reportes del contructor 
   public function getConReporCon($id)
   {
      $result=$this->adapter->query("select b.id, b.nombre
              from c_mu3 a 
                inner join i_constructor b on b.idOm = a.id 
              where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                   
//----------------------------------------
// ***-- FIN CONTRUCTOR ------------------ 
//----------------------------------------  
   // Listado de cuentas
   public function getCuentas($con)
   {
      $result=$this->adapter->query("select * from n_plan_cuentas order by codigo".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                      
   // Listado de proviciones
   public function getProviciones($con)
   {
        //  1 then 'Cesantias' 
        //  2 then 'Intereses' 
  //  3 then 'Primas' 
  //  4 then 'Vacaciones' 
  //  5 then 'Salud' 
  //  6 then 'Pensiones' 
  //  7 then 'Caja de compensación' 
  //  8 then 'Sena' 
  //  9 then 'Icbf'
  //  10 then 'Riesgos profesionales'         
      $result=$this->adapter->query("select *, (porc/100) as por, 
                                      nombre as con 
                                        from n_proviciones   
                                        where id > 0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                                         
   // Tarifas Arl
   public function getTarifas($con)
   {
      $result=$this->adapter->query("select * from n_tarifas where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Codigos de enfermedades
   public function getCodEnf($con)
   {
      $result=$this->adapter->query("select * from n_cod_enf ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Escala salaria para documento de modificacion
   public function getDocEscala($id)
   {
      $result=$this->adapter->query("select a.*, 
                         b.porInc, b.salarioNue, b.salarioAct  
                             from n_salarios a 
                             left join n_asalarial_d b on b.idEsal=a.id and b.idAsal = ".$id." where a.salario>0 order by a.salario" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                               
   // Sueldos para documento de modificacion
   public function getDocEscalaS($id)
   {
      $result=$this->adapter->query("select distinct a.sueldo as salario, '' as codigo , b.porInc, b.salarioNue 
                               from a_empleados a  
                                  left join n_asalarial_d b on TRUNCATE(b.salarioAct,0) = TRUNCATE(a.sueldo,0) and b.idAsal = ".$id."  
                                  where a.activo=0 order by a.sueldo" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                               
   // Periodos cerrados de nomina
   public function getPerCerrados($id)
   {
      $result=$this->adapter->query("select a.*, 
                         b.porInc, b.salarioNue, b.salarioAct  
                             from n_salarios a 
                             left join n_asalarial_d b on b.idEsal=a.id and b.idAsal = ".$id." order by a.salario" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                
   // Abonos extraordinarios sobre un prestamo
   public function getAbonosExtra($id)
   {
      $result=$this->adapter->query("select distinct c.* 
                          from  n_prestamos_tn a
                            inner join n_prestamos b on b.id = a.idPres
                            inner join n_abonos_presta c on c.idPres = b.id 
                            where a.idPres = ".$id." group by c.fecha" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                        
   // Contratos de empleados
   public function getContEmp($id)
   {
      $result=$this->adapter->query("select a.*, b.nombre as nomTcon, b.tipo as tipCon, c.nombre as nomCar    
              from n_emp_contratos a 
                  inner join a_tipcon b on b.id = a.idTcon 
                  left join t_cargos c on c.id = a.idCar 
                  where a.idEmp=".$id." order by a.fechaI desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                           
   // Contrato activo de empleado
   public function getContEmpA($id)
   {
      $result=$this->adapter->query("select a.*,year(a.fechaI) as ano, month(a.fechaI) as mes, day(a.fechaI) as dia, 
              b.nombre as nomTcon, b.tipo as tipCon   
              from n_emp_contratos a 
                  inner join a_tipcon b on b.id = a.idTcon 
                where a.idEmp=".$id." and estado=0 and a.tipo=1 
                  order by a.fechaI desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                              
   // Ultimas vacaciones del empleado
   public function getVacaEmpA($id)
   {
      $result=$this->adapter->query("select year(a.fechaI) as ano,
             month(a.fechaI) as mes, day(a.fechaI) as dia  
              from n_libvacaciones a 
                  where a.idEmp=".$id." and a.estado = 0 
            order by a.fechaI" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                 
   // critererio evaluativos en maestro de cargos
   public function getCritCargos($id)
   {
      $result=$this->adapter->query("select a.*, b.nombre as nomAsp,
                                        c.id as idIasp, c.texto, c.a, c.b, c.c, c.d, c.e, a.tipo, c.idCar ,
                                        case a.hoja when 2 then 'Requerido en educación' 
                                          when 1 then 'Requerido Laboral' end hojTip       
                                           from t_asp_cargo_i a 
                                             inner join t_asp_cargo b on b.id=a.idAsp 
                                             left join t_cargos_a c on c.idIasp=a.id 
                                             where a.idAsp=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                              
   // critererio evaluativos en maestro de cargos y cargos
   public function getCritCargosC($id, $idCar)
   {
      $result=$this->adapter->query("select a.*, b.nombre as nomAsp,
                                        c.id as idIasp, c.texto, c.a, c.b, c.c, c.d, c.e, a.tipo, c.idCar ,
                                        case a.hoja when 2 then 'Requerido en educación' 
                                          when 1 then 'Requerido Laboral' end hojTip       
                                           from t_asp_cargo_i a 
                                             inner join t_asp_cargo b on b.id=a.idAsp 
                                             left join t_cargos_a c on c.idIasp=a.id and c.idCar=".$idCar."  
                                             where a.idAsp=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                 
   // critererio evaluativos en hoja de vida, tanto educativo como laboral 
   public function getCritCargosH($id, $tipo)
   {
      $result=$this->adapter->query("select b.*  
               from t_hoja_vida_c a 
                 inner join t_cargos_a b on b.idCar = a.idCar 
                 inner join t_asp_cargo_i c on c.id = b.idIasp 
               where c.hoja=".$tipo." and a.idHoj=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                    
   // EVALUACION POR COMPETENCIAS

   // Lista de competencias
   public function getCompetenciasCom($con)
   {
      $result=$this->adapter->query("select * from t_competencias where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Lista de objetivos
   public function getObjetivosCom($con)
   {
      $result=$this->adapter->query("select * from t_objetivos a where a.estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Lista de procesos
   public function getProcesosCom($con)
   {
      $result=$this->adapter->query("select * from t_procesos where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       

   // Lista de valroes
   public function getValoresCom($con)
   {
      $result=$this->adapter->query("select * from t_valores where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Lista de comportamientos 
   public function getComportamientos($con)
   {
      $result=$this->adapter->query("select * from t_comportamientos_b where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          

   // Itesmde la evaluacion por competencias
   public function getEvaluacionItems($idEmp, $idEva, $con )
   {
      $result=$this->adapter->query("select a.id as idEmp, e.id as idGrup,
       e.nombre as nomGrup, 
f.id as idCom, g.nombre as nomCom, # Competencia 
i.id as idObj, k.id as idComp, k.nombre as nomCompo, # Comportamientos   
case when j.lista is null then -9 else j.lista end as valor,  # -9 para poner selecciones 
h.id, l.id as idObj, l.nombre as nomObj, ff.nombre as nomVal   
   from a_empleados a
     inner join t_evaluacion aa on aa.id = ".$idEva."  
     inner join t_cargos b on b.id = a.idCar 
     inner join t_nivelasp c on c.id = b.idNcar and c.id = aa.idRol  
     inner join t_nivelasp_g d on d.idNasp = c.id 
     inner join t_grupos_com e on e.id = d.idGrup 
     inner join t_grupos_com_c f on f.idGrup = e.id 
     inner join t_competencias g on g.id = f.idCom 
     inner join t_comportamientos_b_c i on i.idCom = g.id 
     inner join t_comportamientos_b k on k.id = i.idComp 
     inner join t_comportamientos_b_o h on h.idComp = k.id    
     inner join t_comportamientos_b_p hh on hh.idComp = k.id and hh.idProc = aa.idProc         
     inner join t_objetivos l on l.id = h.idObj 
     inner join t_valores ff on ff.id = k.idVal 
     left join t_evaluacion_c_e j on j.idEmp = a.id and j.idIcomp = h.id and  j.idEva = ".$idEva."  
where k.estado = 0 and a.id = ".$idEmp."    
group by e.id, g.id, k.nombre, l.id 
order by e.id, g.id, k.id, l.id  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
   // Itesmde la evaluacion por competencias para plan de mejoramiento 
   public function getEvaluacionItemsPlan($idEmp, $idEva, $con )
   {
      $result=$this->adapter->query("select a.id as idEmp, e.nombre as nomGrup, 
                    g.id as idCom, # Competencia 
                    g.nombre as nomCom,
                     g.nombre as nomItem, j.id ,
                      case when j.lista is null then -9 else j.lista end as valor  # -9 para poner selecciones 
                      , k.descrip, k.recursos , k.acciones, k.fecha  
                                      from a_empleados a
                                        inner join t_cargos b on b.id = a.idCar
                                        inner join t_nivelasp c on c.id = b.idNcar 
                                        inner join t_nivelasp_g d on d.idNasp = b.idNcar   
                                        inner join t_grupos_com e on e.id = d.idGrup # Grupo de comeptencias
                                        inner join t_grupos_com_c f on f.idGrup = d.idGrup # Competencias 
                                        inner join t_competencias g on g.id = f.idCom 
                                        inner join t_competencias_o h on h.idCom = g.id 
                           inner join t_objetivos i on i.id = h.idObj # Objetivos                                                          
                                        inner join t_evaluacion_c_e j on j.idEva = ".$idEva." and j.idEmp = a.id and j.idIcomp = h.id 
                                        left join t_evaluacion_c_e_plan k on k.idIeva = j.id and k.idEmp = j.idEmp   # Datos del plan de mejoramiento                                                                    
                                        where a.id = ".$idEmp." ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                               
   // Datos de evaluacion por competencias
   public function getDatEval($id)
   {
      $result=$this->adapter->query("Select a.*, b.nombre as nomTeva, 
                                         c.nombre as nomNasp , d.nombre as nomProc     
                                    from t_evaluacion a
                                      inner join t_tipo_eva b on b.id = a.idTeva 
                                      inner join t_nivelasp c on c.id = a.idRol 
                                      inner join t_procesos d on d.id = a.idProc 
                                      where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                      
   // Conceptos de talento humano
   public function getConTalento()
   {
      $result=$this->adapter->query("select *, nombre as nomFiltro 
                                      from t_conceptos order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Retroactivo activo general 
   public function getConsRetro($idGrup)
   {
      $result=$this->adapter->query("select count(id) as num , idPerA 
                                      from n_asalarial where estado=1 and idGrup=".$idGrup  ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                      
   // Retroactivo activo individual  
   public function getConsRetroI()
   {
      $result=$this->adapter->query("select count(id) as num 
                                      from n_aumento_sueldo where estado=1" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                         
   // Porentaje evaluacion
   public function getPorcEval($id)
   {
      $result=$this->adapter->query("select a.id as idEmp,count(e.id) as pre,
                                    count(f.id) as res, round( (count(f.id) * 100)/count(e.id),2 ) as por 
                                      from a_empleados a
                                        inner join t_cargos b on b.id = a.idCar
                                        inner join t_nivelasp c on c.id = b.idNcar 
                                        inner join t_asp_cargo d on d.idNasp = b.idNcar 
                                        inner join t_asp_cargo_i e on e.idAsp = d.id  
                                        inner join t_evaluacion_c g on g.idEva = ".$id." and g.idCcos = a.idCcos 
                                        left join t_evaluacion_c_e f on f.idEva = g.idEva and f.idEmp = a.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                                              
   // Grupos de competencias 
   public function getGcomp($id)
   {
      $id  = (int) $id; 
      $result=$this->adapter->query("select a.id, a.nombre 
                       from t_grupos_com a
                left join t_nivelasp_g b on b.idGrup = a.id and b.idNasp = ".$id."  
                where b.id is null ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    
   // Grupos de competencias asocidos a nivel de competencias
   public function getGcomNivelCargo($id)
   {
      $id  = (int) $id;
      $result=$this->adapter->query("select a.id, b.nombre, b.orden  
                           from t_nivelasp_g a 
                              inner join t_grupos_com b on b.id = a.idGrup
                               where a.idNasp =".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }       
   // Lista de ausentismos
   public function getAusentismosDias($idEmp, $fechaI, $fechaF)
   {
        $result=$this->adapter->query("select b.id as idTasu, 
              case when sum(datediff(a.fechaF, a.fechaI)) is null then 0 else sum(datediff(a.fechaF, a.fechaI)  + 1) end as dias 
           from n_ausentismos a 
               inner join n_tip_aus b on b.id = a.idTaus 
               inner join a_empleados nn on nn.id=a.idEmp
           where b.tipo = 2 and a.horas=0 and a.estado=1 and a.idEmp=".$idEmp."
              and a.fechaI>='".$fechaI."' and a.fechaF <= '".$fechaF."'  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }
// Lista de ausentismos anterior 
   public function getAusentismosRegimenAnt($idEmp)
   {
        $result=$this->adapter->query("select b.id as idTasu, 
         case when sum(datediff(a.fechaF, a.fechaI)) is null then 0 else sum(datediff(a.fechaF, a.fechaI) + 1)  end as dias 
           from n_ausentismos a 
           inner join n_tip_aus b on b.id = a.idTaus 
      inner join a_empleados nn on nn.id=a.idEmp
         where b.tipo = 2 and a.horas=0 and nn.regimen = 1 and a.estado=1 and a.idEmp=".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }   
   // Grupos de competencias asocidos a nivel de competencias
   public function getMotivoAntCesantias($id)
   {
      $id  = (int) $id;
      $result=$this->adapter->query("select * from n_tip_cesantias",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }         
   // Resumido por fondos de prestacion planilla unica 
   public function getFondosPlanilla($id)
   {
      $id  = (int) $id;
      $result=$this->adapter->query("# FONDOS DE SALUD  
select 'SALUD' as fondo, d.nombre, sum(b.aporSalud) as aporte, d.codigo  
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonS # fondo de salud 
                                where a.id =  ".$id."  
                                group by d.id 
union all # FONDOS DE PENSION                                 
select 'PENSION' as fondo, d.nombre, sum(b.aporPension+b.aporSolidaridad) as aporte , d.codigo 
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonP # fondo de pension
                                where a.id =  ".$id." 
                                group by d.id 
union all # FONDOS DE RIESGOS 
select 'RIESGOS' as fondo, d.nombre, sum(b.aporRiesgos) as aporte, d.codigo  
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonR  # riesgos 
                                where a.id =  ".$id." 
                                group by d.id                                 
union all # CAJA DE COMPENSACION 
select 'CAJA' as fondo, d.nombre, sum(b.aporCaja) as aporte , d.codigo 
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idCaja  # caja
                                where a.id =  ".$id."  
                                group by d.id 
union all # ICBF 
select 'ICBF' as fondo, 'ICBF', sum(b.aporIcbf) as aporte , '' as codigo 
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                where a.id = ".$id." group by a.id 
union all # SENA 
select 'SENA' as fondo, 'SENA', sum(b.aporSena) as aporte, '' as codigo  
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                where a.id = ".$id." group by a.id ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }         
   // Lista de ciudaes
   public function getCiudades($con)
   {
      $result=$this->adapter->query("select * from n_ciudades ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Lista de areas de gestion
   public function getAreasGestion($con)
   {
      $result=$this->adapter->query("select * from t_areas_gestion ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                         
   // Lista de items areas de gestion
   public function getAreasGestionItem($con)
   {
      $result=$this->adapter->query("select c.id, a.nombre as nomGes,
                b.nombre, c.descrip 
                   from t_areas_gestion a 
                     inner join t_areas_gestion_c b on b.idGarea = a.id
                     inner join t_areas_gestion_c_i c on c.idIgarea = b.id 
                     where a.id>0".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                            
   // Funciones o areas de gestion REvisar
   public function getCargFunc1($con)
   {
      $result=$this->adapter->query("select * from t_cargos_g where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                       
   // Funciones o areas de gestion
   public function getCargFunc($con)
   {
      $result=$this->adapter->query("select * from t_cargos_g where id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                  


   // Lista de tipo de documentos especiales
   public function getTipDocEsp($con)
   {
      $result=$this->adapter->query("select * from t_tip_doc where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                
   // Lista de tipo de documentos de control
   public function getTipDocControl($con)
   {
      $result=$this->adapter->query("select * from t_tip_docontrol where estado=0 " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                
   // Lista de grupos de conceptos 
   public function getGconceptos($con)
   {
      $result=$this->adapter->query("select * from n_conceptos_g ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }    
// CONSULTA ARCHIVOS PLANOS              
   // Plano popular 
   public function getPopular($id, $idBan)
   {
      $result=$this->adapter->query("select  
( select LPAD(ltrim(aa.nit),16,'0') as cedula from c_general aa ) as nitEmp , 
LPAD(ltrim(d.CedEmp),16,'0') as cedula, 
'TR' as campo1, 
ltrim( LPAD(ltrim(d.numCuenta),31,'0')) as cuenta, 
ltrim( d.numCuenta ) as cuenta2,
 case when d.tipCuenta = 1 then 'CA' else 'CC' end as tipoCuen,
 '000002' as codBanco,  
 ( select LPAD( (sum(cc.devengado)-sum(cc.deducido) ),16,'0') as valor  
from n_nomina aa
inner join n_nomina_e bb on bb.idNom = aa.id
inner join n_nomina_e_d cc on cc.idInom = bb.id
inner join a_empleados dd on dd.id = bb.idEmp 
inner join n_bancos ee on ee.id = dd.idBanco 
where aa.id = a.id and dd.formaPago = 1  and bb.pagoCes = 0  )  as valorNom, # total valor de la nomina 

( select LPAD( (sum(cc.devengado)-sum(cc.deducido) ),16,'0') 
from n_nomina aa
inner join n_nomina_e bb on bb.idNom = aa.id
inner join n_nomina_e_d cc on cc.idInom = bb.id
inner join a_empleados dd on dd.id = bb.idEmp 
inner join n_bancos ee on ee.id = dd.idBanco 
where aa.id = a.id and bb.idEmp = b.idEmp ) as valorEmp, # Valor por empleado 
'000000000219999000000000000000000000000000000000000000000000000000000000000000000000000000000000' as campo3,

( select LPAD( count(ee.id) ,8,'0')  
from n_nomina_e ee 
inner join a_empleados pp on pp.id = ee.idEmp 
where ee.idNom = a.id and pp.formaPago = 1  and ee.pagoCes = 0 ) as numReg, 

concat( year(now()) , LPAD( month(now()) ,2,'0')  , LPAD(  day(now()) ,2,'0')  ) as fechaMv ,
concat( LPAD( hour(now()) ,2,'0') , LPAD( minute(now()) ,2,'0')  , LPAD( second(now()) ,2,'0')   ) as horaMv, e.numCuenta as cuentaBanco      
from n_nomina a
inner join n_nomina_e b on b.idNom = a.id
inner join a_empleados d on d.id = b.idEmp 
inner join n_bancos e on e.id = d.idBanco 
where d.formaPago = 1 and b.pagoCes = 0 and d.idBancoPlano = ".$idBan." and a.id = ".$id."  order by d.nombre  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                   
   // Listado arbol de opciones de hrm
   public function getArbolcm1($con)
   {
      $result=$this->adapter->query("select a.id , a.nombre , b.id as idM1, b.                         nombre as nomMod1  
                                     ,c.id as idM2, c.nombre as nomMod2  
                                         from c_mu a 
                                         inner join c_mu1 b on b.idM = a.id
                                         inner join c_mu2 c on c.idM1 = b.id                                         
                                         order by a.id, b.id, c.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                                                      

//-------------------------------------------
// QUERYS GESTOR DE CORREOS ELECTRONICOS ----
//-------------------------------------------

   // Consulta de opcoines dentro de items gestor de correo
   public function getMenuGestorCorreos($id)
   {
      $result=$this->adapter->query("select a.id, a.nombre, c.idM1 , a.idM2, d.idM  
                                         from c_mu3 a
                                         inner join c_mu2 c on c.id = a.idM2 
                                         inner join c_mu1 d on d.id = c.idM1 
                                         where  a.idM2 = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              

   // Consulta de acciones del proceso 
   public function getGestorAccionCorreosItem($id, $idGes)
   {
      $result=$this->adapter->query("select * 
                                       from c_gestor_o a 
                                          where a.idGes = ".$idGes." and a.idM2=".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              

   // Consulta de envio de correos segun opcion 
   public function getGestorCorreos($urlActual)
   {
      $result=$this->adapter->query("select b.nombre as nomOp, g.nombre as nomMod , b.modelo, b.controlador, b.vista,
      c.idEmp, d.email, a.nuevo, a.modificar, a.eliminar, a.aprobar       
from c_gestor_o a 
inner join c_mu3 b on b.id = a.idM3 
inner join c_gestor_c c on c.idGes = a.idGes  
inner join a_empleados d on d.id = c.idEmp 
inner join c_mu2 e on e.id = b.idM2 
inner join c_mu1 f on f.id = e.idM1
inner join c_mu g on g.id = f.idM 
            where concat( b.modelo,'/',b.controlador) like '%".$urlActual."%'" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              

//-------------------------------------------
// FIN QUERYS GESTOR DE CORREOS ELECTRONICOS ----
//-------------------------------------------

//--------------------------------------------------
// CESANTIAS, PRIMAS LIQUIDACION DEFINITiVA
//--------------------------------------------------

   // Validacion de fechas de primas para dias o novedades
   public function getFechasPrimas($id)
   {
      $result=$this->adapter->query("select 
                          # se valida las fechas de novedades diferenes a la fecha de dias para calculo de primas 
                   case when b.fechaInfoI != '0000-00-00' then # Si es diferenre para los dias se usa la fecha informativa 
                          b.fechaInfoI else a.fechaI end as fechaI,      
                   case when b.fechaInfoF != '0000-00-00' then # 
                       b.fechaInfoF else a.fechaF end as fechaF,
# se valida el mes de inicio 
case when b.fechaInfoI = '0000-00-00' then # Si es diferenre para los dias se usa la fecha informativa 
     month(b.fechaInfoI) else month(a.fechaI) end as mesI,      
case when b.fechaInfoF = '0000-00-00' then # 
     month(b.fechaInfoF) else month(a.fechaF) end as mesF                               
                   from n_nomina a 
                      inner join n_tip_calendario_d b on b.id = a.idIcal 
                    where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                

   // Dias calendarios primas y cesantias
   public function getDiasNomina($fechaF, $tipo)
   {
      $result=$this->adapter->query("select a.id , a.idCal , a.fechaI, a.fechaF,
                                  month( a.fechaI ) as mesI, month( '".$fechaF."' ) as mesC,
                                  '".$fechaF."' as fechaC, # fecha de corte para anticipo de cesantias  
                                  datediff( '".$fechaF."', a.fechaI  ) +1 as dias, 
                                    '".$fechaF."' as fechaCorte  
                                     from n_tip_calendario_d a 
                                  where a.idCal = ".$tipo." and a.estado=0 
                                    order by a.fechaI limit 1" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                              

   // Anticpos de cesantias
   public function getAntCesantias($id, $idEmp)
   {
      $result=$this->adapter->query("select sum( a.valor ) as valor, 
        sum( a.interes ) as interes 
              from n_cesantias_anticipos a 
                 inner join n_nomina b on year(b.fechaI) = year( a.fechaCorte ) 
              where b.id = ".$id." and a.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                              

   // Dias de cesantias, primas y consultas
   public function getDiasLiquidacion($idEmp)
   {
      $result=$this->adapter->query("select 

case when a.regimen = 1 then # regimen anterior
# Buscamos los dias correspondientes al primer año de ingreso-----------------------------------------------------
(select (month(ec.fechaI)-1)*30 from n_emp_contratos ec where ec.id = a.idCon ) + 
(select (30-day(ec.fechaI)) +1 from n_emp_contratos ec where ec.id = a.idCon ) +
#Buscamos los dias correspondientes a los años completos de trabajo ---------------------------------------------------------
( ( (year(a.fechaF)-1 )-(select year(ec.fechaI)+1 from n_emp_contratos ec where ec.id = a.idCon ) ) * 12 ) * 30 
#Saco los dias correspondientes al año en curso para liquidacion segun fecha ------------------------------------------------
+ ( ( (month( a.fechaF) - 1 )  * 30  ) +  day( a.fechaF ) )
else 0 
end 
as diasRegimenCesantias, 

                   a.idEmp, month(a.fechaF) as mesF, 
                                            year(a.fechaIc) as anoIc, month(a.fechaIc) as mesIc,
                   # --------------------------- dias Cesantias                          

            ( ( (month( a.fechaF) - month( case when a.fechaIngreso < a.fechaIc 
                                                   then a.fechaIc else 
                                        a.fechaIngreso end) ) * 30  ) +  day( a.fechaF ) ) 
                      - ( case when ( (a.fechaIngreso > a.fechaIc) and (day(a.fechaIngreso)>1) ) 
                                then day(a.fechaIngreso)-1 else 0 end )                   
                                          as diasCesantiasVieja , 
  case when a.fechaIngreso>a.fechaIc then 
      ( ( month( a.fechaF ) - month( a.fechaIngreso) ) * 30 ) + 
         ( ( day( case when day(a.fechaF)=31 then concat(year(a.fechaF),'-',month(a.fechaF), '-30' ) else a.fechaF end ) - day( a.fechaIngreso ) )  ) + 1
  else
      ( ( month( a.fechaF ) - month( a.fechaIc ) ) * 30 ) + 
         ( ( day( case when day(a.fechaF)=31 then concat(year(a.fechaF),'-',month(a.fechaF), '-30' ) else a.fechaF end ) - day( a.fechaIc ) )  ) + 1
  end as diasCesantias , 
                  # --------------------------- dias Primas 

  case when a.fechaIngreso>a.fechaIp then 
      ( ( month( a.fechaF ) - month( a.fechaIngreso) ) * 30 ) + 
         ( ( day( case when day(a.fechaF)=31 then concat(year(a.fechaF),'-',month(a.fechaF), '-30' ) else a.fechaF end ) - day( a.fechaIngreso ) )  ) + 1
  else
      ( ( month( a.fechaF ) - month( a.fechaIp ) ) * 30 ) + 
         ( ( day( case when day(a.fechaF)=31 then concat(year(a.fechaF),'-',month(a.fechaF), '-30' ) else a.fechaF end ) - day( a.fechaIp ) )  ) + 1
  end as diasPrimas , 

                  # --------------------------- dias Primas promedio 

  case when a.fechaIngreso>a.fechaIpN then 
      ( ( month( a.fechaF ) - month( a.fechaIngreso) ) * 30 ) + 
         ( ( day( case when day(a.fechaF)=31 then concat(year(a.fechaF),'-',month(a.fechaF), '-30' ) else a.fechaF end ) - day( a.fechaIngreso ) )  ) + 1
  else
      ( ( month( a.fechaF ) - month( a.fechaIpN ) ) * 30 ) + 
         ( ( day( case when day(a.fechaF)=31 then concat(year(a.fechaF),'-',month(a.fechaF), '-30' ) else a.fechaF end ) - day( a.fechaIpN ) )  ) + 1
  end as diasPrimasProm , 
  
            concat( year(a.fechaF) -1, '-', lpad( month(a.fechaF),2,'0'), '-', lpad( day(a.fechaF),2,'0') ) as fechaAntCesaI, # Fecha de un año atras para calculo de cesantias 

            ( select ec.id from n_emp_contratos ec where ec.id = a.idCon ) as idConTrato,

             # Si el contrato es menor al año antes del retiro
          case when ( select ec.fechaI from n_emp_contratos ec where ec.id = a.idCon ) <  ( concat( year(a.fechaF) -1, '-', lpad( month(a.fechaF),2,'0'), '-', lpad( day(a.fechaF),2,'0') ) ) then # si la efcha inicio fin de contrato es menor al ano ant de retiro
             
             ( concat( year(a.fechaF) -1, '-', lpad( ( month(a.fechaF))  ,2,'0'), '-', lpad( case when day(a.fechaF) > 15 then 15 else 1 end ,2,'0')  ) )

          else # Si es mayor toma la fecha de inicio del contrato 

         ( select ( concat( year(ec.fechaI) , '-', lpad( month(ec.fechaI),2,'0'), '-', lpad( case when day(ec.fechaI) > 15 then 15 else 1 end ,2,'0') ) ) 
              from n_emp_contratos ec where ec.id = a.idCon )   

       end as fechaInicioConsulta,

             # Si el contrato es menor al año antes del retiro
          case when ( select ec.fechaI from n_emp_contratos ec where ec.id = a.idCon ) <  ( concat( year(a.fechaF) -1, '-', lpad( month(a.fechaF),2,'0'), '-', lpad( day(a.fechaF),2,'0') ) ) then # si la efcha inicio fin de contrato es menor al ano ant de retiro
             
             ( concat( year(a.fechaF) -1, '-', lpad( ( month(a.fechaF))  ,2,'0'), '-', lpad( day(a.fechaF) ,2,'0')  ) )

          else # Si es mayor toma la fecha de inicio del contrato 

         ( select ( concat( year(ec.fechaI) , '-', lpad( month(ec.fechaI),2,'0'), '-', lpad( day(ec.fechaI),2,'0') ) ) 
              from n_emp_contratos ec where ec.id = a.idCon)   

       end as fechaInicioConsultaR           
                                         from n_nomina_l a 
                                            inner join a_empleados b on b.id = a.idEmp
                                        where a.idNom = 0 and b.id = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                              

   // Dias de vacaciones, promedios 
   public function getDiasLiquidacion2($idEmp)
   {
      $result=$this->adapter->query('Select 
                                            
( ( ( 12-(month(k.fechaIconsultaR ) )) * 30  ) + 
( (30-day(k.fechaIconsultaR)) +1  ) + 
#Buscamos los dias correspondientes a los años completos de trabajo -------------------------------------------------------- 
( ( (year(k.fechaF) )-( year(k.fechaIconsultaR)+1 ) ) * 12 ) * 30 + 
( ( (month( k.fechaF) - 1 )  * 30  ) +  day( k.fechaF ) ) ) 
as diasPromVac                                  
                         from n_nomina_l k 
                           where k.idNom = 0 and k.idEmp = '.$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                                 

//--------------------------------------------------
// FIN CESANTIAS, PRIMAS LIQUIDACION DEFINITiVA
//--------------------------------------------------

//--------------------------------------------------
// PROGRAMACION DE TURNOS
//--------------------------------------------------
   //Nombres de supervisores activos
   public function getSupervisoresNombresActivos()
   {
     
      $result=$this->adapter->query("select a.id,concat(nombre,' ',apellido) as nomComp
        from n_supervisores a
           inner join a_empleados b on b.id=a.idEmp 
        where a.estado=0",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }     

   //Nombres de supervisores activos
   public function getTurnos($con)
   {
     
      $result=$this->adapter->query("select * from n_turnos_g ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }     

   // Horarios
   public function getHorarios($con)
   {
     
      $result=$this->adapter->query("select * from n_horarios ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }     

   // Horarios por turnos 
   public function getTurnoHorarios($id)
   {
     
      $result=$this->adapter->query("Select a.*, concat( ltrim(b.nombre) , ' (', ltrim(b.codigo) , ')'  ) as nombre 
                                         from n_turnos_g_h a
                                               inner join n_horarios b on b.id = a.idHor 
                               where a.idTur = ".$id." order by a.orden" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }        

   //Nombres de supervisores activos
   public function getTurnosPrograma($idSup, $idPues, $ano, $mes)
   {
      $conSup = ''; 
      if ($idSup > 0)
          $conSup = " and dd.idSup = ".$idSup;

      $conPues = '';   
      if ($idPues >0)          
          $conPues = " and cc.id = ".$idPues;
     
      $result=$this->adapter->query("
select a.id as idEmpM, upper(a.nombre) as nombre, upper(a.apellido) as apellido, b.*   
              ,f.codigo as nom1
              ,g.codigo as nom2
              ,h.codigo as nom3
              ,i.codigo as nom4
              ,j.codigo as nom5
              ,k.codigo as nom6
              ,l.codigo as nom7
              ,m.codigo as nom8
              ,n.codigo as nom9
              ,o.codigo as nom10
              ,p.codigo as nom11
              ,q.codigo as nom12
              ,r.codigo as nom13
              ,s.codigo as nom14
              ,t.codigo as nom15
              ,u.codigo as nom16
              ,v.codigo as nom17
              ,w.codigo as nom18
              ,x.codigo as nom19
              ,z.codigo as nom20
              ,za.codigo as nom21
              ,zb.codigo as nom22
              ,zc.codigo as nom23
              ,zd.codigo as nom24
              ,ze.codigo as nom25
              ,zf.codigo as nom26
              ,zg.codigo as nom27
              ,zh.codigo as nom28
              ,zi.codigo as nom29
              ,zj.codigo as nom30
              ,zk.codigo as nom31,
            ( case when year( con.fechaI ) != per.ano then 
            0
        else
          case when month( con.fechaI ) != per.mes then 
             0
         else           
                 day( con.fechaI ) 
              end  
            end ) as diaConIni,
            ( case when year( con.fechaF ) != per.ano then 
            31
        else
          case when month( con.fechaF) != per.mes then 
             31 
         else           
                 day( con.fechaF ) 
              end  
            end ) as diaConFin   
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_e e on e.id = bb.idIproy 
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_emp_contratos con on con.idEmp = a.id and con.tipo = 1 
        inner join n_nov_prog_p per on per.id = 1         
                left join n_nov_prog b on b.idEmp = a.id and b.fecha = concat( '".$ano."','-', lpad( ".$mes.",2,'0' ), '-01' )    
                left join n_horarios f on f.id=t1
                left join n_horarios g on g.id=t2
                left join n_horarios h on h.id=t3                
                left join n_horarios i on i.id=t4
                left join n_horarios j on j.id=t5
                left join n_horarios k on k.id=t6
                left join n_horarios l on l.id=t7
                left join n_horarios m on m.id=t8
                left join n_horarios n on n.id=t9
                left join n_horarios o  on o.id=t10
                left join n_horarios p on p.id=t11
                left join n_horarios q on q.id=t12
                left join n_horarios r on r.id=t13
                left join n_horarios s on s.id=t14
                left join n_horarios t on t.id=t15
                left join n_horarios u on u.id=t16
                left join n_horarios v  on v.id=t17
                left join n_horarios w on w.id=t18
                left join n_horarios x on x.id=t19
                left join n_horarios z on z.id=t20
                left join n_horarios za on za.id=t21
                left join n_horarios zb on zb.id=t22
                left join n_horarios zc on zc.id=t23
                left join n_horarios zd on zd.id=t24
                left join n_horarios ze on ze.id=t25
                left join n_horarios zf on zf.id=t26
                left join n_horarios zg on zg.id=t27
                left join n_horarios zh on zh.id=t28
                left join n_horarios zi on zi.id=t29
                left join n_horarios zj on zj.id=t30
                left join n_horarios zk on zk.id=t31
         where a.estado = 0 and bb.estado = 0 and e.relevante = 0 and dd.valor = 1 ".$conSup." ".$conPues."
            order by a.nombre, a.apellido",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }
   //Nombres de supervisores activos
   public function getTurnosProgramaAnt($idSup, $idPues, $ano, $mes)
   {
      $conSup = ''; 
      if ($idSup > 0)
          $conSup = " and dd.idSup = ".$idSup;

      $conPues = '';   
      if ($idPues >0)          
          $conPues = " and cc.id = ".$idPues;
     
      $result=$this->adapter->query("
select a.id as idEmpM, upper(a.nombre) as nombre, upper(a.apellido) as apellido, b.*   
              ,f.codigo as nom1
              ,g.codigo as nom2
              ,h.codigo as nom3
              ,i.codigo as nom4
              ,j.codigo as nom5
              ,k.codigo as nom6
              ,l.codigo as nom7
              ,m.codigo as nom8
              ,n.codigo as nom9
              ,o.codigo as nom10
              ,p.codigo as nom11
              ,q.codigo as nom12
              ,r.codigo as nom13
              ,s.codigo as nom14
              ,t.codigo as nom15
              ,u.codigo as nom16
              ,v.codigo as nom17
              ,w.codigo as nom18
              ,x.codigo as nom19
              ,z.codigo as nom20
              ,za.codigo as nom21
              ,zb.codigo as nom22
              ,zc.codigo as nom23
              ,zd.codigo as nom24
              ,ze.codigo as nom25
              ,zf.codigo as nom26
              ,zg.codigo as nom27
              ,zh.codigo as nom28
              ,zi.codigo as nom29
              ,zj.codigo as nom30
              ,zk.codigo as nom31,
            ( case when year( 2017 ) != per.ano then 
            0
        else
          case when month( 6 ) != per.mes then 
             0
         else           
                 day( 1 ) 
              end  
            end ) as diaConIni,
            ( case when year( 2017 ) != per.ano then 
            31
        else
          case when month( 6 ) != per.mes then 
             31 
         else           
                 day( 30 ) 
              end  
            end ) as diaConFin   
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_e e on e.id = bb.idIproy 
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_nov_prog_p per on per.id = 1         
                left join n_nov_prog b on b.idEmp = a.id and b.fecha = concat( '".$ano."','-', lpad( ".$mes.",2,'0' ), '-01' )    
                left join n_horarios f on f.id=t1
                left join n_horarios g on g.id=t2
                left join n_horarios h on h.id=t3                
                left join n_horarios i on i.id=t4
                left join n_horarios j on j.id=t5
                left join n_horarios k on k.id=t6
                left join n_horarios l on l.id=t7
                left join n_horarios m on m.id=t8
                left join n_horarios n on n.id=t9
                left join n_horarios o  on o.id=t10
                left join n_horarios p on p.id=t11
                left join n_horarios q on q.id=t12
                left join n_horarios r on r.id=t13
                left join n_horarios s on s.id=t14
                left join n_horarios t on t.id=t15
                left join n_horarios u on u.id=t16
                left join n_horarios v  on v.id=t17
                left join n_horarios w on w.id=t18
                left join n_horarios x on x.id=t19
                left join n_horarios z on z.id=t20
                left join n_horarios za on za.id=t21
                left join n_horarios zb on zb.id=t22
                left join n_horarios zc on zc.id=t23
                left join n_horarios zd on zd.id=t24
                left join n_horarios ze on ze.id=t25
                left join n_horarios zf on zf.id=t26
                left join n_horarios zg on zg.id=t27
                left join n_horarios zh on zh.id=t28
                left join n_horarios zi on zi.id=t29
                left join n_horarios zj on zj.id=t30
                left join n_horarios zk on zk.id=t31
         where bb.estado = 0 and e.relevante = 0 and dd.valor = 1 ".$conSup." ".$conPues."
            order by a.nombre, a.apellido",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }        
   //Incapacidades empelados en programacion
   public function getIncaPrograma($idSup, $mes)
   {
     
      $result=$this->adapter->query("select  distinct e.id, e.idEmp , day( e.fechai ) as diaI, day( e.fechaf ) as diaF 
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_incapacidades e on e.idEmp = bb.idEmp and year(e.fechai) = year( now() ) and month( e.fechai ) = ".$mes."    
     where a.estado = 0 and dd.idSup = ".$idSup." 

union all 

select e.id, e.idEmp , 1 as diaI, day( e.fechaf ) as diaF 
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_incapacidades e on e.idEmp = bb.idEmp 
         and year(e.fechaf) = year( now() ) and month( e.fechaf ) = ".$mes."     
       and month( e.fechai ) < month( e.fechaf )      
     where a.estado = 0 and dd.idSup = ".$idSup     ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }        
   //Incapacidades empelados en programacion
   public function getIncaProgramaPro($idSup, $mes)
   {
     
      $result=$this->adapter->query("select e.idEmp , day( ee.fechai ) as diaI, day( ee.fechaf ) as diaF 
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_incapacidades_pro ee on ee.idEmp = bb.idEmp and year(ee.fechai) = year( now() ) and month( ee.fechai ) =  ".$mes."        
        inner join n_incapacidades e on e.id = ee.idInc       
     where a.estado = 0 and dd.idSup = ".$idSup,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }            
   //Ausentismos empelados en programacion
   public function getAusPrograma($idSup, $mes)
   {
     
      $result=$this->adapter->query("select e.idEmp , day( e.fechai ) as diaI, day( e.fechaf ) as diaF 
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_ausentismos e on e.idEmp = bb.idEmp and year(e.fechai) = year( now() ) and month( e.fechai ) = ".$mes."   
     where a.estado = 0 and a.activo = 0 and dd.idSup = ".$idSup." 

union all 

select e.idEmp , day( e.fechai ) as diaI, day( e.fechaf ) as diaF 
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_ausentismos e on e.idEmp = bb.idEmp and year(e.fechai) = year( now() ) and month( e.fechai ) = ".$mes."  
        and month( e.fechai ) < month( e.fechaf )  
     where a.estado = 0 and dd.idSup = ".$idSup,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }            
   // Listado de conceptos fijos 
   public function getConcetosFijos($con)
   {
      $result=$this->adapter->query("select a.* , a.nombre as nomFiltro 
                                       from n_conceptos a
                                         inner join n_conceptos_hor b on b.idConc = a.id 
                                       where a.id>0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                           
   //Vacaciones empelados en programacion
   public function getVacPrograma($idSup, $mes)
   {
     
      $result=$this->adapter->query("select e.idEmp , day( e.fechai ) as diaI, day( e.fechaf ) as diaF 
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_vacaciones e on e.idEmp = bb.idEmp and year(e.fechaI) = year( now() ) 
     where a.estado = 0 and dd.idSup = ".$idSup,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }
   //Reemplazos empelados en programacion
   public function getRemPrograma($idSup, $mes)
   {
     
      $result=$this->adapter->query("select a.* 
                                from n_nov_prog_r a 
                              where a.idSup = ".$idSup,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }    
   // Reemplazo en turno
   public function getReemTurnosPrograma($idSup, $dia)
   {
     
      $result=$this->adapter->query("select a.id as idEmpM, lower(a.nombre) as nombre, lower(a.apellido) as apellido,
  c.nombre as horario    
      from a_empleados a 
        inner join n_proyectos_ep bb on bb.idEmp = a.id # Empleados en proyectos
        inner join n_proyectos_p cc on cc.id = bb.idPtra # Puestos de trabajo 
        inner join n_supervisores_p dd on dd.idPues = cc.id # Puestos por supervisores       
        inner join n_nov_prog b on b.idEmp = a.id  
        inner join n_horarios c on c.id = b.t".$dia." 
    where a.estado = 0 and a.activo = 0 and dd.idSup != ".$idSup,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                    
//--------------------------------------------------
// FIN PROGRAMACION DE TURNOS
//--------------------------------------------------

   // Documentos especiales 
   public function getDocuEsp($con)
   {
      $result=$this->adapter->query("select * from t_tip_doc a  ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                                
   // Sucursales
   public function getSucursales($con)
   {
      $result=$this->adapter->query("select * from n_sucursal ".$con." order by nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;

   }                                                           
   // Volantes de pago nomina
   public function getVolantes($id)
   {
      $result=$this->adapter->query("select a.fechaI , a.fechaF, b.nombre as nomTnom, 
concat( case month(a.fechaF) when 9 then 'SEPTIEMBRE' end, ' DEL ', year(a.fechaF) )as titulo, 
c.idEmp , d.CedEmp , d.nombre , d.apellido, LPAD(trim(LEFT(e.id,10)),4, '0') as idCcos, e.nombre as nomCcos, 
f.nombre as nomCar, c.sueldo, # ----------------------------  Datos del empleado 
c.dias , LPAD(trim(LEFT(h.codigo,10)),3, '0') as codCon, h.nombre as nomCon, 
case when g.idConc = 133 then # Vacaciones 
  concat( h.nombre , ' DEL (', i.fechaI ,' AL ', i.fechaF  ,')', ' - ', c.diasVac, ' DIAS'  ) 
else
   case when g.detalle = '' then h.nombre else g.detalle end 
end    
as detalle,
 sum( case when g.idInc > 0 then 0 else g.horas end ) as horas , h.horDia , sum( g.devengado ) as devengado , sum( g.deducido ) as deducido,  # Datos valores nomina
# --------- CASOS ESPECIALES 
c.actVac, c.idVac, i.fechaI as fecVaI, i.fechaF as fecVaF ,c.diasVac, # ------Vacaciones 
c.contra, '' as fecConI , '' as fecConF,  # ----------------------------------Contratos,
sum( g.saldoPact ) as saldoPact, sum( g.saldoPact - g.deducido  ) as saldo, g.idInc, 
g.idProy , ( select pr.nombre  from n_proyectos pr where pr.id = g.idProy  ) as nomProy, # ----------------- Proyectos 
d.numCuenta 
from n_nomina a
inner join n_tip_nom b on b.id = a.idTnom 
inner join n_nomina_e c on c.idNom = a.id # Datos de los empleados 
inner join a_empleados d on d.id = c.idEmp 
inner join n_cencostos e on e.id = d.idCcos 
inner join t_cargos f on f.id = d.idCar 
inner join n_nomina_e_d g on g.idInom = c.id # Datos de los conceptos de nomina
inner join n_conceptos h on h.id = g.idConc  and h.info = 0  
left join n_vacaciones i on i.id = c.idVac # Dato de vacaciones 
where c.id = ".$id."  and g.idConc != 213 and ( g.devengado > 0 or  g.deducido > 0) 
group by c.idEmp , h.tipo, ( case when g.detalle = '' then h.nombre else g.detalle end ) , g.fechaEje , g.idProy 
order by e.id, CAST(  d.CedEmp AS UNSIGNED) , h.tipo, CAST(  h.codigo AS UNSIGNED)

" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;

   } 
   // --- MANEJO DE LABORES ----

   // Tipos de labores 
   public function getTlabores($con)
   {
      $result=$this->adapter->query("select * from n_tip_labor where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Unidades de medida
   public function getUnidades($con)
   {
      $result=$this->adapter->query("select * from n_unidades where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // Labores a liquidar
   public function getLabores($con)
   {
      $result=$this->adapter->query("select * from n_labores where estado=0 ".$con ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                                          
   // FIN MANEJO DE LABORES -----                                                             

// BIENESTAR LABORAL 

public function getVocacionFiltro($con)
   {
       $result=$this->adapter->query("select * , nombre as nomFiltro 
                                       from t_vocacion ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
   
public function getTipViviendaFiltro($con)
   {
       $result=$this->adapter->query("select * , nombre as nomFiltro 
                                       from t_tip_vivienda ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
public function getDiscapasidadesFiltro($con)
   {
       $result=$this->adapter->query("select * , nombre as nomFiltro 
                                       from t_tip_limitacion_fis",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }   
 // Listado de familiares por la hoja de vida. Se muestra en binestar luego de que el empleado a sido  aprobado.
public function getEmpFamiliaresHojaVidaCntra($id)
{
  $result=$this->adapter->query("select a.id, a.nombres, a.apellidos,
a.parentesco, case when  a.sexo =1 then 'Masculino' else 'Femenino' end  as sexo, a.fechaNac, a.instituto, a.lentes, b.nombre as nivEstu,case when a.limFisica = 1 then c.nombre else 'Ninguna' end as limFsic, d.nombre as vocacion,
a.instituto, (YEAR(CURRENT_DATE) - YEAR(fechaNac)) - (RIGHT(CURRENT_DATE,5) < RIGHT(fechaNac,5)) AS edad  
                                       from t_hoja_vida_f a
                                       inner join t_nivel_estudios b on b.id =  a.idNest 
                                       inner join t_tip_limitacion_fis c on c.id = a.idLimFis
                                       inner join t_vocacion d on d.id = a.idVoc
                          inner join t_hoja_vida e on e.id = a.idHoj
                            inner join a_empleados f on f.IdHoj = e.id
                          
                           where f.id =".$id.
                                 "  and e.estEmp = 0  order by (YEAR(CURRENT_DATE) - YEAR(fechaNac)) - (RIGHT(CURRENT_DATE,5) < RIGHT(fechaNac,5))" ,Adapter::QUERY_MODE_EXECUTE);


      $datos=$result->toArray();
      return $datos;
   } 
   
  //traer todos los controles de mujeres embarazadas relacionadas 
  //un empleado.
  public function getConyugesEmpEmbarazada($id)
  {
     $result=$this->adapter->query("
  select b.numHijo,b.fecha ,
case when b.sexo=1 then 'Hombre' else 'Mujer' end as sexo,  b.fecProp, c.nombres, c.apellidos
 from a_empleados a

inner join a_empleados_mc b on b.idEmp = a.id
inner join a_empleados_f c on c.id = b.idEmpCnyu 
 where b.idEmp =".$id,Adapter::QUERY_MODE_EXECUTE);
  $datos=$result->toArray();
    return $datos;
         
  }  
/*Traer todos los controles relacionados con una empleada*/
public function getControlMujerEmbarazada($id)
{
     $result=$this->adapter->query("
select b.numHijo,b.fecha ,
case when b.sexo=1 then 'Hombre' else 'Mujer' end as sexo,  b.fecProp
 from a_empleados a

inner join a_empleados_me b on b.idEmp = a.id
where b.idEmp= ".$id,Adapter::QUERY_MODE_EXECUTE);
  $datos=$result->toArray();
    return $datos;
         
} 

public function getConyugesEmp($id)
  {
     $result=$this->adapter->query("select * from a_empleados_f  a 
where a.idEmp =".$id." and sexo = 2",Adapter::QUERY_MODE_EXECUTE);
  $datos=$result->toArray();
    return $datos;
         
  }

      //tipos de convenios
     public function getConvenios()
   {
      $result=$this->adapter->query("select * from t_tip_convenios" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
     public function getVocacion()
   {
      $result=$this->adapter->query("select * from t_vocacion",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   //Tipo de limitacion 
     public function getLimitacionFisica()
   {
      $result=$this->adapter->query("select * from t_tip_limitacion_fis",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
     public function getTipoGrado()
   {
      $result=$this->adapter->query("select * from t_tip_grado",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 

   //Concepto personal
     public function getConceptoPer()
   {
      $result=$this->adapter->query("select * from t_conceptos_p" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
     /*Concepto economico*/
     public function getConceptoEconomico()
   {
      $result=$this->adapter->query("select * from t_concepto_e" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }   
      /*Concepto social*/
     public function getConceptoSocial()
   {
      $result=$this->adapter->query("select * from t_conceptos_s" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
   /*Tipo de familia */ 
   public function getTipFamilia()
   {
      $result=$this->adapter->query("select * from t_tip_familia" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
   /*Tipo de vivienda */ 
   public function getEstrap()
   {
      $result=$this->adapter->query("select * from t_estrap" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
     /*proyectos imediatos */ 
   public function getTipProyectos()
   {
      $result=$this->adapter->query("select * from t_tip_proyecto" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }  
     /*Ralaciones familiares */ 
      public function getTipRelacion()
   {
      $result=$this->adapter->query("select * from t_tip_relacion_p" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   /*Tipo de vivienda*/  
    public function getTipVivienda()
   {
      $result=$this->adapter->query("select * from t_tip_vivienda" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 
   /*Tipo de vivienda*/  
    public function getConVivienda()
   {
      $result=$this->adapter->query("select * from t_convivienda" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 

     /*Cargar de todos los datos de pestasñas relacionados
     con el mismo id de un empleado*/
 public function getContainerLoad($id)

    {

      $result=$this->adapter->query("select b.id, a.idNumPer as numPer,a.conEntrev as conEnt,
         a.comentario1, a.comentario2,a.idConPer ,a.idConEc,a.conViv,a.conAmb,a.idConSoc,
           h.numHijo, h.sexo,h.fecProp,
           h.historial, h.fecha, i.idTipConv as tipCon, i.entidad, i.valor, i.comentario as comen,j.fecNov,
           j.comentario, j.nombre, j.apellido
           from a_empleados_vs  a
       inner join a_empleados b on b.id = a.idEmp
      
       inner join a_empleados_me h on h.idEmp = b.id
       inner join a_empleados_conv i on i.idEmp = b.id
       inner join a_empleados_d j on j.idEmp = b.id
       where b.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      //$datos=$result->toArray();
      $datos = $result->current();
      return $datos;
   }    

    /*Es una funcion de producto segundario, ya que carga dados luego de que en el modal
    Valoracion Socio Familiar los agrege */
    public function getDatosValoracionModal($id)
   {
      $result=$this->adapter->query("select d.nombre as tipFam, c.nombre tipViv,a.id, a.idNumPer as numePer
, e.nombre , a.conEntrev as conEntrv,j.nombre as conEco, a.ingAdi as ingreso, a.conAmb as conAmbiental,
 f.nombre as proyectos, a.conOperativo as conOper,h.nombre as conPers, i.nombre as
 conSoc, a.conViv as conViv
, a.comentario1, e.nombre  as estrato,  g.nombre as tipoRel, a.comentario2
  from a_empleados_vs  a
       inner join a_empleados b on b.id = a.idEmp
        inner join t_tip_vivienda c on c.id = a.idTipViv
       inner join t_tip_familia d on d.id = a.idTipFam
       inner join t_estrap e on e.id = a.idEstrap 
       inner join t_tip_proyecto f on f.id = a.idPro
       inner join t_tip_relacion_p g on g.id = a.idTipRel
       inner join t_conceptos_p h on h.id = a.idConPer
       inner join  t_conceptos_s i on i.id = a.idConSoc
       inner join  t_concepto_e j on j.id = a.idConEc
  
      
       where a.idEmp=
       ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }

   // DATOS DE LOS RIESGOS 
  //Carga de recomendaciones 
  public function getDatosRecomendaRiesgos($id)
  {
    $result=$this->adapter->query("select b.id, d.id as idEmp, b.actividad, b.fechaFin, b.indicadores,d.CedEmp, d.nombre, d.apellido , 
case b.tipo when 1 then 'ELIMINACACION' 
            when 2 then 'SUSTITUCION'  
            when 3 then 'CONTROL DE INGENIERIA'             
            when 4 then 'CONTROL ADMINISTRATIVO'                         
            when 5 then 'ELEMENTOS DE PROTECCION PERSONAL' 
            when 6 then 'SEÑALIZACION'  end as tipoCon        
  from t_riesgos a 
      inner join t_riesgos_a b on a.id = b.idRies
      left join t_riesgos_a_e c on c.idIries = b.id
      left join a_empleados d on d.id = c.idEmp 
   where a.id =".$id." order by b.id,  d.nombre ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
  }       
  //Carga de controles de riesgo.
  public function getDatosControlesRiesgos($id)
  {
    $result=$this->adapter->query("select c.id, c.actividad, c.fechaFin, c.indicadores,  
case c.tipo when 1 then 'INGENIERIA' 
            when 2 then 'SEÑALIZACION'  
            when 3 then 'ELEMENTOS DE PROTECCION PERSONAL'             
            when 4 then 'CAPACITACION'                         
            when 5 then 'MONITOREO' 
            when 6 then 'ESTANDARIZACION'                         
            when 7 then 'PROCEDIMIENDO' 
            when 8 then 'OBSERVACION'                                 
        end as tipoCon,
    case when ( c.tipo = 1 or c.tipo = 2 ) then 'FUENTE' else 
       case when ( c.tipo = 3 or c.tipo = 4 or c.tipo = 5 ) then 'PERSONA' 
              else 'ADMINISTRATIVO' end end as aplicacion                  
  from t_riesgos a 
      inner join t_riesgos_c c on c.idRies = a.id
   where a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
  }         
    /*Factores de riesgos, carga de todos los registros.*/
  public function getFactoresRiesgos()
  {
      $result=$this->adapter->query("select * from t_factores_riesgos",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
  }    

 /*buesquedas especificas*/
  public function getFactoresRiesgosId($id)
  {
        $result=$this->adapter->query("select  c.id , c.nombre
         from  t_riesgos a
inner join t_riesgos_f b on b.idRies = a.id
inner join t_factores_riesgos c on c.id = b.idFries
where a.id =".$id,Adapter::QUERY_MODE_EXECUTE);
        $datos=$result->toArray();
        return $datos;
 }    
     /*buesquedas de procesos*/
 public function getProcesosRiesgos()
 {
    $result=$this->adapter->query("select * from t_procesos",Adapter::QUERY_MODE_EXECUTE);
    $datos=$result->toArray();
    return $datos;
 }   
     
//procesos acociado al los  riesgos.
  public function getProcesosRiesgosId($id)
  {
       $result=$this->adapter->query("select a.id, a.nombre
       from  t_procesos a 
       inner join t_riesgos_p  b on b.idPro = a.id
       inner join t_riesgos c on c.id = b.idRies  
       where b.idRies =".$id,Adapter::QUERY_MODE_EXECUTE);
       $datos=$result->toArray();
        return $datos;
  }    

//procesos acociado al los  riesgos.
  public function getAreasRiesgosId($id)
  {
       $result=$this->adapter->query("select a.id, a.nombre
       from t_areas a 
         inner join t_riesgos_p b on b.idArea = a.id
         inner join t_riesgos c on c.id = b.idRies     
       where b.idRies =".$id,Adapter::QUERY_MODE_EXECUTE);
       $datos=$result->toArray();
        return $datos;
  }    

//Cargar los reponsables.
  public function getResponsablesActividadesGeneral()
  {
        $result=$this->adapter->query("select b.id, d.id as idEmp, b.actividad, b.fechaFin, b.indicadores,d.CedEmp, d.nombre, d.apellido 
      from t_riesgos a 
      inner join t_riesgos_a b on a.id = b.idRies
      inner join t_riesgos_a_e c on c.idIries = b.id
      inner join a_empleados d on d.id = c.idEmp 
        ",Adapter::QUERY_MODE_EXECUTE);
        $datos=$result->toArray();
      return $datos;
      
  } 
  //Vista lista de riesgos.
  public function getResponsablesActividades($id)
  {
        $result=$this->adapter->query("select  d.id , d.nombre, d.apellido 
      from t_riesgos a 
      inner join t_riesgos_a b on a.id = b.idRies
      inner join t_riesgos_a_e c on c.idIries = b.id
      inner join a_empleados d on d.id = c.idEmp
      where b.id=
        ".$id,Adapter::QUERY_MODE_EXECUTE);
        $datos=$result->toArray();
      return $datos;
      
  } 
  //Esto va en la vista listi de riesactividades.
 
  public function getActividadesRealizadas($id)
  {
        $result=$this->adapter->query("select a.id, a.actividad, a.fechaFin, d.id as idEmp,
      d.nombre, d.apellido
      from t_riesgos_a a
     inner join  t_actividad_accion  b on b.idIries = a.id
     inner join  t_actividad_accion_e c on c.idAct = b.id
     inner join  a_empleados d on d.id = c.idEmp 
     where  a.id = ".$id." order by a.actividad ",Adapter::QUERY_MODE_EXECUTE);
        $datos=$result->toArray();
      return $datos;
      
  } 
  // Listado de actividades
  public function getActividadesListado()
  {
     $result=$this->adapter->query("select * from t_actividades order by id desc",Adapter::QUERY_MODE_EXECUTE);
     $datos=$result->toArray();
    return $datos;
      
  } 
  // Peligros 
  public function getPeligros()
  {
     $result=$this->adapter->query("select * from t_tip_peligros order by id desc",Adapter::QUERY_MODE_EXECUTE);
     $datos=$result->toArray();
    return $datos;      
  } 
  // Listado de efectos  
  public function getEfectosListado()
  {
     $result=$this->adapter->query("select * from t_efectos order by id desc",Adapter::QUERY_MODE_EXECUTE);
     $datos=$result->toArray();
    return $datos;
      
  } 
  public function getActividades()
  {
     $result=$this->adapter->query(" select a.id, a.actividad,a.fechaFin, a.indicadores,
    (  round( ( DATEDIFF( now() , a.fechaFin ) ) / 1 , 0 ))  as diasTrans,
case a.tipo when 1 then 'ELIMINACACION' 
            when 2 then 'SUSTITUCION'  
            when 3 then 'CONTROL DE INGENIERIA'             
            when 4 then 'CONTROL ADMINISTRATIVO'                         
            when 5 then 'ELEMENTOS DE PROTECCION PERSONAL' end as tipo 
              
     from t_riesgos_a a 
    ",Adapter::QUERY_MODE_EXECUTE);
     $datos=$result->toArray();
    return $datos;
      
  } 

 //Cargar los reponsables con id.
  public function getResponsablesComites($id)
  {
        $result=$this->adapter->query("select c.id, c.CedEmp, c.nombre, c.apellido,c.imagen, d.nombre as nomCar, d.id as idCar, c.idCar
        from t_comite a
        inner join t_comite_e b on b.idCom = a.id
        inner join a_empleados c on c.id = b.idEmp
        inner join t_cargos d on d.id = c.idCar 
        where b.idCom =".$id,Adapter::QUERY_MODE_EXECUTE);
        $datos=$result->toArray();
      return $datos;
      
  }   

 
 //Obtener comites.
  public function getComites()
  {
     $result=$this->adapter->query(" select a.id, a.nombre,
  case when a.tipo=0 then 'Copasst'
  else 'Comité de convivencia' end as tipo 
  from   t_comite a 
    ",Adapter::QUERY_MODE_EXECUTE);
     $datos=$result->toArray();
    return $datos;
      
  } 
  
  //Obtener datos de mujeres enbarazadas.
  public function getConMujeres($id)
  {
     $result=$this->adapter->query("select a.numHijo, a.sexo, a.fecProp, a.historial, a.fecha
  from a_empleados_me a 
  inner join a_empleados b on a.idEmp = b.id
  where b.id =".$id,Adapter::QUERY_MODE_EXECUTE);
   $datos=$result->toArray();
   $datos = $result->current();
    return $datos;
         
  } 
  public function getfamiliaresDefunciones($id)
  {
     $result=$this->adapter->query("select * from a_empleados_f  a 
where a.idEmp =".$id,Adapter::QUERY_MODE_EXECUTE);
  $datos=$result->toArray();
    return $datos;
         
  }  

  //Obtener datos de defunciones.
  public function getDefunciones($id)
  {
     $result=$this->adapter->query("select  a.fecNov,  b.nombres, b.apellidos,
case when a.comentario='' then 'No hay comentario'
else  a.comentario end as comentario 
from   a_empleados_d a 
inner join a_empleados_f b on b.id = a.idFam
where a.idEmp=".$id,Adapter::QUERY_MODE_EXECUTE);
     $datos=$result->toArray();
    return $datos;
  } 

  //Obtener datos de getConvenios.
  public function getConveniosBtarLaboral($id)
  {
     $result=$this->adapter->query("select a.id, b.nombre as tipCven, a.entidad, a.valor, a.comentario as comen
from a_empleados_conv a 
inner join t_tip_convenios b  on b.id =a.idTipConv
where a.idEmp =".$id,Adapter::QUERY_MODE_EXECUTE);
  $datos=$result->toArray();
    return $datos;
         
  }   

  //Obtener datos de alertas.
  public function getAlertas($id)
  {
     $result=$this->adapter->query("select c.CedEmp, c.nombre, c.apellido, ltrim( c.email) as email  
                                            from c_alerta_sol a 
                                               inner join c_alerta_sol_d b on b.idAler = a.id 
                                               inner join a_empleados c on c.id = b.idEmp 
                                            where a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
  $datos=$result->toArray();
    return $datos;
         
  }   

  //Busqueda de hojas de vida ingresadas por web interna.
   public function getHojasVidaIngresadasWeb()
   {
      $result=$this->adapter->query("select distinct b.*, case when c.id is null then 0 else c.id end as idEmp, c.finContrato, c.estado as estEmp, c.id as idEmp,(round(datediff(now(),b.fecReg)+1 ) )as ahoraDia   
                                  from t_hoja_vida b 
                                     left join t_hoja_vida_c a on a.id=a.idHoj
                                     left join a_empleados c on c.CedEmp = b.cedula 
                                     where b.estado in ('0',1, '2') and refDoc = 1 order by b.fecReg desc",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
         
   //Busqueda de hojas de vida ingresadas por web interna.
   public function getTipsolicitud()
   {
      $result=$this->adapter->query("select * from t_solicitud_tip",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }   

    //Busqueda de hojas de vida ingresadas por web interna.
   public function getHobbies()
   {
      $result=$this->adapter->query("select * from t_tip_hobbies",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }   

     /* requisitos legales de forma general.*/
  public function getRequisitosLegales()
  {
      $result=$this->adapter->query("select a.id, d.id as idCar, b.nombre as jerarquia, a.numero, a.ano, a.titulo, a.articulo,
       a.descripcion, d.nombre
      from t_requisitos_legales a 
        inner join t_gerarquias_req b on b.id = a.idGquia
      inner join t_requisitos_legales_r c on c.idReqLeg = a.id 
      inner join t_cargos  d on d.id = c.idCar
      order by  b.nombre
      ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;
  }

      /*Jerarquias de requisitos.*/
  public function getGeraquiaRequisitos()
  {
      $result=$this->adapter->query("select * from t_gerarquias_req",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
  } 

  //LLamado para la edicion
  public function getRequisitosLegalesCurrent($id)
  {
     $result=$this->adapter->query("select a.id, d.id as idCar,a.idGquia, a.numero, a.ano, a.titulo, a.articulo,
   a.descripcion, d.nombre
from t_requisitos_legales a 
inner join t_gerarquias_req b on b.id = a.idGquia
inner join t_requisitos_legales_r c on c.idReqLeg = a.id 
inner join t_cargos  d on d.id = c.idCar
where a.id= ".$id." order by  b.nombre  ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
  }
  public function getRequisitosListaCargos($id)
  {
      $result=$this->adapter->query("select a.id, d.id as idCar, b.nombre as jerarquia, a.numero, a.ano, a.titulo, a.articulo,
   a.descripcion, d.nombre
  from t_requisitos_legales a 
      inner join t_gerarquias_req b on b.id = a.idGquia
      inner join t_requisitos_legales_r c on c.idReqLeg = a.id 
      inner join t_cargos  d on d.id = c.idCar
     where a.id= ".$id." order by  b.nombre ",Adapter::QUERY_MODE_EXECUTE);
     $datos = $result->toArray();
     return $datos;
  }   
  public function getNom2Filt($con)
  {   
          $result=$this->adapter->query("select * , id as nomFiltro 
                                       from n_nomina ",Adapter::QUERY_MODE_EXECUTE);
           $datos=$result->toArray();
          return $datos;
  }   
  public function getSupervisoresFilt()
  {
      $result=$this->adapter->query("select b.id, b.nombre as nomFiltro 
                                       from n_supervisores a
                                       inner join a_empleados b on b.id = a.idEmp
                                          order by nombre
                                          ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
  }      
   // PROGRAGRAMACION 
   public function getFormulaDias31($idEmp)
   {     
     $result=$this->adapter->query("select count( a.id ) as num 
                                 from n_nov_prog_m a
                                           inner join n_horarios_f b on b.id = a.idHfor 
                                           inner join n_nov_prog c on c.id = a.idNov 
                                  where c.idEmp = ".$idEmp." and a.dia = 31   
                                          group by b.variable",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;       
   }                          
   public function getFormulaDias($idEmp, $descanso)
   {     
     $conDesca = '';
     if ($descanso != 3)
         $conDesca = 'and a.descanso = '.$descanso;

     $result=$this->adapter->query("select count( a.id ) as num 
                                 from n_nov_prog_m a
                                           inner join n_horarios_f b on b.id = a.idHfor 
                                           inner join n_nov_prog c on c.id = a.idNov 
                                        where c.idEmp = ".$idEmp." ".$conDesca." 
                                           
                                            and a.dia != 31 
                                          group by b.variable",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;       
   }     
}    



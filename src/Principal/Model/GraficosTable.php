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

/// INDICE

// Update general
// Calificacion empe

class GraficosTable extends AbstractTableGateway
{
   protected $table  = 't_nivelasp';   
   
   public $dbAdapter;
    
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
   }
   
   // Consulta general
   public function getGeneral($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
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
// 0. ESTADISTICOS GENERALES ------

   // Sexos en la compañia
   public function getSexo()
   {
      $result=$this->adapter->query("select case when a.SexEmp = 1 
          then 'Hombres' else 'Mujeres' end as nombre , 
            sum( case when a.SexEmp=1 then 1 else 1 end ) as valor 
          from a_empleados a 
            inner join n_cencostos b on b.id = a.idCcos 
            where a.estado=0 and activo=0
            group by a.SexEmp" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Edades en la compañia
   public function getEdades()
   {
      $result=$this->adapter->query("select concat( ( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) , '0' ) as edad, # Grupo de edades
      count( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) as numero  
         from a_empleados a 
            where a.estado=0 and activo=0
            group by substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1)" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Edades en la compañia por sexo
   public function getEdadesSexo()
   {
      $result=$this->adapter->query("select
         case when a.SexEmp=1 then 'Hombres' else 'Mujeres' end as sexo, 
             concat( ( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) , '0' ) as edad, # Grupo de edades
             count( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) as numero  
         from a_empleados a 
            where a.estado=0 and activo=0
            group by a.SexEmp, substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1)" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Sexo en centro de costos
   public function getSexoCecostos()
   {
      $result=$this->adapter->query("select b.nombre , 
            sum( case when a.SexEmp=1 then 1 else 0 end ) as hombres,  
            sum( case when a.SexEmp=2 then 1 else 0 end ) as mujeres  
          from a_empleados a 
            inner join n_cencostos b on b.id = a.idCcos 
            where a.estado=0 and activo=0
          group by b.nombre order by b.nombre" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      

// 0. INCAPACIDADES ------

   // Distribucion incapacidades 
   public function getIncap()
   {
     $result=$this->adapter->query("select b.nombre, count(a.id) as valor  
       from n_incapacidades a
         inner join n_tipinc b on b.id = a.idInc
         group by b.id" ,Adapter::QUERY_MODE_EXECUTE); 
      $datos=$result->toArray();
      return $datos;
   }                               
   // Distribucion incapacidades 
   public function getIncSexo()
   {
      $result=$this->adapter->query("select case when a.SexEmp = 1 
          then 'Hombres' else 'Mujeres' end as nombre , 
            sum( case when a.SexEmp=1 then 1 else 1 end ) as valor 
          from a_empleados a 
            inner join n_incapacidades b on b.idEmp = a.id
            where a.estado=0 and activo=0 
            group by a.SexEmp" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Edades e incapacidades
   public function getIncEdades()
   {
      $result=$this->adapter->query("select
         case when a.SexEmp=1 then 'Hombres' else 'Mujeres' end as sexo, 
             concat( ( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) , '0' ) as edad, # Grupo de edades
             count( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) as numero  
         from a_empleados a 
            inner join n_incapacidades b on b.idEmp = a.id  
            where a.estado=0 and activo=0
            group by a.SexEmp, substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1)" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Linea de incapacidades de los ultimos 3 años
   public function getIncapAnos()
   {
     $result=$this->adapter->query("select num, mes, sum(ano1) as ano1, sum(ano2) as ano2, sum(ano3) as ano3, year( now() ) as ano  
from (
           select month(a.fechai) as num, case month(a.fechai) 
              when 1 then 'Enero'
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
            end as mes , 
       sum(if( year(a.fechai)=year(now()) ,  1  ,  0 )) as ano1,
       sum(if( year(a.fechai)=year(now()) - 1 ,  1  ,  0 )) as ano2,
       sum(if( year(a.fechai)=year(now()) - 2 ,  1  ,  0 )) as ano3
       from n_incapacidades a
          where a.estado = 1 and ( (year(a.fechai)=year(now())) or (year(a.fechai)=year(now())-1) or (year(a.fechai)=year(now())-3)  )  group by month(a.fechai)          
     union all    
           select month(b.fechai) as num, case month(b.fechai) 
              when 1 then 'Enero'
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
            end as mes , 
       sum(if( year(b.fechai)=year(now()) ,  1  ,  0 )) as ano1,
       sum(if( year(b.fechai)=year(now()) - 1 ,  1  ,  0 )) as ano2,
       sum(if( year(b.fechai)=year(now()) - 2 ,  1  ,  0 )) as ano3
       from n_incapacidades a
          inner join n_incapacidades_pro b on b.idInc = a.id 
          where ( (year(b.fechai)=year(now())) or (year(b.fechai)=year(now())-1) or (year(b.fechai)=year(now())-3)  )  group by month(b.fechai)                      
     union all # unir on todo el calendario vacio para armar consulta completa
        select 1 as num, 'Enero' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 2 as num, 'Febrero' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 3 as num, 'Marzo' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 4 as num, 'Abril' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 5 as num, 'Mayo' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 6 as num, 'Junio' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 7 as num, 'Julio' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 8 as num, 'Agosto' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 9 as num, 'Septiembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 10 as num, 'Octubre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 11 as num, 'Noviembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 12 as num, 'Diciembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
        ) as valor 
       group by mes 
         order by num" ,Adapter::QUERY_MODE_EXECUTE); 
      $datos=$result->toArray();
      return $datos;
   }                               
   // Grafico tipos de incapacidades y sexo 
   public function getIncTipSexo( )
   {
      $result=$this->adapter->query("select b.nombre,
            sum( case when c.SexEmp=1 then 1 else 0 end ) as hombre,
            sum( case when c.SexEmp=2 then 1 else 0 end ) as mujer 
              from n_incapacidades a
                 inner join n_tipinc b on b.id = a.idInc
                 inner join a_empleados c on c.id = a.id 
                 where a.estado=1
                 group by a.idInc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Inapacidades por centro de costos y barras
   public function getIncCcos( )
   {
      $result=$this->adapter->query("select id, nombre, sum(numInc) as numInc, sum(numPro) as numPro  from (
select c.id, c.nombre, count(a.id) as numInc, 0 as numPro 
from n_incapacidades a
inner join a_empleados b on b.id = a.idEmp
inner join n_cencostos c on c.id = b.idCcos
where a.estado=1
group by c.id 
union all
select c.id, c.nombre, 0 as numInc, count(a.id) as numPro 
from n_incapacidades a
inner join n_incapacidades_pro aa on aa.idInc = a.id
inner join a_empleados b on b.id = a.idEmp
inner join n_cencostos c on c.id = b.idCcos
group by c.id ) as valor
group by id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      


// 0. AUSENTISMOS ------
   // Distribucion ausentiemos 
   public function getAus() 
   {
     $result=$this->adapter->query("select b.nombre, count(a.id) as valor  
       from n_ausentismos a
         inner join n_tip_aus b on b.id = a.idTaus
         group by b.id" ,Adapter::QUERY_MODE_EXECUTE); 
      $datos=$result->toArray();
      return $datos;
   }                               
   // Distribucion ausentismos 
   public function getAusSexo()
   {
      $result=$this->adapter->query("select case when a.SexEmp = 1 
          then 'Hombres' else 'Mujeres' end as nombre , 
            sum( case when a.SexEmp=1 then 1 else 1 end ) as valor 
          from a_empleados a 
            inner join n_ausentismos b on b.idEmp = a.id
            group by a.SexEmp" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Edades en ausentismos
   public function getAusEdades() 
   {
      $result=$this->adapter->query("select
         case when a.SexEmp=1 then 'Hombres' else 'Mujeres' end as sexo, 
             concat( ( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) , '0' ) as edad, # Grupo de edades
             count( substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1) ) as numero  
         from a_empleados a 
            inner join n_ausentismos b on b.idEmp = a.id  
            group by a.SexEmp, substr( (round( DATEDIFF( now() , a.FecNac ) / 365 )) ,1,1)" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Linea de ausentismos de los ultimos 3 aÃ±os
   public function getAusAnos()
   {
     $result=$this->adapter->query("select num, mes, sum(ano1) as ano1, sum(ano2) as ano2, sum(ano3) as ano3, year( now() ) as ano  
from (
           select month(a.fechai) as num, case month(a.fechai) 
              when 1 then 'Enero'
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
            end as mes , 
       sum(if( year(a.fechai)=year(now()) ,  1  ,  0 )) as ano1,
       sum(if( year(a.fechai)=year(now()) - 1 ,  1  ,  0 )) as ano2,
       sum(if( year(a.fechai)=year(now()) - 2 ,  1  ,  0 )) as ano3
       from n_ausentismos a
       
       #yo quite ' n_incapacidades_pro b ' y todas sus uniones
       
          where a.estado = 1 and ( (year(a.fechai)=year(now())) or (year(a.fechai)=year(now())-1) or (year(a.fechai)=year(now())-3)  )  group by month(a.fechai)                         
     union all # unir on todo el calendario vacio para armar consulta completa
        select 1 as num, 'Enero' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 2 as num, 'Febrero' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 3 as num, 'Marzo' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 4 as num, 'Abril' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 5 as num, 'Mayo' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 6 as num, 'Junio' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 7 as num, 'Julio' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 8 as num, 'Agosto' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 9 as num, 'Septiembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 10 as num, 'Octubre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 11 as num, 'Noviembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 12 as num, 'Diciembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
        ) as valor 
       group by mes 
         order by num" ,Adapter::QUERY_MODE_EXECUTE);    
      $datos=$result->toArray();
      return $datos;
   }                               
   // Grafico tipos de ausentismos y sexo 
   public function getAusTipSexo( )
   {
      $result=$this->adapter->query("select b.nombre,
            sum( case when c.SexEmp=1 then 1 else 0 end ) as hombre,
            sum( case when c.SexEmp=2 then 1 else 0 end ) as mujer 
              from n_ausentismos a
                 inner join n_tip_aus  b on b.id = a.idTaus
                 inner join a_empleados c on c.id = a.idEmp 
                 where a.estado=1
                 group by a.idTaus" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Ausentismos por centro de costos y barras
   public function getAusCcos( )
   {
       /*"select id, nombre, sum(numInc) as numInc, sum(numPro) as numPro  from (
select c.id, c.nombre, count(a.id) as numInc, 0 as numPro 
from n_ausentismos a
inner join a_empleados b on b.id = a.idEmp
inner join n_cencostos c on c.id = b.idCcos
where a.estado=1
group by c.id 
union all
select c.id, c.nombre, 0 as numInc, count(a.id) as numPro 
from n_ausentismos a
#inner join n_incapacidades_pro aa on aa.idInc = a.id
inner join a_empleados b on b.id = a.idEmp
inner join n_cencostos c on c.id = b.idCcos
group by c.id ) as valor
group by id "*/
      $result=$this->adapter->query('select c.id, c.nombre, count(a.id) as numAus  
from n_ausentismos a
inner join a_empleados b on b.id = a.idEmp
inner join n_cencostos c on c.id = b.idCcos
where a.estado=1
group by id' ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Distribucion ausentismos
   public function getAus2()
   {
     $result=$this->adapter->query("select b.nombre, count(a.id) as valor  
       from n_ausentismos a
         inner join n_tip_aus b on b.id = a.idTaus
         group by b.id" ,Adapter::QUERY_MODE_EXECUTE); 
      $datos=$result->toArray();
      return $datos;
      
   }                              
// 0. FIN AUSENTISMOS ------   




// 1. EVALUACION ------

   // Grafico evaluacion por competencias
   public function getEvaluacionItems( $idEva )
   {
      $result=$this->adapter->query("select e.nombre,
            sum( case when c.lista >= f.media then 1 else 0 end ) as positivo,
            sum( case when (c.lista < f.media and c.lista > 1 ) then 1 else 0 end ) as medio,
            sum( case when c.lista < f.media then 1 else 0 end ) as negativo 
              from t_evaluacion a
                 inner join t_evaluacion_c_e c on c.idEva = a.id 
                 inner join t_competencias_o d on d.id = c.idIcomp 
                 inner join t_competencias e on e.id = d.idCom 
                 inner join t_objetivos f on f.id = d.idObj 
                 where a.id = ".$idEva." and c.lista > 0 
            group by e.nombre " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                      
   // Grafico evaluacion por competencias calificaciones
   public function getEvaluacionGeneral( $idEva )
   {
      $result=$this->adapter->query("select sum( case when c.lista > f.media then 1 else 0 end ) as excelente,
            sum( case when c.lista < f.media then 1 else 0 end ) as bueno,
            sum( case when c.lista = 2 then 1 else 0 end ) as insuficiente,
            sum( case when c.lista = 1 then 1 else 0 end ) as critico,
         count(d.id) as preguntas 
              from t_evaluacion a
                 inner join t_evaluacion_c_e c on c.idEva = a.id 
                 inner join t_competencias_o d on d.id = c.idIcomp 
                 inner join t_competencias e on e.id = d.idCom 
                 inner join t_objetivos f on f.id = d.idObj                              
             where a.id = ".$idEva." and c.lista > 0 # Mayor a no aplica " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;
   }                         

   // Grafico evaluacion por competencias empelados detallada
   public function getEvaluacionDetallada( $idEva  )
   {
      $result=$this->adapter->query("select sum( case when c.lista > 3 then 1 else 0 end ) as excelente,
            sum( case when c.lista < 3 then 1 else 0 end ) as bueno,
            sum( case when c.lista = 2 then 1 else 0 end ) as insuficiente,
            sum( case when c.lista = 1 then 1 else 0 end ) as critico,
         count(d.id) as preguntas, e.CedEmp, e.nombre, e.apellido  
              from t_evaluacion a
                 inner join t_evaluacion_c_e c on c.idEva = a.id 
                 inner join t_competencias_o d on d.id = c.idIcomp 
                 inner join a_empleados e on e.id = c.idEmp 
             where a.id = ".$idEva." and c.lista > 0 # Mayor a no aplica
         group by c.idEmp              
             order by ( sum( case when c.lista > 3 then 1 else 0 end ) ) desc" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
// 1. FIN EVALUACION ------
 // EVALUACIONES
      public function getContrat() 
   {
     $result=$this->adapter->query("select c.nombre, count(a.id) as valor  
       from t_lista_cheq a
         inner join t_sol_con b on a.idSol = b.id 
         inner join a_tipcon c on b.idTcon = c.id
         group by c.id" ,Adapter::QUERY_MODE_EXECUTE); 
     
     
      $datos=$result->toArray();
      return $datos;
   }
   public function getContratSexo()
   {
      
      $result=$this->adapter->query("select case when b.SexEmp = 1 
          then 'Hombres' else 'Mujeres' end as nombre , 
            sum( case when b.SexEmp=1 then 1 else 1 end ) as valor 
          from t_lista_cheq a 
            inner join t_hoja_vida b on b.id = a.idHoj
            where a.estado=0 
            #and activo=0 <-no existe esa columna
            group by b.SexEmp" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }
   public function getContratEdades() 
   {
      $result=$this->adapter->query("select
         case when b.SexEmp=1 then 'Hombres' else 'Mujeres' end as sexo, 
             concat( ( substr( (round( DATEDIFF( now() , b.FecNac ) / 365 )) ,1,1) ) , '0' ) as edad, # Grupo de edades
             count( substr( (round( DATEDIFF( now() , b.FecNac ) / 365 )) ,1,1) ) as numero  
         from t_lista_cheq a 
            inner join t_hoja_vida b on a.idHoj = b.id  
            where a.estado=0 
        #and activo=0
            group by b.SexEmp, substr( (round( DATEDIFF( now() , b.FecNac ) / 365 )) ,1,1)" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   }                         
   // Linea de incapacidades de los ultimos 3 aÃ±os
   public function getContratAnos()
   {
     $result=$this->adapter->query("select num, mes, sum(ano1) as ano1, sum(ano2) as ano2, sum(ano3) as ano3, year( now() ) as ano  
from (
           select month(a.fecDoc) as num, case month(a.fecDoc) 
              when 1 then 'Enero'
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
            end as mes , 
       sum(if( year(a.fecDoc)=year(now()) ,  1  ,  0 )) as ano1,
       sum(if( year(a.fecDoc)=year(now()) - 1 ,  1  ,  0 )) as ano2,
       sum(if( year(a.fecDoc)=year(now()) - 2 ,  1  ,  0 )) as ano3
       from t_lista_cheq a
       
       #yo quite ' n_incapacidades_pro b ' y todas sus uniones
       
          where a.estado = 1 and ( (year(a.fecDoc)=year(now())) or (year(a.fecDoc)=year(now())-1) or (year(a.fecDoc)=year(now())-3)  )  group by month(a.fecDoc)                         
     union all # unir on todo el calendario vacio para armar consulta completa
        select 1 as num, 'Enero' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 2 as num, 'Febrero' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 3 as num, 'Marzo' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 4 as num, 'Abril' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 5 as num, 'Mayo' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 6 as num, 'Junio' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 7 as num, 'Julio' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 8 as num, 'Agosto' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 9 as num, 'Septiembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 10 as num, 'Octubre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 11 as num, 'Noviembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
     union all 
        select 12 as num, 'Diciembre' as mes, 0 as ano1, 0 as ano2, 0 as ano3
        ) as valor 
       group by mes 
         order by num" ,Adapter::QUERY_MODE_EXECUTE);    
      $datos=$result->toArray();
      return $datos;
   }                               
   // Grafico tipos de incapacidades y sexo 
   public function getContratTipSexo( )
   {
       
      $result=$this->adapter->query("select c.nombre,
            sum( case when f.SexEmp=1 then 1 else 0 end ) as hombre,
            sum( case when f.SexEmp=2 then 1 else 0 end ) as mujer 
              from t_lista_cheq a
                 inner join t_sol_con  b on b.id = a.idSol
                 inner join a_tipcon c on c.id = b.idTcon 
                 INNER join t_hoja_vida f on f.id = a.idHoj
                 where a.estado=1
                 group by b.idTcon" ,Adapter::QUERY_MODE_EXECUTE); 
      $datos=$result->toArray();
      return $datos;
   }                      
   // Inapacidades por centro de costos y barras
   public function getContratCcos( )
   {
       
      $result=$this->adapter->query('select c.id, c.nombre, count(a.id) as num  
from t_lista_cheq a
inner join t_sol_con b on b.id = a.idSol
inner join n_cencostos c on c.id = b.idCcos
where a.estado=1
group by id' ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
   } 

}


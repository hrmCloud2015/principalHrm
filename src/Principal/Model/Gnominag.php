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
use Principal\Model\Paranomina; // Album de consultas
use Principal\Model\AlbumTable; // Parametros de nomina
 
/// INDICE

// Generacion de empleadoeados 
// Insertar nov automaticas ( n_nomina_e_d ) por tipos de automaticos 
// Insertar nov automaticas ( n_nomina_e_d ) por conceptos automaticos
// Insertar nov automaticas ( n_nomina_e_d ) por otros automaticos
// Consulta para recorrer nomina generada
// Documento de novedades por empleado de acuerdo al tipo 
// Generar periodos de nominas por tipos de nomina, grupos de empleados y calendario 
// ( REGISTRO DE VACACIONES ) ( n_vacaciones ) 
// Vacaciones de empleados
// Incapacidades de empleados
// EMBARGOS NOMINA ( n_nomina_e_d ) 
// ( DIAS TRABAJADOS ULTIMO PERIODO CESANTIAS ) 

class Gnominag extends AbstractTableGateway
{
   protected $table  = '';
   
   
   public $dbAdapter;
   public $salarioMinimo;
   public $horasDias;
   public $subsidioTransporte;
   public $salarioMinimoCovencional;

   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
        // Parametros de nomina para funciones de consulta 
        $pn = new Paranomina($this->adapter);
        $dp = $pn->getGeneral1(1);
        $this->salarioMinimo=ltrim($dp['formula']);   

        $dp = $pn->getGeneral1(2);
        $this->horasDias=$dp['valorNum'];

        $dp = $pn->getGeneral1(3);
        $this->salarioMinimoCovencional=$dp['formula'];// Salario minimo convencional   

        $dp = $pn->getGeneral1(11);
        $this->subsidioTransporte=$dp['valorNum']; // Subsidio de trasnporte              
   }   
   // Generacion de empleados 
   public function modGeneral($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);

   }                  
   // Listado de nominas
   public function getListNominas($con)
   {
     $result=$this->adapter->query('select a.id,a.fechaI,a.fechaF,
                                      a.idGrupo, b.nombre as nomgrup, c.nombre as nomtcale, 
                                        d.nombre as nomtnom,a.estado,a.numEmp, d.tipo,
                                        a.idTnom, a.idTnomL, f.nombre as nomTnomL, # Liquidacion final 
                                      (select count(e.idEmp) from n_nomina_e e where e.idNom = a.id ) as numEmpN, 
                                        case when (
                           select count(g.id) from n_nomina_retro g where g.idNom = a.id 
                           
                           ) is null then 0 else 1 end as idRet, a.pagada, a.archivo, a.pagoCes, a.congelada ,
                                     # ------- Numero de empleados    
                        ( select count(aa.id) 
                                from a_empleados aa 
                                  where aa.idGrup = b.id and aa.estado=0 ) as numEmpGrup,
                  #--- Numero de empleados en nomina angterior
( select count( cc.id ) 
  from n_nomina_e cc 
    where cc.idNom = ( select aa.id 
       from n_nomina aa  
           inner join n_nomina_e bb on bb.idNom = aa.id
       where aa.idTnom=a.idTnom and aa.estado =2 and aa.idGrupo = a.idGrupo 
          order by aa.id desc limit 1)  ) as numEmpAnt,
           #---- Fecha de la ultima nomina antes de esta
  ( select aa.fechaF     
       from n_nomina aa  
       where aa.idTnom=a.idTnom and aa.estado =2 and aa.idGrupo = a.idGrupo 
          order by aa.id desc limit 1) as fechaNomAnt                       

                                   from n_nomina a
                                        inner join n_grupos b on a.idGrupo=b.id 
                                        inner join n_tip_calendario c on a.idCal=c.id 
                                        inner join n_tip_nom d on d.id=a.idTnom 
                                        left join n_tip_nom f on f.id = a.idTnomL # Liquidacion final 
                                        where '.$con.' 
                                        group by a.id order by a.id desc  ',Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }

   // Auto revision de nomina
   public function getAuditoriaNomina($con)
   {
     $result=$this->adapter->query('select a.id as idNom, 
  c.CedEmp, lower(c.nombre) as nombre, lower(c.apellido) as apellido ,
  ( select case when sum( aa.devengado ) - sum( aa.deducido ) < 0 then 1 else 0 end 
        from n_nomina_e_d aa where aa.idInom = b.id ) as negativo,
case when a.idTNom = 1 then 
   case when ( b.dias + b.diasI ) != 15 then
      1 
   else
     case when a.idTNom = 8 then    
        case when ( b.dias + b.diasI ) != 30 then
          1 
        else
        0 
     end     
    end    
  end      
end   
  as diasNega  , ( b.dias + b.diasI )  as dias , b.idVac 
from n_nomina a 
 inner join n_nomina_e b on b.idNom = a.id 
 inner join a_empleados c on c.id = b.idEmp  
where a.estado in (0,1) 
group by a.id, b.id
order by idNom',Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }

   // Auto revision de nomina otros conceptos de nomina
   public function getAuditoriaNominaOtconc($con)
   {
     $result=$this->adapter->query('select 
  c.CedEmp, lower(c.nombre) as nombre, lower(c.apellido) as apellido ,

( select count( aa.id ) 
       from n_nomina_e_d aa 
         inner join n_nomina_e bb on bb.id = aa.idInom 
       where aa.idNom = a.id and bb.idEmp = b.idEmp and aa.tipo = 2 ) as numAutoNom ,   
 
 ( select count( aa.id ) 
       from n_emp_conc aa 
       where aa.idEmp = b.idEmp ) as numAuto 

from n_nomina a 
 inner join n_nomina_e b on b.idNom = a.id 
 inner join a_empleados c on c.id = b.idEmp  
where a.estado in (0,1) and 
( select count( aa.id ) 
       from n_emp_conc aa 
       where aa.idEmp = b.idEmp ) > 0  
group by a.id, b.id',Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }   

   // Verificcion del calendario con el tipo de nomina
   public function getCalendarioTipoNomina($tipo, $idGrupo)
   {
     $result=$this->adapter->query("select a.id, a.fechaI, a.fechaF, year(a.fechaF) as ano   
                                      from n_tip_calendario_d a
                                         inner join n_tip_nom b on b.idTcal = a.idCal and a.idTnom = ".$tipo." 
                                      where b.id = ".$tipo." and a.idGrupo=".$idGrupo."  
                                        and a.estado=0 order by a.fechaI limit 1",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;     
   }   
   //// --- LIQUIDACION FINAL ---- 
   // Consulta ultima periodo de nomina 
   public function getUltimaNomina($tipo, $idGrupo)
   {
     $result=$this->adapter->query("select 
case when ( (month(a.fechaF)=2) and ( day(a.fechaF) > 27 ) ) then # Si es febrero y la fecha final es casi final del mes
  concat( year( a.fechaF ) ,'-', lpad( month( a.fechaF ) +1,2,'0') , '-01'  )
else
  # Si es diferente de febrero y no el 31  
  case when  month( a.fechaF ) > 29 then 
       concat( year( a.fechaF ) ,'-', lpad( month( a.fechaF ) +1,2,'0') , '-01'  )
  else      
      DATE_ADD( a.fechaF , interval 1 day) 
  end 
end
as fechaI,
  c.idTnom, d.idTcal, # Tipo de nomina asociada a la liquidacion     
                     c.idTnomP, e.idTcal as idTcalP, # Tipo de nomina de primas asociada a la liquidacion     
                     c.idTnomC, f.idTcal as idTcalC # Tipo de nomina de cesantias asociada a la liquidacion     
                            from n_nomina a
                                inner join n_nomina_e b on b.idNom = a.id
                                left join n_tip_nom c on c.id = ".$tipo." 
                                left join n_tip_nom d on d.id = c.idTnom # Nomina de novedades  
                                left join n_tip_nom e on e.id = c.idTnomP # Nomina de primas  
                                left join n_tip_nom f on f.id = c.idTnomC # Nomina de cesantias 
                                where a.estado = 2 and a.idGrupo = ".$idGrupo."  
                                order by a.fechaF desc limit 1",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;     
   }   
   // Nomina de primas 
   public function getPrimas($fechaI, $fechaF, $idNom)
   {
     $result=$this->adapter->query("Select a.id, a.idEmp, b.idCcos,
                                  case when c.FechaI > '".$fechaI."' then 
                                       round( ( ( DATEDIFF( '".$fechaF."' , c.FechaI ) + 1 ) * 15 ) / 180,2 )
                                         else 15 
                                       end as diasPrima, b.fecIng, c.FechaI  
                                  from n_nomina_e a 
                                  inner join a_empleados b on b.id = a.idEmp 
                                  inner join n_emp_contratos c on c.idEmp = b.id  
                                  where a.idNom =".$idNom ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }   
   // Liquidacion final primas 
   public function getPrimasFinal($fechaI, $fechaF, $idNom)
   {
     $result=$this->adapter->query("Select a.id, a.idEmp, b.idCcos,
                                  case when c.fechaI > '".$fechaI."' then # Si el ingreso fuer posterior al periodo inicial de primas 
                                       case when ( select aa.fechaF from n_emp_contratos aa where aa.idEmp = b.id  order by aa.fechaF desc limit 1 ) <= '".$fechaF."' then   
                                         ( ( year(( select aa.fechaF from n_emp_contratos aa where aa.idEmp = b.id  order by aa.fechaF desc limit 1 )) - year(c.fechaI) ) * 360 ) + 
                                         ( ( month(( select aa.fechaF from n_emp_contratos aa where aa.idEmp = b.id  order by aa.fechaF desc limit 1 )) - month(c.fechaI) ) * 30 ) + 
                                         ( ( day(( select aa.fechaF from n_emp_contratos aa where aa.idEmp = b.id order by aa.fechaF desc limit 1 )) - day(c.fechaI) )  )                                          
                                       else   
                                         ( ( year('".$fechaF."') - year(c.fechaI) ) * 360 ) + 
                                         ( ( month('".$fechaF."') - month(c.fechaI) ) * 30 ) + 
                                         ( ( day('".$fechaF."') - day(c.fechaI) )  )                                                                                
                                       end 

                                   else # Ingreso antes de periok2do de primas 

                                       case when c.fechaF > '".$fechaF."' then                                      
                                         ( ( year('".$fechaF."') - year('".$fechaI."') ) * 360 ) + 
                                         ( ( month('".$fechaF."') - month('".$fechaI."') ) * 30 ) + 
                                         ( ( day('".$fechaF."') - day('".$fechaI."') )  )                                                                                 
                                       else
                                         case when c.idTcon != 2 then # diferente de contrato indefenido                                                                  # Validar si tiene un contrato vigiente mas arriba del normal 
                             case when (( select aa.fechaF from n_emp_contratos aa where aa.idEmp = b.id order by aa.fechaF desc limit 1 )>'".$fechaF."') then
                                  ( ( year('".$fechaF."') - year('".$fechaI."') ) * 360 ) + 
                                              ( ( month('".$fechaF."') - month('".$fechaI."') ) * 30 ) + 
                                              ( ( day('".$fechaF."') - day('".$fechaI."') )  )
                                    else 
                                           ( ( year(c.fechaF) - year('".$fechaI."') ) * 360 ) + 
                                           ( ( month(c.fechaF) - month('".$fechaI."') ) * 30 ) + 
                                           ( ( day(c.fechaF) - day('".$fechaI."') )  )
                                     end       
                                        else 
                                         ( ( year('".$fechaF."') - year('".$fechaI."') ) * 360 ) + 
                                         ( ( month('".$fechaF."') - month('".$fechaI."') ) * 30 ) + 
                                         ( ( day('".$fechaF."') - day('".$fechaI."') )  )                                                                             
                                        end                                                                                                            
                          end   

                                    end +1 as diasPrima, 

                                    b.fecIng, c.FechaI , 
                                    case when d.diasLab is null then 0 else d.diasLab end diasPrimaN, 
                                    b.idTau2, b.idTau3,b.idTau4,

                                  case when c.FechaI > '".$fechaI."' then # Obtener el mes inicial para calculo de dias
                                         month( c.FechaI ) 

                                   else # Ingreso antes de periodo de primas 
                                         month( '".$fechaI."' ) 
                              
                                    end as mesI, b.sueldo,
                                 # Dias laborados reales en el periodo 
case when (
select sum( bb.dias + bb.diasI + 
(
case when ( select count( a.id ) as num 
from n_conceptos a 
     inner join n_conceptos_pr b on b.idConc = a.id
  where b.idProc = 4 and a.id = 133 ) > 0 then bb.diasVac else 0 end )
 )
from n_nomina aa 
   inner join n_nomina_e bb on bb.idNom = aa.id     
  where aa.fechaI >= 

 ( case when c.fechaI <= '2017-01-01' then '2017-01-01' else 
  
  ( concat( year(c.fechaI),'-',lpad(month(c.fechaI),2,'0'),
      (case when day( c.fechaI)>15 then '-15' else '-01' end)  ) )
  
   end )  

   and aa.fechaF <= '2017-06-30' 
  and aa.idTnom in (1,5,8)  and bb.idEmp = b.id 

    ) is null then 0  else 

(
select sum( bb.dias + bb.diasI + 
(
case when ( select count( a.id ) as num 
from n_conceptos a 
     inner join n_conceptos_pr b on b.idConc = a.id
  where b.idProc = 4 and a.id = 133 ) > 0 then bb.diasVac else 0 end )
 )
from n_nomina aa 
   inner join n_nomina_e bb on bb.idNom = aa.id     
  where aa.fechaI >= 

 ( case when c.fechaI <= '2017-01-01' then '2017-01-01' else 
  
  ( concat( year(c.fechaI),'-',lpad(month(c.fechaI),2,'0'),
      (case when day( c.fechaI)>15 then '-15' else '-01' end)  ) )
  
   end )  

   and aa.fechaF <= '2017-06-30' 
  and aa.idTnom in (1,5,8)  and bb.idEmp = b.id 

    ) end     


    as diasLabor                                     
                              from n_nomina_e a 
                                  inner join a_empleados b on b.id = a.idEmp 
                                  inner join n_nomina e on e.id = a.idNom and a.idEmp = b.id  
                                  inner join n_emp_contratos c on c.idEmp = b.id and c.tipo = 1    
                                  left join n_nomina_nov d on d.estado = 0 and d.idCal = 7 and d.idEmp = a.idEmp # Verificar si tiene guardado dias de primas por modificacion 
                              where a.idNom = ".$idNom." 
                                     and ( c.fechaI <= e.fechaF and c.tipo=1 ) # valdiar que sean empleados con fecha del periodo antes de nmina de primas 
                                  group by a.id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }      

   // Dias laborados durante las vacaciones
   public function getVacasFinalLabor($id, $idEmp)
   {
     $result=$this->adapter->query("Select a.id, a.idEmp, b.idCcos,
                     month( c.fechaI ) as mesIvac,  

# Buscamos los dias correspondientes al primer año de ingreso-----------------------------------------------------                     
( select ( 12-(month(aa.fechaI) )) * 30 from n_libvacaciones aa where aa.estado=0 and aa.idEmp = b.id order by aa.fechaI limit 1 )  +
(select (30-day(aa.fechaI)) +1 from n_libvacaciones aa where aa.estado=0 and aa.idEmp = b.id order by aa.fechaI limit 1 ) + 
#Buscamos los dias correspondientes a los años completos de trabajo ---------------------------------------------------------
( ( (year(e.fechaF) )-(select year(aa.fechaI)+1 from n_libvacaciones aa where aa.estado=0 and aa.idEmp = b.id order by aa.fechaI limit 1 ) ) * 12 ) * 30 + 
#Saco los dias correspondientes al año en curso para liquidacion segun fecha ------------------------------------------------
 ( ( (month( e.fechaF) - 1 )  * 30  ) + ( case when day( e.fechaF )=31 then day( e.fechaF )-1 else day( e.fechaF ) end )  )  as diasTrabajadosPerVaca, 
 
# Buscamos los dias correspondientes al primer año de ingreso-----------------------------------------------------                     
( ( 12-(month(e.fechaIConsultaR) )) * 30  )  + 
( (30-day(e.fechaIConsultaR)) +1  ) + 
#Buscamos los dias correspondientes a los años completos de trabajo ---------------------------------------------------------
( ( (year(e.fechaF) )-(year(e.fechaIConsultaR)+1  ) ) * 12 ) * 30 + 
#Saco los dias correspondientes al año en curso para liquidacion segun fecha ------------------------------------------------
 ( ( (month( e.fechaF) - 1 )  * 30  ) + ( case when day( e.fechaF )=31 then day( e.fechaF )-1 else day( e.fechaF ) end )  )  as diasVaca,
          aa.fechaF, e.diasPromV ,e.diasPromC          
                               from n_nomina_e a 
                                  inner join n_nomina aa on aa.id = a.idNom 
                                  inner join a_empleados b on b.id = a.idEmp 
                                  inner join n_nomina_l e on e.idNom = 0 and e.idEmp = a.idEmp 
                                  inner join n_emp_contratos c on c.id = e.idCon                                    
                              where a.idNom = ".$id." and a.idEmp =".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;     
   }

   // Dias vacaciones liquidacion final 
   public function getVacasFinal($fechaF, $idNom)
   {
     $result=$this->adapter->query("Select distinct a.id, a.idEmp, b.idCcos, c.FechaI, c.fechaF, #Fecha contrato activo 
 ( DATEDIFF( '".$fechaF."' , c.FechaI ) + 1 ) as diasContrato,
( ( ( DATEDIFF( '".$fechaF."' , c.FechaI ) + 1 ) ) * 15 ) / 360 - sum(  d.diasP + d.diasD ) as periodosPenVaca 
                                  from n_nomina_e a 
                                  inner join n_nomina aa on aa.id = a.idNom 
                                  inner join a_empleados b on b.id = a.idEmp 
                                  inner join n_emp_contratos c on c.idEmp = b.id  
                                  inner join n_libvacaciones d on d.idEmp = b.id 
                                  where year(d.fechaF)<= year(aa.fechaI) and d.estado=0 # consulta de periodos de vacaiones pendientes 
                        and a.idNom = ".$idNom ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }

   public function getVacasPromFinal($idEmp, $fechaI, $fechaF, $dias, $fechaAnAnt, $promSubTrans)
   {
      $result=$this->adapter->query("Select 
    (
      ( case when  ( round( ( sum( a.devengado ) / ".$dias." ) * 30, 0 ) ) is null then 
        f.sueldo / 30 
    else
       ( round( ( sum( a.devengado ) / ".$dias." ) * 30, 0 )  ) / 30 end  ) )  as vlrBasePromedioVaca  , sum(a.devengado) as total 

      from n_nomina_e_d a 
                inner join n_conceptos b on a.idConc=b.id 
                        inner join n_nomina d on d.id=a.idNom 
                        inner join n_nomina_e e on e.id = a.idInom and a.idInom = e.id 
                        inner join a_empleados f on f.id=e.idEmp 
                        inner join n_conceptos_pr c on c.idConc=b.id 
      where c.idProc = 7 and d.fechaI >= '".$fechaAnAnt."'  
           and d.fechaF <= concat( year('".$fechaF."'),'-', lpad(month('".$fechaF."'),2,'0') ,'-', (case when day('".$fechaF."')>15 then 30 else 30 end) ) 
        # Se debe tener en cuenta el calendario para la consulta  
       and e.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      //$datos=$result->toArray();    
      return $datos;
   } 

   //// --- FIN LIQUIDACION FINAL ---- 
   // Consulta fecha de ingreso del empleado
   public function getIngresoEmpleado($id)
   {
     $result=$this->adapter->query("Select a.id, ( DATEDIFF( b.fechaF, c.fecIng ) +1 ) as diasH 
                                  from n_nomina_e a
                                       inner join n_nomina b on b.id = a.idNom 
                                       inner join a_empleados c on c.id = a.idEmp 
                                       where b.id = ".$id." and c.fecIng > b.fechaI",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
   }   
   // Dias en proyecto para restar al sueldo principal
   public function getProyectosEmpleado($id)
   {
     $result=$this->adapter->query("update n_nomina_e a
                                       inner join n_proyectos_e c on c.idEmp = a.idEmp
                                       left join n_novedades_pr d on d.idProy = c.idProy and d.idEmp = a.idEmp and d.estado = 0 
                                       set a.dias = a.dias - (case when d.id is null then (c.horas/8/2) else d.dias end )
                                       where  a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
   }      
   // Aumento de sueldo por empleados
   public function getAumentoEmpleado($id)
   {
     $result=$this->adapter->query("update n_nomina_e a ,
      ( select datediff(  c.fechai, b.fechaI )  as dias, c.fechai, # se sacan los dias trabajados con el sueldo viejo 
            c.sueldoAnt, c.idEmp 
                 from  n_nomina_e a
                         inner join n_aumento_sueldo c on c.idEmp = a.idEmp
                            inner join n_nomina b on b.id = a.idNom 
                         where c.fechai >= b.fechaI
                           and c.fechai <= b.fechaF and  a.idNom = ".$id." ) as b
                         set a.dias = a.dias - b.dias, a.fecAum = b.fechai,
                           a.sueldoAnt = b.sueldoAnt, a.diasS = b.dias    
                            where a.idEmp = b.idEmp and a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
   }  
   
   // Generacion de empleados 
   public function getNominaE($id, $idg, $idEmp, $fechaI, $tipNom )
   {
      // $fechaI : fecha inicio de consulta para liquidacion final 

      if ($idEmp=='')// Se generan todos los empleados del grupo
      {
         $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, idVac, actVac, sueldo, salarioMinimo, subTransporte, salarioMinimoConv, liquidado ) 
           (select ".$id." as id2, a.id, c.valor,
              a.idVac, 
                  a.vacAct, a.sueldo, ".$this->salarioMinimo.", ".$this->subsidioTransporte.", ".$this->salarioMinimoCovencional.",
                  # UBICAR SI k2TIENE UNA LIQUIDACION DEFINITIVA EN PARALELO -----------------------------------------------------------------------------------
                 case when ( select f.id from n_nomina e 
                       inner join n_nomina_e f on f.idNom = e.id 
                        where e.estado = 1 and e.idTnomL > 0 and f.idEmp = a.id  ) is null then 
                    0       
                  else 
                     1
                  end                                     
            from a_empleados a 
              inner join n_nomina b on b.idGrupo=a.idGrup and b.id= ".$id."       
              inner join n_tip_calendario c on c.id=b.idCal
              left join n_vacaciones d on d.id=a.idVac
              WHERE a.estado=0 and a.finContrato=0 and # activo para la migracion de empelados activos 
               ( (select aa.fechaI from n_emp_contratos aa where aa.idEmp = a.id and aa.tipo = 1 limit 1) <= b.fechaF ) and  
              not exists (SELECT null from n_nomina_e where a.id=idEmp and idNom=".$id." ) and a.idGrup=".$idg." )",Adapter::QUERY_MODE_EXECUTE);
      }else{
         $grupo = "a.idGrup";
         if ( ($idg==0) and ($tipNom==4) ) // Grupo creado para liquidacion final de empleados 
             $grupo = "99";

         $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, idVac, actVac, diasVac, sueldo, salarioMinimo, subTransporte, salarioMinimoConv ) 
           (select ".$id." as id2, a.id, 

     case when (b.idCal = 6 or b.idTnomL > 0 ) then # Si es nomna de vacaciones o liquidacion final (nomina quincenales, nominas de 30 dias revision)
          case when day(d.fechaI) > 15 then 
               datediff(  d.fechaI, concat( year( d.fechaI ), '-',month(d.fechaI),'-16' ) ) 
          else 
             case when b.idTnomL > 0 then 
                 datediff(  d.fechaI, concat( year( d.fechaI ), '-',month(d.fechaI),'-01' ) ) +1
             else
               datediff(  d.fechaI, concat( year( d.fechaI ), '-',month(d.fechaI),'-01' ) ) 
        end                
          end      

     else # Nomina normal 
         c.valor end as valor ,
            
              case when b.idCal = 6 or ( ( b.fechaI <= d.fechaI ) or (  (d.fechaF <= b.fechaF) and (d.fechaF >= b.fechaI) )  ) # Tener en cuenta los cierres de periodos de vacaciones
                 then
                    a.idVac
                    else 
                       case when d.diasDinero > 0 and d.dias = 0 then 
                           a.idVac 
                       else 0 end     
                         end as idVac,
                 a.vacAct,(d.dias + d.diasNh ), a.sueldo, ".$this->salarioMinimo.", ".$this->subsidioTransporte.", ".$this->salarioMinimoCovencional."     
           from a_empleados a 
              inner join n_nomina b on b.idGrupo=".$grupo."  
              inner join n_tip_calendario c on c.id=b.idCal
              left join n_vacaciones d on d.id=a.idVac
           WHERE ( b.idCal = 6 or b.idGrupo=99 )
                and  # activo para la migracion de empelados activos 
              not exists (SELECT null from n_nomina_e where a.id=idEmp and idNom=".$id." )
                 and a.id=".$idEmp." and b.id=".$id." )",Adapter::QUERY_MODE_EXECUTE);        
//     WHERE a.activo=0 and a.estado=0 and ( b.idCal = 6 or (a.fecIng <= b.fechaI or a.fecIng <= b.fechaF) ) PORQUE FECAH DE INGRESO?
      }
      if ($tipNom==4)// Se generan por empleado liquidacion definitiva
      {
        // $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, fechaI ) 
        //  ( select b.id , a.id , ( DATEDIFF( b.fechaF, b.fechaI ) ) as dias,'".$fechaI."'    
        //      from a_empleados a 
        //      inner join n_nomina b on b.id = ".$id." 
        //      WHERE a.activo=0 and a.id = ".$idEmp."  and a.estado=0 )",Adapter::QUERY_MODE_EXECUTE);
      }      
   }
   // Generacion de empleados para primas 
   public function getNominaEprimas($id, $idg, $idEmp, $fechaI, $tipNom, $idTnom )
   {
      // $fechaI : fecha inicio de consulta para liquidacion final 
         $pagCes = 0;
         if ($idTnom==3) // NOmina d cesantias 
             $pagCes = 1;

         $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, sueldo, salarioMinimo, subTransporte, salarioMinimoConv, pagoCes ) 
           (select ".$id." as id2, a.id, c.valor,
              a.sueldo ,

                   ".$this->salarioMinimo.", ".$this->subsidioTransporte.", ".$this->salarioMinimoCovencional.", ".$pagCes."     
          from a_empleados a 
              inner join n_nomina b on b.idGrupo=a.idGrup and b.id= ".$id."       
              inner join n_tip_calendario c on c.id=b.idCal 
              inner join n_tipemp e on e.id = a.idTemp 
              left join n_vacaciones d on d.id=a.idVac
              where a.integral = 0 and e.tipo=0 and a.activo=0 and a.estado=0  and a.finContrato = 0 and ( a.fecIng <= b.fechaI or a.fecIng <= b.fechaF  )  and  # activo para la migracion de empelados activos 
              not exists (SELECT null from n_nomina_e where a.id=idEmp and idNom=".$id." ) and a.idGrup=".$idg." )",Adapter::QUERY_MODE_EXECUTE);
   }
   // Generacion de empleados por ausentismos
   public function getNominaEaus($id, $idg, $idEmp, $fechaI, $tipNom )
   {
      // $fechaI : fecha inicio de consulta para liquidacion final 

      if ($idEmp=='')// Se generan todos los empleados del grupo
      {
         $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, idVac, actVac, sueldo, salarioMinimo, subTransporte, salarioMinimoConv ) 
           (select ".$id." as id2, a.id, c.valor,
              case when ( ( b.fechaI <= d.fechaI ) or (  (d.fechaF <= b.fechaF) and (d.fechaF >= b.fechaI) )  ) # Tener en cuenta los cierres de periodos de vacaciones
                 then
                    a.idVac
                    else 
                       case when d.diasDinero > 0 and d.dias = 0 then 
                           a.idVac 
                       else 0 end     
                     end as idVac,
                  a.vacAct, a.sueldo, ".$this->salarioMinimo.", ".$this->subsidioTransporte.", ".$this->salarioMinimoCovencional."     
              from a_empleados a 
              inner join n_nomina b on b.idGrupo=a.idGrup and b.id= ".$id."       
              inner join n_tip_calendario c on c.id=b.idCal
              left join n_vacaciones d on d.id=a.idVac
              WHERE a.activo=0 and a.estado=0 and ( a.fecIng <= b.fechaI or a.fecIng <= b.fechaF  )  and  # activo para la migracion de empelados activos 
              not exists (SELECT null from n_nomina_e where a.id=idEmp and idNom=".$id." ) and a.idGrup=".$idg." )",Adapter::QUERY_MODE_EXECUTE);
      }else{
         $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, idVac, actVac, diasVac, sueldo, salarioMinimo, subTransporte, salarioMinimoConv ) 
           (select ".$id." as id2, a.id, 

     case when b.idCal = 6 then # Si es nomna de vacaciones
          case when day(d.fechaI) > 15 then 
               datediff(  d.fechaI, concat( year( d.fechaI ), '-',month(d.fechaI),'-16' ) ) 
          else 
               datediff(  d.fechaI, concat( year( d.fechaI ), '-',month(d.fechaI),'-01' ) ) 
          end      
     else # Nomina normal 
         c.valor end as valor ,
            
              case when b.idCal = 6 or ( ( b.fechaI <= d.fechaI ) or (  (d.fechaF <= b.fechaF) and (d.fechaF >= b.fechaI) )  ) # Tener en cuenta los cierres de periodos de vacaciones
                 then
                    a.idVac
                    else 
                       case when d.diasDinero > 0 and d.dias = 0 then 
                           a.idVac 
                       else 0 end     
                         end as idVac,
                  a.vacAct,(d.dias + d.diasNh ), a.sueldo, ".$this->salarioMinimo.", ".$this->subsidioTransporte.", ".$this->salarioMinimoCovencional."     
              from a_empleados a 
              inner join n_nomina b on b.idGrupo=a.idGrup 
              inner join n_tip_calendario c on c.id=b.idCal
              left join n_vacaciones d on d.id=a.idVac
              WHERE a.activo=0 and a.estado=0 and ( b.idCal = 6 or (a.fecIng <= b.fechaI or a.fecIng <= b.fechaF) )
                and  # activo para la migracion de empelados activos 
              not exists (SELECT null from n_nomina_e where a.id=idEmp and idNom=".$id." )
                 and a.id=".$idEmp." and b.id=".$id." )",Adapter::QUERY_MODE_EXECUTE);        
      }
      if ($tipNom==4)// Se generan por empleado liquidacion definitiva
      {
        // $result=$this->adapter->query("insert into n_nomina_e (idNom, idEmp, dias, fechaI ) 
        //  ( select b.id , a.id , ( DATEDIFF( b.fechaF, b.fechaI ) ) as dias,'".$fechaI."'    
        //      from a_empleados a 
        //      inner join n_nomina b on b.id = ".$id." 
        //      WHERE a.activo=0 and a.id = ".$idEmp."  and a.estado=0 )",Adapter::QUERY_MODE_EXECUTE);
      }      
    }
   // Generacion de empleados para nomina manual  
   public function getNominaEmanual( $id, $idg, $idEmp, $fechaI, $tipNom )
   {
         $result=$this->adapter->query("insert into n_nomina_e (idNom,idEmp)
                                            ( select ".$id.", b.id   
                                               from a_empleados b 
                                                  inner join n_tipemp c on c.id = b.idTemp 
                                                  inner join n_nomina d on d.id = ".$id." 
                                             where c.tipo=0 and b.estado = 0 and  
                                             ( (select aa.fechaI from n_emp_contratos aa 
               where aa.idEmp = b.id and aa.tipo = 1 limit 1) <= d.fechaF ) and 
                                                not exists (SELECT null 
                                                              from n_nomina_e 
                                                                 where b.id=idEmp and idNom=".$id." )
                                                     and b.idGrup=".$idg." ) ",Adapter::QUERY_MODE_EXECUTE);   
   }
   // Generacion de empleados para nomina manual (Especial)
   public function getNominaEspecial( $id, $idg, $idEmp, $fechaI, $tipNom )
   {
         $result=$this->adapter->query("insert into n_nomina_e (idNom,idEmp)
                                            ( select ".$id.", a.idEmp  
                                               from t_docu_esp a 
                                                 inner join a_empleados b on b.id = a.idEmp 
                                             where a.estado = 1 and 
                                                not exists (SELECT null 
                                                              from n_nomina_e 
                                                                 where b.id=idEmp and idNom=".$id." )
                                                     and b.idGrup=".$idg." group by a.idEmp ) ",Adapter::QUERY_MODE_EXECUTE);   
   }
   // Generacion de incapacidades para empleados
   public function getIncapaEmp($id, $tabla)
   {
      $tipo=0;
      if ( $tabla=='n_incapacidades' )
         $tipo=0;
      else 
         $tipo=1;

         $result=$this->adapter->query("insert into n_nomina_e_i (idNom, idEmp, idInc, dias, diasAp, diasDp, reportada, tipo) 
(  select a.id, b.idEmp, c.id as idInc, ( DATEDIFF( c.fechaf, c.fechai ) +1 ) as diasI, # Dias totales de incapacidad
# CUANDO LA INCAPACIDAD ESTA POR DEBAJO DEL PERIODO INICIAL DE NOMINA ----------------------------------
case when ( c.fechai < a.fechaI ) then # Fecha de inicio de la incapacidad es menor a la fecha actual de la incapacidad 
   ( DATEDIFF( a.fechaI , c.fechai ) )
else 
   0 
end as diasAp, 
# CUANDO LA INCAPACIDAD ESTA POR ENCIMA DEL PERIODO INICIAL DE NOMINA ----------------------------------
case when ( c.fechai >=a.fechaI and c.fechaf <= a.fechaF ) then # 1. Caso incapacidad dentro del periodo de nomina
   ( DATEDIFF( c.fechaf , c.fechai ) + 1  )
else
  case when ( c.fechaf > a.fechaF and c.fechai >= a.fechaI  ) then # 2. Caso incapacidad finaliza periodo despues de la nomian actual
    case when ( month( a.fechaf )=2 and c.fechaf > concat(year(a.fechaF),'-02-28' ) ) then # Es el mes de febrero 
     case when day(last_day(a.fechaF))=28 then  # dia 28 
          ( DATEDIFF( a.fechaF , c.fechai ) + 3 )            
       else # dia 29
          ( DATEDIFF( a.fechaF , c.fechai ) + 2 )       
       end    
    else 
       ( DATEDIFF( a.fechaF , c.fechai ) + 1 ) 
    end      
  else 
     case when ( c.fechai < a.fechaI and a.fechaF <= c.fechaf) then # 3. Caso incapacidad fuera de periodo antes y despues 
        case when ( month( a.fechaf )=2 and c.fechaf > concat(year(a.fechaF),'-02-28' ) ) then # Es el mes de febrero 
          case when day(last_day(a.fechaF))=28 then  # dia 28 
             ( DATEDIFF( a.fechaF , a.fechaI ) + 3 )            
          else # dia 29
             ( DATEDIFF( a.fechaF , a.fechaI ) + 2 )       
          end    
        else 
          ( DATEDIFF( a.fechaF , a.fechaI ) + 1  )        
        end           
     else
       case when ( c.fechai < a.fechaI and a.fechaF > c.fechaf) then # 4. Caso incapacidad viene de un periodo antes y termina en el actual
        case when ( month( a.fechaf )=2 and c.fechaf > concat(year(a.fechaF),'-02-28' ) ) then # Es el mes de febrero 
          case when day(last_day(a.fechaF))=28 then  # dia 28 
             ( DATEDIFF( c.fechaf , a.fechaI ) + 3 )            
          else # dia 29
             ( DATEDIFF( c.fechaf , a.fechaI ) + 2 )       
          end    
        else 
          ( DATEDIFF( c.fechaf , a.fechaI ) + 1  )  
        end           
       else
         0 
       end 
     end 
  end
end as diasDp, 
c.reportada, # Dias no reportados despues del periodo        
 ".$tipo."  
from n_nomina a 
inner join n_nomina_e b on b.idNom = a.id
inner join ".$tabla." c on c.reportada in ('0','1')  and c.idEmp = b.idEmp #  Se cargan todas las incapacidades antes de fin del perioso en cuestio
left join c_general d on d.id = 1 # Buscar datos de la confguracion general para incapaciades 
where a.id = ".$id."  and c.fechai <= a.fechaF # el inicio de la prorroga sea menor al inicio de la vacaciones 
  and ( case when ( ( c.reportada = 1 ) and ( c.fechaf < a.fechaI ) ) then 1 else 0 end ) = 0 # verificar que si ya esta reportada al fecha final de la incapacidad este dentro del periodo de liquidacion   
)   ",Adapter::QUERY_MODE_EXECUTE);
//and ( (c.fechai <= a.fechaF) and (c.fechaf >= a.fechaI ) ) ## OJOA ANALIZAR ESTO DE LA MEJR FORMA 

    }    

   // Generacion de ausentismos para empleados
   public function getAusentismosEmp($id)
   {
       $result=$this->adapter->query("insert into n_nomina_e_a (idNom, idEmp, idAus, dias, reportada, horas, tipo) 
( select a.id, b.idEmp, c.id , 
 ( case when (c.fechai <= a.fechaI and c.fechaf >= a.fechaF) then 

    case when ( month( a.fechaf )=2 and c.fechaf > concat(year(a.fechaF),'-02-28' ) ) then # Es el mes de febrero 
     case when day(last_day(a.fechaF))=28 then  # dia 28 
            datediff(a.fechaF , a.fechaI ) + 3 ## Se pasa de todo el periodo 
       else # dia 29
            datediff(a.fechaF , a.fechaI ) + 2 ## Se pasa de todo el periodo 
       end    
    else 
       datediff(a.fechaF , a.fechaI ) + 1 ## Se pasa de todo el periodo 
    end       

else
   case when ( c.fechai <= a.fechaI and c.fechaf < a.fechaF ) then 
      datediff( c.fechaf , a.fechaI ) + 1 ## Fina de ausentismos esta en el periodo
   else    
      case when ( c.fechai >= a.fechaI and c.fechaf > a.fechaF ) then 

        case when ( month( a.fechaf )=2 and c.fechaf > concat(year(a.fechaF),'-02-28' ) ) then # Es el mes de febrero 
          case when day(last_day(a.fechaF))=28 then  # dia 28 
              datediff(a.fechaF , c.fechaI ) + 3 ## Se pasa de todo el periodo 
          else # dia 29
              datediff(a.fechaF , c.fechaI ) + 2 ## Se pasa de todo el periodo 
          end    
        else 
         datediff(a.fechaF , c.fechaI ) + 1 ## Se pasa de todo el periodo 
        end                

      else    
         case when ( c.fechai > a.fechaI and c.fechaf <= a.fechaF ) then 
             datediff( c.fechaf, c.fechaI ) + 1 ## El ausentismo esta dentro del periodo
         end     
    end             
  end       
end) + ( case when c.reportada = 0 and ( c.fechai < a.fechaI and c.fechaf >= a.fechaI )  then # Cuando la fecha trae unos dias anteriores y no ha sido reportado
 datediff( a.fechaI , c.fechai )  
 else 0 
 end  ) as dias, 
 c.reportada, c.horas , d.tipo 
from n_nomina a 
inner join n_nomina_e b on b.idNom = a.id
inner join n_ausentismos c on c.reportada in ('0','1')  and c.idEmp = b.idEmp 
inner join n_tip_aus d on d.id = c.idTaus 
where c.fechaf >= a.fechaI and a.id = ".$id."  )   ",Adapter::QUERY_MODE_EXECUTE);
# AUSENTISMOS NO REPORTADOS PERO EN MENOS DE UN PERIODO 
       $result=$this->adapter->query("insert into n_nomina_e_a (idNom, idEmp, idAus, dias, periodoAnt) 
 ( select a.id, b.idEmp, c.id , 
     datediff( c.fechaf, c.fechai ) + 1 as dias  
, 1 
from n_nomina a 
inner join n_nomina_e b on b.idNom = a.id
inner join n_ausentismos c on c.reportada in ('0')  and c.idEmp = b.idEmp 
where c.fechaf < a.fechaI and (datediff( c.fechaf, c.fechai ) + 1)<=15 and a.id = ".$id."  )   ",Adapter::QUERY_MODE_EXECUTE);

    }  // Fin ausentismos  

   // ( POR TIPO DE AUTOMATICOS ) ( n_nomina_e_d ) 
   public function getNominaEtau($id,$idg,$auto)
   {        
     switch ($auto) {
       case 1:
         $auto = 'b.idTau';
         break;
       case 2:
         $auto = 'b.idTau2';
         break;       
       case 3:
         $auto = 'b.idTau3';
         break;                
       case 4:
         $auto = 'b.idTau4';
         break;                         
       default:
         # code...
         break;
     }
     $result=$this->adapter->query('select distinct c.idNom, c.id, a.idCon, 
                case a.cCosEmp when 0 then a.idCcos
                  when 1 then b.idCcos  End as idCcos,

                case a.horasCal when 0 then ( case when g.valor = 1 then a.valor else 0 end ) 
                  when 1 then (c.dias*'.$this->horasDias.') End as horas,

                   1, 
                case when g.valor=2 then 
                     case when g.tipo = 1 then a.valor else 0 End                             
                when g.valor=1 then 0      
                End as dev,       
                case when g.valor=2 then 
                     case when g.tipo = 2 then a.valor else 0 End                                             
                when g.valor=1 then 0     
    End as ded, h.formula, c.dias, g.tipo
                , b.id as idEmp , g.idFor , a.diasLab, c.diasVac, a.vaca,
                case when ii.codigo is null then "" else ii.codigo end as nitTer, c.actVac,
               # Datos del aumento de sueldo del empleado 
                   c.diasS, c.sueldoAnt, c.sueldoAnt / 30 as vlrDiaAnt, c.fecAum,
 (  select case when k.estado is null then 99 else k.estado end from n_tipemp j 
                    inner join n_tipemp_p k on k.idTemp = j.id where k.idEmp = c.idEmp and k.idTemp=1) as estado, # estado para evaluar pago retro 
                  a.horasCal 
                from n_tip_auto_i a 
                  inner join a_empleados b on a.idTauto='.$auto.' 
                  inner join n_nomina_e c on b.id=c.idEmp 
                  inner join n_nomina d on d.id=c.idNom
                  inner join n_tip_auto_tn e on e.idTnom=d.idTnom  
                  inner join n_tip_calendario f on f.id=d.idCal
                  inner join n_conceptos g on g.id=a.idCon 
                  inner join n_formulas h on h.id=g.idFor                                    
                  left join n_terceros_s i on i.id = g.idTer  
                  left join n_terceros ii on ii.id = i.idTer 
                  WHERE not exists (SELECT null from n_nomina_e_d 
               where c.id=idInom and a.idCon=idConc and a.idCcos=idCcos and tipo=1 ) 
                  and d.estado=0 
                  and ( case when b.idGrup='.$idg.' then  b.idGrup else b.idGrup end ) # si es liquidacion final grupo 99  
                    and c.idNom='.$id.' order by a.horasCal desc',Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
    }    

    // ( POR OTROS AUTOMATICOS ) ( n_nomina_e_d ) por otros automaticos
    public function getNominaEeua($id)
    {        
      $result=$this->adapter->query('select distinct c.idNom, c.id, a.idCon, f.formula,c.dias,e.tipo,a.idEmp,   
                case a.cCosEmp when 0 then a.idCcos
                  when 1 then g.idCcos  End as idCcos, # Centros de costos
                case a.horasCal when 0 then 0 
                  when 1 then 0 End as horas, 1, # Horas desrrollo
                  
                (case when e.valor=2 then 
                case when e.tipo = 1 then a.valor else 0 End                              
                  when e.valor=1 then 0      
                End ) / 30 as dev,  # Devengado
                
                 ( case when e.valor=2 then 
                     case when e.tipo = 2 then a.valor else 0 End                                             
                when e.valor=1 then 0
        End ) / 30 as ded  , # Deducido 
        
         e.idFor, c.diasVac, hh.codigo as nitTer, c.idVac, a.horasCal # Determina si solo se mueve con dias laborados   
         , case when a.fechaI = "0000-00-00" # Evaluar cuando tenga programacion el automatico por empleado
         then 
            0  
        else # Hay programacion y se vaida si esta dentro del rango de la nomina 
            case when ( (a.fechaI>=d.fechaI) and (a.fechaI <= d.fechaF) ) or ( (a.fechaF>=d.fechaI) and (a.fechaF <= d.fechaF) )  then  
                 1 # Activa el concepto 
             else
               2 # Desactiva el automatico  
           end  
        end as fecAct , d.idTnom, a.id as idEmpCon , a.valor as valorFijo, e.perAuto, g.vacAct, e.valor as horasConc, 
  ( select count(b.id) from n_emp_conc_tn b where b.idEmCon = a.id and b.idTnom = 11 ) as nomAnt, v.fechaR, year(d.fechaF) as ano, month(d.fechaF) as mes   
from n_emp_conc a 
inner join n_nomina_e c on a.idEmp=c.idEmp 
inner join n_nomina d on d.id=c.idNom
inner join n_conceptos e on e.id=a.idCon
inner join n_formulas f on f.id=e.idFor
inner join a_empleados g on g.id=c.idEmp
left join n_terceros_s h on h.id = e.idTer  
left join n_terceros hh on hh.id = h.idTer 
left join n_vacaciones v on v.id = g.idVac 
inner join n_tip_calendario cal on cal.id = d.idCal 
WHERE not exists (SELECT null from n_nomina_e_d 
where c.id=idInom and a.idCon=idConc and a.idCcos=idCcos and tipo=2 )
and d.estado=0 and c.idNom='.$id.' and g.vacAct = 0 ',Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;        
    }        
   // ( POR CONCEPTOS DE AUTOMATICOS ) ( n_nomina_e_d ) por conceptos automaticos 
   public function getNominaEcau($id)
   {        
     $result=$this->adapter->query('select a.id as idNom, d.id ,d.dias as dias, b.idConc as idCon, 
            e.idCcos , f.formula, c.tipo, e.id as idEmp, 0 as horas, 
           c.idFor, e.idFpen, c.fondo, d.actVac, g.tipo as tipEmp, 
           h.idTemp as idTempCon , e.pensionado   
         from n_nomina a 
            inner join n_conceptos_tn b on b.idTnom=a.idTnom
            inner join n_conceptos c on c.id=b.idConc
            inner join n_nomina_e  d on d.idNom = a.id
            inner join a_empleados e on e.id = d.idEmp 
            inner join n_conceptos_te i on i.idConc = c.id and i.idTemp = e.idTemp 
            inner join n_formulas f on f.id=c.idFor 
            inner join n_tipemp g on g.id = e.idTemp and g.tipo = 0 
            left join n_conceptos_te h on h.idConc = c.id and h.idTemp    
        where c.auto=1 and a.id='.$id.' 
           group by d.id, c.id  
        order by d.idEmp',Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
    }        

   // CONCEPTOS HIJOS  
   public function getNominaConH($id)
   {        
     $result=$this->adapter->query('select a.id as idNom, e.id, e.dias, d.id as idCon, f.idCcos,
       g.formula, d.tipo, e.idEmp, 0 as horas, g.id as idFor,  
     case when cc.id is null then 0 else cc.id end as Temp, e.diasVac  
     from n_nomina_e_d a 
       inner join n_conceptos b on b.id=a.idConc 
       inner join n_conceptos_th c on c.idConc=b.id 
       inner join n_conceptos d on d.id=c.idCon 
       inner join n_nomina_e e on e.id=a.idInom
       inner join a_empleados f on f.id = e.idEmp
       left join  n_conceptos_te cc on cc.idConc=c.idCon and cc.idTemp = f.idTemp        
       inner join n_formulas g on g.id = d.idFor  
       where a.idNom='.$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }               
   // CONCEPTOS EXTRALEGALES DE PRIMAS
   public function getConceptosExtra($id)
   {        
     $result=$this->adapter->query('select a.id, a.idEmp, 0 as dias, 0 as diasVac , 0 as horas, g.formula , f.tipo, b.idCcos,
 f.id as idCon, g.id as idFor   
     from n_nomina_e a 
       inner join a_empleados b on b.id = a.idEmp
       inner join n_cencostos c on c.id = b.idCcos 
     inner join n_conceptos_pr e on e.idProc = 16 # Conceptos que sean extralegales 
     inner join n_conceptos f on f.id = e.idConc  
       inner join n_formulas g on g.id = f.idFor 
       inner join n_conceptos_te h on h.idConc = f.id and h.idTemp = b.idTemp # Amarrar tipo de empleado necesario para la funcion 
       where a.idNom='.$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                  
   // FONDOS DE SOLIDARIDAD
   public function getSolidaridad( $id )
   {
      $result=$this->adapter->query("Select a.id, a.idEmp , e.idCcos, year(b.fechaI) as ano , month(b.fechaI) as mes, e.vacAct     
             from  n_nomina_e a 
             inner join a_empleados e on e.id = a.idEmp
             inner join n_nomina b on b.id = a.idNom 
             inner join n_tip_calendario c on c.id = b.idCal 
             inner join n_tip_calendario_p d on d.idCal = c.id 
             where a.idNom = ".$id." and e.pensionado=0 and e.idFpen!=1 # Recordar que 1 es no tiene pension  
       and d.idCal = c.id 
             group by a.idEmp ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();
      return $datos;
    }    
    
   // Estado de periodos generados por nomina
   public function getNominaCuP($id)
   {        
     $result=$this->adapter->query('update n_conceptos a 
        inner join n_conceptos_tn b on b.idConc=a.id
        inner join n_nomina c on c.idTnom=b.idTnom
        set b.periodo = case when a.perAuto>b.periodo then b.periodo+1 else 1 end  
        where a.perAuto>1 and c.id='.$id ,Adapter::QUERY_MODE_EXECUTE);
   }                  
    

   // ( PRESTAMOS ) ( n_nomina_e_d ) 
   public function getPrestamos($id, $periodoNomina)
   {        
     $con = '';
     //$periodoNomina = 0;
     if ($periodoNomina==2)
     { 
         $con = "   and  ( (e.perAuto = ( case when e.perAuto = 2 and 2 = ".$periodoNomina." then 2 else 0 end  ) or    
          e.perAuto = ( case when e.perAuto = 2 and 2 = ".$periodoNomina." then 2 else 1 end ) ) or   
          e.perAuto = ( case when e.perAuto = 2 and a.diasVac > 0 then 2 else 1 end  )          
          ) " ;
      }else{
         $con = " and ( (e.perAuto in (0,1)) or (e.perAuto = ( case when aa.idTnom = 5 then 2 else 0 end))  )" ;       
      }    

     $result=$this->adapter->query("select distinct a.idEmp, c.vacAct, a.dias , 
                      case when ff.codigo is null then '' else ff.codigo end as nitTer , a.diasVac , e.perAuto, v.fechaR, d.tipo, aa.fechaF       
                  from n_nomina_e a 
                      inner join n_nomina aa on aa.id = a.idNom  
                      inner join n_prestamos b on b.idEmp=a.idEmp
                      inner join a_empleados c on c.id=a.idEmp 
                      inner join n_tip_prestamo d on d.id = b.idTpres 
                      inner join n_conceptos e on e.id = d.idConE 
                      left join n_terceros_s f on f.id = e.idTer  
                      left join n_terceros ff on ff.id = f.idTer # Funciones para retorno y salida de vacaciones 
                      left join n_vacaciones v on v.id = a.idVac 
                  where c.vacAct in ('0','2') and b.estado=1 and a.idNom=".$id." ".$con." 
                    group by a.idEmp order by a.idEmp ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }            
   // ( PRESTAMOS CUOTAS ) ( n_nomina_e_d ) Salida
   public function getCprestamosS($id,$idEmp)
   {        
     $result=$this->adapter->query('select distinct a.id, a.idEmp,0 as dias,0 as horas, 0 as formula, f.tipo, h.idCcos,
             e.idConE as idCon, f.idFor, cc.id as idPres, 1 as cuota, 

         case when j.id >0 then ( (cc.valCuota / (case when e.tipo = 0 then 15 else 30 end) )  ) * ( 
 ( case when a.contra=2 then i.valor else (a.dias+a.diasI+a.diasVac) end ) # cuando finaliza contrato le descuentan toda la cuota 
           )  else 
         cc.valCuota end as valor, 

         cc.cuotas, cc.valCuota, 
         case when kk.codigo is null then "" else kk.codigo end as nitTer,
         case when np.valor is null then 0 else np.valor end as valorPresN ,
              ( case when np.id is null then 0 else np.id end  ) as idEpres , b.fechaF, j.fechaR, e.tipo as prestMensual,
            year( b.fechaF ) as ano, month( b.fechaF ) as mes, f.perAuto     
         from n_nomina_e a 
             inner join n_nomina b on b.id=a.idNom
             inner join n_prestamos c on c.idEmp=a.idEmp 
             inner join n_prestamos_tn cc on cc.idPres = c.id and ( cc.idTnom = b.idTnom or b.idTnom = 5 ) 
             inner join n_tip_prestamo e on e.id=c.idTpres
             inner join n_conceptos f on f.id=e.idConE  
             inner join n_formulas g on g.id=f.idFor 
             inner join a_empleados h on h.id=a.idEmp 
             inner join n_tip_calendario i on i.id = b.idCal 
             left join n_vacaciones j on j.id=a.idVac
             left join n_terceros_s k on k.id = f.idTer
             left join n_terceros kk on kk.id = k.idTer                                   
             left join n_nomina_pres np on np.idCal = b.idCal and np.idEmp = '.$idEmp.' and np.idPres = cc.id and np.estado=0 # Buscar cambios en nomina activa con el prestamo
             where a.idNom='.$id.' and c.estado=1 and a.idEmp='.$idEmp." and ( cc.pagado + cc.saldoIni ) < cc.valor group by c.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }               
   // ( PRESTAMOS CUOTAS ) ( n_nomina_e_d ) Regreso
   public function getCprestamosR($id,$idEmp)
   {        
     $result=$this->adapter->query('select distinct a.id, a.idEmp,0 as dias,0 as horas, 0 as formula, f.tipo, h.idCcos,
             e.idConE as idCon, f.idFor, cc.id as idPres, 1 as cuota, 
         ( (cc.valCuota / i.valor ) * ( case when ( c.fecDoc >= b.fechaI ) then i.valor else a.dias end ) )  as valor, # Evalua si el prestamo es despues de las vacaciones para decontar los dias calendarios completos,
                                 case when kk.codigo is null then "" else kk.codigo end as nitTer ,
                                 case when np.valor is null then 0 else np.valor end as valorPresN ,
                    ( case when np.id is null then 0 else np.id end  ) as idEpres                         
         from n_nomina_e a 
             inner join n_nomina b on b.id=a.idNom
             inner join n_prestamos c on c.idEmp=a.idEmp 
             inner join n_prestamos_tn cc on cc.idPres = c.id and cc.idTnom = b.idTnom 
             inner join n_tip_prestamo e on e.id=c.idTpres
             inner join n_conceptos f on f.id=e.idConE  
             inner join n_formulas g on g.id=f.idFor 
             inner join a_empleados h on h.id=a.idEmp 
             inner join n_tip_calendario i on i.id = b.idCal 
             left join n_terceros_s k on k.id = f.idTer
             left join n_terceros kk on kk.id = k.idTer    
             left join n_nomina_pres np on np.idPres = cc.id and np.fechaI = b.fechaI and np.fechaF = b.fechaF and np.estado=0 # Buscar cambios en nomina activa con el prestamo                               
             where a.idNom='.$id.' and c.estado=1 and a.idEmp='.$idEmp." and ( cc.pagado + cc.saldoIni ) < cc.valor group by c.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }     
   // ( PRESTAMOS CUOTAS ) LIQUIDCION FINAL 
   public function getCprestamosL($id,$idEmp)
   {        
     $result=$this->adapter->query('select distinct a.id, a.idEmp,0 as dias,0 as horas, 0 as formula, f.tipo, h.idCcos,
             e.idConE as idCon, f.idFor, cc.id as idPres, 1 as cuota, 
               ( cc.valor - ( cc.pagado + cc.saldoIni ) ) as valor, 
         cc.cuotas, cc.valCuota, 
         case when kk.codigo is null then "" else kk.codigo end as nitTer,
         case when np.valor is null then 0 else np.valor end as valorPresN    
         from n_nomina_e a 
             inner join n_nomina b on b.id=a.idNom
             inner join n_prestamos c on c.idEmp=a.idEmp 
             inner join n_prestamos_tn cc on cc.idPres = c.id and ( cc.idTnom = b.idTnom or b.idTnom = 6 ) 
             inner join n_tip_prestamo e on e.id=c.idTpres
             inner join n_conceptos f on f.id=e.idConE  
             inner join n_formulas g on g.id=f.idFor 
             inner join a_empleados h on h.id=a.idEmp 
             inner join n_tip_calendario i on i.id = b.idCal 
             left join n_vacaciones j on j.id=a.idVac
             left join n_terceros_s k on k.id = f.idTer
             left join n_terceros kk on kk.id = k.idTer                                   
             left join n_nomina_pres np on np.idPres = cc.id and np.fechaI = b.fechaI and np.fechaF = b.fechaF and np.estado=0 # Buscar cambios en nomina activa con el prestamo
             where a.idNom='.$id.' and c.estado=1 and a.idEmp='.$idEmp." and ( cc.pagado + cc.saldoIni ) < cc.valor group by c.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                  
   // ( PRESTAMOS EN PRIMAS ) ( n_nomina_e_d ) 
   public function getPrestamosPrimas($id)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, 0 as dias, 0 as horas, '' as formula, 
e.id as idCon, e.tipo, c.idCcos , b.valor , -99 as idFor, g.id as idPres, ff.codigo as nitTer 
            from n_nomina_e a 
                      inner join n_nomina aa on aa.id = a.idNom 
                      inner join n_presta_primas b on b.idEmp=a.idEmp and b.idIcal = aa.idIcal  
                      inner join a_empleados c on c.id=a.idEmp 
                      inner join n_prestamos_tn g on g.id = b.idIpres 
                      inner join n_prestamos h on h.id = g.idPres 
                      inner join n_tip_prestamo d on d.id = h.idTpres 
                      inner join n_conceptos e on e.id = d.idConE 
                      left join n_terceros_s f on f.id = e.idTer # sucursal del tercero  
                  left join n_terceros ff on ff.id = f.idTer # Funciones para retorno y salida de vacaciones 
                      where b.estado =0 and h.estado=1 and a.idNom=".$id." group by a.idEmp order by a.idEmp",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }               
   // ( DESCEUENTOS POR TIPOS DE PRESTAMOS EN PRIMAS )  
   public function getPrestamosPrimasNuev($id)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, 0 as dias, 0 as horas, '' as formula, 
e.id as idCon, e.tipo, c.idCcos , b.valor , -99 as idFor, 0 as idPres, ff.codigo as nitTer 
            from n_nomina_e a 
                      inner join n_presta_primas_nu b on b.idEmp=a.idEmp
                      inner join a_empleados c on c.id=a.idEmp 
                      inner join n_tip_prestamo d on d.id = b.idTpres 
                      inner join n_conceptos e on e.id = d.idConE 
                     left join n_terceros_s f on f.id = e.idTer # sucursal del tercero  
                  left join n_terceros ff on ff.id = f.idTer # Funciones para retorno y salida de vacaciones 
                      where b.estado = 0 and a.idNom=".$id."  
            group by a.idEmp, b.idTpres   
            order by a.idEmp",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                  
   // Consulta para recorrer nomina generada 
   public function getNgenerada($id,$ti)
   {
      $result=$this->adapter->query("select b.id,a.idEmp,b.horas,e.formula,d.tipo,a.dias,b.horas,b.idInom
                                   from n_nomina_e a inner join n_nomina_e_d b on a.id=b.idInom
                                   inner join a_empleados c on c.id=a.idEmp
                                   inner join n_conceptos d on d.id=b.idConc
                                   inner join n_formulas e on e.id=d.idFor
           where a.idNom=".$id." and b.tipo=".$ti."  and ( b.devengado=0 and b.deducido=0)  
    order by a.idEmp",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }        

   // Generar periodos de nominas por tipos de nomina, grupos de empleados y calendario 
   public function getGenerarP($idTnom,$idGrupE,$idCal)
   {

       $this->adapter->query("insert into n_tip_calendario_d (idTnom,idGrupo,idCal,fechaI,fechaF,estado)(
                              select ".$idTnom." as idTnom, ".$idGrupE." as idGrup, ".$idCal." as idTcal,
                                     concat( year(now()),'-',a.mesI,'-',a.diaI),
                                     concat(year(now()),'-',a.mesF,'-',a.diaF), 0 as estado                  
             from n_tip_calendario_p a 
                                     where a.idCal=".$idCal." and not exists (SELECT null from n_tip_calendario_d c 
                                        where c.idTnom=".$idTnom." and c.idGrupo=".$idGrupE." and c.idCal=".$idCal." and year(c.fechaI)=year(now()) )
                                        order by a.orden)",Adapter::QUERY_MODE_EXECUTE);                                                      
   }            
   // Generar periodos de nominas por tipos de nomina, grupos de empleados y calendario proyectado
   public function getGenerarPro($idTnom,$idGrupE,$idCal,$ano)
   {

       $this->adapter->query("insert into n_tip_calendario_d (idTnom,idGrupo,idCal,fechaI,fechaF,estado)(
                              select ".$idTnom." as idTnom, ".$idGrupE." as idGrup, ".$idCal." as idTcal,
                                     concat( ".$ano." ,'-',a.mesI,'-',a.diaI),
                                     concat( ".$ano." ,'-',a.mesF,'-',a.diaF), 0 as estado                  
             from n_tip_calendario_p a 
                                     where a.idCal=".$idCal." and not exists (SELECT null from n_tip_calendario_d c 
                                        where c.idTnom=".$idTnom." and c.idGrupo=".$idGrupE."
                                         and c.idCal=".$idCal." and year(c.fechaI)= ".$ano."  )
                                        order by a.orden)",Adapter::QUERY_MODE_EXECUTE);                                                      
   }               
   // ( REGISTRO DE NOVEDADES ) ( n_novedades ) 
   public function getRnovedades($id, $idIcal)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, 0 as dias, c.horas, 
      c.devengado as dev, c.deducido as ded, i.formula, c.id as idInov, 
g.tipo, h.idCcos, c.idConc as idCon, i.id as idFor, c.calc, c.fechaEje, c.idProy,
case when k.cuotas is null then 0 else (if( c.devengado > 0, (c.devengado/k.cuotas), (c.deducido/k.cuotas)  )) end as valCuota, # Cuotas de la novedad          
k.pagado 
from n_nomina_e a 
 inner join n_nomina b on b.id=a.idNom
inner join n_novedades c on c.idEmp=a.idEmp
left join n_tip_matriz_tnv d on d.id=c.idTmatz
left join n_tip_matriz e on e.id=d.idTmatz and e.idTnom=b.idTnom 
inner join n_conceptos g on g.id=c.idConc 
inner join a_empleados h on h.id=a.idEmp 
inner join n_formulas i on i.id=g.idFor 
inner join n_tip_calendario_d j on j.id=c.idCal 
left join n_novedades_cuotas k on k.idInov = c.id # Novedades por cuotas  
where c.estado=0 and ( c.idCal = ".$idIcal." or b.idTnom = 5 ) and  b.id =".$id."  
  order by g.ordenN desc # Colocar de primero los de gran prioridad por logeneral sin formulas ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                      
   // ( REGISTRO DE NOVEDADES EN LIQUIDACION) ( n_nomina_nove ) 
   public function getRnovedadesN($id, $con )
   {        
     $result=$this->adapter->query("select distinct a.id, a.idEmp, 0 as dias, d.horas, 
      d.devengado as dev, d.deducido as ded, i.formula, d.id as idInov, 
         g.tipo, h.idCcos, d.idConc as idCon, i.id as idFor, 0 as calc, 
         '' as fechaEje, 0 as idProy, 0 as valCuota # Cuotas de la novedad          
         ,d.editado, d.idInov as idInovN , b.idIcal, g.nombre     
     from n_nomina_e a 
        inner join n_nomina b on b.id=a.idNom
        inner join n_nomina_nov d on d.idIcal = b.idIcal and d.idEmp = a.idEmp 
        inner join n_conceptos g on g.id=d.idConc 
        inner join a_empleados h on h.id=a.idEmp 
      inner join n_formulas i on i.id=g.idFor 
    where d.estado=0 and b.id = ".$id." ".$con  ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }           
   // ( ANTICIPOS PARA DESCONTAR EN NOMINA ) ( n_nomina_nove ) 
   public function getDescontarAnt($id )
   {        
     $result=$this->adapter->query("select distinct a.id, a.idEmp, 0 as dias, 0 as horas, 
     0 as dev, 
        ( select sum( bb.devengado ) from n_nomina_e aa 
                      inner join n_nomina_e_d bb on bb.idInom = aa.id 
                      inner join n_nomina cc on cc.id = aa.idNom 
              where aa.idEmp = a.idEmp and cc.idTnom = 11 and bb.idConc = 52 and bb.causado=0  ) as ded, '' as formula, 0 as idInov,      
         g.tipo, h.idCcos, 0 , i.idDesAnt as idCon, 0 as idFor, 0 as calc, 
         ' ' as fechaEje, 0 as idProy, 0 as valCuota # Cuotas de la novedad          
         ,0 as editado, 0 as idInovN , b.idIcal, g.nombre     
     from n_nomina_e a 
        inner join n_nomina b on b.id=a.idNom
        inner join c_general i on i.id = 1
        inner join n_conceptos g on g.id=i.idDesAnt  
        inner join a_empleados h on h.id=a.idEmp 
    where b.id = ".$id  ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                 
   // Documento de novedades por empleado de acuerdo al tipo
   public function getDocNove($idn,$con)
   {        
     // $id    : Id documento de novedades
     // $tipo  : tipo ('1','2','3')

     $result=$this->adapter->query("select a.idNom,a.dias,b.id,b.horas,d.nombre,e.formula,d.tipo,d.valor,b.idCcos,b.devengado,b.deducido
                                        ,d.id as idCon,c.id as idEmp,d.idFor,b.horDias, a.diasVac, b.saldoPact, b.idCpres,
                                        b.idProy, b.fechaEje, b.idInov , f.idTnom     
                                        from n_nomina_e a 
                                        inner join n_nomina_e_d b on a.id=b.idInom
                                        inner join a_empleados c on c.id=a.idEmp
                                        inner join n_conceptos d on d.id=b.idConc
                                        inner join n_formulas e on e.id=d.idFor 
                                        inner join n_nomina f on f.id = a.idNom 
                                        where b.idInom=".$idn." ".$con." order by d.tipo " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;     
    }        
   // Numero de novedades por empleado de acuerdo al tipo
   public function getDocNoveN($idn,$con)
   {        
     // $id    : Id documento de novedades
     // $tipo  : tipo ('1','2','3')

     $result=$this->adapter->query("select count(b.id) as num   
                                        from n_nomina_e a 
                                        inner join n_nomina_e_d b on a.id=b.idInom
                                        inner join a_empleados c on c.id=a.idEmp
                                        inner join n_conceptos d on d.id=b.idConc
                                        inner join n_formulas e on e.id=d.idFor
                                        where b.idInom=".$idn." ".$con." order by d.tipo " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;     
    }            
   // ( REGISTRO DE NOVEDADES DE PROYECTOS) ( n_novedades ) 
   public function getRproyectos($id,$fechaI,$fechaF)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, 0 as dias,
             (case when d.id is null then (c.horas/8/2) else d.dias end ) * 8 as horas, e.formula,
              122 as idCon, 1 as tipo, 0 as dev, 0 as ded, 0 as calc , 
              '' as fechaEje, c.idProy, b.idCcos , 3 as idFor, # Momentanea sueldo           
              c.prog , d.dias as diasProy , c.horasLiq, b.sueldo    
                    from n_nomina_e a 
                      inner join n_proyectos_e c on c.idEmp = a.idEmp 
                      inner join a_empleados b on b.id = a.idEmp 
                      inner join n_formulas e on e.id = 3 
                      left join n_novedades_pr d on d.idProy = c.idProy and d.idEmp = a.idEmp and d.estado = 0 and c.estado = 0 
                    where a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                       
   // ( REGISTRO DE NOVEDADES DE PROYECTOS) ( n_novedades ) 
   public function getRproyectosE($id)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, b.idCcos, d.idMod   
                    from n_nomina_e a 
                      inner join n_proyectos_e c on c.idEmp = a.idEmp 
                      inner join a_empleados b on b.id = a.idEmp 
                      inner join n_proyectos d on d.id = c.idProy 
                    where a.idNom = ".$id." and c.estado = 0 and c.prog = 1",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                          
   // ( REGISTRO DE NOVEDADES DE LIQUIDACION) ( n_desvicnulaciones ) 
   public function getLiquida($id,$fechaI,$fechaF)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, 0 as horas,
             (  datediff( c.fechaF, d.fechaI ) + 1 ) as dias, '' as formula,
              0 as idCon, 1 as tipo, 0 as dev, 0 as ded, 0 as calc , 
              '' as fechaEje, 0 as idProy, b.idCcos , 0 as idFor # Momentanea sueldo           
                    from n_nomina_e a 
                      inner join t_desvinculacion_e c on c.idEmp = a.idEmp 
                      inner join a_empleados b on b.id = a.idEmp 
                      inner join n_nomina d on d.id = a.idNom 
                    where a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                          
   // ( REGISTRO DE VACACIONES DISFRUTE) ( n_vacaciones ) 
   public function getVacacionesG($id)
   {        
     $result=$this->adapter->query("select a.idNom, a.id, b.idCon, ' ' as formula, b.dias, d.tipo,  a.idEmp,  
                 f.idCcos , 0 as horas , # Para obtener el numero de periodos pagados en esta nomina 
         b.valor as dev,0 as ded, e.id as idFor, b.diasCal, day( b.fechaI ) as diaI ,
       case when ( (month(b.fechaF ) > month(b.fechaI) ) and c.idCal=6 ) # Si es una vacacion por nomina de vacaciones y pasa al otro periodo 
               then 99 # se pone esto para que aplique el fondo de solidaridad
                else day( b.fechaI ) end as fondo, year(c.fechaI) as ano , month(c.fechaI) as mes, 

      # calcular los valores para restar en la salida de vacaciones es despues -----------
                
           round( ( case when month(DATE_SUB( b.fechaR ,INTERVAL 1 day) ) > month( c.fechaF ) then 
                 datediff( DATE_SUB( b.fechaR ,INTERVAL 1 day), concat( year(b.fechaR),'-',month(b.fechaR),'-01')  ) + 1  
            else
               0 
              end ) * ( b.valor / b.diasCal ) ) as valRestaVaca , b.dias as diasVac 

            from n_nomina_e a 
                 inner join n_vacaciones b on b.id=a.idVac and b.estado in ('1')  
                 inner join n_nomina c on c.id=a.idNom     
                 inner join n_conceptos d on d.id=b.idCon
                 inner join n_formulas e on e.id=d.idFor
                 inner join a_empleados f on f.id=a.idEmp
                 where  (  c.idCal = 6 or (b.fechaI >= c.fechaI and b.fechaI <= b.fechaF) )
                    and a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }// FIN ( REGISTRO DE VACACIONES COMPENSADAS) ( n_vacaciones ) 
   // ( REGISTRO DE VACACIONES DISFRUTE) ( n_vacaciones ) 
   public function getVacacionesGc($id)
   {        
     $result=$this->adapter->query("select a.idNom, a.id, b.idCon, ' ' as formula, 
                 b.diasDinero as dias, d.tipo,  a.idEmp,  
                 f.idCcos , 0 as horas , # Para obtener el numero de periodos pagados en esta nomina 
         b.valorDinero as dev,0 as ded, e.id as idFor, b.diasCal, day( b.fechaI ) as diaI       
            from n_nomina_e a 
                 inner join n_vacaciones b on b.idEmp = a.idEmp and b.estado in ('1')  
                 inner join n_nomina c on c.id=a.idNom     
                 inner join n_conceptos d on d.id=b.idCon
                 inner join n_formulas e on e.id=d.idFor
                 inner join a_empleados f on f.id=a.idEmp
                 where b.diasDinero > 0 and b.estado = 1 and a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }// FIN ( REGISTRO DE VACACIONES COMPENSADAS) ( n_vacaciones )    
   // Vacaciones de empleados
   public function getVacaciones($id)
   {
      $result=$this->adapter->query("select b.fechaI as fecVacI, b.fechaR as fecVacF,
                 c.fechaI as fecPerI, c.fechaF as fecPerF, 
                 a.idEmp, b.estado, d.idCcos,c.idTnom , 
              # validar mes-----------------------------    
          case when ( select a.idTnom 
                 from n_nomina a 
                          inner join n_nomina_e b on b.idNom = a.id 
                        where b.idEmp = a.idEmp and a.idTnom in (1,8) 
                       order by a.id desc limit 1 ) = 8 then 
      
              # Dias de vacaciones 
            (case when b.fechaI <= c.fechaI and b.fechaF >= c.fechaF then 30 else # fuera de los periodos de nomina
                  case when b.fechaI > c.fechaI and b.fechaF >= c.fechaF then # Mayor al periodo inicial y mayor al periodo final 
              30 - ( datediff( concat( year(c.fechaF) , '-',lpad( month(c.fechaF),2,'0' ) , '-', 
              ( case when day(last_day(c.fechaF)) != 31 then 30 else 30 end ) ), b.fechaI ) + 1 ) 
            else 
               case when b.fechaI < c.fechaI and b.fechaF <= c.fechaF then # Menor a fecha inicial y fecha menor a fecha mayor
                  datediff( b.fechaF , concat( '01-',lpad( month(c.fechaI),2,'0' ) , '-', year(c.fechaI)  ) )
               else 
                 # dias dentro del periodo 
                   case when b.fechaI > c.fechaI then 
                    datediff( b.fechaI, c.fechaI ) 
                 else    
                   ( datediff( concat( year(c.fechaF) , '-',lpad( month(c.fechaF),2,'0' ) , '-30') , b.fechaR  ) + 1 ) 
                end   
               end 
            end    
             
             end   )                       
                      
      else # Tipo de nomina quincenal                 

          case when day(b.fechaR) = 31 then 0 else 
          
              # Dias de vacaciones 
            (case when b.fechaI <= c.fechaI and b.fechaR >= c.fechaF then 
              0
               else # fuera de los periodos de nomina
                  case when b.fechaI >= c.fechaI and b.fechaR >= c.fechaF then # Mayor al periodo inicial y mayor al periodo final 

               ( datediff( b.fechaI , c.fechaI )  ) 

            else 
               case when b.fechaI < c.fechaI and b.fechaR <= c.fechaF then # Menor a fecha inicial y fecha menor a fecha mayor

                  datediff( c.fechaF, b.fechaR  )+1

               else 
                 # dias dentro del periodo 
                 ( datediff( b.fechaI , c.fechaI ) ) + ( 0 )
               end 
            end    
             
             end   )                                        
        end    

     end as dias, 

  # Validar retorno  
    case when ( ( b.fechaR >= c.fechaI) and ( b.fechaR <= c.fechaF ) ) then 
     1 
    else 0 end             
    as retorna ,
   datediff( b.fechaR, b.fechaI ) as diasVac ,
 ( select count(aa.id) 
      from n_nomina_e aa  
          inner join n_nomina bb on bb.id = aa.idNom 
         where aa.idEmp = a.idEmp and aa.idVac = b.id and aa.idNom != a.idNom and bb.estado =  2 
    ) as vacPaga , # Dtereminar si la vacacion ya fue pagada  
( datediff( c.fechaF , b.fechaR ) + 1 ) as diasRetorno 
            from n_nomina_e a 
                 inner join n_vacaciones b on b.id=a.idVac and b.estado in ('1','2')  
                 inner join n_nomina c on c.id=a.idNom    
                 inner join a_empleados d on d.id = a.idEmp 
                 where b.dias >0 and b.fechaR >= c.fechaI and a.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
    }         
   // Incapacidades de empleados
   public function getIncapacidades($id)
   {
        $result=$this->adapter->query("select a.id, c.fechai, c.fechaf, b.idEmp, b.idInc,
          d.fechaI, d.fechaF, a.dias, ( case when b.reportada = 0 then sum( b.diasAp )else 0 end ) as diasAp, sum( b.diasDp ) as diasDp,
          e.dias as diasEnt , e.nombre, b.reportada,
          case when c.fechai <= d.fechaI and c.fechaf >= d.fechaF then 1 else 0 end as total, # Pasa todo el periodo de nomina           
          ( DATEDIFF( d.fechaF , d.fechaI )+ 1 ) as diasCal  
           from n_nomina_e a
            inner join n_nomina_e_i b on b.idEmp = a.idEmp and b.idNom = a.idNom 
            left join n_incapacidades c on c.id = b.idInc
            inner join n_nomina d on d.id = b.idNom 
            left join n_tipinc e on e.id = c.idInc 
            where a.idNom = ".$id." 
            group by a.idEmp 
            order by a.idEmp",Adapter::QUERY_MODE_EXECUTE);
      //$datos = $result->current();
      $datos=$result->toArray();
      return $datos;
    }             
   // Ausentismos de empleados
   public function getAusentismos($id)
   {
      $result=$this->adapter->query("select b.id, sum( a.dias ) as dias, sum( a.diasAp  ) as diasAp, a.tipo 
            from n_nomina_e_a a
                inner join n_nomina_e b on b.idNom = a.idNom and b.idEmp = a.idEmp 
             where a.horas = 0 and a.idNom = ".$id." group by a.idEmp, a.tipo   ",Adapter::QUERY_MODE_EXECUTE);
      //$datos = $result->current();
      $datos=$result->toArray();
      return $datos;
    }            


   // Ausentismos de empleados no reportados en fecha convenida
   public function getAusentismosNoreport($id)
   {
        $result=$this->adapter->query("select b.id , a.dias  
            from n_nomina_e_a a
                inner join n_nomina_e b on b.idNom = a.idNom and a.idEmp = b.idEmp 
                where a.periodoAnt = 1 and a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      //$datos = $result->current();
      $datos=$result->toArray();
      return $datos;
    }         


   // EMBARGOS NOMINA ( n_nomina_e_d ) 
   public function getIembargos($id)
   {
      $result=$this->adapter->query("select a.id, a.idEmp, a.idEmp, b.valor , 
                c.idCon as idCon,0 as dias, b.id as idEmb, b.valor - ( b.pagado + b.saldoIni ) as pagado , g.sueldo, 
                b.formula , e.tipo, g.idCcos , 0 as idFor ,0 as horas, hh.codigo as nitTer   # Se colcoa 9 para que realice la formula
                from n_nomina_e a 
                inner join n_embargos b on b.idEmp=a.idEmp  
                inner join n_tip_emb c on c.id = b.idTemb 
                inner join n_conceptos e on e.id=c.idCon 
                inner join a_empleados g on g.id=a.idEmp 
                inner join n_terceros_s h on h.id = b.idTer
            inner join n_terceros hh on hh.id = h.idTer  
                where b.estado=1 and a.idNom=".$id." and g.vacAct = 0
                   group by b.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }     
   // EMBARGOS NOMINA ( n_nomina_e_d ) 
   public function getIembargosPrimas($id)
   {
      $result=$this->adapter->query("select a.id, a.idEmp, a.idEmp, b.valor , 
                c.idCon as idCon,0 as dias, b.id as idEmb, b.valor - ( b.pagado + b.saldoIni ) as pagado ,
                b.formulaPrimas as formula , e.tipo, g.idCcos , 0 as idFor ,0 as horas, hh.codigo as nitTer   # Se colcoa 9 para que realice la formula
                from n_nomina_e a 
                inner join n_embargos b on b.idEmp=a.idEmp  
                inner join n_tip_emb c on c.id = b.idTemb 
                inner join n_conceptos e on e.id=c.idCon 
                inner join a_empleados g on g.id=a.idEmp 
                inner join n_terceros_s h on h.id = b.idTer
            inner join n_terceros hh on hh.id = h.idTer  
                where c.tipo=1 and b.estado=1 and a.idNom=".$id." group by b.id" ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                         
   // INCAPACIDADES NOMINA ( n_nomina_e_d ) 
   public function getIncapNom($id, $tipo)
   {
      if ($tipo==0) // Incapacidades
      {
         $diasEmp = 'b.diasEmp';
         $con = 'inner join n_incapacidades b on b.id=a.idInc 
                inner join n_tipinc c on c.id=b.idInc';
         $campo = '';    
         $tipInc = ' ';   
      }else{ // Prorrogas
         $diasEmp = '0';
         $con = 'inner join n_incapacidades_pro b on b.id=a.idInc 
                 inner join n_incapacidades bp on bp.id = b.idInc  
                 inner join n_tipinc c on c.id=bp.idInc';
         $campo = ', bp.id as idInPadre';               
         $tipInc = ' and d.tipo = 2 # Solo lo que paga la entidad ';   
      }
      
      $result=$this->adapter->query("select h.id, a.idEmp, a.idEmp, d.idConc as idCon,0 as dias,
                f.formula , e.tipo, g.idCcos , e.idFor, a.diasAp, a.diasDp,
           case when ( ( a.diasAp + a.diasDp ) >= (c.dias - 1) )# Dias inicio pago empreasa
             then ( c.dias - 1) - ".$diasEmp." # se restan los dias por la empresa ya apgos, esto pasa cuando en un periodo no se pagan los dias de la empresa completo
                else 
                    ( a.diasAp + a.diasDp ) end as diasEmp,              
             # Se buscan dias anteriores reportados o no reportados 
          case when a.reportada = 1 then    
            case when ( ( a.diasDp ) > (c.dias - 1) )# Dias inicio pago entidad
               then ( ( a.diasDp ) - ( c.dias - 1 ) ) else diasDp - ( ( c.dias - 1 )- ".$diasEmp." ) # caso cuando queda pendiente un pago por la empresa 
                end 
          else 
            case when ( ( a.diasAp + a.diasDp ) > (c.dias - 1) )# Dias inicio pago entidad
               then ( ( a.diasAp + a.diasDp ) - ( c.dias - 1 ) ) else 0 end 
          end  as diasEnt,             
               d.tipo as tipInc, b.id as idInc, a.reportada ,
              case when (
                ((DATE_ADD( b.fechai , interval (c.dias-1) day)) >= i.fechaI ) and ((DATE_ADD( b.fechai , interval (c.dias-1) day)) <= i.fechaF) )
                 then # Verificar si los dias de la empresa estan dentro del periodo de nomina                    
                      c.dias -1
              else                   
                   ( a.diasAp + a.diasDp )   
                 end   as diasEmpN,  # dias pagados por empresa , para reeemplazar al de arriba                                  
                 a.reportada, a.diasInc , j.valor as diasCal, b.fechai , b.fechaf , c.nombre as nomTinc ".$campo."  
                from n_nomina_e_i a 
                ".$con." 
                inner join n_tipinc_c d on d.idTinc=c.id 
                inner join n_conceptos e on e.id=d.idConc
                inner join n_formulas f on f.id=e.idFor
                inner join a_empleados g on g.id=a.idEmp
                inner join n_nomina_e h on h.idEmp = a.idEmp and h.idNom = a.idNom   
                inner join n_nomina i on i.id = h.idNom 
                inner join n_tip_calendario j on j.id = i.idCal 
                where a.idNom = ".$id." and a.tipo = ".$tipo."  ".$tipInc." 
                order by a.idEmp",Adapter::QUERY_MODE_EXECUTE);       
   
      $datos=$result->toArray();
      return $datos;
    }                 
   // INCAPACIDADES POR PROROGAS NOMINA ( n_nomina_e_d ) 
   public function getIncaPpNom($id)
   {
      $result=$this->adapter->query("select h.id, a.idEmp, a.idEmp, d.idConc as idCon,0 as dias,
                f.formula , e.tipo, g.idCcos , e.idFor, a.diasAp, a.diasDp,
           case when ( ( a.diasAp + a.diasDp ) >= (c.dias - 1) )# Dias inicio pago empreasa
             then ( c.dias - 1) else ( a.diasAp + a.diasDp ) end as diasEmp,              
             # Se buscan dias anteriores reportados o no reportados 
          case when a.reportada = 1 then    
            case when ( ( a.diasDp ) > (c.dias - 1) )# Dias inicio pago entidad
               then ( ( a.diasDp ) - ( c.dias - 1 ) ) else 0 end 
          else 
            case when ( ( a.diasAp + a.diasDp ) > (c.dias - 1) )# Dias inicio pago entidad
               then ( ( a.diasAp + a.diasDp ) - ( c.dias - 1 ) ) else 0 end 
          end  as diasEnt,             
               d.tipo as tipInc, b.id as idInc, bp.id as idInPadre         
                from n_nomina_e_i a 
                inner join n_incapacidades_pro b on b.id=a.idInc 
                inner join n_incapacidades bp on bp.id = b.idInc 
                inner join n_tipinc c on c.id=bp.idInc
                inner join n_tipinc_c d on d.idTinc=c.id 
                inner join n_conceptos e on e.id=d.idConc
                inner join n_formulas f on f.id=e.idFor
                inner join a_empleados g on g.id=a.idEmp
                inner join n_nomina_e h on h.idEmp = a.idEmp and h.idNom = a.idNom   
                where a.idNom = ".$id."  and a.tipo = 1 
                order by a.idEmp",Adapter::QUERY_MODE_EXECUTE);       
   
      $datos=$result->toArray();
      return $datos;
    }                     
   // ( POR AUSENTISMOS ) 
   public function getNominaAus($id)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, a.idEmp, d.idConc as idCon,0 as dias,
                '' as formula , e.tipo, g.idCcos , e.idFor, g.idCcos, 

                   case when b.horas > 0 then b.horas else aa.dias end as horas, # temporal para cuando cambie el periodo , tener en cuenta saldo horas
                   
                b.fechai , b.fechaf, e.nombre as nomCon, c.tipo as tipAus , 
                ( g.sueldo / 30 ) * aa.dias as valor, b.horas as horAus # determina si es en horas o dias el ausentismo                 
             from n_nomina_e a 
                inner join n_nomina_e_a aa on aa.idNom = a.idNom and aa.idEmp = a.idEmp 
                inner join a_empleados g on g.id = a.idEmp 
                inner join n_ausentismos b on b.id = aa.idAus                           
                inner join n_tip_aus c on c.id = b.idTaus
                inner join n_tip_aus_c d on d.idTaus = c.id 
                inner join n_conceptos e on e.id = d.idConc
                inner join n_formulas f on f.id = e.idFor  
                inner join n_nomina h on h.id = a.idNom 
                where b.estado=1 and a.idNom=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }

   // ( DIAS TRABAJADOS ULTIMO PERIODO CESANTIAS ) DEBE TENER LIBRO DE CESANTIAS O MADAR ERROR EN LACNSLTA  
   public function getDiasCesa($id, $idNom, $fechaI)
   {        
     $result=$this->adapter->query("select a.idNom, a.id, a.idEmp, b.idFces,   
              case when c.fechaF is null then b.fecIng else c.fechaF end as fechaC,
                 case when c.fechaF is null 
                      then  # Se saca dias de cesantias 
                         case when ( bb.fechaI < d.fechaIc ) then # si el contrato es antes del calendario actual de cesantias 
                           datediff( d.fechaF, '".$fechaI."'  ) + 1 
                         else
                          
                           datediff( concat( case when year(d.fechaF)='2016' then year(d.fechaF)-1 else year(d.fechaF) end  ,'-',
                        lpad(  case when year(d.fechaF)='2016' then 12 else month( d.fechaF ) end ,2,'0'),'-31' ) , bb.fechaI ) +1

                         end 
                       else
                         (DATEDIFF( d.fechaF , d.fechaIc ) + 1)  # Esta con el calendario actual 
               end as diasCes, # OJO DIAS CALENDARIOS  
         e.tipo, e. id as idCon, b.idCcos, bb.fechaI, month( bb.fechaI ) as mesIngr, 
         ( case when b.sueldo < (2*".$this->salarioMinimo.") then ".$this->subsidioTransporte." else 0 end ) as subTransporte,
               month(d.fechaF) as mesF, b.variable, d.idTnom                 
                 from n_nomina_e a 
                 inner join a_empleados b on b.id = a.idEmp 
                 inner join n_emp_contratos bb on bb.tipo = 1 and bb.idEmp = b.id # Buscar contrato activo 
                 left join  n_cesantias c on c.idEmp = b.id  
                 inner join n_nomina d on d.id = a.idNom 
                 inner join n_conceptos e on e.id=195 # Se debe colocar el id del concepto usado para las cesantias 
                 where a.idEmp = ".$id." and a.idNom = ".$idNom,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   } 
   // ( CONSULTA EMPLEADOS PRIMAS SEMESTRALES ) 
   public function getDiasPrima($idEmp, $fechaI, $fechaF, $diasPrima, $diasLabor, $idNom)
   {       

      $d = new AlbumTable($this->adapter); 
      $datGen = $d->getConfiguraG(''); //---------------- CONFIGURACIONES GENERALES (1)

     if ( $datGen['promPrimas'] == 0)
     { 
       // Procedimiento ra actualizar por este  
     $result=$this->adapter->query("select  
round( case when sum(valor) is null then 
    ( sum(sueldo) + ( case when representante=1 then (4*".$this->salarioMinimo.") else 0 end  ) + (  case when sum(sueldo) <= (2*".$this->salarioMinimo.") then ".$this->subsidioTransporte." else 0 end )  ) * ".$diasPrima." / 360  
 else 
   # Cuando hay novedades se hace esta formula 
( ( ( sum(valor) ) / ( (".$diasPrima." ))*30 ) + sum(sueldo)  + ( case when representante=1 then (4*".$this->salarioMinimo.") else 0 end  ) + (  case when sum(sueldo) <= (2*".$this->salarioMinimo.") then ".$this->subsidioTransporte." else 0 end )  ) * ".$diasPrima."/ 360 
end ,0 ) as promedioMes,
 ( case when sum(sueldo) <= (2*".$this->salarioMinimo.") then ".$this->subsidioTransporte." else 0 end ) as subTransporte     

      from (
select  sum( i.devengado) as valor, h.sueldo as sueldo, h.representante 
                from n_nomina_e a 
                inner join n_nomina b on b.id=a.idNom
                inner join a_empleados h on h.id=a.idEmp 
                inner join n_nomina_e_d i on i.idInom = a.id 
                inner join n_conceptos j on j.id = i.idConc 
                inner join n_conceptos_pr k on k.idConc = j.id and k.idProc = 4 # Procesos de primas 
            where b.idTnom != 6 and b.estado=2 and (b.fechaI>='".$fechaI."' and b.fechaF<='".$fechaF."') and  
    b.fechaI >= ( select emp.fechaI  from n_emp_contratos emp where emp.tipo = 1 and emp.idEmp = a.idEmp limit 1 ) # validacion novedades despues de ingreso del empleado                  

and i.idConc !=122  and i.idConc != ( case when h.representante=1 then 173 else 0 end ) 
    and i.idConc != ( case when h.representante=1 then 231 else 0 end )#diferente al sueldo 
    
 and a.idEmp = ".$idEmp."  
union all # unido con procesos extralegales 
select sum( i.devengado  ) as 'valor', 0 as sueldo, h.representante   
          from n_nomina_e a 
                inner join n_nomina b on b.id=a.idNom
                inner join a_empleados h on h.id=a.idEmp 
                inner join n_nomina_e_d i on i.idInom = a.id 
                inner join n_cencostos l on l.id = h.idCcos 
                inner join t_cargos m on m.id = h.idCar 
                inner join n_conceptos j on j.id = i.idConc 
                inner join n_conceptos_pr k on k.idConc = j.id and k.idProc = 16 # Procesos de primas 
                where a.idNom = ".$idNom." and a.idEmp = ".$idEmp." ) as a",Adapter::QUERY_MODE_EXECUTE);     
      }else{
         // Se incluye el promedio 
       // Procedimiento ra actualizar por este  
     $result=$this->adapter->query("select  
( ( ( ( sum(valor) ) / ( (".$diasLabor."
 ))) *30  )  ) * ".$diasPrima."/ 360  as promedioMes, 0 as subTransporte   
      from (
select  sum( i.devengado - i.deducido ) as valor, h.sueldo as sueldo, h.representante  
            from n_nomina_e a 
                inner join n_nomina b on b.id=a.idNom
                inner join a_empleados h on h.id=a.idEmp 
                inner join n_nomina_e_d i on i.idInom = a.id 
                inner join n_conceptos j on j.id = i.idConc 
                inner join n_conceptos_pr k on k.idConc = j.id and k.idProc = 4 # Procesos de primas 
            where b.idTnom != 6 and b.estado=2 and i.desc = 0 and (b.fechaI>='".$fechaI."' and b.fechaF<='".$fechaF."')  and   
    b.fechaI >= ( select concat( year(emp.fechaI),'-',lpad(month(emp.fechaI),2,'0'),
      (case when day( emp.fechaI)>15 then '-15' else '-01' end)   ) from n_emp_contratos emp where emp.tipo = 1 and emp.idEmp = a.idEmp limit 1 ) # validacion novedades despues de ingreso del empleado                  
     and a.idEmp = ".$idEmp." ) as a",Adapter::QUERY_MODE_EXECUTE);     
      }  
      $datos=$result->current();
      return $datos;       
   }        
   // ( CONSULTA EMPELADOS PARA PRIMA DE ANTIGUEDADES CONDICIONADAS ) 
   public function getDiasPanti($id, $ano, $mes)
   {                  
      if ($mes > 0 )
      {
       $mes = $mes + ( $ano * 12 ); // Pasar los años a meses    
       $result=$this->adapter->query("select b.id, b.idEmp ,0 as dias,c.idCcos, 
             c.fecing , DATE_ADD( c.fecing , interval ".$mes." month) , c.CedEmp , c.nombre , c.idTemp, case when e.id > 0 then 1 else 0 end as pg,  
             case when (day( d.fecha) >= day(a.fechaI)) and (day( d.fecha) <= day(a.fechaF)) then 1 else 0 end as diaI , d.fecha   
             from n_nomina a
             inner join n_nomina_e b on b.idNom = a.id
             inner join a_empleados c on c.id = b.idEmp 
             inner join n_tipemp_p d on d.idEmp = b.idEmp and d.idTemp = 1 # mientras 1 es para los convencionados 
             left join n_pg_primas_ant e on e.idEmp = b.idEmp and year(e.fechaI) = year(a.fechaI) and month( e.fechaI ) and month( a.fechaI )
             where c.activo=0 and c.estado = 0 
             and (  ( year( DATE_ADD( d.fecha , interval ".$mes." month) ) = year( a.fechaI ) )
             and ( month( DATE_ADD( d.fecha , interval ".$mes." month) ) = month( a.fechaI ) ) ) 
             and a.id = ".$id." 
             order by c.fecing desc",Adapter::QUERY_MODE_EXECUTE);                 
      }else{
       $result=$this->adapter->query("select b.id, b.idEmp ,0 as dias,c.idCcos, 
             c.fecing , day( d.fecha ) as diaI, DATE_ADD( c.fecing , interval ".$ano." year) , c.CedEmp , c.nombre , c.idTemp, case when e.id > 0 then 1 else 0 end as pg,
             case when (day( d.fecha) >= day(a.fechaI)) and (day( d.fecha) <= day(a.fechaF)) then 1 else 0 end as diaI, d.fecha                   
             from n_nomina a
             inner join n_nomina_e b on b.idNom = a.id
             inner join a_empleados c on c.id = b.idEmp 
             inner join n_tipemp_p d on d.idEmp = b.idEmp and d.idTemp = 1 # mientras 1 es para los convencionados
             left join n_pg_primas_ant e on e.idEmp = b.idEmp and year(e.fechaI) = year(a.fechaI) and month( e.fechaI ) and month( a.fechaI ) 
             where c.activo=0 and c.estado = 0 
             and (  ( year( DATE_ADD( d.fecha , interval ".$ano." year) ) = year( a.fechaI ) )
             and ( month( DATE_ADD( d.fecha , interval ".$ano." year) ) = month( a.fechaI ) ) ) 
             and a.id = ".$id." 
             order by c.fecing desc ",Adapter::QUERY_MODE_EXECUTE);      
      }      
      $datos=$result->toArray();
      return $datos;       
   } 
   // ( CONSULTA EMPELADOS PARA PRIMA DE ANTIGUEDADES ANUALES ) 
   public function getDiasPantiA($id, $ano, $anoF)
   {                        
       $result=$this->adapter->query("select b.id, b.idEmp ,0 as dias,c.idCcos, 
             c.fecing, ( DATE_FORMAT( a.fechaF  , '%Y-%m-%d' ) - DATE_FORMAT( d.fecha , '%Y-%m-%d' ) ) 
         , c.CedEmp , c.nombre , c.idTemp , case when e.id > 0 then 1 else 0 end as pg, 
             case when (day( d.fecha) >= day(a.fechaI)) and (day( d.fecha) <= day(a.fechaF)) then 1 else 0 end as diaI, d.fecha    
             from n_nomina a
             inner join n_nomina_e b on b.idNom = a.id
             inner join a_empleados c on c.id = b.idEmp 
             inner join n_tipemp_p d on d.idEmp = b.idEmp and d.idTemp = 1 # mientras 1 es para los convencionados 
             left join n_pg_primas_ant e on e.idEmp = b.idEmp and year(e.fechaI) = year(a.fechaI) and month( e.fechaI ) and month( a.fechaI ) 
             where c.activo=0 and c.estado = 0 
             and a.id = ".$id." and (  ( DATE_FORMAT( a.fechaF  , '%Y-%m-%d' )
                 - DATE_FORMAT( d.fecha , '%Y-%m-%d' ) )>= ".$ano." 
               and  ( DATE_FORMAT( a.fechaF  , '%Y-%m-%d' )
                 - DATE_FORMAT( d.fecha , '%Y-%m-%d' ) ) < ".$anoF."  ) 
             and month( d.fecha ) = month(a.fechaF)
             order by c.fecing desc  ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   } 
 
   // Retencion en la fuente a empleados
   public function getRetFuente($id, $periodoNomina)
   {
        $result=$this->adapter->query("Select d.id, d.idEmp , e.idCcos,
           year(f.fechaF) as ano, month(f.fechaF) as mes ,d.dias, a.idNom   ,   
 ( select sum(cc.devengado) 
     from n_nomina aa 
      inner join n_nomina_e bb on bb.idNom = aa.id
        inner join n_nomina_e_d cc on cc.idInom = bb.id  
        inner join n_conceptos dd on dd.id = cc.idConc  
        inner join n_conceptos_pr ee on ee.idConc = dd.id 
           where ee.idProc = 8 # Todos los conceptos que hacen part de la retencion  
            and year(aa.fechaI) = year(f.fechaF)  and month(aa.fechaF ) = month(f.fechaF) and bb.idEmp = e.id ) as valor 
         , case when g.tipo is null then 0 else g.tipo end as proce, d.diasVac    
            from  n_nomina_e_d a 
               inner join n_conceptos b on a.idConc=b.id  
               inner join n_conceptos_pr c on c.idConc=b.id 
               inner join n_nomina_e d on d.id=a.idInom
               inner join a_empleados e on e.id=d.idEmp
               inner join n_nomina f on f.id = d.idNom 
               left join a_empleados_rete g on g.idEmp = d.idEmp # Buscar si tiene matriculado algun procedimiento 
            where c.idProc=8 # Todos los conceptos que hacen part de la retencion  
               and f.id=".$id." group by e.id    
            order by sum( a.devengado ) desc" ,Adapter::QUERY_MODE_EXECUTE);


      $datos=$result->toArray();
      return $datos;
    }                    

   // Reemplazo de empleados
   public function getReemplazos($id)
   {
        $result=$this->adapter->query("select b.id, c.idEmpR as idEmp, 0 as horas,
             '' as formula,0 as tipo, e.idCcos, c.fechaI, c.fechaF,   
             g.idRem as idCon, 
         case when c.idSal = 0 then # maneja diferencia en sueldo
           ( case when ( d.sueldo > e.sueldo ) then ( (

  # Buscar sueldo de diferencia de sueldo en el mes 
      ( select bb.sueldo 
            from n_nomina aa 
              inner join n_nomina_e bb on bb.idNom = aa.id 
        where year( aa.fechaF ) = year(c.fechaf) and month( aa.fechaF ) = month(a.fechaF) 
               and bb.idEmp = c.idEmp order by bb.id desc limit 1) 

            -  ( select bb.sueldo 
            from n_nomina aa 
              inner join n_nomina_e bb on bb.idNom = aa.id 
        where year( aa.fechaF ) = year(c.fechaf) and month( aa.fechaF ) = month(a.fechaF) 
               and bb.idEmp = c.idEmpR order by bb.id desc limit 1)  ) / 30 )  else 0 end )  
         else
           ( f.salario - e.sueldo ) / 30  
         end  as vlrHora, 
              0 as ded, -99 as idFor, c.id as idRem, 
              
 # DIAS DE LA DIFERENCIA DE SUELDO
 case when (c.fechai <= a.fechaI and c.fechaf >= a.fechaF) then 
   datediff(a.fechaF , a.fechaI ) + 1 ## Se pasa de todo el periodo 
else
   case when ( c.fechai >= a.fechaI and c.fechaf < a.fechaF ) then 
      datediff( c.fechaf , c.fechai ) + 1 ## Fina de ausentismos esta en el periodo
   else    
      case when ( c.fechai >= a.fechaI and c.fechaf > a.fechaF ) then 
         datediff( a.fechaF, c.fechai ) + 1 ## Final de ausentismos esta en otro periodo
      else    
         case when ( c.fechai > a.fechaI and c.fechaf <= a.fechaF ) then 
             datediff( c.fechaf, c.fechai ) + 1 ## El ausentismo esta dentro del periodo 
         else 
             case when ( c.fechai <= a.fechaI and c.fechaf <= a.fechaF ) then  
                datediff( c.fechaf, c.fechai ) + 1         
             else
                 # Se busca en periodos antes del periodo liquidado de nomina
                 datediff( c.fechaf, c.fechai ) + 1         
             end      
         end     
    end             
  end       
end 
as dias, # dias a pagar en periodo activo 

case when c.reportada=0 then 
  case when ( (c.fechai < a.fechaI) and (c.fechaf > a.fechaI ) ) then 

( 
(( (
      TIMESTAMPDIFF(month, case when CAST(DAYOFMONTH( c.fechai ) AS UNSIGNED) >1 
        then c.fechai  
           else c.fechai end, a.fechaI ))
           
      + 1)  * 30) - (case when CAST(DAYOFMONTH(c.fechai) AS UNSIGNED) >1 then CAST(DAYOFMONTH(c.fechai) - 1 AS UNSIGNED) else 0 end) )  
    
  else
    0
  end    
else 0        
end as diasAnt # Para pagar retro del reemplazo   
, c.reportada , day( a.fechaF) as diasFinMes   
             from n_nomina a
               inner join n_nomina_e b on b.idNom = a.id
               inner join n_reemplazos c on c.idEmpR = b.idEmp
               left join a_empleados e on e.id = c.idEmpR # Reemplazado por                ñ
               left join a_empleados d on d.id = c.idEmp # Reemplazar por 
               left join n_salarios f on f.id = c.idSal 
               left join c_general g on g.id = 1  # Configuraciones generales  
               where c.reportada = 0 and e.vacAct!=1 and a.id = ".$id." 
union all                

select b.id, c.idEmpR as idEmp, 0 as horas,
             '' as formula,0 as tipo, e.idCcos, c.fechaI, c.fechaF,   
             g.idRem as idCon, 
         case when c.idSal = 0 then # maneja diferencia en sueldo
           ( case when ( d.sueldo > e.sueldo ) then ( (d.sueldo - e.sueldo) / 30 )  else 0 end )  
         else
           ( f.salario - e.sueldo ) / 30  
         end  as vlrHora, 
              0 as ded, -99 as idFor, c.id as idRem, 
              
 # DIAS DE LA DIFERENCIA DE SUELDO
 case when (c.fechai >= a.fechaI and c.fechaf >= a.fechaF) then 
   datediff( a.fechaF , c.fechai ) + 1 ## Se pasa de todo el periodo 
else
   case when (c.fechai < a.fechaI and c.fechaf >= a.fechaF) then 
      datediff( a.fechaF , a.fechaI ) + 1 ## Se pasa de todo el periodo 
   else
      datediff( c.fechaf , a.fechaI ) + 1 ## Se pasa de todo el periodo   
  end    
end 
as dias, # dias a pagar en periodo activo 

0 as diasAnt # Para pagar retro del reemplazo  
, c.reportada  , day( a.fechaF) as diasFinMes  
             from n_nomina a
               inner join n_nomina_e b on b.idNom = a.id
               inner join n_reemplazos c on c.idEmpR = b.idEmp
               left join a_empleados e on e.id = c.idEmpR # Reemplazado por                
               left join a_empleados d on d.id = c.idEmp # Reemplazar por 
               left join n_salarios f on f.id = c.idSal 
               left join c_general g on g.id = 1  # Configuraciones generales  
               where c.reportada = 1 and c.fechaf > a.fechaI and e.vacAct!=1 and a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      //$datos = $result->current();
      $datos=$result->toArray();
      return $datos;
    }                    

   // Consulta contrato de empleados en generacion de nomina 
   public function getContratosActivos($id)
   {
        $result=$this->adapter->query("select a.id, sum( 
case when ( d.fechaI >= b.fechaI and d.fechaF <= b.fechaF ) # Cuando el inicio y fin de contrato esta dentro del periodo de nonima
   then ( DATEDIFF(  d.fechaF , d.fechaI ) +1 ) 
     else
        case when ( d.fechaI < b.fechaI and ( d.fechaF >= b.fechaI and d.fechaF <= b.fechaF) ) # Cuando el fin contrato esta dentro del periodo de nonima
          then 
           ( DATEDIFF(  d.fechaF , b.fechaI )  ) +1
          else
             case when ( d.fechaF > b.fechaF and ( d.fechaI >= b.fechaI and d.fechaI <= b.fechaF) ) # Cuando el inicio contrato esta dentro del periodo de nonima
                 then 
                    case when ( day(b.fechaF)=31 )  then ( DATEDIFF(  b.fechaF , d.fechaI ) ) else ( DATEDIFF(  b.fechaF , d.fechaI )+1  ) end   
               else
                 
                  # Fecha antes del periodo y reportado igual a 0 y fecha de contrato superior a fecha final 
                  case when ( ( d.fechaI < b.fechaI ) and ( d.reportada = 0 ) and ( d.fechaF >= b.fechaI ) ) then 
                     ( DATEDIFF(  b.fechaF , d.fechaI )  ) +1
                  else
                     # Fecha antes del periodo y reportado igual a 0 y fecha de contrato inferio a fecha final 
                     case when ( ( d.fechaI < b.fechaI ) and ( d.reportada = 0 ) and ( d.fechaF >= b.fechaI ) ) then 
                        ( DATEDIFF(  b.fechaF , b.fechaI )  ) +1
                     else
                        0
                     end   
                  end 

              end   
      end  
     end ) as diasH , # Dias contrato 
 ( case when (d.fechaI <= b.fechaI) and c.iniContra = 1  
        then ( datediff( b.fechaI , d.fechaI ) + 1 ) + ( case when (d.fechaF > b.fechaF) then 15 else 0 end   ) # Suma los dias de la nomina 
     else 0 end  ) as diasA , # Cuando el ingreso se dan antes y no se liquido en otra nomina 
case when ( d.fechaI >= b.fechaI and d.fechaF <= b.fechaF ) # Cuando el inicio y fin de contrato esta dentro del periodo de nonima     
   then 1
     else
        case when ( d.fechaI < b.fechaI and ( d.fechaF >= b.fechaI and d.fechaF <= b.fechaF) ) # Cuando el fin contrato esta dentro del periodo de nonima
          then 
           2 
          else
             case when ( d.fechaF > b.fechaF and ( (d.fechaI >= b.fechaI and d.fechaI <= b.fechaF) or d.reportada = 0) ) # Cuando el inicio contrato esta dentro del periodo de nonima
                 then 
                 3
               else
                 0
              end   
      end  
     end  as contra, 
     ( select case when datediff(con.fechaF, b.fechaI) + 1 is null 
                     then 0 else datediff(con.fechaF, b.fechaI)+1 end  from n_emp_contratos con where con.idEmp = c.id 
                          and con.estado = 1 and ( (con.fechaI between b.fechaI and b.fechaF) or (con.fechaF between b.fechaI and b.fechaF) ) order by con.fechaI desc limit 1 ) as diasFinC #  Buscar si el fin de contrato esta n el mismo mes
, c.nombre,d.id as idCon # id del contrato
, day( b.fechaF ) as diasFin                              
            from n_nomina_e a
                                       inner join n_nomina b on b.id = a.idNom 
                                       inner join a_empleados c on c.id = a.idEmp 
                                       inner join n_emp_contratos d on d.idEmp = c.id and d.idTcon != 2  
                                    where b.id = ".$id." and ( ( (d.fechaI between b.fechaI and b.fechaF) or (d.fechaF between b.fechaI and b.fechaF) ) ) 
                                       and d.estado in ('0')  and d.tipo=1   group by c.id" ,Adapter::QUERY_MODE_EXECUTE);
      //$datos = $result->current();
      $datos=$result->toArray();
      return $datos;
    }                    

   // Retroactivos 
   public function getRetroActivos($id, $tipo, $idGrup )
   {
        // Conceptos con documento de incremento de sueldos 
        $result=$this->adapter->query("insert into n_nomina_retro
( idNom, idEmp, idAsal, idNomAnt, idInomAnt, idConc, horas, porRetro, vlrRetro, retroConv, porRetroConv, vlrRetroConv, vlrTrans, sueldoAnt, sueldoAct) 
select ".$id.", b.idEmp, aa.id, a.id, c.id , c.idConc, c.horas, 
# DATOS DEL INCREMENTO POR SUELDO STANDAR 
# Validacion empanda diferencia en sueldo porcentaje 8----------------------------------------- 
(case when c.idConc =134 then (case when dd.porRetro>0  then  dd.porRetro else  8 end ) else (((100 * dd.sueldo) / b.sueldo)-100) end ) as porRetro,
case when ( (c.idConc =134 ) and ( dd.idTemp != 3) ) then 
  ( c.devengado + ( (c.devengado * (case when dd.porRetro>0  then  dd.porRetro else  8 end ) / 100 ) ) ) - c.devengado
else 
(case when (d.tipo=1) then # Debito 
  ( c.devengado + ( (c.devengado *     (((100 * dd.sueldo) / b.sueldo)-100))    / 100 ) ) - c.devengado
else # Credito
  ( c.deducido + ( (c.deducido * (((100 * dd.sueldo) / b.sueldo)-100))/100) ) - c.deducido 
end ) end as vlrRetro, # valor del retroactivo  ---------------------------------------------------------------
d.retroConv,  
# DATOS DEL INCREMENTO POR SUELDO CONVENCIONAL   
(((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100) as porRetroConv,
(case when (d.tipo=1) then # Debito 
  ( c.devengado + ( (c.devengado * (((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100))/100) ) -c.devengado  
else # Credito
  ( c.deducido + ( (c.deducido * (((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100))/100) ) -c.deducido 
end ) as vlrRetroConv,  # valor del retroactivo convencional  
# Valor de transporte pagado de mas 
(case when c.idConc = 122 then 
( case when ( ( b.sueldo < (".$this->salarioMinimo."*2) ) and ( f.sueldo > (".$this->salarioMinimo."*2))  ) then ".$this->subsidioTransporte."/30 else 0 end) else 0 end) * (c.horas/8) as subTrans,
b.sueldo, f.sueldo    
from n_asalarial aa 
inner join n_tip_calendario_d bb on bb.id = aa.idPerI # Periodo inicial
inner join n_tip_calendario_d cc on cc.id = aa.idPerF # Periodo final 
inner join n_nomina a on a.id = ".$id." and a.idGrupo = aa.idGrup # truco para unr tabla
inner join n_nomina_e b on b.idNom = a.id
inner join n_nomina_e_d c on c.idInom = b.id 
inner join n_conceptos d on d.id = c.idConc 
inner join n_conceptos_pr e on e.idConc = d.id and e.idProc = 15 # Solo los que manejan retroactivos 
inner join a_empleados dd on dd.id = b.idEmp 
inner join n_nomina_e f on f.idEmp = b.idEmp and f.idNom = ".$id." 
inner join n_cencostos g on g.id=dd.idCcos
inner join t_cargos h on h.id=dd.idCar 
inner join n_nomina ff on ff.id = f.idNom 
where aa.estado =1 and a.estado=2 and aa.idGrup = ".$idGrup." and dd.pagoRetro=0 and c.fecha = '0000-00-00' # solo fecha de este año por eso 0000 
  and ( a.fechaI >= bb.fechaI and a.fechaF <= ff.fechaI ) # Consulta del periodo de retroactivo 
order by b.idEmp, a.fechaI, b.id, c.id" ,Adapter::QUERY_MODE_EXECUTE);

        // Conceptos con documento de incremento de sueldos ano anateiores 31 de diciembre 
        $result=$this->adapter->query("insert into n_nomina_retro
( idNom, idEmp, idAsal, idNomAnt, idInomAnt, idConc, horas, porRetro, vlrRetro, retroConv, porRetroConv, vlrRetroConv, vlrTrans, sueldoAnt, sueldoAct,  retroVaca ) 
select ".$id.", b.idEmp, aa.id, a.id, c.id , c.idConc, c.horas, 
# DATOS DEL INCREMENTO POR SUELDO STANDAR 
 (((100 * dd.sueldo) / b.sueldo)-100) as porRetro,
(case when (d.tipo=1) then # Debito 
  ( c.devengado + ( (c.devengado * (((100 * dd.sueldo) / b.sueldo)-100)) / 100 ) ) - c.devengado
else # Credito
  ( c.deducido + ( (c.deducido * (((100 * dd.sueldo) / b.sueldo)-100))/100) ) - c.deducido 
end ) as vlrRetro, # valor del retroactivo  
d.retroConv,  
# DATOS DEL INCREMENTO POR SUELDO CONVENCIONAL   
(((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100) as porRetroConv,
(case when (d.tipo=1) then # Debito 
  ( c.devengado + ( (c.devengado * (((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100))/100) ) -c.devengado  
else # Credito
  ( c.deducido + ( (c.deducido * (((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100))/100) ) -c.deducido 
end ) as vlrRetroConv,  # valor del retroactivo convencional  
# Valor de transporte pagado de mas 
(case when c.idConc = 122 then 
( case when ( ( b.sueldo < (".$this->salarioMinimo."*2) ) and ( f.sueldo > (".$this->salarioMinimo."*2))  ) then ".$this->subsidioTransporte."/30 else 0 end) else 0 end) * (c.horas/8) as subTrans,
b.sueldo, f.sueldo, 1     
from n_asalarial aa 
inner join n_tip_calendario_d bb on bb.id = aa.idPerI # Periodo inicial
inner join n_tip_calendario_d cc on cc.id = aa.idPerF # Periodo final 
inner join n_nomina a on a.id > 0  # truco para unr tabla
inner join n_nomina_e b on b.idNom = a.id
inner join n_nomina_e_d c on c.idInom = b.id 
inner join n_conceptos d on d.id = c.idConc 
inner join n_conceptos_pr e on e.idConc = d.id and e.idProc = 15 # Solo los que manejan retroactivos 
inner join a_empleados dd on dd.id = b.idEmp 
inner join n_nomina_e f on f.idEmp = b.idEmp and  f.idNom = ".$id." 
inner join n_cencostos g on g.id=dd.idCcos
inner join t_cargos h on h.id=dd.idCar 
inner join n_nomina ff on ff.id = f.idNom 
where aa.estado =1 and  and aa.idGrup = ".$idGrup." and a.estado=2 and c.idConc in (133,135) 
  and ( a.fechaF = concat( year(now()) -1 ,'-12-30') ) # Consulta del periodo de retroactivo año anterior , vacaciones 
order by b.idEmp, a.fechaI, b.id, c.id" ,Adapter::QUERY_MODE_EXECUTE);

   }                        
   // Conceptos retro 

   // Retroactivos 
   public function getRetroActivosNom($id, $tipo)
   {

//      case when b.idConc = 122 then b.sueldoAnt else 0 end as sueldoAnt,
//  case when b.idConc = 122 then b.sueldoAct else 0 end as sueldoAct,
        // Conceptos con documento de incremento de sueldos 
    $con = '';    
    if ($tipo==1)
        $con = 'and b.idConc Not in (122)';
        $result=$this->adapter->query("select a.id, a.idEmp, c.idCcos , b.idConc as idCon, 0 as dias, '' as formula,   
d.tipo, 

    case when b.retroConv = 0 then 
       sum(b.vlrRetro) 
    else
      sum(b.vlrRetroConv) end 
   as valor,

sum(vlrTrans) as vlrTrans, d.idConcRetro,
  b.sueldoAnt as sueldoAnt,
  b.sueldoAct as sueldoAct,  
  case when e.valorAct is null then 0 else e.valorAct -e.valor end as aumentoConv, c.idTemp,  concat( '* ', 
               case when d.idConcRetro > 0 then  
                    f.nombre else d.nombre end  ) as nomCon, b.retroVaca  
from n_nomina_e a
  inner join n_nomina_retro b on b.idNom = a.idNom and a.idEmp = b.idEmp  
  inner join a_empleados c on c.id = a.idEmp 
  inner join n_conceptos d on d.id = b.idConc 
  left join n_prima_anti_aum e on e.idConc = b.idConc 
  left join n_conceptos f on f.id = d.idConcRetro # Concepto de rero activos 
where a.idNom = ".$id."  ".$con." 
group by a.idEmp, b.idConc  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                          

   // Retroactivos individuales por aumento de sueldo por empleados
   public function getRetroActivosNomI($id)
   {
        // Conceptos con documento de incremento de sueldos 
        $result=$this->adapter->query("select a.id, a.idEmp, c.idCcos , b.idConc as idCon, 0 as dias, '' as formula,   
d.tipo, 
case when b.retroConv = 0 then 
  sum(b.vlrRetro) 
else
  sum(b.vlrRetroConv) end as valor, sum(vlrTrans) as vlrTrans, d.idConcRetro,
  case when b.idConc = 122 then b.sueldoAnt else 0 end as sueldoAnt,
  case when b.idConc = 122 then b.sueldoAct else 0 end as sueldoAct,
  case when e.valorAct is null then 0 else e.valorAct -e.valor end as aumentoConv, c.idTemp                            
from n_nomina_e a
inner join n_nomina_retro_i b on b.idNom = a.idNom and a.idEmp = b.idEmp  
inner join a_empleados c on c.id = a.idEmp 
inner join n_conceptos d on d.id = b.idConc 
left join n_prima_anti_aum e on e.idConc = b.idConc 
where a.idNom = ".$id."  
group by a.idEmp, b.idConc  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;
    }                          

   // Retroactivos por aumento de sueldo individual 
   public function getRetroActivosI($id)
   {
        // Conceptos con documento de incremento de sueldos 
        $result=$this->adapter->query("insert into n_nomina_retro_i 
( idNom, idEmp, idAsal, idNomAnt, idInomAnt, idConc, horas, porRetro, vlrRetro, retroConv, porRetroConv, vlrRetroConv, vlrTrans, sueldoAnt, sueldoAct ) 
select ".$id.", b.idEmp, aa.id, a.id, c.id , c.idConc, c.horas, 
# DATOS DEL INCREMENTO POR SUELDO STANDAR 
 (((100 * dd.sueldo) / b.sueldo)-100) as porRetro,
(case when (d.tipo=1) then # Debito 
  ( c.devengado + ( (c.devengado * (((100 * dd.sueldo) / b.sueldo)-100)) / 100 ) ) - c.devengado
else # Credito
  ( c.deducido + ( (c.deducido * (((100 * dd.sueldo) / b.sueldo)-100))/100) ) - c.deducido 
end ) as vlrRetro, # valor del retroactivo  
d.retroConv,  
# DATOS DEL INCREMENTO POR SUELDO CONVENCIONAL   
(((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100) as porRetroConv,
(case when (d.tipo=1) then # Debito 
  ( c.devengado + ( (c.devengado * (((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100))/100) ) -c.devengado  
else # Credito
  ( c.deducido + ( (c.deducido * (((100 * f.salarioMinimoConv) / b.salarioMinimoConv )-100))/100) ) -c.deducido 
end ) as vlrRetroConv,  # valor del retroactivo convencional  
# Valor de transporte pagado de mas 
(case when c.idConc = 122 then 
( case when ( ( b.sueldo < (".$this->salarioMinimo."*2) ) and ( f.sueldo > (".$this->salarioMinimo."*2))  ) then ".$this->subsidioTransporte."/30 else 0 end) else 0 end) * (c.horas/8) as subTrans,
b.sueldo, f.sueldo    
from n_aumento_sueldo aa 
inner join n_nomina a on a.estado = 2 and a.fechaI >= aa.fechai
inner join n_nomina_e b on b.idNom = a.id and b.idEmp = aa.idEmp # Solo trae los que tengan aumento de sueldo 
inner join n_nomina_e_d c on c.idInom = b.id 
inner join n_conceptos d on d.id = c.idConc 
inner join n_conceptos_pr e on e.idConc = d.id and e.idProc = 15 # Solo los que manejan retroactivos 
inner join a_empleados dd on dd.id = b.idEmp 
inner join n_nomina_e f on f.idEmp = b.idEmp and f.idNom = ".$id." 
inner join n_cencostos g on g.id=dd.idCcos
inner join t_cargos h on h.id=dd.idCar 
inner join n_nomina ff on ff.id = f.idNom 
where aa.estado = 1 and d.id not in (134) and dd.vacAct != 1  
order by b.idEmp, a.fechaI, b.id, c.id" ,Adapter::QUERY_MODE_EXECUTE);
    }                        
    // Conceptos retro 
   // 11. Sumatoria procesos cesantias
   public function getCesantias($idEmp, $fechaI, $fechaF, $dias, $fechaAnAnt, $promSubTrans)
   {
      // $fechaI = a la echa de retiro un año atras 
      // MAndar ano de consulta 
    if ($promSubTrans==0) // Se promedia el subs de transporte
    {
       $result=$this->adapter->query("Select 
     case when f.regimen = 0 then                 
        (  ( ( case when ( ( sum( a.devengado ) / ".$dias." ) * 30 ) is null then 0 else ( ( sum( a.devengado ) / ".$dias." ) * 30 )  end ) + f.sueldo )       
       * ".$dias." )/360 

else # Calculo para el regumen anterior       

        (  ( ( case when ( ( sum( a.devengado ) / 360 ) * 30 ) is null then 0 else ( ( sum( a.devengado ) / 360 ) * 30 )  end ) + f.sueldo )       
       * ".$dias." )/360 

     end as valorCesantias, 
     # promedio  

     case when f.regimen = 0 then                 
        (  ( ( case when ( ( sum( a.devengado ) / ".$dias." ) * 30 ) is null then 0 else ( ( sum( a.devengado ) / ".$dias." ) * 30 )  end ) + f.sueldo )       
        ) 

else # Calculo para el regumen anterior       

        (  ( ( case when ( ( sum( a.devengado ) / 360 ) * 30 ) is null then 0 else ( ( sum( a.devengado ) / 360 ) * 30 )  end ) + f.sueldo )       
        ) 

     end  as promedioCesantias 

      from n_nomina_e_d a 
                inner join n_conceptos b on a.idConc=b.id 
                        inner join n_nomina d on d.id=a.idNom 
                        inner join n_nomina_e e on e.id = a.idInom and a.idInom = e.id 
                        inner join a_empleados f on f.id=e.idEmp 
                        inner join n_conceptos_pr c on c.idConc=b.id 
      where c.idProc = 5 and a.idConc!= 122  and d.fechaI >= '".$fechaAnAnt."' 
           and d.fechaF <= concat( year('".$fechaF."'),'-', lpad(month('".$fechaF."'),2,'0') ,'-' , (case when day('".$fechaF."')>15 then 30 else 30 end) ) 
        # Se debe tener en cuenta el calendario para la consulta 
           
       and e.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
   }else{ //Se saca del promedio 
       $result=$this->adapter->query("Select 
        (  ( ( case when ( ( sum( a.devengado ) / ".$dias." ) * 30 ) is null then 0 else ( ( sum( a.devengado ) / ".$dias." ) * 30 )  end ) + 
        ( f.sueldo + ( case when f.sueldo <= ( 2*".$this->salarioMinimo." ) then 
             ".$this->subsidioTransporte." else 0 end) ) )       
        )/360 as valorCesantias ,

     # promedio  

        (  ( ( case when ( ( sum( a.devengado ) / ".$dias." ) * 30 ) is null then 0 else ( ( sum( a.devengado ) / ".$dias." ) * 30 )  end ) + 
        ( f.sueldo + ( case when f.sueldo <= ( 2*".$this->salarioMinimo." ) then 
             ".$this->subsidioTransporte." else 0 end) ) )       
        ) as promedioCesantias 
             
      from n_nomina_e_d a 
                inner join n_conceptos b on a.idConc=b.id 
                        inner join n_nomina d on d.id=a.idNom 
                        inner join n_nomina_e e on e.id = a.idInom and a.idInom = e.id 
                        inner join a_empleados f on f.id=e.idEmp 
                        inner join n_conceptos_pr c on c.idConc=b.id 
      where c.idProc = 5 
           and a.idConc not in (122,174) and d.fechaI >= '".$fechaAnAnt."' 
           and d.fechaF <= concat( year('".$fechaF."'),'-', lpad(month('".$fechaF."'),2,'0') ,'-' , (case when day('".$fechaF."')>15 then 30 else 30 end) ) 
        # Se debe tener en cuenta el calendario para la consulta 
           
       and e.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);


   }
      //$datos = $result->current();
      $datos=$result->toArray();    
      return $datos;   }                                 
   // 11. Sumatoria procesos cesantias con sueldo variable 
   public function getCesantiasS($idEmp, $fechaI, $fechaF, $dias, $fechaAnAnt, $promSubTrans, $diasPromedio)
   {
    //echo 'Fecha anteriore'.$fechaF;
//    echo 'Dias promedioas '.$diasPromedio;
      $result=$this->adapter->query("Select 
     case when f.regimen = 0 then

        ( ( ( ( sum( a.devengado ) / ".$dias." ) * 30 ) + (case when dd.promPrimas = 0 then (f.sueldo 
+ ( case when f.sueldo>(2*".$this->salarioMinimo.")  then 0 else ".$this->subsidioTransporte." end ) + ( case when f.representante=1 then (4*".$this->salarioMinimo.") else 0 end ) 
        ) else 0 end )  )* ".$dias.")/360   
     else # Calculo para el regumen aanterior       

        (( ( ( sum( a.devengado ) / ".$dias." ) * 30 ) + (case when dd.promPrimas = 0 then f.sueldo + 
( case when f.sueldo>(2*".$this->salarioMinimo.") then 0 else ".$this->subsidioTransporte." end ) + ( case when f.representante=1 then (4*".$this->salarioMinimo.") else 0 end ) 

      else 0 end )  ) * ".$dias.")/360   
     end as valorCesantias, 

      case when f.regimen = 0 then

        ( ( sum( a.devengado ) / ".$dias." ) * 30 )    
     else # Calculo para el regumen aanterior       

        (( ( ( sum( a.devengado ) / 1 ) * 1) + (case when dd.promPrimas = 0 then f.sueldo + 
( case when f.sueldo>(2*".$this->salarioMinimo.") then 0 else ".$this->subsidioTransporte." end ) + ( case when f.representante=1 then (4*".$this->salarioMinimo.") else 0 end ) 

      else 0 end )  ) * ".$dias.")/360   
     end as promedioCesantias , 

     sum(a.devengado) as deven, f.regimen, count( a.id ) as registros    

      from n_nomina_e_d a 
                inner join n_conceptos b on a.idConc=b.id 
                        inner join n_nomina d on d.id=a.idNom 
                        inner join n_nomina_e e on e.id = a.idInom and a.idInom = e.id 
                        inner join a_empleados f on f.id=e.idEmp 
                        inner join n_conceptos_pr c on c.idConc=b.id 
                        inner join c_general dd on dd.id = 1 
      where c.idProc = 5 and d.fechaI >= concat( year( '".$fechaI."' ) ,'-',lpad(month( '".$fechaI."' ),2,'0'), (case when day('".$fechaI."')>15 then '-16' else '-01' end) )  
           and d.fechaF <= concat( year('".$fechaF."'),'-', lpad(month('".$fechaF."'),2,'0') ,'-' , (case when day('".$fechaF."')>15 then 30 else 30 end) ) 
        # Se debe tener en cuenta el calendario para la consulta 
           
       and e.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      if ($datos['registros'] == 0) // si es cero 
      {
           $result=$this->adapter->query("Select 
        case when f.regimen = 0 then         
        ( ( (case when dd.promPrimas = 0 then f.sueldo 
+ 
( case when f.sueldo>(2*".$this->salarioMinimo.") then 0 else ".$this->subsidioTransporte." end )

        else 0 end )  )* ".$dias.")/360   
            else # Calculo para el regumen aanterior       
            ((  (case when dd.promPrimas = 0 then f.sueldo 

+ 
( case when f.sueldo>(2*".$this->salarioMinimo.") then 0 else ".$this->subsidioTransporte." end )

          else 0 end )  ) * ".$dias.")/360   
          end as valorCesantias,  

          case when f.regimen = 0 then         
            (case when dd.promPrimas = 0 then f.sueldo else 0 end )     
         else # Calculo para el regumen aanterior       
           (( (case when dd.promPrimas = 0 then f.sueldo else 0 end )  ) ) 
          end as promedioCesantias , f.regimen     
      from a_empleados f 
         inner join c_general dd on dd.id = 1 
      where f.id = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);          
          $datos = $result->current();
      }  
      return $datos;
   }                                 

   // 11. Sumatoria procesos primas con sueldo
   public function getPrimasS($idEmp, $fechaI, $fechaF, $dias, $diasPromedio, $diasAus )
   {
      //$dias = $dias - $diasAus;
      $result=$this->adapter->query("Select 
     case when f.regimen = 0 then         
        ( ( ( sum( a.devengado ) / ".$diasPromedio." ) * 30 ) * ".$dias.")/360   
     else # Calculo para el regumen aanterior       
        (( ( ( sum( a.devengado ) / 360 ) * 30 ) ) * ".$dias.")/360   
     end as valorPrimas , 

     case when f.regimen = 0 then         
        ( ( ( sum( a.devengado ) / ".$diasPromedio." ) * 30 ) )    
     else # Calculo para el regumen aanterior       
        (( ( ( sum( a.devengado ) / 360 ) * 30 ) ) ) 
     end as promedioCesantias 

      from n_nomina_e_d a 
                inner join n_conceptos b on a.idConc=b.id 
                        inner join n_nomina d on d.id=a.idNom 
                        inner join n_nomina_e e on e.id = a.idInom and a.idInom = e.id 
                        inner join a_empleados f on f.id=e.idEmp 
                        inner join n_conceptos_pr c on c.idConc=b.id 
      where c.idProc = 4 and d.fechaI >= '".$fechaI."' 
           and d.fechaF <= concat( year('".$fechaF."'),'-', lpad(month('".$fechaF."'),2,'0') ,'-' , (case when day('".$fechaF."')>15 then 30 else 30 end) ) 
        # Se debe tener en cuenta el calendario para la consulta 
           
       and e.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      //$datos=$result->toArray();    
      return $datos;
   }                                 

   // FONDO Y RETENCION PAGADOS ANTERIORE MENTE EN EL MISMO MES PARA DESCONTAR 
   public function getFondSolAnt( $ano, $mes, $id, $idNom, $idCon )
   {
      $result=$this->adapter->query("select sum(c.deducido) as deducido, sum(c.devengado) as devengado 
from n_nomina a 
inner join n_nomina_e b on b.idNom = a.id
inner join n_nomina_e_d c on c.idInom = b.id
where year(a.fechaI) = ".$ano." and month(a.fechaF) = ".$mes." and a.id != ".$idNom." 
   and a.idTnom in (1,4,5) and b.idEmp = ".$id." and c.idConc in (".$idCon .")",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
    }    

// CASOS NOMINAS MANUALES 
   // ( REGISTRO DE NOVEDADES DE PROYECTOS) ( n_novedades ) 
   public function getManualesDocEspe($id,$fechaI,$fechaF)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, 0 as dias,0 as horas, f.formula,
              e.id as idCon, e.tipo , 0 as calc , 
              '' as fechaEje, 0 as idProy, b.idCcos ,d.valor,
              0 as dev, 0 as ded, f.id as idFor             
                    from n_nomina_e a 
                      inner join t_docu_esp c on c.idEmp = a.idEmp 
                      inner join a_empleados b on b.id = a.idEmp 
                      inner join t_tip_doc_c d on d.idTdoc = c.idTdoc                       
                      inner join n_conceptos e on e.id = d.idConc
                      inner join n_formulas f on f.id = e.idFor 
                    where c.estado=1 and a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                       

   // Sumatoria del mes de salud, pension 
   public function getSumaFondos($idEmp,$ano,$mes)
   {        
     $result=$this->adapter->query("Select case when sum( a.devengado ) is null 
                              then 0 else sum( a.devengado ) end as valorBase ,
                              ( select sum(a.devengado) as valor   
                                        from  n_nomina_e_d a 
                                            inner join n_conceptos b on a.idConc=b.id 
                                            inner join n_conceptos_pr c on c.idConc=b.id 
                                            inner join n_nomina_e d on d.id=a.idInom
                                            inner join a_empleados e on e.id=d.idEmp
                                            inner join n_nomina f on f.id = d.idNom 
                                        where c.idProc=18 and 
                                           year(f.fechaF) = ".$ano." and month(f.fechaF) = ".$mes."  and e.id = ".$idEmp.") as otrosIngreso,                               

                        # Devengados ley 100------------------------
                                round( case when sum( a.devengado ) is null 
                                   then 0 else                         
                                     # Devengados ley 100------------------------
                                    sum( a.devengado ) 
                                    * (4/100) end ,0 ) as valor 
                              from  n_nomina_e_d a 
                                  inner join n_conceptos b on a.idConc=b.id 
                                  inner join n_conceptos_pr c on c.idConc=b.id 
                                  inner join n_nomina_e d on d.id=a.idInom
                                  inner join a_empleados e on e.id=d.idEmp
                                  inner join n_nomina f on f.id = d.idNom 
                              where c.idProc=1 and 
                                 b.id != ( case when f.idTnom = 6 then 133 else 0 end ) # Ignorar vacaciones en liquidacion final 
                              and # Todos los conceptos que hacen part de ley 100  
                        year(f.fechaF) = ".$ano." and month(f.fechaF) = ".$mes." and e.id = ".$idEmp,Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;       
   }                       
   // ( REGISTRO DE CONCEPTOS POR NOMINAS MANUALES)
   public function getNomConceptos($id)
   {        
     $result=$this->adapter->query("select a.id, a.idEmp, a.dias as dias,
             0 as horas, e.formula,
              d.id as idCon, 1 as tipo, 0 as dev, 0 as ded, 0 as calc , 
              '' as fechaEje, b.idCcos , d.idFor, ( b.sueldo / 30 )  as valDia 
                    from n_nomina_e a 
                      inner join a_empleados b on b.id = a.idEmp 
                      inner join n_nomina_c c on c.idNom = ".$id." 
                      inner join n_conceptos d on d.id = c.idConc 
                      inner join n_formulas e on e.id = d.idFor                      
                    where a.idNom = ".$id,Adapter::QUERY_MODE_EXECUTE);
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
   public function getFormulaDias($idEmp, $descanso, $festivo, $domingo, $dia, $tipo)
   {     
     $conDesca = '';
     if ($descanso != 3)
         $conDesca = 'and a.descanso = '.$descanso;

     $conDomingo = '';
     if ($domingo != 3)
         $conDomingo = 'and a.domingo = '.$domingo;  

     $confestivo = '';
     if ($festivo != 3)
         $confestivo = 'and a.festivo = '.$festivo; 

     $condia = '';
     if ($dia != 3)
         $condia = 'and b.dia  = '.$dia;     

     $contipo = '';  
     if ($tipo != 3)
         $contipo = 'and b.tipo  = '.$tipo;                            

     $result=$this->adapter->query("select count( a.id ) as num, 
                                   sum( d.horasAdi ) as horasAdicionales,   
                                   sum( d.horasRec ) as recargo
                                 from n_nov_prog_m a
                                   inner join n_horarios_f b on b.id = a.idHfor 
                                   inner join n_nov_prog c on c.id = a.idNov 
                                   left join n_horarios d on d.id = a.idHor 
                                where c.idEmp = ".$idEmp." ".$conDesca." ".$confestivo." 
                                   ".$conDomingo." ".$condia." ".$contipo." and a.dia != 31 
                                          ",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->current();
      return $datos;       
   }

 // Empleados en liquidacion 
   public function getEmpReLiq($con)
   {     
     $result=$this->adapter->query("select b.id, b.idNom, c.CedEmp, c.nombre , c.apellido , d.fechaIngreso, d.fechaI,  d.fechaF, c.idGrup   
                     from n_nomina a 
                       inner join n_nomina_e b on b.idNom = a.id 
                       inner join a_empleados c on c.id = b.idEmp 
                       inner join n_nomina_l d on d.idNom = a.id and d.idEmp = c.id 
                     where a.idGrupo = 99 and a.estado = 2 ".$con." 
                        order by a.fechaF desc",Adapter::QUERY_MODE_EXECUTE);
      $datos=$result->toArray();
      return $datos;       
   }                                      
}
?>
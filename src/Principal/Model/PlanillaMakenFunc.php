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
use Principal\Model\Paranomina; // Parametros de nomina
use Principal\Model\AlbumTable; // Libro de consultas
use Principal\Model\Gnomina; // Generacion de nomina

/// INDICE

//// FUNCIONES BASICAS ------------------------------------------
// 0. FUNCION GENERAL PARA CALCULOS EN PLANILLAS
// 01. VALOR DE FORMULAS
// 1. Dias del mes trabajador 
// 2. Sumatoria ley 100 
     
//// FUNCIONES GENERALES ----------------------------------------
class PlanillaFunc extends AbstractTableGateway
{
   protected $table  = '';   
      
   public $dbAdapter;
   public $salarioMinimo;
   public $horasDias;
   public $salarioMinimoCovencional;
     
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
        // Parametros de nomina para funciones de consulta 
        $pn = new Paranomina($this->adapter);
        $dp = $pn->getGeneral1(1);
        $this->salarioMinimo=$dp['formula'];
        $dp = $pn->getGeneral1(2);
        $this->horasDias=$dp['valorNum'];
        $dp = $pn->getGeneral1(3);
        $this->salarioMinimoCovencional=$dp['formula'];// Salario minimo convencional        
   }
    
   // ----------------------------------------------------- 
   // FORMULAS FIJAS EN PROGRAMA *-----------------------------------------------------------------------------------------------
   // ------------------------------------------------- 
   // 1. Dias del mes trabajador 
   public function getDiasEmp($idPla , $id)
   {
      $result=$this->adapter->query("select 30 as valor 
              from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->current();
      return $datos;
    }                   

   // 2. Sumatoria procesos Ley 100
   public function getLey($id, $idEmp )
   {
      $result=$this->adapter->query("Select ( case when sum(e.devengado) is null then 0 else sum(e.devengado) end ) + 
         ( case when  c.diasVaca > 0 then (c.diasVaca * c.valorUniVaca) else 0 end ) as valor, c.sueldo  
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                   where e.desc=0 and g.idProc in (1,17) # procesos ley 100 y adicionales planila unica 
                        and e.idConc not in( 133 ) # 1 es proceso de ley 100, las vacaciones ya se calcularon los dias dentro del mes 
                        and c.idEmp=".$idEmp." and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                           


   // 2. Sumatoria procesos Ley 100 menos EGA , AT  
   public function getLeyR($id, $idEmp, $diasRiesgos )
   {
      $result=$this->adapter->query("Select 
(    ( sum(case when f.id = 133 then # ES UN CONCEPTO DE VACACIONES
       ( round( ( e.devengado / d.diasVac ), 2 ) ) * ( case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 c.diasVaca  
              else                 
                0 end  )
    else # ES OTRO CONCEPTO 
case when ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end ) > (25 * 689454)  # valida que no supere tope maximo         
      then (25 * 689454) 
    else 
         ( case f.tipo when 1 then (e.devengado)
              when 2 then (0) end )        
      end      
    end ) + (  case when  ( (valorRetVaca is null) or ( month(h.fechaI) = a.mes and month(h.fechaF) = a.mes) )  then 0 else 0 end  )  )  )  as valor 
 , 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) > (25 * 689454)          
      then (25 * 689454)          
    else 
         0
      end  as topMax, 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) < (1 * 689454)          
      then (1 * 689454)          
    else 
         0
      end  as topMin,   
      c.sueldo  ,
      # Vacaciones 
              case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 ( (datediff( b.fechaF , b.fechaI)+1) * 2 ) - d.dias   
              else                 
                 case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes) and (month(h.fechaF) > a.mes) ) then # Vacaciones dentro del mismo mes
                    ( (datediff( concat( a.ano,'-',a.mes,'-30' )  , h.fechaI) +1)  )   
                 else 
                  0 
                end                 
                 end as diasVac,                              
      round( ( e.devengado / d.diasVac ), 2 ) as valorDiaVac, c.valorRetVaca # Vacciones por ingreso     
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                        where e.desc=0 and ( g.idProc=1 or j.tipo = 1 ) and e.idConc not in ('201','202','203','204','236','280',   '210','208',      '205','207'  )   # 1 es proceso de ley 100  
                        and e.idConc not in (133) # Sin vacaciones 
                        and c.idEmp=".$idEmp." and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                           

   // 2. Sumatoria procesos Ley 100 menos EGA , AT  Detallado 
   public function getLeyRD($id, $idEmp )
   {
      $result=$this->adapter->query("Select f.nombre as nomCon, e.horas , b.fechaI, b.fechaF, 0 as deducido, e.detalle, 
(    ( (case when f.id = 133 then # ES UN CONCEPTO DE VACACIONES
       ( round( ( e.devengado / d.diasVac ), 2 ) ) * ( case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 c.diasVaca  
              else                 
                0 end  )
    else # ES OTRO CONCEPTO 
case when ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end ) > (25 * 689454)  # valida que no supere tope maximo         
      then (25 * 689454) 
    else 
         ( case f.tipo when 1 then (e.devengado)
              when 2 then (0) end )         
      end      
    end ) + (  case when  ( (valorRetVaca is null) or ( month(h.fechaI) = a.mes and month(h.fechaF) = a.mes) )  then 0 else 0 end  )  )  )  as devengado  
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                        where e.desc=0 and ( g.idProc=1 or j.tipo = 1 ) and e.idConc not in ('201','202','203','204','236','280',   '210','208',      '205','207'  )   # 1 es proceso de ley 100  
                        and e.idConc not in ( '133' ) # No vacaciones 
                        and c.idEmp=".$idEmp." and c.regAus=0  and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
//    then 0 else c.valorRetVaca end  )  )  ) as linea cambiada 
   }    

   // 2.1 Detallado procesos Ley 100
   public function getLeyD($id, $idEmp)
   {
      $result=$this->adapter->query("Select b.fechaI, b.fechaF, d.dias,
          (datediff( b.fechaF , b.fechaI)+1) * 2  as diasCal, c.diasVaca, 
       f.nombre as nomCon, 
                    e.devengado, e.deducido, 
                c.valorUniVaca as valorDiaVac , 
                e.horas, c.sueldo, f.id as idCon, c.diasRetVaca, e.detalle   
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        left join n_vacaciones h on h.id = d.idVac 
                        where e.desc=0 and g.idProc in (1,17) # 1 es proceso de ley 100 y adicionales planilla unica 
                        and c.idEmp=".$idEmp." and c.regAus=0 and e.idConc !=133 # Menos vacaciones 
                          and a.id=".$id."  order by b.fechaI, b.fechaF, e.detalle, f.codigo   ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }                           
   // 3. Sumatoria fondos de solidaridad
   public function getSolidaridad($id, $idEmp)
   {
      $result=$this->adapter->query("Select case when sum(e.deducido) != null then 0 else sum(e.deducido) end as valor  
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        where e.desc=0 and e.idConc = 21 # 1 Fondos de solidaridad
                        and c.idEmp=".$idEmp." and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                              
   
   // 4. Sumatoria caja de compensacion 
   public function getCaja($id, $idEmp)
   {
      $result=$this->adapter->query("select sum(e.devengado) as valor  
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
      inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id
                        where e.desc=0 and g.idProc in (1,3) and e.idConc not in ('201','202','203','204','236','280', '205','207'  )   # 1 es proceso de ley 100  
                        and c.idEmp=".$idEmp." and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                

   // 4. Sumatoria caja de compensacion detallado
   public function getCajaD($id, $idEmp)
   {
      $result=$this->adapter->query("select 
        f.nombre as nomCon, e.devengado, e.deducido, e.horas, b.fechaI, b.fechaF, e.detalle  
                    from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
      inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id
                        where e.desc=0 and g.idProc in (1,3) and e.idConc not in ('201','202','203','204','236','280', '205','207'  )   # 1 es proceso de ley 100  
                        and c.idEmp=".$idEmp." and c.regAus=0 and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }                
   // 5. Ausentismos
   public function getAus($id, $idEmp)
   {
      $result=$this->adapter->query("select sum( d.dias ) as diasAus 
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e_a d on d.idNom = b.id and d.idEmp = c.idEmp  
                        inner join n_ausentismos e on e.id = d.idAus and e.horas = 0 
                     where a.id = ".$id." and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                               

   // 5. Incapacidades 
   public function getInca($id, $idEmp)
   {
      $result=$this->adapter->query("select sum( d.diasInc ) as diasInc, f.tipo 
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e_i d on d.idNom = b.id and d.idEmp = c.idEmp  
                        inner join n_incapacidades e on e.id = d.idInc 
                        inner join n_tipinc f on f.id = e.idInc                         
                     where d.tipo = 0 and a.id = ".$id." and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                               
   // 5. Incapacidades pro 
   public function getIncaPro($id, $idEmp)
   {
      $result=$this->adapter->query("select sum( d.diasInc ) as diasInc, f.tipo 
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e_i d on d.idNom = b.id and d.idEmp = c.idEmp  
                        inner join n_incapacidades_pro e on e.id = d.idInc 
                        inner join n_incapacidades ee on ee.id = e.idInc 
                       inner join n_tipinc f on f.id = ee.idInc                         
                     where d.tipo = 1 and a.id = ".$id." and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                               
   // 5. Estados del empleado en el mes 
   public function getEstados($id, $idEmp)
   {
      $result=$this->adapter->query("Select distinct d.idInc, d.dias as diasInc  
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e_i d on d.idEmp = c.idEmp  
                        where a.id = ".$id." and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }       
   // 6. Diferencia de sueldo
   public function getDsueldos($id, $idEmp)
   {
      $result=$this->adapter->query("Select count( distinct(d.sueldo) ) as valor 
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id
                        where g.idProc=1 # 1 es proceso de ley 100
                        and c.idEmp=".$idEmp." and a.id=".$id."  order by b.fechaI, b.fechaF, f.codigo   ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }       

   // 7. Vacaciones
   public function getVaca($id, $idEmp)
   {
      $result=$this->adapter->query("Select d.id as idVac , 
case when ( month(d.fechaI) = a.mes and month(d.fechaF) > a.mes ) then # Si inicia el periodo de vacaciones en el mes y termina en otro mes nomina actual tiene el idvac y dias 
     30 - sum(e.dias) # Sumo los dias pagados en nomina - el restante de los 30 para salir 
   else # Vacaciones comprendidas en el mismo periodo 
     case when ( month(d.fechaI)=a.mes and month(d.fechaF)=a.mes ) then # Vacaciones comprendidas en el mismo mes de liquidacion
          ( DATEDIFF( d.fechaR , d.fechaI ) )
        else # Caso vacaciones inician antes y terminan en otro periodo 
          case when ( month(d.fechaI)< a.mes and month(d.fechaF)>a.mes ) then # Disfurte de vacaciones dentro del periodo de planilla unica
             30
          else # Cas retorno de vacaciones 
            case when ( month(d.fechaI)< a.mes and month(d.fechaF)=a.mes ) then # Fin de vacaciones despeus de varios dias
                 datediff( d.fechaF , concat( year(d.fechaF) ,'-', month(d.fechaF), '-01' ) ) + 1 
            else # Periodo es fin de año y retorna en enero 
                  datediff( d.fechaR , concat( year(b.fechaI) ,'-', month(b.fechaI), '-01' )  )  
        end
          end 
     end      
end as diasVac , 
 d.fechaI, d.fechaF, 
 ( select case when sum(ee.diasInc) is null then 0 else sum(ee.diasInc) end from n_nomina bb 
            inner join n_nomina_e_i ee on ee.idNom = bb.id 
           where year(bb.fechaI) = a.ano and month(bb.fechaI) = a.mes  and ee.idEmp = e.idEmp ) as diasInc,
                  # valor vaacaciones 
        d.valor / d.diasCal as valorVac             
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e e on e.idNom = b.id and e.idEmp = c.idEmp 
                        inner join n_vacaciones d on d.idEmp = c.idEmp and year(d.fechaI)=year(b.fechaI)
                and year(d.fechaI)=a.ano 
           and ( a.mes between month(d.fechaI) and ( case when year(d.fechaF)>a.ano then  12 else month( d.fechaF) end  )  ) 
           or ( (year( d.fechaI)=(a.ano-1) ) and ( (year(d.fechaF)=a.ano) and month(d.fechaF)=1 ) and d.idEmp = e.idEmp  ) 
                        where a.id = ".$id." 
                          and c.idEmp = ".$idEmp."  and e.actVac = 0 group by d.id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }
   // Contrados de empleados
   public function getContratos($id, $idEmp)
   {
      $result=$this->adapter->query("Select distinct c.contra, 
# dias por nomina -----------------------------------------------------
( select sum( cc.dias ) as dias  
                    from n_nomina_e cc 
               inner join n_nomina bb on bb.id = cc.idNom 
                      where cc.idEmp = c.idEmp and year(bb.fechaI) = year(b.fechaI) and month( bb.fechaI ) = month( b.fechaI )  ) as dias, 
# dias por contrato -------------------------------------------------------
( select sum( cc.horas/8 ) 
      from n_nomina aa 
          inner join n_nomina_e bb on bb.idNom = aa.id 
             inner join n_nomina_e_d cc on cc.idInom = bb.id and cc.idConc = 122 
               inner join n_proyectos_e dd on dd.idEmp = bb.idEmp 
            where bb.idEmp = d.id and year(aa.fechaI) = a.ano and month(aa.fechaI) = a.mes ) as diasProy,                  
# fin de contrato --------------------------------------                         
               d.finContrato    
                        from n_planilla_unica a 
                         inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                         inner join n_nomina_e c on c.idNom = b.id 
                         inner join a_empleados d on d.id = c.idEmp                
                     where a.id = ".$id." and (c.contra > 0 or d.finContrato = 1 ) and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }       
   // Consulta tipo 01
   public function getCon01($id)
   {
      $result=$this->adapter->query("select '01' as tipo , lpad( @item := @item + 1, 5, '0' ) as consecutivo,
rpad( c.empresa, 200,' ') as empresa, 'NI' as nit,
rpad(  substring( rtrim(c.nit) , 1 ,length( rtrim(c.nit) ) - 1  ) , 16,' ') as nitEmpresa, '1E' as e,
lpad( '' , 20,' ') as blanco20, 'S' as s,
'01' as e01, lpad( '' , 8,' ') as blanco8, 
'01' as e02, lpad( '' , 38,' ') as blanco38,
ltrim(c.codigoArl) as e1425, lpad( '' , 1,' ') as blanco1,   # NO SE QUE ES ESTO 
concat( a.ano , '-' ,lpad( a.mes , 2,'0') ) as perPension,

case when a.mes = 12 then 
  concat( a.ano+1 , '-',lpad( 1 , 2,'0') ) 
else 
  concat( a.ano , '-',lpad( a.mes+1 , 2,'0') ) end as perSalud, 

lpad( '' , 9,'0') as blanco9, '1' as e1, # NO SE SABE QUE ES ESTO 
lpad( '' , 10,' ') as blanco11 , 
lpad( (select count(b.id) from n_planilla_unica_e b where b.idPla = a.id and b.nAus=0 ) , 5, '0' ) as numEmp,

lpad( (select round(sum( cc.devengado ),0)  
from n_nomina aa
inner join n_nomina_e bb on bb.idNom = aa.id
inner join n_nomina_e_d cc on cc.idInom = bb.id
where cc.devengado > 0 and year(aa.fechaI ) = a.ano and month(aa.fechaI)=a.mes ) , 12, '0' ) as valNomina,  
 '101' as e101 
             from n_planilla_unica a , (SELECT @item := 0) item  
             inner join c_general c on c.id = 1
             where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }          
   // Consulta tipo 02
   public function getCon02($id)
   {
      $result=$this->adapter->query("select b.id, '02' as tipo, lpad( @item := @item + 1, 5, '0' ) as consecutivo,  
'CC' as tipCed, rpad( d.CedEmp , 16,' ') as cedula, 
case when b.aprendiz = 1 then # Aprendiz productivo 
  '19'
else 
  '01' end as tipAporte,
case when b.pensionado = 1 then '04' else '00' end as blanco2,  
 '  ' as e00, '08001' as ciuDepa ,
rpad( d.apellido1, 20,' ') as apellido1 ,rpad( d.apellido2 , 30,' ') as apellido2 ,
rpad( d.nombre1 , 20,' ') as nombre1, rpad( d.nombre2 , 30,' ') as nombre2,     
case when nIngreso = 1 then 'X' else ' ' end as ingreso ,    
case when nRetiro = 1 then 'X' else ' ' end as retiro ,    
' ' as trasSalud, ' ' as idFsalTras , 
' ' as trasPension, ' ' as idFpenTras ,         
lpad( '' , 1 ,' ') as e1, # CAMPO SIN IDENTIFICAR 
case when nVsp = 1 then ' ' else ' ' end as nVaca , # Campo sin identificar Variacion permanente de trabajo 
case when (nVst=1 or nVsp=1 ) then 'X' else ' ' end as nVst ,  # Variacion termporal de devengados
case when b.regAus = 1 then 'X' else ' ' end as nAus ,  # Suspencion
# De las incapacidades
case when b.nInca =1 then 'X' else ' ' end  as incGeneral, 
case when ( b.Mat=1 or b.Pat =1)  then 'X' else ' ' end as incMaternidad, # Fin de las incapacidades 
case when nVaca = 1 then 'X' else ' ' end as vaca , # Vacaciones ,
' ' as blan1 , # pendienes por identificar
' ' as blan2 , # pendienes por identificar
case when b.at = 1 then lpad(b.diasInc,2,'0') else '00' end as acct, # Accidente de trabajo 
lpad( case when e.codigo is null then ' ' else e.codigo end , 6,' ') as codFonPension,
lpad( '' , 6,' ') as espaPension,
lpad( f.codigo , 6,' ') as codFonSalud,
lpad( '' , 6,' ') as espaSalud,
lpad( case when g.codigo is null then '' else g.codigo  end  , 5,' ') as codCaja,
lpad( '' , 1,' ') as espaCaja, 
lpad( b.diasPension , 2,'0') as diasPension,
lpad( b.diasSalud , 2,'0') as diasSalud,
lpad( b.diasRiesgos , 2,'0') as diasRiesgos,
lpad( b.diasCaja , 2,'0') as diasCaja,
lpad( b.sueldo , 9,'0') as salarioBase,
' ' as blan4 ,
lpad( round( b.ibcSalud, 0) , 9,'0') as ibcSalud, 
lpad( round(b.ibcPension, 0) , 9,'0') as ibcPension, 
lpad( round(b.ibcRiesgos, 0) , 9,'0') as ibcRiesgos,
lpad( round(b.ibcCaja, 0) , 9,'0') as ibcCaja,
lpad( round(b.porPension,4) , 6,'0') as porPension, # Aportes de pension
lpad( round(b.aporPension,0) , 10,'0') as aporPension,
lpad( '' , 17,'0') as cerosPension,
lpad( round(b.aporPension,0) , 10,'0') as aporPension,
lpad( round(b.aporSol1,0) , 9,'0') as aporSolidaridad,
lpad( round(b.aporSol2,0) , 9,'0') as aporSolidaridad9,
lpad( '' , 9,'0') as cerosSolidaridad, # ( 300 - 307 )
lpad( round(b.porSalud, 4) , 6,'0') as porSalud,  # Aportes de salud
lpad( round(b.aporSalud,0), 10,'0') as aporSalud, # ( 314 - 323 )
lpad( '' , 9,'0') as cerosSalud,
lpad( '' , 15,' ') as espaciosPension,
lpad( '' , 9,'0') as cerosSalud2,
lpad( '' , 15,' ') as espaciosPension2, 
lpad( '' , 9,'0') as cerosSalud3, # ( 372 - 380  )
lpad( round(b.tarifaArl/100,5) , 7,'0') as tarifaArl,   # Aportes de pension
 lpad( '' , 10,'0') as cerosArl,
1 as claseRiesgo ,
lpad( round(b.aporRiesgos,0) , 9,'0') as aporRiesgos, 
lpad( round(b.porCaja,5) , 6,'0') as porCaja,   # Aportes caja 
lpad( round(b.aporCaja,0) , 10,'0') as aporCaja, # ( 399 )
lpad( round(b.porSena,5) , 6,'0') as porSena,   # Aportes sena
lpad( round(b.aporSena,0) , 10,'0') as aporSena,
lpad( round(b.porIcbf,5) , 6,'0') as porIcbf,   # Aportes icbf
lpad( round(b.aporIcbf,0) , 10,'0') as aporIcbf, 
'0.0000' as valor1, 
lpad( '' , 10,'0') as cerosFinal,
'0.0000' as valor2,
lpad( '' , 10,'0') as cerosFinal2,  
lpad( '' , 18,' ') as cerosFinal3, 
case when b.pagoEmp=1 then 'S' else 'N' end as ley14 
from n_planilla_unica a
inner join n_planilla_unica_e b on b.idPla = a.id  
inner join a_empleados d on d.id = b.idEmp 
inner join c_general c on c.id = 1 # configuraciones generales 
left join t_fondos e on e.id = b.idFonP 
left join t_fondos f on f.id = b.idFonS  
left join t_fondos g on g.id = b.idCaja  
, (SELECT @item := 0) item  
where a.id = ".$id." order by b.idEmp" ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }             
   // Consulta planilla empleado 
   public function getPlanillaE($id, $campo)
   {
      $result=$this->adapter->query("Select ".$campo." as valor , topMax , topMin  
                           from n_planilla_unica_e  
                        where id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }          
   // Valdiaciones de top para IBC 
   public function getTopeIbc($id, $campo, $valor, $dias)
   {
     if ($dias == 30)
     {
       // Tope Maximo 
       if ($valor > ( 25 * $this->salarioMinimo) )
       {
          $valor = ( 25 * $this->salarioMinimo);
          $result=$this->adapter->query("update n_planilla_unica_e 
                 set topMax =1 , ".$campo." = ".$valor." where id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
       }
       // Tope Minimo
       if ($valor < ( $this->salarioMinimo) )
       {
          $valor = ( $this->salarioMinimo);
          $result=$this->adapter->query("update n_planilla_unica_e 
                 set topMin =1 , ".$campo." = ".$valor." where id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
       }      
     }  
   }

   // Valdiaciones de top para IBC Caja
   public function getTopeIbcCaja($id, $campo, $valor, $dias)
   {
     if ($dias == 30)
     {
       // Tope Minimo
       if ($valor < ( $this->salarioMinimo) )
       {
          $valor = ( $this->salarioMinimo);
          $result=$this->adapter->query("update n_planilla_unica_e 
                 set topMin =1 , ".$campo." = ".$valor." where id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
       }      
     }  
   }             

   // 5. Validacion solidaridad
   public function getValSolidaridad($idPla, $idEmp)
   {
      $result=$this->adapter->query("Select 
case when ( ( a.ibcSalud ) >(4*".$this->salarioMinimo.") and ( a.ibcSalud ) <=(16*".$this->salarioMinimo.")  ) then ( a.ibcSalud*(1/100) )
     when ( ( a.ibcSalud ) >(16*".$this->salarioMinimo.") and ( a.ibcSalud ) <=(17*".$this->salarioMinimo.")  ) then  ( a.ibcSalud*(1.2/100) )
     when ( ( a.ibcSalud ) >(17*".$this->salarioMinimo.") and ( a.ibcSalud ) <=(18*".$this->salarioMinimo.") ) then ( a.ibcSalud*(1.4/100) )    
     when ( ( a.ibcSalud ) >(18*".$this->salarioMinimo.") and ( a.ibcSalud ) <=(19*".$this->salarioMinimo.") ) then ( a.ibcSalud*(1.6/100) )             
     when ( ( a.ibcSalud ) >(19*".$this->salarioMinimo.") and ( a.ibcSalud ) <=(20*".$this->salarioMinimo.") ) then ( a.ibcSalud*(1.8/100) )                      
     when ( ( a.ibcSalud ) >(20*".$this->salarioMinimo.") and ( a.ibcSalud ) <(25*".$this->salarioMinimo.") ) then ( a.ibcSalud*(2/100) )                      
     when ( ( a.ibcSalud ) >=(25*".$this->salarioMinimo.") ) then ( ".$this->salarioMinimo." / 2  )                               
    else 0  
end as valor  
from n_planilla_unica_e a where a.idPla=".$idPla." and a.idEmp=".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
    }

/// Funciones para ausentismos quitar despues
   // 2. Sumatoria procesos Ley 100
   public function getLeyAus($id, $idEmp, $dias )
   {
      $result=$this->adapter->query("Select 
( (sum(case when f.id = 133 then # ES UN CONCEPTO DE VACACIONES
       ( round( ( e.devengado / d.diasVac ), 2 ) ) * ( case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 c.diasVaca 
              else                 
                0 end  )
    else # ES OTRO CONCEPTO 
case when ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end ) > (25 * 689454)  # valida que no supere tope maximo         
      then (25 * 689454) 
    else 
         ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end )        
      end      
    end ) + (  case when  ( (valorRetVaca is null) or ( month(h.fechaI) = a.mes and month(h.fechaF) = a.mes) )  then 0 else c.valorRetVaca end)  ) / (30-".$dias.")  ) * ".$dias." as valor 
 , 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) > (25 * 689454)          
      then (25 * 689454)          
    else 
         0
      end  as topMax, 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) < (1 * 689454)          
      then (1 * 689454)          
    else 
         0
      end  as topMin,   
      c.sueldo  ,
      # Vacaciones 
              case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 ( (datediff( b.fechaF , b.fechaI)+1) * 2 ) - d.dias   
              else                 
                 case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes) and (month(h.fechaF) > a.mes) ) then # Vacaciones dentro del mismo mes
                    ( (datediff( concat( a.ano,'-',a.mes,'-30' )  , h.fechaI) +1)  )   
                 else 
                  0 
                end                 
                 end as diasVac,                              
      round( ( e.devengado / d.diasVac ), 2 ) as valorDiaVac, c.valorRetVaca # Vacciones por ingreso     
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                        where ( g.idProc=1 or j.tipo = 1 )  # 1 es proceso de ley 100                        
                        and c.idEmp=".$idEmp." and a.id=".$id." order by c.idEmp",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                              
   // IBC Salud anterior
   public function getIbcAnt($idEmp, $ano, $mes)
   {
      $mes = $mes ;

      $result=$this->adapter->query("select 
case when ( ( select year(con.fechaI) 
  from n_emp_contratos con where con.idEmp = b.idEmp order by con.id limit 1 ) = a.ano 
and ( select month(con.fechaI)  from n_emp_contratos con where con.idEmp = b.idEmp order by con.id limit 1 ) = a.mes ) then  
   b.sueldo 
else
   b.ibcSalud  
end as ibcSalud                  
         from n_planilla_unica a 
            inner join n_planilla_unica_e b on b.idPla = a.id
         where b.idEmp = ".$idEmp." and a.ano = ".$ano." and a.mes = ".$mes ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
      // CASOS       
      // 1. Cuando el empleado ingreso el mes anterior y tiene en el mes actual una licencia no remunerada 
      // Debe tomar como ibc de salud y pension para los dias de no asistentia el sueldo como base , ya que no debe pagar por debajo del sueldo
      // 
   }
}




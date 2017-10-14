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
       // $this->salarioMinimo=644350;
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
   public function getLey($idPla, $idEmp, $id )
   {
      // Buscar si es retiro e ingreso partido en el mismo mes
      $result=$this->adapter->query("Select * 
                    from n_planilla_unica_e a   
                        where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      $con = ''; $con2 = ''; 
      $pri = $datos['priRetiro'];
      if ($datos['priRetiro']==1) // Caso cuando se detecta retiro en el mismo mes 
      {
          $con = " and b.id <= c.idNomRet "; 
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }
      if ($datos['priRetiro']==2) // Caso cuando se detecta ingreso en el mismo mes 
      {
          $con = " and d.contra = 3 ";     
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      } 
      $result=$this->adapter->query("select ( case when integral then 
                ( sum(valor) / (1.3) )   
             else 
                sum(valor) end ) as valor , 
          sueldo from ( 
        Select sum(e.devengado) + 
         ( case when  c.diasVaca > 0 then (c.diasVaca * c.valorUniVaca) else 0 end ) as valor, c.sueldo, c.integral   
                    from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2." 
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                   where g.idProc in (1,17) # procesos ley 100 y adicionales planila unica 
                        and e.idConc not in( 133 ) # 1 es proceso de ley 100, las vacaciones ya se calcularon los dias dentro del mes 
                        ".$con." and c.idEmp=".$idEmp." and a.id=".$idPla." 
union all
# Caso para vacaciones que toman el mes completo  
Select ( case when  c.diasVaca = 30 then (c.diasVaca * c.valorUniVaca) else 0 end ) as valor, c.sueldo, c.integral   
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2." 
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                   where g.idProc in (1) # procesos ley 100 y adicionales planila unica 
                        ".$con." 
                        and c.idEmp=".$idEmp." and a.id=".$idPla." 

                         ) as a ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                           

   // 2. Sumatoria procesos que no son Ley 100
   public function getNoLey($id, $idEmp )
   {
      $result=$this->adapter->query("Select sum(e.devengado) as valor 
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                   where g.idProc in (18) # procesos ley 100 y adicionales planila unica 
                        and c.idEmp=".$idEmp." and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                           

   // 2. Sumatoria procesos Ley 100 menos EGA , AT  
   public function getLeyR($idPla, $idEmp, $id )
   {
      // Buscar si es retiro e ingreso partido en el mismo mes
      $result=$this->adapter->query("Select * 
                    from n_planilla_unica_e a   
                        where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      $con = ''; $con2 = ''; 
      $pri = $datos['priRetiro'];
      if ($datos['priRetiro']==1) // Caso cuando se detecta retiro en el mismo mes 
      {
          $con = " and b.id <= c.idNomRet "; 
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }
      if ($datos['priRetiro']==2) // Caso cuando se detecta ingreso en el mismo mes 
      {
          $con = " and d.contra = 3 ";     
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      } 

      $result=$this->adapter->query("Select 
(    ( sum(case when f.id = 133 then # ES UN CONCEPTO DE VACACIONES
       ( round( ( e.devengado / d.diasVac ), 2 ) ) * ( case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 c.diasVaca  
              else                 
                0 end  )
    else # ES OTRO CONCEPTO 
case when ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end ) > (25 * ".$this->salarioMinimo.")  # valida que no supere tope maximo         
      then (25 * ".$this->salarioMinimo.") 
    else 
         ( case f.tipo when 1 then (e.devengado)
              when 2 then (0) end )        
      end      
    end ) + (  case when  ( (valorRetVaca is null) or ( month(h.fechaI) = a.mes and month(h.fechaF) = a.mes) )  then 0 else 0 end  )  )  )  as valor 
 , 

( case when c.integral then 
                ( sum(e.devengado) / (1.3) )   
             else 
                sum(e.devengado) end ) as valorInt , 


case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) > (25 * ".$this->salarioMinimo.")          
      then (25 * ".$this->salarioMinimo.")          
    else 
         0
      end  as topMax, 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) < (1 * ".$this->salarioMinimo.")          
      then (1 * ".$this->salarioMinimo.")          
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
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2." 
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                    where ( g.idProc=23 or g.idProc=17 ) and e.idConc not in ('207' )   # 1 es proceso de ley 100  
                        and e.idConc not in (133, 294) # Sin vacaciones 
                      ".$con."   
                        and c.idEmp=".$idEmp." and a.id=".$idPla ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                           

   // 2. Sumatoria procesos Ley 100 menos EGA , AT  Detallado 
   public function getLeyRD($id, $idPla, $idEmp )
   {
      // Buscar si es retiro e ingreso partido en el mismo mes
      $result=$this->adapter->query("Select * 
                    from n_planilla_unica_e a   
                        where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      $con = ''; $con2 = ''; 
      $pri = $datos['priRetiro'];
      if ($datos['priRetiro']==1) // Caso cuando se detecta retiro en el mismo mes 
      {
          $con = " and b.id <= c.idNomRet "; 
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }
      if ($datos['priRetiro']==2) // Caso cuando se detecta ingreso en el mismo mes 
      {
          $con = " and d.contra = 3 ";     
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }

      $result=$this->adapter->query("Select f.nombre as nomCon, e.horas , b.fechaI, b.fechaF, 0 as deducido, e.detalle, 
(    ( (case when f.id = 133 then # ES UN CONCEPTO DE VACACIONES
       ( round( ( e.devengado / d.diasVac ), 2 ) ) * ( case when ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) then # fecha de inicio de vacaciones es menor al final del mes 
                 c.diasVaca  
              else                 
                0 end  )
    else # ES OTRO CONCEPTO 
case when ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end ) > (25 * ".$this->salarioMinimo.")  # valida que no supere tope maximo         
      then (25 * ".$this->salarioMinimo.") 
    else 
         ( case f.tipo when 1 then (e.devengado)
              when 2 then (0) end )         
      end      
    end ) + (  case when  ( (valorRetVaca is null) or ( month(h.fechaI) = a.mes and month(h.fechaF) = a.mes) )  then 0 else 0 end  )  )  )  as devengado  
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2." 
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        inner join a_empleados i on i.id = d.idEmp 
                        inner join n_tipemp j on j.id = i.idTemp 
                        left join n_vacaciones h on h.id = d.idVac 
                    where ( g.idProc=23 or g.idProc=17 ) and e.idConc not in ('207' )   # 1 es proceso de ley 100  
                        and e.idConc not in (133, 294) # Sin vacaciones 
                    ".$con." 
                         and c.idEmp=".$idEmp." and c.regAus=0  and a.id=".$idPla,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
//    then 0 else c.valorRetVaca end  )  )  ) as linea cambiada 
   }    

   // 2.1 Detallado procesos Ley 100
   public function getLeyD($id, $idPla, $idEmp)
   {  
      // Buscar si es retiro e ingreso partido en el mismo mes
      $result=$this->adapter->query("Select * 
                    from n_planilla_unica_e a   
                        where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      $con = ''; $con2 = ''; 
      $pri = $datos['priRetiro'];
      if ($datos['priRetiro']==1) // Caso cuando se detecta retiro en el mismo mes 
      {
          $con = " and b.id <= c.idNomRet "; 
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }
      if ($datos['priRetiro']==2) // Caso cuando se detecta ingreso en el mismo mes 
      {
          $con = " and d.contra = 3 ";     
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }

      $result=$this->adapter->query("Select b.fechaI, b.fechaF, d.dias,
          (datediff( b.fechaF , b.fechaI)+1) * 2  as diasCal, c.diasVaca, 
       f.nombre as nomCon, 
                    e.devengado, e.deducido, 
                c.valorUniVaca as valorDiaVac , 
                e.horas, c.sueldo, f.id as idCon, c.diasRetVaca, e.detalle   
                    from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2." 
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
                        inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id 
                        left join n_vacaciones h on h.id = d.idVac 
                        where g.idProc in (1,17) # 1 es proceso de ley 100 y adicionales planilla unica 
                        and c.idEmp=".$idEmp." and c.regAus=0 and e.idConc !=133 # Menos vacaciones 
                         ".$con." 
                          and a.id=".$idPla."  order by b.fechaI, b.fechaF, e.detalle, f.codigo   ",Adapter::QUERY_MODE_EXECUTE);
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
                        where e.idConc = 21 # 1 Fondos de solidaridad
                        and c.idEmp=".$idEmp." and a.id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                              
   
   // 4. Sumatoria caja de compensacion 
   public function getCaja($idPla, $idEmp, $id)
   {
      // Buscar si es retiro e ingreso partido en el mismo mes
      $result=$this->adapter->query("Select * 
                    from n_planilla_unica_e a   
                        where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      $con = ''; $con2 = ''; 
      $pri = $datos['priRetiro'];
      if ($datos['priRetiro']==1) // Caso cuando se detecta retiro en el mismo mes 
      {
          $con = " and b.id <= c.idNomRet "; 
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }
      if ($datos['priRetiro']==2) // Caso cuando se detecta ingreso en el mismo mes 
      {
          $con = " and d.contra = 3 ";     
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      } 

      $result=$this->adapter->query("select 
        ( case when c.integral then 
                ( sum(e.devengado) / (1.3) )   
             else 
                sum(e.devengado) end ) as valor

                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2."  
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
      inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id
                        where g.idProc in (3)  # 3 es proceso parafiscal 
                        ".$con." and c.idEmp=".$idEmp." and a.id=".$idPla,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                

   // 4. Sumatoria caja de compensacion detallado
   public function getCajaD($id, $idPla, $idEmp)
   {
      // Buscar si es retiro e ingreso partido en el mismo mes
      $result=$this->adapter->query("Select * 
                    from n_planilla_unica_e a   
                        where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      $con = ''; $con2 = ''; 
      $pri = $datos['priRetiro'];
      if ($datos['priRetiro']==1) // Caso cuando se detecta retiro en el mismo mes 
      {
          $con = " and b.id <= c.idNomRet "; 
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }
      if ($datos['priRetiro']==2) // Caso cuando se detecta ingreso en el mismo mes 
      {
          $con = " and d.contra = 3 ";     
          $con2 = " and c.priRetiro = ".$datos['priRetiro'];
      }    
      $result=$this->adapter->query("select 
        f.nombre as nomCon, e.devengado, e.deducido, e.horas, b.fechaI, b.fechaF, e.detalle  
                    from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes # Traer datos del mes en cuestion
                        inner join n_planilla_unica_e c on c.idPla = a.id ".$con2." 
                        inner join n_nomina_e d on d.idNom = b.id and d.idEmp = c.idEmp
                        inner join n_nomina_e_d e on e.idInom = d.id 
      inner join n_conceptos f on f.id = e.idConc
                        inner join n_conceptos_pr g on g.idConc = f.id
                    where g.idProc in (3) 
                       ".$con." 
                        and c.idEmp=".$idEmp." and c.regAus=0 and a.id=".$idPla ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }                
   // 5. Ausentismos
   public function getAus($id, $idEmp)
   {
      $result=$this->adapter->query("select sum( d.dias ) as diasAus,
 ( case when month(e.fechai)<a.mes then concat( year(e.fechai),'-', lpad(a.mes,2,'0'),'-01' ) else e.fechai end ) as fechaI ,

( case when month(e.fechaf)>a.mes then concat( year(e.fechaf),'-', lpad(a.mes,2,'0'),'-28' ) else e.fechaf end ) as fechaF        
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e_a d on d.idNom = b.id and d.idEmp = c.idEmp  
                        inner join n_ausentismos e on e.id = d.idAus and e.horas = 0 
                        inner join n_tip_aus f on f.id = e.idTaus 
                     where a.id = ".$id." and f.tipo = 2 and f.luto = 0 and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                               

   // 5. Incapacidades 
   public function getInca($id, $idEmp)
   {
      $result=$this->adapter->query("select sum( d.diasInc ) as diasInc, f.tipo,

 
 ( select case when month(aa.fechai)<a.mes then concat( year(aa.fechaI),'-', lpad(a.mes,2,'0'),'-01' ) else aa.fechai end 
  
   from n_incapacidades aa where aa.id = d.idInc  order by aa.id desc limit 1  ) as fechaI ,

 ( select case when month(aa.fechaf)>a.mes then concat( year(aa.fechaf),'-', lpad(a.mes,2,'0'), '-28' ) else aa.fechaf end 
  
   from n_incapacidades aa where aa.id = d.idInc order by aa.id desc limit 1  ) as fechaF 
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
      $result=$this->adapter->query("
# Verificacion por aumento sin escala salarial 
        Select 
             case when b.salarioAct != b.salarioNue then 1 else 0 end as valor, a.fecDoc   
                        from n_asalarial a 
                           inner join n_asalarial_emp b on b.idAsal = a.id 
                           inner join n_planilla_unica_e c on c.idEmp = b.idEmp  
                                                          and c.idPla = ".$id." 
                        where a.reporPlanilla=0 and c.idEmp=".$idEmp." 
union all
# Verificacion por aumento por escala salarial 
    select  case when b.salarioAct != b.salarioNue then 1 else 0 end as valor, a.fecDoc               
                        from n_asalarial a 
                           inner join n_asalarial_d b on b.idAsal = a.id 
                           inner join a_empleados d on d.idSal = b.idEsal 
                        where a.reporPlanilla=0  and b.porInc>0 and d.id=".$idEmp." 
                        ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }       

   // 7. Vacaciones
   public function getVaca($id, $idEmp)
   {
      $result=$this->adapter->query("Select d.id as idVac , 

case when ( month(d.fechaI) = a.mes and month(d.fechaR) > a.mes ) then # Si inicia el periodo de vacaciones en el mes y termina en otro mes nomina actual tiene el idvac y dias 

     30 - ( sum(e.dias) + sum( e.diasI ) )  # Sumo los dias pagados en nomina - el restante de los 30 para salir 

   else # Vacaciones comprendidas en el mismo periodo 

     case when ( month(d.fechaI)=a.mes and month(d.fechaR)=a.mes ) then # Vacaciones comprendidas en el mismo mes de liquidacion
          
       ( DATEDIFF( d.fechaR , d.fechaI ) )
          
        else # Caso vacaciones inician antes y terminan en otro periodo 
          case when ( month(d.fechaI)< a.mes and month(d.fechaR)>a.mes ) then # Disfurte de vacaciones dentro del periodo de planilla unica
             30
          else # Cas retorno de vacaciones 
            case when ( month(d.fechaI)< a.mes and month(d.fechaR)=a.mes ) then # Fin de vacaciones despeus de varios dias

                 case when ( d.fechaF!=d.fechaR ) then # Cuando la fecha de regreso y fin de vacaciones son diferentes 
                    datediff( d.fechaR , concat( year(d.fechaR) ,'-', month(d.fechaR), '-01' ) )  
            else  
                    datediff( d.fechaR , concat( year(d.fechaR) ,'-', month(d.fechaR), '-01' ) ) + 1 
                 end 


            else # Periodo es fin de año y retorna en enero 
               
datediff( concat( a.ano, '-12-30' ) , d.fechaI  )  +1 
        end
          end 
     end      
end as diasVac , 
 
 ( select case when sum(ee.diasInc) is null then 0 else sum(ee.diasInc) end from n_nomina bb 
            inner join n_nomina_e_i ee on ee.idNom = bb.id 
           where year(bb.fechaI) = a.ano and month(bb.fechaI) = a.mes  and ee.idEmp = e.idEmp ) as diasInc,
                  # valor vaacaciones 
        ( select bb.devengado  
             from n_nomina_e aa 
               inner join n_nomina_e_d bb on bb.idInom = aa.id 
           where aa.idEmp = c.idEmp and bb.idConc = 133 and aa.idVac = d.id limit 1  ) / d.diasCal as valorVac ,

 ( case when month(d.fechaI)<a.mes then concat( year(d.fechaI),'-', lpad(a.mes,2,'0'),'-01' ) else d.fechaI end ) as fechaI ,

( case when month(d.fechaR)>a.mes then concat( year(d.fechaR),'-', lpad(a.mes,2,'0'),'-28' ) else d.fechaF end ) as fechaF  
 
                        from n_planilla_unica a 
                        inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                        inner join n_planilla_unica_e c on c.idPla = a.id 
                        inner join n_nomina_e e on e.idNom = b.id and e.idEmp = c.idEmp 
                        inner join n_vacaciones d on d.idEmp = c.idEmp and year(d.fechaI)=year(b.fechaI)
                and year(d.fechaI)=a.ano 
           and ( a.mes between month(d.fechaI) and ( case when year(d.fechaF)>a.ano then  12 else month( d.fechaF) end  )  ) 
           or ( (year( d.fechaI)=(a.ano-1) ) and ( (year(d.fechaF)=a.ano) and month(d.fechaF)=1 ) and d.idEmp = e.idEmp  ) 
                        where a.id = ".$id." and year(d.fechaI)>=year(b.fechaI)
                          and c.idEmp = ".$idEmp."  and d.dias>0 and e.actVac = 0 group by d.id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }
   // Contrados de empleados
   public function getContratos($id, $idEmp)
   {
      $result=$this->adapter->query("Select b.id as idNom, b.idTnom, c.id, c.contra, ( select case when sum( cc.dias ) > 30 then 30 else sum( cc.dias ) end as dias 
                    from n_nomina_e cc inner join n_nomina bb on bb.id = cc.idNom 
                      where cc.idEmp = c.idEmp and year(bb.fechaI) = year(b.fechaI) and month( bb.fechaI ) = month( b.fechaI ) and cc.id=c.id  ) as dias, d.finContrato,
               ( select aa.fechaI from n_emp_contratos aa where aa.idEmp = d.id and aa.tipo = 1 order by id limit 1 ) as fechaIcontrato,   
               ( select aa.fechaF from n_emp_contratos aa where aa.idEmp = d.id and aa.tipo = 1 order by id limit 1 ) as fechaFcontrato                             
                    from n_planilla_unica a 
                         inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                         inner join n_nomina_e c on c.idNom = b.id 
                         inner join a_empleados d on d.id = c.idEmp 
                     where a.id = ".$id." and (c.contra > 0 or b.idTnom in (1,6) or d.finContrato = 1 ) and c.idEmp = ".$idEmp."  
                   group by  b.id, b.idTnom   
                     order by b.id " ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }       
   // Contrados de empleados que se renuevan en el mes 
   public function getContratosRenova($id, $idEmp)
   {
      $result=$this->adapter->query("Select c.id, c.contra, ( select case when sum( cc.dias ) > 30 then 30 else sum( cc.dias ) end as dias 
                    from n_nomina_e cc inner join n_nomina bb on bb.id = cc.idNom 
                      where cc.idEmp = c.idEmp and year(bb.fechaI) = year(b.fechaI) and month( bb.fechaI ) = month( b.fechaI ) and cc.id=c.id  ) as dias, d.finContrato    
                    from n_planilla_unica a 
                         inner join n_nomina b on year(b.fechaI) = a.ano and  month(b.fechaI) = a.mes 
                         inner join n_nomina_e c on c.idNom = b.id 
                         inner join a_empleados d on d.id = c.idEmp 
                         inner join n_planilla_unica_e e on e.idEmp = d.id 
                     where e.priRetiro = 2 and c.contra = 3 and a.id = ".$id." and (c.contra > 0 or d.finContrato = 1 ) and c.idEmp = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }          
   // Consulta tipo 01
   public function getCon01($id, $idSuc)
   {
      $con = "";
      $con2 = "";
      $campoSuc = "d.e02";
      $cerosSuc = "";
      if ( $idSuc != 0 )
      {  
          $con = "and b.codSuc = '".$idSuc."'";
          $con2 = "and dd.codSuc = '".$idSuc."'";  
          $campoSuc = "'".$idSuc."'";  
          $cerosSuc = '0';
      }

      $result=$this->adapter->query("select '01' as tipo , lpad( @item := @item + 1, 5, '0' ) as consecutivo,
rpad( c.empresa, 200,' ') as empresa, 
d.nit as nit,
rpad(  ltrim(d.nitEmp) , 16,' ') as nitEmpresa, d.e as e,
lpad( '' , 20,' ') as blanco20, lpad(d.s, 1 , '' ) as s,
lpad( ltrim(".$campoSuc."), 4, '".$cerosSuc."' ) as e01, lpad( '' , 6,' ') as blanco8, 
rpad( ltrim(d.e02), 2, ' ' ) as e02, lpad( '' , 38,' ') as blanco38,
rpad( ltrim(d.e1425), 5, ' ' ) as e1425, lpad( '' , 1,' ') as blanco1,   # NO SE QUE ES ESTO 
concat( a.ano , '-' ,lpad( a.mes , 2,'0') ) as perPension,

case when a.mes = 12 then 
  concat( a.ano+1 , '-',lpad( 1 , 2,'0') ) 
else 
  concat( a.ano , '-',lpad( a.mes+1 , 2,'0') ) end as perSalud, 

lpad( '' , 9,'0') as blanco9, d.e1 as e1, # NO SE SABE QUE ES ESTO 
lpad( '' , 10,' ') as blanco11 , 
lpad( (select count( distinct(b.idEmp) ) 
         from n_planilla_unica_e b where b.idPla = a.id  ".$con.") , 5, '0' ) as numEmp,

lpad( (select round(sum( cc.devengado ),0)  
from n_nomina aa
  inner join n_nomina_e bb on bb.idNom = aa.id
  inner join n_nomina_e_d cc on cc.idInom = bb.id 
  inner join a_empleados dd on dd.id = bb.idEmp 
where cc.devengado > 0 ".$con2." and year(aa.fechaI ) = a.ano and month(aa.fechaI)=a.mes ) , 12, '0' ) as valNomina,  
 d.e101 as e101 
          from n_planilla_unica a , (SELECT @item := 0) item  
             inner join c_general c on c.id = 1
             inner join c_general_pla d on d.id = 1    
          where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }          
   // Consulta tipo 02
   public function getCon02($id, $idSuc)
   {
      $con = "";
      $con2 = "";
      if ( $idSuc != 0 )
      {  
          $con = "and b.codSuc = '".$idSuc."'";
          $con2 = "and dd.codSuc = '".$idSuc."'";          
      }    
      $result=$this->adapter->query("select b.id, '02' as tipo,  
'CC' as tipCed, rpad( d.CedEmp , 16,' ') as cedula, 
case when b.aprendiz = 1 and h.tipo = 1 then # Aprendiz productivo 
  '19'
else 
  case when h.tipo = 3 then # Aprendiz etapa electiva 
   '12'
 else   
  '01' end  end as tipAporte,
case when b.pensionado = 1 then '04' else '00' end as blanco2,  
 '  ' as e00, k.codigo as ciuDepa , # configurar esto 
rpad( replace(d.apellido1,'Ñ','N') , 20,' ') as apellido1 ,rpad( replace(d.apellido2,'Ñ','N')  , 30,' ') as apellido2 ,
rpad( d.nombre1 , 20,' ') as nombre1, rpad( d.nombre2 , 30,' ') as nombre2,     
case when nIngreso = 1 then 'X' else ' ' end as ingreso ,    
case when nRetiro = 1 then 'X' else ' ' end as retiro ,    
' ' as trasSalud, ' ' as idFsalTras , 
' ' as trasPension, ' ' as idFpenTras ,         
case when (nVsp=1 ) then 'X' else ' ' end  as e1, # VSP 
case when nVsp = 1 then ' ' else ' ' end as nVaca , # Campo sin identificar Variacion permanente de trabajo 
case when (nVst=1 ) then 'X' else ' ' end as nVst ,  # Variacion (145)termporal de devengados
case when b.regAus = 1 then 'X' else ' ' end as nAus ,  # Suspencion
# De las incapacidades
case when b.nInca =1 then 'X' else ' ' end  as incGeneral, 
case when ( b.Mat=1 or b.Pat =1)  then 'X' else ' ' end as incMaternidad, # Fin de las incapacidades 
case when nVaca = 1 then 'X' else ' ' end as vaca , # Vacaciones ,
' ' as blan1 , # pendienes por identificar
' ' as blan2 , # pendienes por identificar
case when b.at = 1 then lpad(sum(b.diasInc),2,'0') else '00' end as acct, # Accidente de trabajo  
lpad( case when e.codigo is null then ' ' else e.codigo end , 6,' ') as codFonPension,
lpad( '' , 6,' ') as espaPension,
lpad( f.codigo , 6,' ') as codFonSalud,
lpad( '' , 6,' ') as espaSalud,
lpad( case when
       ( select gg.codigo from t_fondos gg where gg.id = d.idCaja ) is null
          then '' else ( select gg.codigo from t_fondos gg where gg.id = d.idCaja )  end  , 5,' ') as codCaja,
lpad( '' , 1,' ') as espaCaja, 
 sum(b.diasPension)  as diasPension,
 sum(b.diasSalud)  as diasSalud,
 sum(b.diasRiesgos)  as diasRiesgos,
( case when b.nRetiro =1 or b.at=1 then sum(b.diasRiesgos) else (b.diasCaja) end ) as diasCaja,
lpad( b.sueldo , 9,'0') as salarioBase,
' ' as blan4 ,
round( sum(b.ibcSalud) , 0 ) as ibcSalud, 
round( sum(b.ibcPension), 0) as ibcPension, 
round( sum(b.ibcRiesgos), 0) as ibcRiesgos,
round( sum(b.ibcCaja) ,0 ) as ibcCaja,
lpad( round(b.porPension,4) , 6,'0') as porPension, # Aportes de pension
lpad( round( sum(b.aporPension) ,0) , 10,'0') as aporPension,
round( sum(b.aporPension) ,0)  as aporPension2,
lpad( '' , 17,'0') as cerosPension,
lpad( round( sum(b.aporPension),0) , 10,'0') as aporPension,
lpad( round( sum(b.aporSol1),0) , 9,'0') as aporSolidaridad,
round( sum(b.aporSol1),0)  as aporSolidaridad2,
lpad( round( sum(b.aporSol2),0) , 9,'0') as aporSolidaridad9,
round( sum(b.aporSol2),0)as aporSolidaridad92,
lpad( '' , 9,'0') as cerosSolidaridad, # ( 300 - 307 )
lpad( round(b.porSalud, 4) , 6,'0') as porSalud,  # Aportes de salud
lpad( round(sum(b.aporSalud),0), 10,'0') as aporSalud, # ( 314 - 323 )
      round( sum(b.aporSalud),0) as aporSalud2, # ( 314 - 323 )
lpad( '' , 9,'0') as cerosSalud,
lpad( '' , 15,' ') as espaciosPension,
lpad( '' , 9,'0') as cerosSalud2,
lpad( '' , 15,' ') as espaciosPension2, 
lpad( '' , 9,'0') as cerosSalud3, # ( 372 - 380  )
lpad( round(b.tarifaArl/100,5) , 7,'0') as tarifaArl,   # Aportes de pension
 lpad( '' , 10,'0') as cerosArl,
1 as claseRiesgo ,
lpad( round(sum(b.aporRiesgos),0) , 9,'0') as aporRiesgos, 
round(sum(b.aporRiesgos),0)  as aporRiesgos2, 
lpad( round(sum(b.porCaja),5) , 6,'0') as porCaja,   # Aportes caja 
lpad( round(sum(b.aporCaja),0) , 10,'0') as aporCaja, # ( 399 )
round(sum(b.aporCaja),0) as aporCaja2, # ( 399 )
lpad( round(sum(b.porSena),5) , 6,'0') as porSena,   # Aportes sena
lpad( round(sum(b.aporSena),0) , 10,'0') as aporSena,
round(sum(b.aporSena),0) as aporSena2,
lpad( round(sum(b.porIcbf),5) , 6,'0') as porIcbf,   # Aportes icbf
lpad( round(sum(b.aporIcbf),0) , 10,'0') as aporIcbf, 
round(sum(b.aporIcbf),0)  as aporIcbf2, 
'0.0000' as valor1, 
lpad( '' , 10,'0') as cerosFinal,
'0.0000' as valor2,
lpad( '' , 10,'0') as cerosFinal2,  
lpad( '' , 18,' ') as cerosFinal3, 
case when b.pagoEmp=1 then 'S' else 'N' end as ley14 ,
lpad( ltrim(i.codigo) , 6, ' ' ) as codRiesgo,
lpad( ltrim(j.tipo ) , 1, ' ' ) as idNriesgo ,
' ' as tipoTarifa,

case when b.fechaI = '0000-00-00' then '' else b.fechaI end as fechaI,
case when b.fechaR = '0000-00-00' then '' else b.fechaR end as fechaR,
case when b.fechaVsp = '0000-00-00' then '' else b.fechaVsp end as fechaVsp,
case when b.fechaIsln = '0000-00-00' then '' else b.fechaIsln end as fechaIsln,
case when b.fechaFsln = '0000-00-00' then '' else b.fechaFsln end as fechaFsln,
case when b.fechaIige = '0000-00-00' then '' else b.fechaIige end as fechaIige,
case when b.fechaFige = '0000-00-00' then '' else b.fechaFige end as fechaFige,
case when b.fechaIlma = '0000-00-00' then '' else b.fechaIlma end as fechaIlma,
case when b.fechaFlma = '0000-00-00' then '' else b.fechaFlma end as fechaFlma,
case when b.fechaIvac = '0000-00-00' then '' else b.fechaIvac end as fechaIvac,
case when b.fechaFvac = '0000-00-00' then '' else b.fechaFvac end as fechaFvac,
case when b.fechaIvct = '0000-00-00' then '' else b.fechaIvct end as fechaIvct,
case when b.fechaFvct = '0000-00-00' then '' else b.fechaFvct end as fechaFvct,
case when b.fechaIirl = '0000-00-00' then '' else b.fechaIirl end as fechaIirl,
case when b.fechaFirl = '0000-00-00' then '' else b.fechaFirl end as fechaFirl,
b.ibcOtPara, b.horas, b.idEmp, b.diasInc,
# corregir funcion de dias pagados por aumento de sueldo 
( select case when ( sum(aa.dias ) ) is null then 0 else sum(aa.dias) end  
   from n_nomina_e aa
    where aa.idNom in ( 319, 318 )and aa.idEmp = d.id ) as diasRetro, b.diasVaca     
 
from n_planilla_unica a
  inner join n_planilla_unica_e b on b.idPla = a.id  
  inner join a_empleados d on d.id = b.idEmp 
  inner join c_general c on c.id = 1 
  left join t_fondos e on e.id = b.idFonP 
  left join t_fondos f on f.id = b.idFonS  
  left join t_fondos g on g.id = b.idCaja  
  left join n_tipemp h on h.id = d.idTemp 
  left join t_fondos i on i.id = d.idFarp
  left join n_tarifas j on j.id = d.idRies
  left join n_ciudades k on k.id = c.idCiu 
where a.id = ".$id."  ".$con." and h.tipo in (0,1,3) 
  group by b.idEmp , b.regAus, b.priRetiro 
order by d.CedEmp, b.priRetiro " ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->toArray();      
      return $datos;
   }          


   // Consulta tipo 01 nuevo formato
   public function getCon01nuevo()
   {

      $result=$this->adapter->query("select a.*, substr( ltrim(c.codigo), 1,2 ) as codDepar,
          substr( ltrim(c.codigo), 3,10 ) as codCiu, 
          substr( now() ,1 ,10)  as fecha ,
          concat( year(now()), '-', lpad( month(now())-1, 2 , '0') )  as periodoOsal ,                 
           concat( year(now()), '-', lpad( month(now()), 2 , '0') ) as periodoSal 
        from c_general_pla2 a 
           inner join c_general b on b.id = 1
           inner join n_ciudades c on c.id = b.idCiu" ,Adapter::QUERY_MODE_EXECUTE);
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
              when 2 then (e.deducido) end ) > (25 * ".$this->salarioMinimo.")  # valida que no supere tope maximo         
      then (25 * ".$this->salarioMinimo.") 
    else 
         ( case f.tipo when 1 then (e.devengado)
              when 2 then (e.deducido) end )        
      end      
    end ) + (  case when  ( (valorRetVaca is null) or ( month(h.fechaI) = a.mes and month(h.fechaF) = a.mes) )  then 0 else c.valorRetVaca end)  ) / (30-".$dias.")  ) * ".$dias." as valor 
 , 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) > (25 * ".$this->salarioMinimo.")          
      then (25 * ".$this->salarioMinimo.")          
    else 
         0
      end  as topMax, 
case when ( case f.tipo when 1 then sum(e.devengado)
              when 2 then (e.deducido) end ) < (1 * ".$this->salarioMinimo.")          
      then (1 * ".$this->salarioMinimo.")          
    else 
         0
      end  as topMin,   
      c.sueldo  ,
      # Vacaciones 
  case when (  ( (year(h.fechaI) = a.ano) and (month(h.fechaI) = a.mes ) ) and (month(h.fechaF) = a.mes) ) then # fecha de inicio de vacaciones es menor al final del mes 
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
    $mesF = $mes ; 
      if ( $mes == 1 )
      {
         $mes = 12;
         $ano = $ano - 1;
      }else 
         $mes = $mes - 1;    

      $ibcBasico = 1;   
      if ($ibcBasico == 0)
      {  
      $result=$this->adapter->query("select sum( 
case when ( ( select year(con.fechaI) 
  from n_emp_contratos con where con.idEmp = b.idEmp order by con.id limit 1 ) = a.ano 
and ( select month(con.fechaI)  from n_emp_contratos con where con.idEmp = b.idEmp order by con.id limit 1 ) = a.mes ) then  
   b.sueldo 
else
   b.ibcSalud  
end ) as ibcSalud                  
         from n_planilla_unica a 
            inner join n_planilla_unica_e b on b.idPla = a.id
         where b.idEmp = ".$idEmp." and a.ano = ".$ano." and a.mes = ".$mes ,Adapter::QUERY_MODE_EXECUTE);
      }else{
      $result=$this->adapter->query("select b.sueldo as ibcSalud                  
         from n_planilla_unica a 
            inner join n_planilla_unica_e b on b.idPla = a.id
         where b.idEmp = ".$idEmp." and a.ano = ".$ano." and a.mes = ".($mes) ,Adapter::QUERY_MODE_EXECUTE);

      }
      $datos = $result->current();      
      return $datos;
      // CASOS       
      // 1. Cuando el empleado ingreso el mes anterior y tiene en el mes actual una licencia no remunerada 
      // Debe tomar como ibc de salud y pension para los dias de no asistentia el sueldo como base , ya que no debe pagar por debajo del sueldo
      // 
   }
}




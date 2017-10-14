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
class IntegrarFunc extends AbstractTableGateway
{
   protected $table  = '';   
      
   public $dbAdapter;
   public $salarioMinimo;
   public $horasDias;
   public $salarioMinimoCovencional;
   public $dvIntegracion;
     
   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
        // Parametros de nomina para funciones de consulta 
        $d = new AlbumTable($this->adapter);
        $pn = new Paranomina($this->adapter);
        $dp = $pn->getGeneral1(1);
        $this->salarioMinimo=$dp['formula'];
        $dp = $pn->getGeneral1(2);
        $this->horasDias=$dp['valorNum'];
        $dp = $pn->getGeneral1(3);
        $this->salarioMinimoCovencional=$dp['formula'];// Salario minimo convencional        
        $dt = $d->getConfiguraG('');
        $this->dvIntegracion = $dt['dvIntegracion'];
   }
    
   // ----------------------------------------------------- 
   // FORMULAS FIJAS EN PROGRAMA *-----------------------------------------------------------------------------------------------
   // ------------------------------------------------- 
   // 1. Integracion de nomina 
   public function getIntegrarNomina($id, $idNom, $pagCes, $salPagarTipo)
   {
      $result=$this->adapter->query("insert into n_nomina_e_d_integrar (idNom, idInom, idIinom, idCpres ,idCon, nomCon, codCon, valor, idPref, pref,  codCta, gas, natCta, ter, nitTer, nitFon, idFonS, nitFonS, idFonP, nitFonP, error, idCcos, nit, dv, codCcos, embargo, nitEmb) 
select a.idNom, a.id as idInom, b.id as idIinom, b.idCpres, d.id, d.nombre as nomCon , d.id as codigo , 
case when sum(b.devengado) > 0 then 
  sum(b.devengado) 
 
else sum(b.deducido) end as valor, # Valor del concepto 
h.id as idPref, h.nombre as pref , 
case when e.gast=0 then # Si no es gasto lleva la cuenta completa
   d.codCta 
else # Si es une el prefijo a la cuenta de gasto
   case when ee.codCta != '' then 
      ee.codCta 
   else  
     concat( ltrim(h.nombre), substr(ltrim(d.codCta), length( ltrim(h.nombre)  )+1 ,100) )   
   end 
end as codCta, 
case when e.gast=0 then 'N' else 'S' end as gast,
case when d.natCta=0 then 'Debito' else 'Credito' end as natCta , 
case when e.ter=0 then 'N' else 'S' end as ter , 

  case when ( d.nitFon = 0 and d.idTer > 1 ) then  # Maneja el tercero preasignado
      i.codigo 
   else 
         case when ( d.nitFon = 1 and d.fondo = 1 ) then  # Maneja el tercero del fondo de salud
            f.nit 
         else 
              case when ( d.nitFon = 1 and d.fondo = 2 ) then  # Maneja el tercero del fondo de pension
                  g.nit 
               else  
                  case when ( d.nitFon = 0 and d.idTer = 1 ) then  # Maneja el nit del empleado
                      c.CedEmp  
                 else
                     case when ( d.nitFon = 0 and d.idTer = 0 ) then  # 0 seleccione nit tercero en blanco 
                        c.CedEmp
                       end               
                end 
         end       
      end
  end 
as nitTer , # Nit de la cuenta contable 
case when d.nitFon=0 then 'N' else 'S' end as nitfon, f.id, f.nit as nitSal , g.id, g.nit as nitPen,
case when e.id is null then 'Sin definir' else '' end cuentaE, b.idCcos, 

# Nit real del registro para contabilidad y nomina
case when e.ter = 1 then # Mameja tercero la cuenta 
 case when d.fondo = 1 then # Si es un fondo de salud marcado en el concepto 
      f.nit  
 else      
    case when d.fondo = 2 then # Si es un fondo de pensiones marcado en el concepto
      g.nit 
   else 
      case when d.id = 21 then # Si es un fondo de solidaridad
         case when g.nit = 0 then c.CedEmp else g.nit end # Esto no deberia ocurrir  
      else
       case when d.idTer > 0 then # usa el nit del tercero 
          i.codigo 
       else # Usa la cedula del empleado como tercero 
          c.CedEmp  
       end 
    end    
   end 
end else
  0 
end as nit, 
# Nit real del registro para contabilidad y nomina
case when e.ter = 1 then # Mameja tercero la cuenta 
  case when d.fondo = 1 then # Si es un fondo de salud 
     f.dv 
 else      
   case when d.fondo = 2 then # Si es un fondo de pensiones
      g.dv 
   else 
      case when d.id = 21 then # Si es un fondo de solidaridad
         g.dv 
      else
       case when d.idTer > 0 then # usa el nit del tercero 
          i.dv 
       else # Usa la cedula del empleado como tercero 
          ' '  
       end 
    end    
   end 
end 
else '' end as digVer, 
# Validacion centro de costo 
case when e.ccos = 0 then   
     0
   else 
     j.codigo  
end codCcos, b.idRef, # Marca del registro para embargos         
 case when m.dv > 0 then concat( m.codigo, '-', m.dv ) else m.codigo end as nitEmb  # Marca del registro para embargos         
from n_nomina_e a 
inner join n_nomina_e_d b on b.idInom = a.id
inner join a_empleados c on c.id = a.idEmp 
inner join n_conceptos d on d.id = b.idConc 
left join n_plan_cuentas e on e.codigo = d.codCta
inner join t_fondos f on f.id = c.idFsal
inner join t_fondos g on g.id = c.idFpen
inner join n_pref_con h on h.id = c.idPref 
left join n_conceptos_pref ee on ee.idConc = d.id and ee.idPref = h.id # Prefijo contable amarrado a la cuenta  
left join n_terceros_s ii on ii.id = d.idTer # tercero de sucursal 
left join n_terceros i on i.id = ii.idTer 
left join n_cencostos j on j.id = b.idCcos 
left join n_embargos k on k.id = b.idRef 
left join n_terceros_s l on l.id = k.idTer # Tercero asociado al embargo 
left join n_terceros m on m.id = l.idTer 
where a.idNom=".$idNom." and d.info=0 
group by a.id, b.idCcos, d.id" ,Adapter::QUERY_MODE_EXECUTE);

if ($salPagarTipo==0) // INtegracion empleado por empleado
{
    // Integrar salarios por pagar nomina 
    $result=$this->adapter->query("insert into n_nomina_e_d_integrar_pagar ( idNom , idInom , nomCon , valor, codCta , nit ) 
      select bb.id, d.id, 'SALARIOS POR PAGAR',  
          sum( case when a.natCta = 'Debito' then 
            a.valor
         else 0 end ) - sum( case when a.natCta = 'Credito' then a.valor else 0 end ) as pagar,
          cg.cuentaCuentaPagar,
case when bb.idTnom = 1 then (case when cg.nitCuentaPagar = 0 
                 then f.CedEmp # Ojo Configurar que si no pide nit por empleados 
                    else cg.nitCuentaPagar end)  else f.CedEmp end as nit 
      from n_nomina_e_d_integrar a 
         inner join n_nomina bb on bb.id = a.idNom 
         inner join n_nomina_e d on d.id = a.idInom and d.idNom = bb.id  
        left join n_cencostos c on c.id = a.idCcos 
        left join n_plan_cuentas e on e.codigo = a.codCta 
        left join a_empleados f on f.id = d.idEmp 
        left join n_conceptos g on g.id = a.idCon  
        left join n_terceros h on h.codigo = a.nitTer 
        left join c_general cg on cg.id = 1 # Condiguracion general 
        where a.idNom = ".$idNom." and g.info=0 
      group by d.idEmp" ,Adapter::QUERY_MODE_EXECUTE);
}

if ($salPagarTipo==1) // INtegracion por grupo
{
    // Integrar salarios por pagar nomina 
    $result=$this->adapter->query("insert into n_nomina_e_d_integrar_pagar ( idNom , idInom , nomCon , valor, codCta , nit ) 
      select bb.id, d.id, 'SALARIOS POR PAGAR',  
          sum( case when a.natCta = 'Debito' then 
            a.valor
         else 0 end ) - sum( case when a.natCta = 'Credito' then a.valor else 0 end ) as pagar,
          cg.cuentaCuentaPagar, '' as nit 
      from n_nomina_e_d_integrar a 
         inner join n_nomina bb on bb.id = a.idNom 
         inner join n_nomina_e d on d.id = a.idInom and d.idNom = bb.id  
        left join n_cencostos c on c.id = a.idCcos 
        left join n_plan_cuentas e on e.codigo = a.codCta 
        left join a_empleados f on f.id = d.idEmp 
        left join n_conceptos g on g.id = a.idCon  
        left join n_terceros h on h.codigo = a.nitTer 
        left join c_general cg on cg.id = 1 # Condiguracion general 
        where a.idNom = ".$idNom." and g.info=0 " ,Adapter::QUERY_MODE_EXECUTE);
}

    } // FIn integrar nomina                  
    
    // 2. Integracion de proviciones 
   public function getIntProv($idProc , $idEmp, $nombre, $idProv, $idNom)   
   {
      if ( $idEmp > 0 )
      {
         $con = '';
         if ( $nombre != 'Vacaciones' )
            $con = ' and f.integral = 0';

         $d = new AlbumTable($this->adapter); 
         $datProv = $d->getGeneral1("select count(a.id) as num 
          from n_nomina a
             inner join n_nomina_e b on b.idNom = a.id
             inner join n_nomina_e_d c on c.idNom = a.id and c.idInom = b.id
             inner join n_conceptos d on d.id = c.idConc
             inner join n_conceptos_pr e on e.idConc = d.id
             inner join a_empleados f on f.id = b.idEmp 
             left join n_pref_con g on g.id = f.idPref 
             left join n_plan_cuentas h on h.codigo = d.codCta
             left join n_terceros i on i.id = d.idTer 
             left join n_proviciones j on j.id = ".$idProv." # datos de la provicion
             left join t_fondos k on k.id = f.idFces # Solo para el nit de las cesantias               
             where a.id = ".$idNom." and b.idEmp = ".$idEmp." and e.idProc = ".$idProc." ".$con); 

         if ($datProv['num']>0)
         {

           $result=$this->adapter->query("insert into n_provisiones_integrar_p (idNom, idEmp, nombre, valor, codCtaD, 
                codCtaC, nitTerD, nitTerC, idUsu, fecha) 
         (select ".$idNom.", b.idEmp, '".$nombre."' ,
 sum( ( case when d.natCta=0 then c.devengado else c.deducido end ) * (j.porc/100) ) as valor, 

case when jd.gast=0 then # Si no es gasto lleva la cuenta completa
   j.codCtaD  
else # Si es une el prefijo a la cuenta de gasto
   case when ee.codCta != '' then 
      ee.codCta 
   else    
      concat( ltrim(g.nombre), substr(ltrim(j.codCtaD), length( ltrim(g.nombre)  )+1 ,100) )   
   end  
end as codCtaD, 

j.codCtaC as codCtaC,
 f.CedEmp as nitTerD, f.CedEmp as nitTerC, # si provision es 1  pone nit del fondo credito 
    1 as idUsu, now() 
          from n_nomina a
             inner join n_nomina_e b on b.idNom = a.id
             inner join n_nomina_e_d c on c.idNom = a.id and c.idInom = b.id
             inner join n_conceptos d on d.id = c.idConc
             inner join n_conceptos_pr e on e.idConc = d.id
             inner join a_empleados f on f.id = b.idEmp 
             left join n_pref_con g on g.id = f.idPref 
             left join n_plan_cuentas h on h.codigo = d.codCta
             left join n_terceros i on i.id = d.idTer 
             left join n_proviciones j on j.id = ".$idProv." # datos de la provicion
             left join n_proviciones_pref ee on ee.idProv = j.id and ee.idPref = g.id # Prefijo contable amarrado a la cuenta                              
             left join n_plan_cuentas jd on jd.codigo = j.codCtaD  # cuenta debito 
             left join t_fondos k on k.id = f.idFces # Solo para el nit de las cesantias               
             where a.id = ".$idNom." and b.idEmp = ".$idEmp." and e.idProc = ".$idProc." ".$con .")" ,Adapter::QUERY_MODE_EXECUTE);                       
        }
      }
// concat( j.codCtaD,'',g.nombre) as codCtaD,   Esto es importante para concatenar el prefijo a la cuenta de la provision 
//concat( j.codCtaC,'',g.nombre) as codCtaC,      
   }  

   // 3. Integracion de proviciones 
   public function getIntegrarProviciones($id)
   {
      $result=$this->adapter->query("( select a.idNom, b.id as idInom, d.id, d.nombre as nomCon , d.codigo , case when b.devengado > 0 then b.devengado else b.deducido end as valor, 
h.nombre as pref , d.codCta, case when e.gast=0 then 'N' else 'S' end as gast , case when d.natCta=0 then 'Debito' else 'Credito' end as natCta , 
e.ter, i.codigo as nitTerP , case when d.nitFon=0 then 'N' else 'S' end as nitfon, f.nit as nitSal , g.nit as nitFon, d.nombre  
from n_nomina_e a 
inner join n_nomina_e_d b on b.idInom = a.id
inner join a_empleados c on c.id = a.idEmp 
inner join n_conceptos d on d.id = b.idConc 
inner join n_plan_cuentas e on e.codigo = d.codCta
inner join t_fondos f on f.id = c.idFsal
inner join t_fondos g on g.id = c.idFpen
inner join n_pref_con h on h.id = c.idPref 
inner join n_terceros i on i.id = d.idTer )" ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->current();
      return $datos;
    }

   // Integracion planilla unica
   public function getIntegrarPlanilla($id)
   {
      $result=$this->adapter->query("# FONDOS DE SALUD  
select *, round(sum(valor),0) as valorF from ( 
select h.id as idCcos, d.id as idFon, a.ano, a.mes, case when f.ter = 1 
   then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitCred ,
case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then ltrim(d.nit) else concat(ltrim(d.nit), (case when ltrim(d.nit) = '0'then '' else '-' end) ,d.dv ) end ) else 0 end as nitDeb , d.nombre, 
case when b.aprendiz = 1  then (b.aporSalud) else (b.aporSalud) - 

( select sum( cc.deducido ) from n_nomina aa 
              inner join n_nomina_e bb on bb.idNom = aa.id  
              inner join n_nomina_e_d cc on cc.idInom = bb.id
     where year(aa.fechaI) = a.ano and month(aa.fechaI) = a.mes 
      and bb.idEmp = c.id and cc.idConc = 15  ) end as valor  , 
     
( select sum( cc.deducido ) from n_nomina aa 
              inner join n_nomina_e bb on bb.idNom = aa.id  
              inner join n_nomina_e_d cc on cc.idInom = bb.id
     where year(aa.fechaI) = a.ano and month(aa.fechaI) = a.mes 
      and bb.idEmp = c.id and cc.idConc = 15  ) as valorDescNom  ,      
     
     b.aporSalud as aporPlanilla,      
                      f.codigo as cuentaCred, 
                        case when ee.codCta != '' then 
                            ee.codCta 
                        else
                          g.codCtaD end as cuentaDeb, 
                  case when f.ccos = 1 then h.codigo else 0 end as codCcosC, 
                 case when i.ccos = 1 then h.codigo else 0 end as codCcosD ,'SALUD' as fondo, a.id , f.cxCxp as cxCxpC, i.cxCxp as cxCxpD     
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonS # ---------- fondo de salud 
                                inner join n_conceptos e on e.id = 15 # --------- Salud 
                                left join n_plan_cuentas f on f.codigo = e.codCta 
                                left join n_proviciones g on g.id = '5' # Salud
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                              
                                left join n_plan_cuentas i on i.codigo = g.codCtaD                                                               
                                left join n_cencostos h on h.id = c.idCcos 
                                where a.id = ".$id." ) as a 
                                   group by idCcos, idFon, cuentaDeb             

union all
# FONDOS DE PENSION + SOLIDARIDAD 

select *, round(sum(valor),0) as valorF from ( 
select h.id as idCcos, d.id as idFon, a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitCred , 
case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitDeb , d.nombre, 
case when b.aprendiz = 1  then 0 else (b.aporPension+b.aporSolidaridad )  - 

( select sum( cc.deducido ) from n_nomina aa 
              inner join n_nomina_e bb on bb.idNom = aa.id  
              inner join n_nomina_e_d cc on cc.idInom = bb.id
     where year(aa.fechaI) = a.ano and month(aa.fechaI) = a.mes 
      and bb.idEmp = c.id and cc.idConc in (11,21)  ) end as valor  , 
     
( select sum( cc.deducido ) from n_nomina aa 
              inner join n_nomina_e bb on bb.idNom = aa.id  
              inner join n_nomina_e_d cc on cc.idInom = bb.id
     where year(aa.fechaI) = a.ano and month(aa.fechaI) = a.mes 
      and bb.idEmp = c.id and cc.idConc in (11,21)  ) as valorDescNom  ,      
     
     b.aporSalud as aporPlanilla,      
                      f.codigo as cuentaCred, 
                        case when ee.codCta != '' then 
                            ee.codCta 
                        else
                          g.codCtaD end as cuentaDeb, 
                  case when f.ccos = 1 then h.codigo else 0 end as codCcosC, 
                 case when i.ccos = 1 then h.codigo else 0 end as codCcosD ,'PENSION' as fondo, a.id , f.cxCxp as cxCxpC, i.cxCxp as cxCxpD     
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonP # ---------- fondo de pension 
                                inner join n_conceptos e on e.id in (11) # --------- Provision
                                left join n_plan_cuentas f on f.codigo = e.codCta 
                                left join n_proviciones g on g.id = '6' # Salud 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_plan_cuentas i on i.codigo = g.codCtaD                                                               
                                left join n_cencostos h on h.id = c.idCcos 
                                where a.id =  ".$id." ) as a 
                     group by idCcos, idFon ,cuentaDeb           

union all                              
# ARL
select h.id as idCcos, d.id as idFon,a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitCred ,
           case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitDeb , d.nombre, round(sum(b.aporRiesgos),0) as valor, 
        0 as valorDescNom, 0 as aporPlanilla, 
        g.codCtaC as cuentaCred, 
            case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC,
            case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'ARL' as fondo, a.id,        i.cxCxp as cxCxpC, f.cxCxp as cxCxpD , 0 as valorF   
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonR  # riesgos 
                                left join n_proviciones g on g.id = '10' # Riesgos 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos 
                                left join n_plan_cuentas f on f.codigo = g.codCtaD 
                      left join n_plan_cuentas i on i.codigo = g.codCtaC                                                                                                 
                                where a.id = ".$id."  
                                group by h.id , d.id 
union all
# CAJA 
select h.id as idCcos, d.id as idFon,a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitCred,
 case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitDeb , d.nombre, round(sum(b.aporCaja),0) as valor, 
   0 as valorDescNom, 0 as aporPlanilla, 
    g.codCtaC as cuentaCred,  case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC, case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'CAJA' as fondo, a.id,         i.cxCxp as cxCxpC, f.cxCxp as cxCxpD , 0 as valorF   
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idCaja  
                                left join n_proviciones g on g.id = '7' # Caja
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos 
                                left join n_plan_cuentas f on f.codigo = g.codCtaD  
                                left join n_plan_cuentas i on i.codigo = g.codCtaC                                                               
                                where a.id =  ".$id."    
                                group by h.id , d.id
union all                              
# SENA
select h.id as idCcos, d.id as idFon,a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitCred , 
case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitDeb  , 'SENA' as nombre, round(sum(b.aporSena),0) as valor,0 as valorDescNom, 0 as aporPlanilla,  g.codCtaC as cuentaCred, 
 case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC, case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'SENA' as fondo, a.id,        i.cxCxp as cxCxpC, f.cxCxp as cxCxpD, 0 as valorF                 
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.tipo = 7 # ---------- Sena
                                left join n_proviciones g on g.id = '8' # Sena 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos 
                                left join n_plan_cuentas f on f.codigo = g.codCtaD       
                      left join n_plan_cuentas i on i.codigo = g.codCtaC                                                                                           
                                where a.id =  ".$id."  
                                group by h.id
union all
# ICBF 
select h.id as idCcos, d.id as idFon,a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitCred , 
case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),(case when ltrim(d.nit) = '0'then '' else '-' end),d.dv) end ) else 0 end as nitDeb , 'ICBF' as nombre, round(sum(b.aporIcbf),0) as valor, 0 as valorDescNom, 0 as aporPlanilla, g.codCtaC as cuentaCred,

 case when ee.codCta != '' then 
      ee.codCta 
   else
      g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC, case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'ICBF' as fondo , a.id,         i.cxCxp as cxCxpC, f.cxCxp as cxCxpD, 0 as valorF    
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.tipo = 6 # ---------- Icbf 
                                left join n_proviciones g on g.id = '9' # Icbf 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos  
                                left join n_plan_cuentas f on f.codigo = g.codCtaD      
                      left join n_plan_cuentas i on i.codigo = g.codCtaC                                                                                           
                             where a.id =  ".$id."  
                                group by h.id" ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->toArray();
      return $datos;
    }
   // Integracion planilla unica resmuida
   public function getIntegrarPlanillaResum($id)
   {
      $result=$this->adapter->query("insert into n_integracion_paso_planilla_r ( idPla, ano, mes , nit, nombre, ctaDeb, ctaCred, valor, fondo ) 
(
select a.id, ano, mes, nitCred as nit, nombre, cuentaDeb, cuentaCred, sum(valor) as valor, fondo  
from (  
# FONDOS DE SALUD  
select a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitCred , 
case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitDeb , d.nombre, 
sum(b.aporSalud) as valor,f.codigo as cuentaCred,  case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when f.ccos = 1 then h.codigo else 0 end as codCcosC, case when i.ccos = 1 then h.codigo else 0 end as codCcosD ,'SALUD' as fondo, a.id  
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonS # ---------- fondo de salud 
                                inner join n_conceptos e on e.id = 15 # --------- Salud 
                                left join n_plan_cuentas f on f.codigo = e.codCta 
                                left join n_proviciones g on g.id = '5' # Salud
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                              

                                left join n_plan_cuentas i on i.codigo = g.codCtaD                                                               
                                left join n_cencostos h on h.id = c.idCcos 
                              where a.id = ".$id."    
                                group by a.id, h.id , d.id
union all
# FONDOS DE PENSION + SOLIDARIDAD 
select a.ano, a.mes, 
case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitCred , 
case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitDeb, d.nombre, 
(sum(b.aporPension) + sum( case when b.regAus = 1 then 0 else b.aporSolidaridad end  ) ) as valor, f.codigo as cuentaCred,  case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when f.ccos = 1 then h.codigo else 0 end as codCcosC, case when i.ccos = 1 then h.codigo else 0 end as codCcosD ,'PENSION' as fondo, a.id 
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonP # ---------- fondo de pension 
                                inner join n_conceptos e on e.id in (11) # --------- Pension
                                left join n_plan_cuentas f on f.codigo = e.codCta 
                                left join n_proviciones g on g.id = '6' # Pension
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                              

                                left join n_plan_cuentas i on i.codigo = g.codCtaD                                                               
                                left join n_cencostos h on h.id = c.idCcos 
                              where a.id = ".$id."                                    
                       group by a.id, h.id , d.id 
union all                              
# ARL
select a.ano, a.mes, 
   case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitCred ,
           case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitDeb , d.nombre, round(sum(b.aporRiesgos),0) as valor, g.codCtaC as cuentaCred, 
            case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC,
            case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'ARL' as fondo, a.id            
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idFonR  # riesgos 
                                left join n_proviciones g on g.id = '10' # Riesgos 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                              

                                left join n_cencostos h on h.id = c.idCcos 
                                left join n_plan_cuentas f on f.codigo = g.codCtaD 
                      left join n_plan_cuentas i on i.codigo = g.codCtaC                                                                                                 
                              where a.id = ".$id."                          
                                group by a.id, h.id , d.id
union all
# CAJA 
select a.ano, a.mes, case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitCred,
 case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitDeb , d.nombre, round(sum(b.aporCaja),0) as valor, g.codCtaC as cuentaCred,  case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC, case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'CAJA' as fondo, a.id            
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.id = b.idCaja  
                                left join n_proviciones g on g.id = '7' # Caja
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos 
                                left join n_plan_cuentas f on f.codigo = g.codCtaD  
                                left join n_plan_cuentas i on i.codigo = g.codCtaC                                                               
                              where a.id = ".$id."                                    
                                group by a.id, h.id , d.id
union all                              
# SENA
select a.ano, a.mes, case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitCred , case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitDeb  , 'SENA' as nombre, round(sum(b.aporSena),0) as valor, g.codCtaC as cuentaCred,  case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC, case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'SENA' as fondo, a.id            
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.tipo = 7 # ---------- Sena
                                left join n_proviciones g on g.id = '8' # Sena 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos 
                                left join n_plan_cuentas f on f.codigo = g.codCtaD       
                      left join n_plan_cuentas i on i.codigo = g.codCtaC                                                                                           
                              where a.id = ".$id."                          
                                group by a.id, h.id
union all
# ICBF 
select a.ano, a.mes, case when f.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitCred ,
 case when i.ter = 1 then ( case when ".$this->dvIntegracion."=0 then d.nit else concat(ltrim(d.nit),'-',d.dv) end ) else 0 end as nitDeb , 'ICBF' as nombre, round(sum(b.aporIcbf),0) as valor, g.codCtaC as cuentaCred,  case when ee.codCta != '' then 
      ee.codCta 
   else g.codCtaD end as cuentaDeb, case when i.ccos = 1 then h.codigo else 0 end as codCcosC, case when f.ccos = 1 then h.codigo else 0 end as codCcosD ,'ICBF' as fondo , a.id           
                      from n_planilla_unica a 
                                inner join n_planilla_unica_e b on b.idPla = a.id 
                                inner join a_empleados c on c.id = b.idEmp 
                                inner join t_fondos d on d.tipo = 6 # ---------- Icbf 
                                left join n_proviciones g on g.id = '9' # Icbf 
                                left join n_proviciones_pref ee on ee.idProv = g.id and ee.idPref = c.idPref # Prefijo contable amarrado a la cuenta                                                     
                                left join n_cencostos h on h.id = c.idCcos  
                                left join n_plan_cuentas f on f.codigo = g.codCtaD      
                      left join n_plan_cuentas i on i.codigo = g.codCtaC                                                                                           
                              where a.id = ".$id."                          
                                group by a.id, h.id ) as a  
 where not exists (SELECT null from n_integracion_paso_planilla_r where idPla = a.id )                                  
group by ano, mes, fondo, nombre      
order by ano, mes, fondo, nombre )",Adapter::QUERY_MODE_EXECUTE);
      //$datos = $result->toArray();
      //return $datos;

   }   
   // Integracion planilla unica
   public function getIntegrarPlanilla2($id, $con)
   {
      $result=$this->adapter->query("select aa.ano, aa.mes, d.nombre as nomCon, 0 as debito, 
case when b.devengado > 0 then sum(b.devengado) else sum(b.deducido) end as credito, 
e.nombre as nomCta ,  
case when e.ter = 0 then ' ' else  # No maneja tercero # Nit ------------------------------------------------------
  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer > 0 ) then  # Maneja el tercero preasignado
      i.codigo   else 
         case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 1 ) then  # Maneja el tercero del fondo de salud
           f.nit  else 
              case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 2 ) then  # Maneja el tercero del fondo de pension
                g.nit  else  
                  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer = 0 ) then  # Maneja el nit del empleado
                      c.CedEmp  end 
         end       
      end
  end 
 end as nitTer ,
 case when e.ter = 0 then ' ' else  # No maneja tercero # Nombre ------------------------------------------------------
  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer > 0 ) then  # Maneja el tercero preasignado
      i.nombre   else 
         case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 1 ) then  # Maneja el tercero del fondo de salud
           f.nombre  else 
              case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 2 ) then  # Maneja el tercero del fondo de pension
                g.nombre  else  
                  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer = 0 ) then  # Maneja el nit del empleado
                      c.nombre  end 
         end       
      end
  end 
 end as nomTer ,
 d.codCta as coCtaCredito, 
f.nit as nitSal, 
substring(j.nombre,1,20) as nomCcos, j.codigo as codCcos ,
case when b.idConc = '15' then cc.aporSalud else # Salud
  case when b.idConc = '11' then cc.aporPension else # Pension 
    case when b.idConc = '21' then cc.aporSolidaridad else # Solidaridad
    0 end  
  end   
end as valPlanilla, 
cc.aporCaja, k.codCtaC as codCtaCCaja, k.codCtaD as codCtaDCaja ,l.nombre as nomCaja,l.nit as nitCaja, ll.nombre as nomConCaja, lll.nombre as nomConDCaja, # Caja de compensacion
cc.aporIcbf, m.codCtaC as codCtaCIcbf, m.codCtaD as codCtaDIcbf, mm.nombre as nomConIcbf, mmm.nit as nitIcbf, mmm.nombre as nomIcbf, md.nombre as nomDonIcbf ,# Icbf
cc.aporSena, n.codCtaC as codCtaCSena, n.codCtaD as codCtaDSena, nn.nombre as nomConSena, nnn.nit as nitSena, nnn.nombre as nomSena, ns.nombre as nomDSena, # Sena
cc.aporRiesgos, o.codCtaC as codCtaCArl, o.codCtaD as codCtaDArl, oo.nombre as nomArl,ooo.nit as nitArl, ooo.nombre as nomConArl,os.nombre as nomDarl,  # Riesgos laborales
r.codCtaD as codctaDs , rr.nombre as nomCtaSalud,s.codCtaD as codctaDp , ss.nombre as nomCtaPension, '' as error  
from n_planilla_unica aa 
inner join n_nomina bb on year(bb.fechaI) = aa.ano and  month(bb.fechaI) = aa.mes # Traer datos del mes en cuestion
inner join n_planilla_unica_e cc on cc.idPla = aa.id
inner join n_nomina_e a on a.idNom = bb.id and a.idEmp = cc.idEmp 
inner join n_nomina_e_d b on b.idInom = a.id 
inner join a_empleados c on c.id = a.idEmp 
inner join n_conceptos d on d.id = b.idConc 
left join n_plan_cuentas e on e.codigo = d.codCta
inner join t_fondos f on f.id = c.idFsal # --------------- Salud 
inner join t_fondos g on g.id = c.idFpen # --------------- Pension 
inner join n_pref_con h on h.id = c.idPref 
left join n_terceros i on i.id = d.idTer 
left join n_cencostos j on j.id = b.idCcos 
left join n_proviciones k on k.nombre = '7' # --------------- Caja de compensacion  
left join t_fondos l on l.id = c.idCaja 
left join n_plan_cuentas ll on ll.codigo = k.codCtaC
left join n_plan_cuentas lll on lll.codigo = k.codCtaD
left join n_proviciones m on m.nombre = '9' # Icbf
left join n_plan_cuentas mm on mm.codigo = m.codCtaC 
left join n_plan_cuentas md on md.codigo = m.codCtaD 
left join t_fondos mmm on mmm.id = 31 # Fondo del icbf  
left join n_proviciones n on n.nombre = '8' # Sena
left join n_plan_cuentas nn on nn.codigo = n.codCtaC 
left join n_plan_cuentas ns on ns.codigo = n.codCtaD 
left join t_fondos nnn on nnn.id = 32 # Fondo del Sena
left join n_proviciones o on o.nombre = '10' # Arl
left join n_plan_cuentas oo on oo.codigo = o.codCtaC 
left join n_plan_cuentas os on os.codigo = o.codCtaD 
left join t_fondos ooo on ooo.id = c.idFarp  # Riesgos profesionales 
left join n_proviciones r on r.nombre = '5' # Salud
left join n_plan_cuentas rr on rr.codigo = r.codCtaC
left join n_proviciones s on s.nombre = '6' # Pension
left join n_plan_cuentas ss on ss.codigo = s.codCtaC
where b.idConc in ('15','11','21') ".$con." 
group by nomTer
order by nomCon" ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->toArray();
      return $datos;
    }


   // Integracion no sirve para nada
   public function getIntegrarPlanilla22($id, $con)
   {
      $result=$this->adapter->query("select aa.ano, aa.mes, d.nombre as nomCon, 0 as debito, 
case when b.devengado > 0 then sum(b.devengado) else sum(b.deducido) end as credito, 
e.nombre as nomCta ,  
case when e.ter = 0 then ' ' else  # No maneja tercero # Nit ------------------------------------------------------
  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer > 0 ) then  # Maneja el tercero preasignado
      i.codigo   else 
         case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 1 ) then  # Maneja el tercero del fondo de salud
           f.nit  else 
              case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 2 ) then  # Maneja el tercero del fondo de pension
                g.nit  else  
                  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer = 0 ) then  # Maneja el nit del empleado
                      c.CedEmp  end 
         end       
      end
  end 
 end as nitTer ,
 case when e.ter = 0 then ' ' else  # No maneja tercero # Nombre ------------------------------------------------------
  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer > 0 ) then  # Maneja el tercero preasignado
      i.nombre   else 
         case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 1 ) then  # Maneja el tercero del fondo de salud
           f.nombre  else 
              case when ( e.ter = 1 and d.nitFon = 1 and d.fondo = 2 ) then  # Maneja el tercero del fondo de pension
                g.nombre  else  
                  case when ( e.ter = 1 and d.nitFon = 0 and d.idTer = 0 ) then  # Maneja el nit del empleado
                      c.nombre  end 
         end       
      end
  end 
 end as nomTer ,
 d.codCta as coCtaCredito, 
f.nit as nitSal, 
substring(j.nombre,1,20) as nomCcos, j.codigo as codCcos ,
case when b.idConc = '15' then cc.aporSalud else # Salud
  case when b.idConc = '11' then cc.aporPension else # Pension 
    case when b.idConc = '21' then cc.aporSolidaridad else # Solidaridad
    0 end  
  end   
end as valPlanilla, 
cc.aporCaja, k.codCtaC as codCtaCCaja, k.codCtaD as codCtaDCaja ,l.nombre as nomCaja,l.nit as nitCaja, ll.nombre as nomConCaja, lll.nombre as nomConDCaja, # Caja de compensacion
cc.aporIcbf, m.codCtaC as codCtaCIcbf, m.codCtaD as codCtaDIcbf, mm.nombre as nomConIcbf, mmm.nit as nitIcbf, mmm.nombre as nomIcbf, md.nombre as nomDonIcbf ,# Icbf
cc.aporSena, n.codCtaC as codCtaCSena, n.codCtaD as codCtaDSena, nn.nombre as nomConSena, nnn.nit as nitSena, nnn.nombre as nomSena, ns.nombre as nomDSena, # Sena
cc.aporRiesgos, o.codCtaC as codCtaCArl, o.codCtaD as codCtaDArl, oo.nombre as nomArl,ooo.nit as nitArl, ooo.nombre as nomConArl,os.nombre as nomDarl,  # Riesgos laborales
r.codCtaD as codctaDs , rr.nombre as nomCtaSalud,s.codCtaD as codctaDp , ss.nombre as nomCtaPension, '' as error  
from n_planilla_unica aa 
inner join n_nomina bb on year(bb.fechaI) = aa.ano and  month(bb.fechaI) = aa.mes # Traer datos del mes en cuestion
inner join n_planilla_unica_e cc on cc.idPla = aa.id
inner join n_nomina_e a on a.idNom = bb.id and a.idEmp = cc.idEmp 
inner join n_nomina_e_d b on b.idInom = a.id 
inner join a_empleados c on c.id = a.idEmp 
inner join n_conceptos d on d.id = b.idConc 
left join n_plan_cuentas e on e.codigo = d.codCta
inner join t_fondos f on f.id = c.idFsal # --------------- Salud 
inner join t_fondos g on g.id = c.idFpen # --------------- Pension 
inner join n_pref_con h on h.id = c.idPref 
left join n_terceros i on i.id = d.idTer 
left join n_cencostos j on j.id = b.idCcos 
left join n_proviciones k on k.nombre = '7' # --------------- Caja de compensacion  
left join t_fondos l on l.id = c.idCaja 
left join n_plan_cuentas ll on ll.codigo = k.codCtaC
left join n_plan_cuentas lll on lll.codigo = k.codCtaD
left join n_proviciones m on m.nombre = '9' # Icbf
left join n_plan_cuentas mm on mm.codigo = m.codCtaC 
left join n_plan_cuentas md on md.codigo = m.codCtaD 
left join t_fondos mmm on mmm.id = 31 # Fondo del icbf  
left join n_proviciones n on n.nombre = '8' # Sena
left join n_plan_cuentas nn on nn.codigo = n.codCtaC 
left join n_plan_cuentas ns on ns.codigo = n.codCtaD 
left join t_fondos nnn on nnn.id = 32 # Fondo del Sena
left join n_proviciones o on o.nombre = '10' # Arl
left join n_plan_cuentas oo on oo.codigo = o.codCtaC 
left join n_plan_cuentas os on os.codigo = o.codCtaD 
left join t_fondos ooo on ooo.id = c.idFarp  # Riesgos profesionales 
left join n_proviciones r on r.nombre = '5' # Salud
left join n_plan_cuentas rr on rr.codigo = r.codCtaC
left join n_proviciones s on s.nombre = '6' # Pension
left join n_plan_cuentas ss on ss.codigo = s.codCtaC
where b.idConc in ('15','11','21') ".$con." 
group by nomTer
order by nomCon" ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->toArray();
      return $datos;
    }


   // Integracion nomina paso 
   public function getIntegraNominaPaso($idNom)
   {
      # Agrupamiento de nomina por pagar 
      $agrupar = 0;
      $conAgr = ', e.idEmp ';
      if ( $agrupar == 1 )
           $conAgr = '  ';

      $result=$this->adapter->query("select * from ( 
           select 0 as cedula ,a.idInom, concat( year(now()) , '-', month(now())  , '-', day(now())   ) as fecha,
            b.fechaI, b.fechaF, a.codCta, 
               case when a.natCta = 'Debito' then round(a.valor,0) else 0 end as debito,   
               case when a.natCta = 'Credito' then round(a.valor,0) else 0 end as credito,
               case when a.dv = ' ' then a.nit  
                    else concat( a.nit,'-' ,a.dv ) end as nit, a.codCcos as idCcos,
          concat( g.nombre , '- (', b.fechaI, '-' ,b.fechaF , ')' )  as detalle,  
                 c.nombre as origen, '0' as formaPago,
                        case when a.embargo>1 then 'EMBARGO' else  
                 case when f.tercero=1 then 'TERCERO' else '0' end end as tercero, 
              case when a.embargo>1 then a.nitEmb else  
                      case when f.tercero=1 then (case when f.codCta = '999999000000' then '0' else concat( a.nitTer, '-', h.dv ) end ) 
                else  '0'  end end as nitTer, 
                case when a.embargo>1 then 'EMBARGO' else '' end as embargo, a.nitEmb as nitEmb, b.id as idNom,
                a.idCpres , a.idIinom, j.fecDoc, case when j.docRef!='' then j.docRef else 'SIN CODIGO' end as docRef, e.cxCxp,  concat( ltrim(dd.nombre) , '' , ltrim(dd.apellido) ) as empleado , dd.DirEmp as direccion , dd.CedEmp                                                                         
               from n_nomina_e_d_integrar a 
                  inner join n_nomina b on b.id = a.idNom 
                  inner join n_tip_nom c on c.id = b.idTnom 
                  inner join n_cencostos d on d.id = a.idCcos 
                  inner join n_plan_cuentas e on e.codigo = a.codCta 
                  inner join n_conceptos f on f.id = a.idCon 
                  inner join n_grupos g on g.id = b.idGrupo 
                  left join n_prestamos_tn i on i.id = a.idCpres 
                  left join n_prestamos j on j.id = i.idPres                   
                  left join n_terceros h on h.codigo = a.nitTer 
                  left join n_nomina_e ee on ee.id = a.idInom 
                  left join a_empleados dd on dd.id = ee.idEmp 
              where a.idNom = ".$idNom."                    
   union all  
         select d.CedEmp as cedula, a.idInom,concat( year(now()) , '-', month(now())  , '-', day(now())   ) as fecha, b.fechaI, b.fechaF, a.codCta, 
               0 as debito, round(sum(a.valor),0) as credito,
               d.CedEmp as nit, 0 as idCcos, concat( g.nombre , '- (', b.fechaI, '-' ,b.fechaF , ')' )  as detalle,  
                 c.nombre as origen, case when d.formaPago = 1 
                                    then concat('TRANNSFERENCIA_', ltrim(h.nombre)) else 
                                       case when d.formaPago = 2 then  
                                          'CHEQUE'
                                       else
                                          'EFECTIVO'
                                       end 
                                    end as formaPago, '0'  as tercero , 
                              case when d.formaPago = 2 then 
                        d.cedEmp 
                     else d.CedEmp end as nitTer , 
                                    ''  as embargo, '0' as nitEmb, b.id as idNom,
                  0 as idCpres, 0 as idIinom, '' as fecDoc, '' as docRef, 0 as cxCxp,  concat( ltrim(d.nombre) , '' , ltrim(d.apellido) ) as empleado , d.DirEmp as direccion, d.CedEmp                                   
               from n_nomina_e_d_integrar_pagar a 
                  inner join n_nomina b on b.id = a.idNom
                  inner join n_nomina_e e on e.id = a.idInom  
                  inner join n_tip_nom c on c.id = b.idTnom 
                  inner join a_empleados d on d.id = e.idEmp 
                  inner join n_grupos g on g.id = b.idGrupo  
                  left join n_bancos h on h.id = d.idBancoPlano                   
              where a.idNom = ".$idNom."      
                group by a.idNom ".$conAgr."                
               ) as a order by idInom, debito, credito" ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->toArray();
      return $datos;
    }

   // Consulta integracion provisiones paso 
   public function getConsIntegrarProviciones($id)
   {
      $result=$this->adapter->query("select * from ( 
Select d.id as idNom, d.fechaI, d.fechaF, a.nombre as nomCon, a.codCtaD as codCta, round(a.valor,0) as debito, 0 as credito, 
i.nombre as nomCta, a.nitTerD as nit, c.codigo as idCcos, substring( ltrim(c.nombre), 1, 30) as nomCcos
, b.CedEmp, b.nombre as nomEmp, b.apellido, '  ' as error , e.nombre as nomGrupo       
                  from n_provisiones_integrar_p a
                        left join n_plan_cuentas i on i.codigo = a.codCtaD # Cuenta debito                         
                        left join a_empleados b on b.id = a.idEmp 
                        left join n_cencostos c on c.id = b.idCcos
                        left join n_nomina d on d.id = a.idNom 
                        left join n_grupos e on e.id = d.idGrupo 
   where d.estado = 2 and d.idTnom in (1,5, 8) and d.id = ".$id."   
union all
Select d.id as idNom, d.fechaI, d.fechaF, a.nombre as nomCon, a.codCtaC as codCta, 0 as debito, round(a.valor,0) as credito, 
i.nombre as nomCta, case when i.ter = 1 then b.CedEmp else '' end as nit, ## Se coloco porque debe ser el nit del empleado siempre 
 '' as idCcos, substring( ltrim(c.nombre), 1, 30) as nomCcos 
, b.CedEmp, b.nombre as nomEmp, b.apellido, '  ' as error , e.nombre as nomGrupo    
                        from n_provisiones_integrar_p a
                        left join n_plan_cuentas i on i.codigo = a.codCtaD # Cuenta debito                         
                        left join a_empleados b on b.id = a.idEmp 
                        left join n_cencostos c on c.id = b.idCcos 
                        left join n_nomina d on d.id = a.idNom 
                        left join n_grupos e on e.id = d.idGrupo 
where d.estado = 2 and d.idTnom in (1,5, 8) and d.id = ".$id."  
) as a 
order by idNom, CedEmp, nomCon, credito, debito" ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->toArray();
      return $datos;
    }    
}
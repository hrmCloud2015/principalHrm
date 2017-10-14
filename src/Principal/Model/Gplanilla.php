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
use Principal\Model\AlbumTable; // Parametros de nomina
 
/// INDICE

// Generacion de empleados 

class Gplanilla extends AbstractTableGateway
{
   protected $table  = '';
   
   
   public $dbAdapter;
   public $salarioMinimo;
   public $horasDias;
    
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
   }   
   // Generacion de empleados 
   public function modGeneral($con)
   {
      $result=$this->adapter->query($con,Adapter::QUERY_MODE_EXECUTE);

    }                  
   // Generacion de empleados
   public function getNominaE($id,$idg)
   {
      $result=$this->adapter->query("insert into n_planilla_unica_e (idPla, idEmp, sueldo, pensionado, diasRetVaca, valorRetVaca, aprendiz, codSuc ) 
       (select distinct b.id , a.id, c.sueldo , a.pensionado,
( select datediff( g.fechaF , concat( b.ano ,'-', b.mes ,'-01' )  ) + 1 
     from n_vacaciones g 
        where month(g.fechaF) = month(d.fechaI) and g.estado = 2 and g.idEmp = c.idEmp 
          and (year( g.fechaI ) = b.ano ) and (month( g.fechaI ) < b.mes ) 
or ( (year( g.fechaI)=(b.ano-1) ) and ( (year(g.fechaF)=b.ano) and month(g.fechaF)=1 ) and g.idEmp = c.idEmp  )        
       )  as diasRetVaca, 
( select ( g.valor / ( g.dias + g.diasNh ) ) * 
                  ( datediff( g.fechaF , concat( b.ano ,'-', b.mes ,'-01' ) ) + 1 ) 
     from n_vacaciones g 
        where  month(g.fechaF) = month(d.fechaI) and g.estado = 2 and g.idEmp = c.idEmp 
        and (year( g.fechaI ) = b.ano ) and (month( g.fechaI ) < b.mes )
      or ( (year( g.fechaI)=(b.ano-1) ) and ( (year(g.fechaF)=b.ano) and month(g.fechaF)=1 ) and g.idEmp = c.idEmp  ) 
      
        )  as valorRetVaca, 
       g.tipo, case when i.codigo is null then '' else i.codigo end as codSuc   
         from a_empleados a 
      inner join n_planilla_unica b on b.id = ".$id." 
         inner join n_nomina_e c on c.idEmp = a.id 
         inner join n_nomina d on d.id = c.idNom and year(d.fechaI) = b.ano and month(d.fechaI) = b.mes 
         left join n_tip_calendario_d e on e.idCal = d.idCal 
         left join n_tip_calendario_p f on f.id = e.idCal 
         left join n_tipemp g on g.id = a.idTemp        
         left join n_sucursal_e h on h.idEmp = a.id
         left join n_sucursal i on i.id = h.idSuc               
      WHERE g.tipo in (0,1)  and not exists (SELECT null from n_planilla_unica_e where a.id=idEmp and idPla=".$id." )" 
         . "  )",Adapter::QUERY_MODE_EXECUTE);
    }
 
   // Modificacion planilla unida por empleado
   public function getPlanillaE($id, $campo, $valor )
   {
                   //if ($campo =='nVaca')
                      //echo  $id.' - '.$campo.' :'.$valor.' <br /> ' ;    
      $result=$this->adapter->query("update n_planilla_unica_e set ".$campo." = ".$valor." where id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
   }    
}
?>
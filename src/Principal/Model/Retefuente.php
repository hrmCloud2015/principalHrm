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

// Generacion de empleados 


class Retefuente extends AbstractTableGateway
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
   // 1. Retencion en la fuente
   public function getReteConc($id, $idEmp)
   {
      // DEFINICION DEL CONCEPTO DE RETENCION EN LA FUENTE PARA EJECUION DEL PERIODO    
      $result=$this->adapter->query("select * from n_conceptos a where a.id = 10" ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();          
      $perAuto = $datos['perAuto']; // 0: se ejecuta por cada pago de nomina, 1: Ejecuta solo en la primera nomina, 2: ejecuta en la seguna mas la primera nomina 
      
      // DATOS DE LA NOMINA
      $result=$this->adapter->query("select distinct year(b.fechaI) as ano, month(b.fechaI) as mes, a.idNom, b.idCal,
                                     month(b.fechaF) as mesF # Mes final con la fecha final    
                                       from n_nomina_e a 
                                          inner join n_nomina b on b.id = a.idNom 
                                           where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();          
      $ano = $datos['ano']; 
      $mes = $datos['mes'];  
      $mesF = $datos['mesF'];  # con la Fecha final de la nomina 
      $idNom = $datos['idNom'];    
      $idCal = $datos['idCal'];    

      $pn = new Paranomina($this->adapter);
      $dp = $pn->getGeneral1(10); // Funcion para traer parametros de nomina
      $uvt = $dp['valorNum'];// Uvt

      //echo "id Emplead".$idEmp.'<br />';
      $result=$this->adapter->query("select a.porcentaje, a.promSalud, a.tipo        
       from a_empleados_rete a
           where a.idEmp= ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();    
      $porcentaje = 0;
      $tipo       = 1; // Tipo de retencion  
      if ( $datos['porcentaje'] > 0 )      
      {  
          $porcentaje = $datos['porcentaje']; // porcentaje fijo por promedio año anterior 
         $tipo       = $datos['tipo']; // Tipo de retencion  
         $dp = $pn->getGeneral1(13); // Funcion para traer parametros de nomina
         $uvt = $dp['valorNum'];// Uvt proc 2 ( averiguar uvt para proc 2 )        
      }
      if ( $perAuto == 0 ) 
         $con = "a.idInom = ".$id;
      else   
         $con = " d.idEmp= ".$idEmp." and year(f.fechaI) = ".$ano." and month(f.fechaI) = ".$mes;

      if ( $idCal == 7 )  // Calendario de primas 
         $con = "a.idInom = ".$id;

      //-----         
      // 1. TOTAL DEVENGADOS ( CONCEPTOS QUE HACEN PARTE DE LA RETENCION EN LA FUENTE)
      $result=$this->adapter->query("Select case when ( sum(a.devengado) - sum(a.deducido) ) is null 
then 0 else  ( sum(a.devengado) - sum(a.deducido) ) end as valor, a.idNom   
            from  n_nomina_e_d a 
               inner join n_conceptos b on a.idConc=b.id 
               inner join n_conceptos_pr c on c.idConc=b.id 
               inner join n_nomina_e d on d.id=a.idInom
               inner join a_empleados e on e.id=d.idEmp
               inner join n_nomina f on f.id = d.idNom 
          where f.idTnom != 6 and c.idProc=8 # Todos los conceptos que hacen part de la retencion  
                 and ".$con ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $totIngresosBases = $datos['valor'];
          
     // echo 'Ingresos: '.number_format($totIngresosBases).'<br />';
    
      // 2. TOTAL EXENTAS PARA CONCEPTOS DE NOMINA ( EJEMPLO: PENSION, FONDO DE SOLIDARIDAD, ECT ) 
      $result=$this->adapter->query("Select case when (  sum(a.deducido) - sum(a.devengado) )is null then 0 else (  sum(a.deducido) - sum(a.devengado) ) end as valor  
            from  n_nomina_e_d a 
               inner join n_conceptos b on a.idConc=b.id 
               left join n_conceptos_pr c on c.idConc=b.id 
               inner join n_nomina_e d on d.id=a.idInom
               inner join a_empleados e on e.id=d.idEmp
               inner join n_nomina f on f.id = d.idNom 
          where f.idTnom != 6 and c.idProc=14 and b.id not in (11,21,15) 
                   and ".$con ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();      
      $totExentasN = 0;
      //if ($datos['valor']>0) 
         $totExentasN = $datos['valor'];  

      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totExentasN = 0;  
                // echo 'Total exentas conceptos : '.number_format($totExentasN).'<br />';
    
      // 2.1 TOTAL EXENTAS EN CONCEPTOS DE RETENCION ( EJEMPLO: AHORRO VOLUNTARIO )
      $result=$this->adapter->query("select case when sum(b.valor) is null then 0 else sum(b.valor) end as valor, a.porcentaje, a.promSalud, a.tipo        
        from a_empleados_rete a
           inner join a_empleados_rete_d b on b.idEret = a.id
           inner join n_rete_conc c on c.id = b.idCret
           where a.idEmp= ".$idEmp." and c.formula = '' and c.idGrup=2 # Solo exentas " ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $totExentasC = 0;
      if ($datos['valor']>0)
          $totExentasC = $datos['valor'];     

      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totExentasC = 0;  

      //echo 'Total exentas retefuente : '.number_format($totExentasC).'<br />';    
      $totExentas = $totExentasN + $totExentasC; 


      // 3. TOTAL SALUD ESPECIAL PARA 384  
      $result=$this->adapter->query("Select case when (  sum(a.deducido) - sum(a.devengado) ) is null then 0 else (  sum(a.deducido) - sum(a.devengado) ) end  as valor  
            from  n_nomina_e_d a 
               inner join n_conceptos b on a.idConc=b.id 
               left join n_conceptos_pr c on c.idConc=b.id 
               inner join n_nomina_e d on d.id=a.idInom
               inner join a_empleados e on e.id=d.idEmp
               inner join n_nomina f on f.id = d.idNom 
        where f.idTnom != 6 and a.idConc in ('15') and ".$con ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $totSal = $datos['valor'];
      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totSal = 0;  

      $promSalud = $totSal;
      //echo 'Total salud : '.number_format($totSal).'<br />';
    
      // 3. TOTAL PENSION ESPECIAL PARA 384  
      $result=$this->adapter->query("Select case when (  sum(a.deducido) - sum(a.devengado) ) is null then 0 else (  sum(a.deducido) - sum(a.devengado) ) end  as valor  
            from  n_nomina_e_d a 
               inner join n_conceptos b on a.idConc=b.id 
               left join n_conceptos_pr c on c.idConc=b.id 
               inner join n_nomina_e d on d.id=a.idInom
               inner join a_empleados e on e.id=d.idEmp
               inner join n_nomina f on f.id = d.idNom 
        where f.idTnom != 6 and a.idConc in ('11') and ".$con ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();      
      $totPen = 0;            
      if ( $datos['valor']>0 )
         $totPen = $datos['valor'];            

      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totPen = 0;  

      //echo 'Total pension : '.number_format($totPen).'<br />';

      //Total solidaridad
      $result=$this->adapter->query("Select case when ( sum(a.deducido) - sum(a.devengado) ) is null  
  then  0 else ( sum(a.deducido) - sum(a.devengado) )  end
as valor  
            from  n_nomina_e_d a 
               inner join n_conceptos b on a.idConc=b.id 
               left join n_conceptos_pr c on c.idConc=b.id 
               inner join n_nomina_e d on d.id=a.idInom
               inner join a_empleados e on e.id=d.idEmp
               inner join n_nomina f on f.id = d.idNom 
        where f.idTnom != 6 and a.idConc in ('21') and ".$con ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $totSol = $datos['valor'];            
      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totSol = 0;     
    
      // 3. TOTAL DEDUCCIONES FIJAS  
      $result=$this->adapter->query("select 
        case when sum(b.valor) is null then 0 else sum(b.valor) end as valor, a.porcentaje, a.promSalud, a.tipo, c.tope         
       from a_empleados_rete a
           left join a_empleados_rete_d b on b.idEret = a.id
           left join n_rete_conc c on c.id = b.idCret
           where a.idEmp= ".$idEmp." and c.id != 4 # No se tiene en cuenta salud obligatoria 
              and c.formula = '' and c.idGrup=1 # Solo deducciones " ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $totDeducciones = 0;
      if ( $datos['valor'] > 0 )
         $totDeducciones = $datos['valor'];

      if ( $totDeducciones > ( $uvt * $datos['tope'] ) )
           $totDeducciones = ( $uvt * $datos['tope'] );
      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totDeducciones = 0;     

      // 3. TOTAL DEDUCCIONES FIJAS  SALUDO OBLIGATORIA PARA REEMPLAZO 
      $result=$this->adapter->query("select 
        case when sum(b.valor) is null then 0 else sum(b.valor) end as valor  
       from a_empleados_rete a
           left join a_empleados_rete_d b on b.idEret = a.id
           left join n_rete_conc c on c.id = b.idCret
           where a.idEmp= ".$idEmp." and c.id = 4 # Tiene en cuenta salud obligatoria 
              and c.formula = '' and c.idGrup=1 # Solo deducciones " ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      if ( $datos['valor'] > 0 )
      {  
         $promSalud = $datos['valor'];
         $totSal = $datos['valor'];
      }   
      
//echo 'Total deduccines no formuladas : '.number_format( $totDeducciones ).'<br />';    
      // 3.1 TOTAL DEDUCCIONES FORMULADAS ( EJEMPLO DEPENDIENTES ) 
      $result=$this->adapter->query("select c.formula, c.tope      
       from a_empleados_rete a
           inner join a_empleados_rete_d b on b.idEret = a.id
           inner join n_rete_conc c on c.id = b.idCret
           where a.idEmp= ".$idEmp." and c.formula != ''" ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->toArray();
      $totDeduccionesF = 0;
      foreach($datos as $dat)
      {
        $formula = $dat['formula'];
        eval("\$totDeduccionesF = $formula;");           
        // Valdiacion UVT 
        if ( $totDeduccionesF > ( $uvt * $dat['tope'] ) )
            $totDeduccionesF = ( $uvt * $dat['tope'] );
      }   
      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totDeduccionesF = 0;     

      //echo 'Total deduccines formuladas : '.number_format( $totDeduccionesF ).'<br />';    
     
      $totDeducciones = $totDeducciones + $totDeduccionesF; // Sumo las deducciones con formulas y las que no tiene formula         

      if ( ($idCal == 77) and ( $tipo ==11  ) )  // Calendario de primas solo excantas 
           $totDeducciones = 0;     

      //echo 'Total deducciones G: '.number_format($totDeducciones).'<br />';
      //echo 'Porcenta: '.number_format($porcentaje).'<br />';
    
      $red = 3; // redondeo de decimanles
    
      // Calulos de pie
      $baseGravableBruta = $totIngresosBases - ($totExentas+$totPen+$totSol) -  ( $totDeducciones + $totSal ) ;
//      $baseGravableBruta = $totIngresosBases - $totExentas -  ( $totDeducciones ) ;    
      //echo 'baseGravableBruta:  '.number_format($baseGravableBruta, $red).'<br />';   
                  
      // Validar si el 25% excede 240 UVT
      $rentaExenta = $baseGravableBruta * ( 25/100 );    
            
      if ( $rentaExenta > ( $uvt * 240 ) ) // Si el valor esta por debajo de los UVT toma el limite de UTV
          $rentaExenta = ($uvt * 240);
    
      //$rentaExenta = ceil( $rentaExenta );
      $rentaExenta = round( $rentaExenta, 0 );
      $lon = strlen( $rentaExenta );    
      // Validar paa subir unidades
      $sumar = 0;
      if ( substr( $rentaExenta,$lon-3, 1) > 5 ) // Mayor a 5 pasa a la sieguiente unidad
         $sumar = 1;

      $rentaExenta = ((substr( $rentaExenta,0, $lon-3))+$sumar).'000' ;      
      $rentaExenta = $rentaExenta ;    
      //echo substr( $rentaExenta,0, $lon-3).'<br />';
      //echo substr( $rentaExenta,$lon-3, 1).'<br />';
      //echo 'rentaExenta 25%:  '.number_format( $rentaExenta ).'<br />';
    
      // 4. CALCULOS PARA CALCULO DE LA RENTECION PARA 383 ---------------------------------------------------------
      $baseGravableNeta    = $baseGravableBruta - $rentaExenta;
      $baseGravableNeta383 = $baseGravableBruta - $rentaExenta;
      //echo 'baseGravableNeta 383: '.number_format($baseGravableNeta383,$red).'<br />';
      //echo '-------------------------------------------------------------------------<br />';   

      //******************************************************--------------------------------------
      // HASTA AQUI $baseGravableNeta383 ES IGUAL PARA LOS DOS PROCEDIMIENTOS 1 O 2 ---------------------------------
      //******************************************************--------------------------------------         
    
      $reteArt383 = 0;        
      // 
      if ($tipo==1) // Proc 1  
      {
          $valEnUvt = $baseGravableNeta / $uvt;
        //  echo '--Proc 1 valor uvt: '.$valEnUvt.'<br />';  
          $result=$this->adapter->query("select 
                        case when ( ".$valEnUvt." >= desde and ".$valEnUvt." < hasta )  then 
                            ( ( ( ".$valEnUvt." ) - restarUvt ) * ( impuesto / 100) ) + sumarUvt  
                        end as por, 
                        case when ( ".$valEnUvt." >= desde and ".$valEnUvt." < hasta )  then 
                            ( ( ( ( ".$valEnUvt." ) - restarUvt ) * ( impuesto / 100) ) + sumarUvt  ) * ".$uvt."  
                        end as valor
                    from n_rete_art383  where  ( case when ( ".$valEnUvt." >= desde and ".$valEnUvt." < hasta )  then 
                                  ( ( ( ".$valEnUvt." ) - restarUvt ) * ( impuesto / 100) ) + sumarUvt end ) > 0" ,Adapter::QUERY_MODE_EXECUTE);  
          $datos = $result->toArray();
          
          foreach($datos as $dat)
          {
               $reteArt383 = $dat['valor'] ;    
               $porcentaje = $dat['por'] ;    
          }
          //echo '--Proc 1 porcentaje : '.number_format( $porcentaje,2 ).'<br />';                    
          //echo '--Proc 1 valor : '.number_format( $reteArt383 ).'<br />';                        
      } 
                  
      // Se multiplica por el porcentaje del empleado para procedmiento 2
      if ($tipo==2) // Proc 2 
      { 
         $reteArt383 = ( $porcentaje/100 ) * $baseGravableNeta;
         //echo '--Proc 2 porcentaje : '.number_format( $porcentaje,2 ).'<br />';                   
         //echo '--Proc 2 valor : '.number_format($reteArt383, $red ).'<br />';
      }       
      //echo '-------------------------------------------------------------------------<br />';   
    
      // CALCULOS PARA CALCULO DE LA RENTECION PARA 384 RETENCION MINIMA ------------------------------------------------ proc 1 y proc 2
      $baseGravableNeta    = ( $totIngresosBases - ( $totPen + $promSalud + $totSol ) ) / $uvt ;
      $baseGravableNeta384 = ( $totIngresosBases - ( $totPen + $promSalud) ) / $uvt ;
        
      //echo 'baseGravableNeta 384: '.number_format($baseGravableNeta,$red).'<br />';
          
      $result=$this->adapter->query("select impuesto from n_rete_art384
                        where ".$baseGravableNeta." >= desde and ".$baseGravableNeta." <= hasta" ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $reteArt384 = $datos['impuesto'] * $uvt ; 
    
      //echo 'reteArt384:  '.number_format($reteArt384,$red).'<br />';

      //echo '--------------------------------------------------';

      // Retornar valor final - proc 1
         
      $valorProce1 = 0;
      $valorProce2 = 0;
      // Validacion de retefuente 384 eliminada e el 2017----------------
      //if ( $reteArt384 > $reteArt383 )
      //   $valorProce = $reteArt384;
      //else 
      //   $valorProce = $reteArt383;
      // ------------++++++++++++++----------------------------------------
      $reteArt384 = 0;
      $valorProce = $reteArt383;        

      if ($tipo==1)
         $valorProce1 = $valorProce ;
      else
         $valorProce2 = $valorProce;

$sw = 0;
if ( $sw == 1 )
 { 
echo '***---------------------------------------<br />';    
echo $totIngresosBases.'--1<br />';
echo $totExentasN.'--2<br />';
echo $totExentasC.'--3<br />';
echo $totPen.'--4<br />';
echo $totDeducciones.'--5<br />';
echo $totDeduccionesF.'--6<br />';
echo $porcentaje.'--7<br />';
echo $baseGravableBruta.'--8<br />';
echo $rentaExenta.'--9<br />';
echo $baseGravableNeta383.'--10<br />';
echo $baseGravableNeta384.'--11<br />';
echo $reteArt383.'--12<br />';
echo $reteArt384.'--13<br />';
echo $valorProce2.'--14<br />';
echo $valorProce1.'--15<br />';
echo $uvt.'--16<br />';
echo $totSal.'--17<br />';
echo $totSol.'--18<br />';
echo 'FIn ***---------------------------------------<br />';    
echo $idNom.", ".$idEmp;
}
      // DATOS DE ALIVIOS TRIBUTARIOS
      // Valor intereses 
      $intViv = 0;
      $result=$this->adapter->query("select sum(b.valor) as valor 
                from a_empleados_rete a
                   left join a_empleados_rete_d b on b.idEret = a.id
                   left join n_rete_conc c on c.id = b.idCret
                where a.idEmp = ".$idEmp." and c.id = 2" ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      if ( $datos['valor'] > 0 )
           $intViv = $datos['valor'];            
      
          
    if ( ($valorProce1>0) or ($valorProce2>0) )
    {  
      // Guardar valores de la retencion en la fuente
      $result=$this->adapter->query("insert into n_nomina_e_rete (idNom, idEmp, tipo, vlrIngresos, vlrExentasN, vlrExentasC, vlrPension, vlrTotalDed, 
             vlrDedForm , porcentaje, baseGravableBruta, rentaExenta, baseGravableNeta_383, baseGravableNeta_384, 
             vlrRete383, vlrRete384, vlrProc2, vlrProc1, uvtActual, vlrSalud, vlrSolidaridad, VlrIntViv, vlrOtrEx )
          values(".$idNom.", ".$idEmp.", ".$tipo.", ".$totIngresosBases.", ".$totExentasN.", ".$totExentasC.", ".$totPen.",
             ".$totDeducciones.", ".$totDeduccionesF.", ".$porcentaje.", ".$baseGravableBruta.", ".$rentaExenta.", ".$baseGravableNeta383.", 
             ".$baseGravableNeta384.",".$reteArt383.",".$reteArt384.", ".$valorProce2.",".$valorProce1.", ".$uvt.", ".$totSal.", ".$totSol." ,".$intViv.", ".$totExentasN." )" ,Adapter::QUERY_MODE_EXECUTE);      
    }  
    // Fin -----
 
    // Validar periodo de nomina 
    $result=$this->adapter->query("select  c.periodo  
             from n_nomina a
               inner join n_tip_calendario_d b on b.fechaI=a.fechaI and b.fechaF = a.fechaF
               inner join n_tip_calendario_p c on month(a.fechaI) = c.mesI and day(a.fechaI) = c.diaI 
                           and month(a.fechaF) = c.mesF and day(a.fechaF) = c.diaF                
               where a.id = ".$idNom ,Adapter::QUERY_MODE_EXECUTE);  
      $datos = $result->current();
      $periodo = $datos['periodo'];            
    
    $sw = 0;
    if ( $perAuto == 2 ) // Esta restringido para ejecutar en el segundo periodo
    {
       if ( ($periodo == 1) or ($periodo == 0 )  )
          $sw = 1;
    }
       

 //   if ( $sw==0 )
       return $valorProce;
  //  else
    //   return 0 ;

  }// FIN PROCEDIMENTOS DE RETEFUENTE

}
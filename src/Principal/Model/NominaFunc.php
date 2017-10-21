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
use Principal\Model\Pgenerales; // Parametros generales
use Principal\Model\Paranomina; // Parametros de nomina
use Principal\Model\AlbumTable; // Libro de consultas
use Principal\Model\Gnominag; // Generacion de nomina

use Principal\Model\Retefuente; // Retefuente
use Nomina\Model\Entity\Cesantias; // Cesantias

/// INDICE

//// FUNCIONES BASICAS ------------------------------------------
// 0. FUNCION GENERAL PARA CALCULOS EN NOMINA
// 01. VALOR DE FORMULAS
// 1. Sueldo empleado
// 2. Valor hora empleado
// 3. Valor dia empleado
// 4. Sumatoria procesos Ley 100
// 5. Solidaridad
// 6. Sumatoria procesos subsidios
// 7. Devolver dia de inicio calendario1
// 8. Dias laborado por empleado
// 9. Dias habiles o no habiles 
// 10.Subsidio de transporte
// 11. Sumatoria procesos cesantias
// 12. Recalculo de documento de nomina
// 13. Sumaoria Devengados deducidos y totales a pagar 
// 14. Dias por retorno de vacacioens
// 15. Creacion de periodos de vacacioens
// 17. Dias laborados por contrato empleado
// 18. Salario integral 0 o 1 
     
//// FUNCIONES GENERALES ----------------------------------------
class NominaFunc extends AbstractTableGateway
{
   protected $table  = '';   
      
   public $dbAdapter;
   public $salarioMinimo;
   public $horasDias;
   public $salarioMinimoCovencional;
   public $subsidioTransporte;

   public function __construct(Adapter $adapter)
   {
        $this->adapter = $adapter;
        $this->initialize();
        // Parametros de nomina para funciones de consulta 
        $pn = new Paranomina($this->adapter);
        $dp = $pn->getGeneral1(1);
        $this->salarioMinimo=$dp['valorNum'];// Salario minimo

        $dp = $pn->getGeneral1(2);
        $this->horasDias=$dp['valorNum'];// Horas dia de trabajo

        $dp = $pn->getGeneral1(3);
        $this->salarioMinimoCovencional=$dp['formula'];// Salario minimo convencional   

        $dp = $pn->getGeneral1(11);
        $this->subsidioTransporte=$dp['valorNum']; // Subsidio de trasnporte    
   }
   // 0. FUNCION GENERAL PARA CALCULOS EN NOMINA
   public function getNomina($idn, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos ,
                             $idCon, $ac, $tipn,$dev,$ded,$idfor,$diasLabC,$idCpres,$calc,$conVac,$obId, $fechaEje, $idProy )
   {       
       // $ac      Insertar o modificar novedad ( 0: nuevo  1:modificar )
       // $idn     Id de la nomina
       // $iddn    Id dcumento de novedad (n_nomina_e)
       // $idin    Id novedad             (n_nomina_e_d)
       // $ide     Id del empleado
       // $diasLab Dias laborados 
       // $diasVac Dias vacaciones
       // $horas   Horas laborados o valores
       // $formula Formula
       // $tipo    Devengado o Deducido  
       // $idCcos  id del Centro de costo   
       // $idCon   id del Concepto
       // $ac      accion 
       // $tipn    Tipo dde novedad ( 1: tipos auto, 2:otros automa, 3:calculados )
       // $dev     Devengados de consulta
       // $ded     Deducidos de consulta
       // idfor   Ide de la formula, importante para determinar si es calculada o MANUAL
       // $diasLabC Dias laborados, solo para calculados
       // $idCpres Id de la cuota de un prestamo 
       // $calc   Define si se calcula o se deja el concepto con los valoes que viene
       // $conVac Si es 1 el concepto se recalcula con dias calendario en vacaciones para pagar o descontar
       // $obId   Obtener id
       // $fechaEje Fecha de ejecucion de la novedad
       // $idProy id del proyecto

     $t = new AlbumTable($this->adapter);  
     $g = new Gnominag($this->adapter);  
   //  $datAus = $t->getGeneral1("select count(id) as sw 
     //                             from n_nomina_e where dias=0 and aus=1 and id = ".$iddn); // Verificar si esta asutente totalmente 
    // if ($datAus['sw']==0) // Valdiacion importante para empleados ausentes
	  // {
       $saldoPact=0; // Variable para saldos de prestamos
       if ($calc==0) // Debe ser calculado el concepto , si es 1 ya fue calculado
       {    
            // Funcion de formula 
            //if ( ($dev==0) and ($ded==0) )
            //{
                $datFor = $this->getFormula($formula,$idfor,$tipo,$horas,$ide,$iddn,$diasLab, $diasVac,$idCon, $fechaEje,  $idProy);// Funcion de formulas
                if ($datFor['dev']>0)
                    $dev = $datFor['dev'];
                if ($datFor['ded']>0)
                   $ded = $datFor['ded'];
                $diferencia = $datFor['diferencia']; // Valor cuando una validacion en la formula es verdader
           // }
                //if ($ide == 2803) 
                   //echo $idCon.' '.$dev.'ff <br /> ';                      
            //  if ( ( $horas > 0 )  ) // Si hay horas se calcula 
            //  {
            //    $datFor = $this->getFormula($formula,$idfor,$tipo,$horas,$ide,$iddn,$diasLab, $diasVac,$idCon);// Funcion de formulas
            //    if ($datFor['dev']>0)
            //       $dev = $datFor['dev'];
            //    if ($datFor['ded']>0)
            //       $ded = $datFor['ded'];
            //  }            
            //} 
            // CONCEPTOS EN AUTOMATICOS QUE SE CALCULEN NUEVAMENTE CON DIAS CALENDARIO PARA PAGARLOS DURANTE LAS VACACIONES
            if ( ($conVac>0) and ($diasVac>0) )
            {
                $datFor = $this->getFormula($formula,$idfor,$tipo,$horas,$ide,$iddn,15, 0,$idCon, $fechaEje, $idProy);// Funcion de formulas
                if ($datFor['dev']>0)
                    $dev = $dev + $datFor['dev'];
                if ($datFor['ded']>0)
                   $ded = $ded + $datFor['ded'];

                $diferencia = $datFor['diferencia'];                                  
            }            
            
       }else{

           if ($idCpres>0) // Se Valida el saldo del prestamo realizado
           {
             // BUscar saldo actual del prestamo
             //$datNom  = $t->getGeneral1("select idTnom from n_nomina where id = ".$idn);
             $datIpres = $t->getGeneral1("select idPres from n_prestamos_tn where id = ".$idCpres);
             // Cuotas fijas y abonos  
             $datPres = $t->getGeneral1("select sum( a.valor-( a.pagado + a.saldoIni ) ) # Prestamos por cuotas
                                          as saldoAct   
                                             from n_prestamos_tn a 
                                                 inner join n_prestamos b on b.id = a.idPres 
                                                     where a.idPres = ".$datIpres['idPres']);
             // Cuotas programadas y abonos
             $datProg = $t->getGeneral1("select case when sum( d.valor - d.pagado ) is null 
                                            then 0 else sum( d.valor - d.pagado ) end # Abonos extraordinarios 
                                          as saldoAct  
                                             from n_prestamos_tn a 
                                                 inner join n_prestamos b on b.id = a.idPres 
                                                 inner join n_prestamos_pro d on d.idPres = b.id 
                                                     where a.idPres = ".$datIpres['idPres']);                          
             // Abonos extraordinarios
             $datAbo = $t->getGeneral1("select sum(case when d.valor is null then 0 else d.valor
                                               end ) as abonoExtra # Abonos extraordinarios 
                                             from n_prestamos_tn a 
                                                 inner join n_prestamos b on b.id = a.idPres 
                                                 inner join n_abonos_presta d on d.idPres = b.id 
                                                     where a.idPres = ".$datIpres['idPres']);             
			 			 $saldoPact = ( $datPres['saldoAct'] + $datProg['saldoAct'] ) - $datAbo['abonoExtra'] ;						  
             //$saldoPact = ( $datPres['saldoAct'] )  ;                          
			       if ( $ded > $saldoPact )					 
                 $ded = $saldoPact; // Se pone el saldo actual para cobro               
           }
//if ($ide==179)
    //echo 'calc '.$calc.'valor amm '.$ded.' id C pres:'.$idCpres.' fffff <br />';                                                    
           $diferencia = 0;                                  
       }
       // ---       
       $valorOtrosIngr = 0;    
       // Buscar herencia en tablas      
       $datCon = $t->getGeneral1("select 
                                      ( select case when a.devengado>0 then sum(a.devengado) else sum(a.deducido) end 
                                      from n_nomina_e_d a 
                                         inner join n_nomina_e b on b.id = a.idInom 
                                       where a.idConc = idConcHere and a.causado = 0 and b.idEmp = ".$ide.") as valorHerencia   
                                  from n_conceptos where idConcHere>0 and id = ".$idCon);
//echo $idCon.'valor '.$datCon['valorHerencia'].'<br />';
       $valorHerencia = 0; 
       if ($datCon['valorHerencia']>0)
           $ded = $datCon['valorHerencia']; 

       if ( ($dev>0) or ($ded>0))
       {
          $dev = round($dev,0); 
          $ded = round($ded,0); 
          $detalle = '';
          $swVp  = 0; // Valdia detalle por topes superados 
          // Comportamiento del concepto con respecto a los fondos de salud y pension
          $periodoNomina = 0;
          $idFondo = 0;
          $datCon = $t->getGeneral1("select fondo, verPeriodo,perAuto, nombre 
                                        from n_conceptos where id = ".$idCon);
          $periodoConcepto = $datCon['perAuto']; 
          // Ensayo de conceptos para guardado  
                $datPer = $t->getGeneral1("Select case when a.idTnom = 5 then 0 else b.periodo end as periodo,
                                year(a.fechaF) as ano, month(a.fechaF) as mes,
                                 count(a.id) as num , a.idTnom    
                                from n_nomina a 
                                    inner join n_tip_calendario_p b on substr(a.fechaI,6,2) = b.mesI and day(a.fechaI)=b.diaI 
                                 where a.idTnom in (1,4,5,6) and a.id = ".$idn);
          $idTnom = $datPer['idTnom'];  
          $periodoNomina = 0; 
          $swV = 0;
          if ( $datPer['num']>0 )    
          {            
              $periodoNomina = $datPer['periodo']; 
              $swV = 1;
          }    
          // Nomina manual 
          if ($idTnom==4) // Se activa seguridad social de la nomina
          {            
              $periodoNomina = 2; 
              $swV = 1;
          }    
             //if ($ide==2702)
               // echo $periodoConcepto.'-'.$periodoNomina.' con '.$idCon.' '.$ac.'<br />';
          $valorConcAnt = 0;
          $valorTotal4 = 0;
          $valorOtrosIngr = 0;          
          $valorIBC = 0;
          if ( ($datCon['fondo']>0) and (($idCon==11) or ($idCon==15))  ) // Aplica datos de los fondos
          {  
            //echo 'Deducido 2 :'.$ded.'<br />';
            //echo 'Periodo '.$periodoNomina;
                if ($periodoNomina==2) // Si el periodo es 2, hace la sumatoria de periodos anteiores 
                {
                   // Se busca el valor de la quincena pasada 
                   $datScon = $t->getGeneral1("Select 
                     case when sum(a.deducido) is null then 0 else 
                       sum(a.deducido) end as valor  
                        from n_nomina_e_d a 
                        inner join n_conceptos b on a.idConc=b.id 
                        inner join n_nomina d on d.id = a.idNom 
                        inner join n_nomina_e e on e.idNom = d.id and e.id = a.idInom 
                        where year(d.fechaF)=".$datPer['ano']." 
                         and month(d.fechaF)=".$datPer['mes']."
                          and e.idEmp = ".$ide."  and a.idConc = ".$idCon);                        
                    $valorConcAnt = $datScon['valor']; // Valor decontado nomina anterior 

                    // Aca saco el toal ingreso base para salu y pension del mes
                    $datScon = $g->getSumaFondos($ide,$datPer['ano'],$datPer['mes']);
                    $valorTotal4 = $datScon['valor']; // Valor decontado nomina anterior
                    $valorIBC = $ñdatScon['valorBase'];// IBC 
                    $valorOtrosIngr = $datScon['otrosIngreso']; // Valor otros ingre
                     // Si es mayor a 25 salarios minimos 
                     if ( $valorTotal4 > ($this->salarioMinimo) ) //  
                     {
                        $valorTotal4 = ($this->salarioMinimo*25) * (4/100);
                     }

                     if ( $valorConcAnt > 0 )
                     {
                         $ded = ( $valorTotal4 - $valorConcAnt );
                         $swVp = 1;
                     } 

                }// FIn validacion segundo periodo para salud pension y solidaridad
                if ( ($swV >0) and ($datPer['periodo']==1) ) 
                {  
                     // Validacion salud y pension en primas 
                    $datScon = $t->getGeneral1("select sum( c.deducido ) as valor 
                                   from n_nomina a 
                                       inner join n_nomina_e b on b.idNom = a.id 
                                       inner join n_nomina_e_d c on c.idInom = b.id 
                                 where a.idTnom = 2 and b.idEmp = ".$ide." and 
                                 year(a.fechaF)= ".$datPer['ano']." 
                                   and month(a.fechaF)=".$datPer['mes']." and c.idConc=".$idCon);                       
                    if ( $datScon['valor']>0 )    
                         $valorConcAnt = $datScon['valor']; // Valor decontado nomina anterior              
                    if ($valorConcAnt>0)
                    {
                        $swVp=1;  
                        // Aca saco el toal ingreso base para salu y pension del mes
                        $datScon = $g->getSumaFondos($ide,$datPer['ano'],$datPer['mes']);
                        $valorTotal4 = $datScon['valor']; // Valor decontado nomina anterior

                        // Si es mayor a 25 salarios minimos 
                        if ( $valorTotal4 > ($this->salarioMinimo) ) //  
                        {
                           $valorTotal4 = ($this->salarioMinimo*25) * (4/100);
                        }

                        if ( $valorConcAnt > 0 )
                        {
                           $ded = ( $valorTotal4 - $valorConcAnt );
                        }
                    }// Fin sw validatorio de nominas nomrlaes     
                }//Fin validacion de salud y pensin en primas ------------------ 
                //secho 'DEDUCIDO # 3: '.$ded;
             $fondo = $datCon['fondo'];    
             // Consulta fondos del empleado
             $datEmp = $t->getGeneral1("select d.id as idFsal, d.nombre as nomSal,
             e.id as idFpen, e.nombre as nomPen,
				f.nombre as nomCes, g.nombre as nomArp, g.nombre as nomFav, h.nombre as nomFafc, a.medioTiempo  
                         from a_empleados a 
                                inner join n_cencostos c on a.idCcos=c.id 
                                inner join t_fondos d on d.id=a.idFsal
                                inner join t_fondos e on e.id=a.idFpen
                                left  join t_fondos f on f.id=a.idFces
                                left join t_fondos g on g.id=a.idFarp 
                                left join t_fondos h on h.id=a.idFav 
                                left join t_fondos i on i.id=a.idFafc where a.id=".$ide);              
             $medioTiempo = $datEmp['medioTiempo'];
             switch ($fondo) {
                 case 1:// Salud 
                     $detalle = $datEmp['nomSal'];
                     $idFondo = $datEmp['idFsal'];
                     break;
                 case 2:// Pension
                     $detalle = $datEmp['nomPen'];
                     $idFondo = $datEmp['idFpen'];                     
                     break;
                 default:
                     break;
             }       
             //if ($swVp==1)       
                 //$detalle = $detalle.' -(DESC ANT) '.number_format($valorTotal4).'-'.number_format($valorConcAnt);
             if ($valorOtrosIngr>0)
             {
                 // VALIDAR SI APLICA EL 40% PARA DESCUENTO EN SEGURIDAD SOCIAL
                 $base = ( $valorIBC + $valorOtrosIngr );
                 $valorPor  = $base * (40/100);
                 $otrIngreso  = $valorOtrosIngr - $valorPor;
                 if ( $otrIngreso > 0 ) // Si el valor es positivo toma el ingresio y sñe clcua seguridad social
                 {
                    if ( $valorConcAnt == 0 )
                         $ded= round( ( $valorIBC + $otrIngreso ) * (4/100), 0 ) ; 
                    else
                         $ded= ( round( ( $valorIBC + $otrIngreso ) * (4/100), 0 ) ) - $valorConcAnt ;    
                    $detalle = $detalle.'-*(OT INGR) '.number_format($valorOtrosIngr);
                 }else
                 {
                    $detalle = $detalle.'-(OT INGR) '.number_format($valorOtrosIngr);
                 }
             }           
             $valorTotal4 = 0;
             // Validar si es empleado de medio tiempo y la salud al minimo
             if ( ($medioTiempo == 1) and ($idTnom!=6) )
             {
                 if ( ($this->salarioMinimo/2) * (4/100) )
                 {
                    if ($ded < ( ($this->salarioMinimo/2) * (4/100) ) )
                        $ded = ( ($this->salarioMinimo/2) * (4/100) );
                 }
             } 

          }// Fin validacion datos de los fondos           

          // Guardar cambios
          if ($ac==0) // Inertar registro
          {
             // Buscar si el concepto hace parte de alguna excepcion para que no lo tenga en cuenta para el empleado
             $datExc = $t->getGeneral1("select count(id) as num
                              from n_emp_conc_exc where idEmp = ".$ide." and idCon = ".$idCon);              

             if ( $datExc['num'] == 0 )
             {
                  //if ( ($periodoConcepto==$periodoNomina) or ($periodoConcepto==0))
                  //{
                    // echo $datCon['nombre'].$periodoConcepto.' '.$periodoNomina.'<br />';
                $swG = 0;     
                if ( $periodoConcepto == 0) // Si el concepto se ejecuta en todos los periodos 
                    $swG = 0; 
                else // Validacion del periodo 
                {
                   if ($periodoNomina==0)
                       $swG = 0; 
                   else
                   {
                      if ($periodoConcepto!=$periodoNomina)
                          $swG = 1;   
                   }     
                }
                // Validar vacaciones y retefuente
                if ( ($diasVac>0) and ($idCon==10) )       
                   $swG = 0;

                if ( ($diasVac>0) and ($idCpres > 0) )  // Valida salida vacaciones y prestamos de segundo periodo para ignorar     
                   $swG = 0;                   

                if ( ($idTnom == 6) or ($idTnom == 5) )
                {  
                   $swG = 0;                  
                } 
                if ( $swG == 0 )
                {   

                    $result=$this->adapter->query("insert into n_nomina_e_d (idNom,idInom,idConc,idCcos,horas,tipo,devengado, deducido, devengadoAnt, deducidoAnt, horDias, idCpres, saldoPact, detalle, vlrDiferencia, idFondo)
                       values (".$idn.",".$iddn.",".$idCon.",".$idCcos.",".$horas.",".$tipn.",".$dev.",".$ded.",".$dev.",".$ded.",".$diasLabC.",".$idCpres.",".$saldoPact.",'".$detalle."' ,".$diferencia." , ".$idFondo.");" ,Adapter::QUERY_MODE_EXECUTE);                            
                    if ($obId == 1)
                    {
                        $id = $this->adapter->getDriver()->getLastGeneratedValue(); 
                        return $id;          
                    }else 
                        return 0;               
                }      
             }else  
                return 0;               
          }else{ // Modificar registro

            if ( ($horas==0) and ( $idCon == 122 ) )
               $dev = 0;

             $result=$this->adapter->query("Update n_nomina_e_d set idCcos=".$idCcos.",horas=".$horas.",devengado=".$dev.", 
                                         deducido=".$ded.", saldoPact=".$saldoPact." where id =".$idin  ,Adapter::QUERY_MODE_EXECUTE);                  
             return 0;               
          }
       }// Validar que valor no sea cero          
       // Si los dias laborados son 0, el edita y deja en 0 
       if ( ($diasLab==0) and ( $idCon == 122 ) )
       {
//
       } 
     //}// Validar ausentismo total del empleado  
   }                
   // 01. VALOR DE FORMULAS
   public function getFormula($formula,$idfor,$tipo,$horas,$ide,$iddn,$diasLab, $diasVac,$idCon,$fechaEje,$idProy)
   {       
       ini_set('max_execution_time', 1200); // 5 minutos pro procesamiento ( si Safe mode en php.ini esta desabilitado funciona )            
       $cadena=ltrim($formula);
       // Recorro buscando las variables de la formula para buscar su funcion        
       $variablesg=$this->getVarForm($cadena); // Extraer variables de formulas que se validan en este registro
       $var='';$i=0;       
       
       // FUNCIONES PROPIAS DE NOMINA ----------------------------------------------------------------------
       if (in_array('sueldo', $variablesg)) // SUELDO
       {
         $dat     =  $this->getSueldo($ide, $idProy);
         $sueldo  =  $dat->valor; 
       } 
       if (in_array('Valhora', $variablesg)) // VALOR HORA
       {
         $dat     =  $this->getVlrhora($ide, $idProy,$fechaEje, $idCon);
         $Valhora =  $dat->valor; 
       }
       if (in_array('Valdia', $variablesg)) // VALOR DIA 
       {
         $dat     =  $this->getVlrdia($ide); 
         $Valdia  =  $dat->valor;       
       }
       if (in_array('ValdiaP', $variablesg)) // VALOR DIA PRIMAS CASO CAJAMAG REPRE LEGAL 
       {
         $dat     =  $this->getVlrdiaP($ide); 
         $ValdiaP  =  $dat->valor;       
       }       
       if (in_array('ValhoraIBC', $variablesg)) // VALOR DIA IBC ANTERIOR 
       {
         $dat     =  $this->getValhoraIBC($iddn); 
         $ValhoraIBC   =  $dat->valor;       
       }                   
       if (in_array('SumLey', $variablesg))  // LEY 100
       {
         $dat     =  $this->getLey($iddn);   
         $SumLey  =  $dat->valor;             
       }                   
       if (in_array('Subsidio', $variablesg))  // SUBSIDIO
       {
         $dat     =  $this->getSubsidio($iddn);   
         $Subsidio  =  $dat->valor;             
       }          
       if (in_array('SubTrans', $variablesg))  // Transporte
       {
         $dat       =  $this->getSubTrans($iddn);   
         if ( $dat == '' )
            $SubTrans  =  0;              
         else  
            $SubTrans  =  $dat->valor;             
       }
       if (in_array('diasLabMes', $variablesg))  // Dias laborados mes del trabajador
       {
         $dat       =  $this->getdiasLabMes($iddn);   
         if ( $dat == '' )
            $diasLabMes  =  0;              
         else  
            $diasLabMes  =  $dat->valor;             
       }       
       $diasAntesInicioAumentoSueldo = 0;
       if (in_array('diasAntesInicioAumentoSueldo', $variablesg))  // dias antes del aumento de sueldo
       {
         $dat       =  $this->getDiasAntesAumSuedo($iddn);   
         if ( $dat == '' )
            $diasAntesInicioAumentoSueldo = 0;              
         else  
            $diasAntesInicioAumentoSueldo = $dat->valor;             
       }

       $diasGrupo = 0; 
       if (in_array('diasGrupo', $variablesg))  // Dias diferencia ingreso al grupo nomina actual
       {
         $dat       =  $this->getGrupo($iddn);   
         if ( $dat != '' )
            $diasGrupo  =  $dat->valor;             
       }
       $diasGrupoIngre = 0; 
       if (in_array('diasGrupoIngre', $variablesg))  // Dias diferencia ingreso al grupo nomina actual
       {
         $dat       =  $this->getIngresoGrupoActual($iddn);   
         if ( $dat != '' )
            $diasGrupoIngre  =  $dat->valor;             
       }
       $diasContra = 0; 
       if (in_array('diasContra', $variablesg))  // Dias laborados desde ultimo contrato segun periodo de nomina
       {
         $dat       =  $this->getDiasContratoActual($iddn);   
         //if ( $dat != '' )
            $diasContra = $dat;             
       }
        
       if (in_array('minimocoop', $variablesg)) // VALIDACION ESCALA ID 2 ESPECIAL CAJAMAG
       {
         $dat     =  $this->getEscala(2);
         $minimocoop  =  $dat->valor; 
       }      
       if (in_array('idGrupo', $variablesg)) // VALIDACION ESCALA ID 2 ESPECIAL CAJAMAG
       {
         $dat     =  $this->getGrupoEmp($ide);
         $idGrupo  =  $dat->valor; 
       }             
       if (in_array('integral', $variablesg)) // Salario integral 
       {
         $dat     =  $this->getSalarioIntegral($ide);
         $integral  =  $dat->valor; 
       }             
       // Validaciones de formulas : se repite el cargue de formulas debidoa que hay valores diferentes en la valdiacion
       $t = new AlbumTable($this->adapter); 
       $datCon = $t->getGeneral1("select validacion, si, no 
                                     from n_formulas 
                                        where id = ".$idfor);
       if ( ($datCon['validacion']!='') and ($datCon['validacion']!=NULL) )       
       {
          $cadena=ltrim( $datCon['validacion'] );
          // Recorro buscando las variables de la formula para buscar su funcion        
          $variablesg=$this->getVarForm($cadena); // Extraer variables de formulas que se validan en este registro
          $var='';$i=0;       
       
          // FUNCIONES PROPIAS DE NOMINA ----------------------------------------------------------------------
          if (in_array('sueldo', $variablesg)) // SUELDO
          {
             $dat     =  $this->getSueldo($ide, $idProy);
             $sueldo  =  $dat->valor; 
          }       
          if (in_array('Valhora', $variablesg)) // VALOR HORA
          {
             $dat     =  $this->getVlrhora($ide, $idProy, $fechaEje, $idCon );
             $Valhora =  $dat->valor; 
          }
          if (in_array('Valdia', $variablesg)) // VALOR DIA 
          {
             $dat     =  $this->getVlrdia($ide); 
             $Valdia  =  $dat->valor;       
          }
       if (in_array('ValdiaP', $variablesg)) // VALOR DIA PRIMAS CASO CAJAMAG REPRE LEGAL 
       {
         $dat     =  $this->getVlrdiaP($ide); 
         $ValdiaP  =  $dat->valor;       
       }                 
          if (in_array('SumLey', $variablesg))  // LEY 100
          {
             $dat     =  $this->getLey($iddn);   
             $SumLey  =  $dat->valor;             
          }                   
          if (in_array('Subsidio', $variablesg))  // SUBSIDIO
          {
              $dat     =  $this->getSubsidio($iddn);   
              $Subsidio  =  $dat->valor;             
          }          
          if (in_array('SubTrans', $variablesg))  // Transporte
          {
             $dat       =  $this->getSubTrans($iddn);   
             if ( $dat == '' )
                $SubTrans  =  0;              
             else  
                $SubTrans  =  $dat->valor;             
          }
          if (in_array('idGrupo', $variablesg)) // VALIDACION ESCALA ID 2 ESPECIAL CAJAMAG
          {
              $dat     =  $this->getGrupoEmp($ide);
              $idGrupo  =  $dat->valor; 
          }                        

          if (in_array('integral', $variablesg)) // Salario integral 
          {
             $dat     =  $this->getSalarioIntegral($ide);
             $integral  =  $dat->valor; 
          }                         
       }// Fin validacion en formulas
       $diasInca  =  0;             
       $dat     =  $this->getDiasInca($iddn, $ide);  // Dias de incapacidad 
       if ($dat!='')
          $diasInca  =  $dat->valor;         

       $diasAus  =  0;             
       $dat     =  $this->getDiasAus($iddn, $ide);  // Dias de ausencia
       if ($dat!='')
          $diasAus  =  $dat->valor;         	   

       $sumDev  =  0;
       $sumDed  =  0;
       $sumTot  =  0;
	                
//       $dat     =  $this->getSumDeCreTot($iddn);  // Dias de incapacidad 
 //      if ($dat!=''){
   //       $sumDev  =  $dat['devengado'] ;
     //     $sumDed  =  $dat['deducido'] ; 
       //   $sumTot  =  $dat['pagar'];
	    // }

       $dat     =  $this->getPeriodoVaca($iddn);  // Datos de las vacaciones
       if ($dat!=''){
          $perVac   =  $dat['perVac'] ; // Periodos pagados en esta nomina , ejemplo 1 = 15 dias, 2 = 30 dias 
          $perConv  =  $dat['perConv'] ; // Periodos de vacacines convencionados
	     }	   
       else{
          $perVac  = 0;          
          $perConv = 0; 
       }	   
       // Diferencia en dias no laborados, casos modificaiones en nominas o inicios de conratos, 
       // esto afectae descuentos que deben ser completos
       $dat   =  $this->getDifDiasCal($iddn);  
       if ($dat!=''){
          //$diasMod =  $dat['valor'] ; 
          $diasMod =  0; 
       }     
       else{
          $diasMod  = 0;          
       }     
       // Dias no trabajados por inicio o fin de contrato
       $dat   =  $this->getDifDiasCalContra($iddn);  
       if ($dat!=''){
          $diasModContra =  $dat['valor'] ; 
       }     
       else{
          $diasModContra  = 0;          
       }     	   
       $smlc = $this->salarioMinimoCovencional; 
       $smlv = $this->salarioMinimo;       

       // PROCESOS ESPECIALES EMPRESAS       
       $difSueldo  =  0;             
       $dat     =  $this->getDiferenciaSueldo($ide, $iddn, $fechaEje);  // Diferencia en sueldo empleados
       if ($dat!='')
          $difSueldo  =  $dat->valor;   
       
       $dev=0;
       $ded=0;
       if ($idfor!=1) // Predeteminado el 1 para formulas manuales en conceptos
       {
         // Ejecucion de formula ------------------------------------------------------------              
         $valor = 0;
//         echo $formula.' - ' ;
         if ($formula!='')           
          {
             eval("\$str =$formula;");
             $valor = $str;  
  //           echo $valor.'<br /> ';
          }
           if ($valor!=0)
             {
              if ($horas==0)
              {
                if ($tipo==1)
                   $dev  = $valor;
                else 
                   $ded  = $valor; 
              }else{
                if ($tipo==1)
                   $dev  = $valor*$horas;
                else 
                   $ded  = $valor*$horas;                   
              }
             }// Fin val valor
       }// Fin valdia id de formula       
       if ($idCon==122) // Fijo para el id del concepto del sueldo
       {
           if ($horas==0)
              $dev=0; 
       }
       $validacion = '';
       $swVal = 0;
       $vlrDiferencia = 0; // Valores validados en formulas (SALUD , PENSION)
       if ( ($datCon['validacion']!='') and ($datCon['validacion']!=NULL) )
       {
          $val = trim($datCon['validacion']);  
          eval(
            ''.$val.'{'.
               '$swVal=1;'.
            '}'); 
          //   
          if ( $swVal == 1 ) // Valor del si cuando cumple la condicion
          {    
             if ( $ded > 0  )  
                $dedA = $ded;
             else 
                $devA = $dev ;

             $formula = $datCon['si'];
             eval("\$str =$formula;");
             $valor = $str;           
             if ( $ded > 0 )  
             {
                $ded = $valor;
                $vlrDiferencia = $dedA - $ded ; // Valor deiferencia                 
             }
             else 
             {
                $dev = $valor;
                $vlrDiferencia = $devA - $dev ; // Valor deiferencia                 
             }   
          }  
       }
       $valores = array("dev"=>$dev,"ded"=>$ded,"diferencia"=>$vlrDiferencia);       
       return $valores; 
   } // Fin guardar formula en novedades   
   // 
   public function getNove($idNom, $idEmp ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos ,
                             $idCon, $ac, $tipn,$dev,$ded,$idfor,$diasLabC,$idCpres,$calc,$conVac,$obId)
   { 

   }

   // Busqueda de variables dentro de la formula para validar funciones de busqueda
   public function getVarForm($cadena)
   {
       $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
       $variablesg=array();
       $var='';
       for( $i=0; $i<=strlen($cadena); $i++)
       {
	        $caracter = substr(ltrim($cadena),$i,1);
         if (strstr($permitidos, $caracter ))
	       { 
            $var .=$caracter;			
         }else{
	    // Buscar nombre de la variable
            if (!(in_array($var, $variablesg)) )
 	          {
		              $variablesg[]=ltrim($var); 
                  $var = '';
            }// Fin si variable 		
	        }// Fin armado variable		 
       }// Fin para               
       return $variablesg;       
   }  
   
    
   // ----------------------------------------------------- 
   // FORMULAS FIJAS EN PROGRAMA *-----------------------------------------------------------------------------------------------
   // ------------------------------------------------- 
   // 1. Salario empleado
   public function getSueldo($id, $idProy)
   {
      if ($idProy>1) // Proyectos 
      {
          $result=$this->adapter->query("select a.sueldo as valor  
               from n_proyectos_e a
                   where a.idProy = ".$idProy." and a.idEmp = ".$id,Adapter::QUERY_MODE_EXECUTE);      
        }
      else  
      {
          $result=$this->adapter->query("select (sueldo) as valor 
              from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      }
      $datos = $result->current();
      return $datos;
    }                   
   // 2. Valor hora empleado
   public function getVlrhora($id, $idProy, $fechaEje, $idCon )
   {
      if ($idProy>1) // Proyectos 
      {    
          $result=$this->adapter->query("select (a.sueldo/a.horas) as valor  
               from n_proyectos_e a
                   where a.idProy = ".$idProy." and a.idEmp = ".$id,Adapter::QUERY_MODE_EXECUTE);
        }
      else 
      {
         $result=$this->adapter->query("select (sueldo/horas) as valor 
                                  from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);               
       }
       $datos = $result->current();
      if ( $fechaEje != '0000-00-00' ) 
      {
         if ($idCon != 122)
         { 
            $result2 =$this->adapter->query("select count(a.id) as num, (b.sueldo/240) as valor  
                        from n_nomina a 
                           inner join n_nomina_e b on b.idNom = a.id 
                        where a.estado = 2 and '".$fechaEje."' 
                           between a.fechaI and a.fechaF and b.idEmp = ".$id." 
                        order by a.id desc limit 1",Adapter::QUERY_MODE_EXECUTE);
            $datosR = $result2->current();

            if ( $datosR['num'] > 0 )
                 $datos = $datosR ;

         }  

      }      
      return $datos;
    }                   

   // 2.1 Valor hora empleado por IBC anterior
   public function getValhoraIBC($idInom)
   {
       $result=$this->adapter->query("select year( b.fechaF ) as ano, (month( b.fechaF ) - 1) as mes, a.idEmp 
          from n_nomina_e a 
                  inner join n_nomina b on b.id = a.idNom 
             where a.id = ".$idInom ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();    

       $result=$this->adapter->query("select case when sum(a.devengado) is null then # si no se usa el ibc del mes anterior 
      (e.sueldo/ ( ( select a.diasSalud from n_planilla_unica_e a where a.regAus = 0 and  a.idEmp = ".$datos['idEmp']." order by a.id desc limit 1 )*8) )    
  else
     sum(a.devengado) / ( (select a.diasSalud from n_planilla_unica_e a where a.regAus = 0 and a.idEmp = ".$datos['idEmp']." order by a.id desc limit 1 )*8 )    
  end as valor        
from  n_nomina_e_d a 
  inner join n_conceptos b on a.idConc=b.id 
  inner join n_conceptos_pr c on c.idConc=b.id 
  inner join n_nomina_e d on d.id=a.idInom
  inner join a_empleados e on e.id=d.idEmp
  inner join n_nomina f on f.id = d.idNom 
where c.idProc=1 and 
   b.id != ( case when f.idTnom = 6 then 257 else 0 end ) # Ignorar vacaciones en liquidacion final 
and # Todos los conceptos que hacen part de ley 100  
year(f.fechaI) = ".$datos['ano']." and month(f.fechaI) = ".$datos['mes']." and e.id = ".$datos['idEmp'] ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->current();
      return $datos;
    }                   

   // 3. Valor dia empleado
   public function getVlrdia($id)
   {
      $result=$this->adapter->query("select (sueldo/30) as valor from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
    }                   
   // 3. Valor dia empleado caso primas Cajamag y extralelaes 
   public function getVlrdiaP($id)
   {
      $result=$this->adapter->query("select
         ( (case when representante =1 then (4*".$this->salarioMinimo.") else 0 end ) + sueldo)  /30 as valor 
           from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
    }                       
   // 4. Sumatoria procesos Ley 100
   public function getLey($id)
   {
      $result=$this->adapter->query("Select sum(devengado) as valor  
                      from n_nomina_e_d a 
                        inner join n_conceptos b on a.idConc=b.id 
                        inner join n_conceptos_pr c on c.idConc=b.id 
                        inner join n_nomina d on d.id = a.idNom 
                      where c.idProc=1 and 
                        b.id != ( case when d.idTnom = 6 then 133 else 0 end ) # Ignorar vacaciones en liquidacion final 
                          and  a.idInom=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   }                           
   // 5. Solidaridad
   public function getSolidaridad($ano, $mes , $idEmp)
   {
      $result=$this->adapter->query("Select d.id,  
case when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >(4*".$this->salarioMinimo.") and ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) <=(16*".$this->salarioMinimo.")  ) then ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end)*(1/100) )
     when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >(16*".$this->salarioMinimo.") and ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) <=(17*".$this->salarioMinimo.")  ) then  ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end)*(1.2/100) )
     when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >(17*".$this->salarioMinimo.") and ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) <=(18*".$this->salarioMinimo.") ) then ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end)*(1.4/100) )    
     when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >(18*".$this->salarioMinimo.") and ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) <=(19*".$this->salarioMinimo.") ) then ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end)*(1.6/100) )             
     when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >(19*".$this->salarioMinimo.") and ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) <=(20*".$this->salarioMinimo.") ) then ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end)*(1.8/100) )                      
     when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >(20*".$this->salarioMinimo.") and ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) <(25*".$this->salarioMinimo.") ) then ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end)*(2/100) )                      
     when ( ( sum(case when e.integral = 1 then (a.devengado/1.3) else a.devengado end) ) >=(25*".$this->salarioMinimo.") ) then ( ".$this->salarioMinimo." / 2  )                               
    else 0  
end as valor  
from  n_nomina_e_d a 
inner join n_conceptos b on a.idConc=b.id 
inner join n_conceptos_pr c on c.idConc=b.id 
inner join n_nomina_e d on d.id=a.idInom
inner join a_empleados e on e.id=d.idEmp
inner join n_nomina f on f.id = d.idNom 
where c.idProc=1 and 
   b.id != ( case when f.idTnom = 6 then 133 else 0 end ) # Ignorar vacaciones en liquidacion final 
and # Todos los conceptos que hacen part de ley 100  
year(f.fechaI) = ".$ano." and month(f.fechaI) = ".$mes." and e.id =".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
    }

   // 5. Solidaridad VACACIONES
   public function getSolidaridadv($ano, $mes, $id, $idEmp, $vlrRestaVaca)
   {
      $result=$this->adapter->query("Select d.id,  
case when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(4*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) <=(16*".$this->salarioMinimo.")  ) then ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  )*(1/100) )
     when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(16*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) <=(17*".$this->salarioMinimo.")  ) then  ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  )*(1.2/100) )
     when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(17*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) <=(18*".$this->salarioMinimo.") ) then ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  )*(1.4/100) )    
     when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(18*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) <=(19*".$this->salarioMinimo.") ) then ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  )*(1.6/100) )             
     when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(19*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) <=(20*".$this->salarioMinimo.") ) then ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  )*(1.8/100) )                      
     when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(20*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) <=(25*".$this->salarioMinimo.") ) then ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  )*(2/100) )                               
     when ( ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) >(25*".$this->salarioMinimo.") and ( sum( case when a.idConc = 133 then a.devengado-".$vlrRestaVaca." else a.devengado end  ) ) > (25*".$this->salarioMinimo.") ) then ( ".$this->salarioMinimo." / 2  )                             
    else 
        
      0 
end as valor  
from  n_nomina_e_d a 
inner join n_conceptos b on a.idConc=b.id 
inner join n_conceptos_pr c on c.idConc=b.id 
inner join n_nomina_e d on d.id=a.idInom
inner join a_empleados e on e.id=d.idEmp 
inner join n_nomina f on f.id = d.idNom 
where c.idProc=1 and e.pensionado=0 and year(f.fechaI) = ".$ano." and month(f.fechaI) = ".$mes." 
 and e.id=".$idEmp ,Adapter::QUERY_MODE_EXECUTE);

      $datos = $result->current();
      return $datos;
    }     
   // 6. Sumatoria procesos subsidios
   public function getSubsidio($id)
   {
      $result=$this->adapter->query("Select 
case when ( e.sueldo < (2*".$this->salarioMinimo.") ) then ".$this->subsidioTransporte."/2
	  else 0  
end as valor  
from  n_nomina_e_d a 
inner join n_conceptos b on a.idConc=b.id 
inner join n_conceptos_pr c on c.idConc=b.id 
inner join n_nomina_e d on d.id=a.idInom
inner join a_empleados e on e.id=d.idEmp
where c.idProc=1 and  a.idInom=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
    }    
    // 7. Devolver dia de inicio calendario   
   public function getPeriodo($fechai,$dias)
   {
       
       $nuevafecha = strtotime ( '+'.$dias.' day' , strtotime ( $fechai ) ) ;
       $fechaf  = date ( 'Y-m-j' , $nuevafecha );
       $dias=1;
       $nuevafecha = strtotime ( '+'.$dias.' day' , strtotime ( $fechaf ) ) ; // Inicio del periodo siguiente
       $fechai2 = date ( 'Y-m-j' , $nuevafecha );
       // Regular las fechas 
       $ini=0;$fin=0;
       
       $fechas = array("fechaI"=>$fechai , "fechaF"=>$fechaf, "fechaI2"=>$fechai2 );         
       
       return $fechas;       
   }    

   // 8. Dias laborado por empleado SIN USO
   public function getDiasLab($id)
   {
      $result=$this->adapter->query("select DATEDIFF( '2002-11-02', fecIng) from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }         

   // 9. Dias habiles o no habiles SIN USO
   public function getDiasHN($id)
   {
      $result=$this->adapter->query("select DATEDIFF( '2002-11-02', fecIng) from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }             
   // 10. Subsidio de transporte
   public function getSubTrans($id)
   {
      // Sumar todos los conceptos que hagan parte del sueldo 122 = Concepto de sueldo
      $result=$this->adapter->query("select perAuto from n_conceptos where id = 174",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      if ( $datos['perAuto'] == 0 ) // Cuando el transporte es en todos los periodos
      {  
         $result=$this->adapter->query("Select case when(  ( ( e.sueldo/2 ) + ( sum( case when ( (  a.idConc != 122) and ( c.id > 0 ) ) 
then a.devengado else 0 end ) )) <= ".$this->salarioMinimo." ) then ( ".$this->subsidioTransporte." / 30) else 0 end as valor 
        from n_nomina_e_d a 
            inner join n_conceptos b on a.idConc=b.id 
            inner join n_nomina_e d on d.id=a.idInom
            inner join a_empleados e on e.id=d.idEmp
            left join n_conceptos_pr c on c.idConc=b.id and c.idProc = 2            
          where a.idInom = ".$id." group by e.id ",Adapter::QUERY_MODE_EXECUTE);

      }else{ // Cuando es al final de mes

         $result=$this->adapter->query("Select 

case when d.sueldo <= ( 2 * ".$this->salarioMinimo." ) then 
case when 
# ----------- conceptos que hacen parte del sub de transporte  ( 1 ) 
( case when ( select round( sum( aa.devengado ),0 )  
        from n_nomina_e_d aa 
            inner join n_conceptos bb on aa.idConc = bb.id 
            inner join n_nomina_e dd on dd.id = aa.idInom
            inner join a_empleados ee on ee.id = dd.idEmp
            inner join n_conceptos_pr cc on cc.idConc = bb.id 
            inner join n_nomina ff on ff.id = dd.idNom    
         where cc.idProc = 2 and year( ff.fechaF ) = year( f.fechaF ) 
          and month( ff.fechaF ) = month( f.fechaF ) and dd.idEmp = a.idEmp    
  ) is null then 0 else 
  
    ( select round( sum( aa.devengado ),0 )  
        from n_nomina_e_d aa 
            inner join n_conceptos bb on aa.idConc = bb.id 
            inner join n_nomina_e dd on dd.id = aa.idInom
            inner join a_empleados ee on ee.id = dd.idEmp
            inner join n_conceptos_pr cc on cc.idConc = bb.id 
            inner join n_nomina ff on ff.id = dd.idNom    
         where cc.idProc = 2 and year( ff.fechaF ) = year( f.fechaF ) 
          and month( ff.fechaF ) = month( f.fechaF ) and dd.idEmp = a.idEmp    
    )      
  end  )# ----------- Fin conceptos que hacen parte del sub de transporte ( 1 )
  <= ( 2 * ".$this->salarioMinimo." ) then 
      round( ".$this->subsidioTransporte." / 30, 4 ) 
  
  else 0 end   
  
else 0 end as valor 
        from n_nomina_e a 
            inner join n_nomina f on f.id = a.idNom    
            inner join a_empleados d on d.id = a.idEmp 
         where a.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);

      }   
      $datos = $result->current();
      return $datos;
   }                


   // 12. Numero de dias por incapacidad
   public function getDiasInca($id, $ide)
   {
      $result=$this->adapter->query("select  case when ( case when a.reportada = 0 
          then sum( a.diasAp + a.diasDp ) else sum(a.diasDp )  end ) > 15 then
             15
        else  

( case when a.reportada = 0 
          then sum( a.diasAp + a.diasDp ) else sum(a.diasDp )  end ) 
             
       end 
       as valor 
           from n_nomina_e_i a 
           inner join n_nomina_e b on b.idNom = a.idNom and b.idEmp = a.idEmp
            where b.id = ".$id." and a.idEmp = ".$ide,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                
   // 12. Numero de dias por ausentismo
   public function getDiasAus($id, $ide)
   {
      $result=$this->adapter->query("select a.dias as valor  
           from n_nomina_e_a a 
           inner join n_nomina_e b on b.idNom = a.idNom and b.idEmp = a.idEmp
            where b.id = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                   
   
   // Sacar diferencia en sueldo para sumarlo al sueldo ( Temporal  )
   public function getDiferenciaSueldo($id, $idnn, $fechaEje )
   {      
      # Se trae el concepto de diferencia en sueldo para procesos especiales  
      if ($fechaEje=='0000-00-00')
      {  
          $result=$this->adapter->query("select case when c.idSal = 0 then 
                         ( (g.sueldo-e.sueldo)) /240  
                      else  
                       (  (f.salario-e.sueldo)) /240 end as valor 
              from n_nomina_e_d a
              inner join n_nomina_e b on b.id = a.idInom
              inner join n_reemplazos c on c.id = a.idRem 
              inner join a_empleados e on e.id = c.idEmpR # Reemplazado por               
              inner join a_empleados g on g.id = c.idEmp # Reemplazado por                             
              left join n_salarios f on f.id = c.idSal # Salrio del reemplazo                
              where idConc = 134 and b.id=".$idnn." and b.idEmp = ".$id,Adapter::QUERY_MODE_EXECUTE);
      }else{
          $result=$this->adapter->query("select case when c.idSal = 0 then 
                         ( (g.sueldo-e.sueldo)) /240  
                      else  
                       (  (f.salario-e.sueldo)) /240 end as valor 
              from n_nomina_e_d a
              inner join n_nomina_e b on b.id = a.idInom
              inner join n_reemplazos c on c.id = a.idRem 
              inner join a_empleados e on e.id = c.idEmpR # Reemplazado por               
              inner join a_empleados g on g.id = c.idEmp # Reemplazado por                             
              left join n_salarios f on f.id = c.idSal # Salrio del reemplazo                
              where '".$fechaEje."' between c.fechai and c.fechaf and 
               idConc = 134 and b.id=".$idnn." and b.idEmp = ".$id,Adapter::QUERY_MODE_EXECUTE);        
      }    
      $datos = $result->current();
      return $datos;
   }               
     
   
   // Recalculo de documento de nomina   
   public function getRecalculo($idn)
   {
        $u = new Gnominag($this->adapter);
        // *-------------------------------------------------------------------------------
        // ----------- RECALCULO DE DOCUMENTO DE NOMINA -----------------------------------
        // *-------------------------------------------------------------------------------
        $datos2 = $u->getDocNove($idn, " and b.tipo in ('0','1','3')" );// Insertar nov automaticas ( n_nomina_e_d ) por tipos de automaticos                                                  
        foreach ($datos2 as $dato)
        {         
             $id      = $dato['idNom'];; // Id nomina  
             $iddn    = $idn;            // Id dcumento de novedad
             $idin    = $dato['id'];      // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados 
             $diasVac = $dato['diasVac'];    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["devengado"]; // Devengado
             $ded     = $dato["deducido"];  // Deducido  
             $idfor   = $dato["idFor"];   // Id de la formula   
             $diasLabC= $dato["horDias"];   // Si se afecta el cambio de dias laborados en el registro 
             $conVac   = 0;
             $obId     = 0;
             $fechaEje = $dato["fechaEje"] ;
             $idProy   = $dato["idProy"];
             // Llamado de funion -------------------------------------------------------------------
             $this->getNomina($id, $iddn, $idin, $ide ,$diasLab,$diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 1, 2,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy);                                          
        }
   }

   // Total para suma de creditos y debitos en documento de empleados
   public function getSumDeCreTot($id)
   {
      # Se trae el concepto de diferencia en sueldo para procesos especiales  
      $result=$this->adapter->query("Select sum( a.devengado ) as devengado,
        sum( a.deducido) as deducido, 
        sum( a.devengado ) - 
		  ( sum( a.deducido ) ) as pagar 
        from  n_nomina_e_d a 
        where a.idInom = ".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                

   // Datos de vacacion de empleado de acuerdo al ingreso a a convencion ( ESPECIAL CAJAMAG )
   public function getPeriodoVaca( $idnn )
   {
      $result=$this->adapter->query("select a.idVac , round( ( c.dias + c.diasDin ) / 15, 2 ) as perVac, 
         ( sum( case when c.perGrup > 0 
            then  round( ( c.dias + c.diasDin ) / 15, 2 )  * c.perGrup else 0 end ) ) as perConv  # Periodo como convecionado dentro de esta vacacion 
              from n_nomina_e a 
              inner join n_vacaciones b on b.id = a.idVac 
              inner join n_vacaciones_p c on c.idVac = b.id 
              where a.id=".$idnn." and c.perGrup > 0 
              group by a.id  ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                   
   
   // Dias no laborados en el periodo de nomina sin retorno de vacaciones , producto de modificaciones en sus dias laborados
   public function getDifDiasCal( $idnn )
   {
      $result=$this->adapter->query("select case when a.idVac > 0 then 0 else
case when d.id is null then ( c.valor - a.dias ) else 
 case when d.diasDp=15 then 0 else ( ( c.valor - a.dias )  - ( d.diasAp + d.diasDp ) ) 
    end 
 end 
 end as valor 
            from n_nomina_e a 
               inner join n_nomina b on b.id = a.idNom
               left join n_nomina_e_i d on d.idNom = b.id and d.idEmp = a.idEmp  
              inner join n_tip_calendario c on c.id = b.idCal 
               where a.id=".$idnn." group by a.id  ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                      

   // Crear periodos de vacaciones por empleado
   public function getPervaca( $idEmp, $anoS, $mes, $dia, $idCon )
   {
      $d = new AlbumTable($this->adapter); 
      $fechaI = $anoS.'-'.$mes.'-'.$dia;
      $fechaF = ($anoS+1).'-'.$mes.'-'.$dia;      
      // Consulto que no exista periodo ya generado para ese empleado 
      $datCon = $d->getGeneral1("select count(id) as num
                                   from n_libvacaciones 
                      where idEmp = ".$idEmp." and year(fechaI)=".$anoS." and month(fechaI)=".$mes." and year(fechaF)=".($anoS+1)." and month(fechaF)=".$mes." and day(fechaF)=".$dia);
      if ( $datCon['num']==0 )       
      {
           $d->modGeneral("insert into n_libvacaciones 
                             (idEmp, fechaI,fechaF, idCon ) 
                      values(".$idEmp.",'".$fechaI."','".$fechaF."', ".$idCon.")");      
      }
   }                         

   // Dias por aumento de sueldo individual 
   public function getDiasAntesAumSuedo($idInom)
   {
      $result=$this->adapter->query("select diasS as valor 
         from n_nomina_e where diasS>0 and id = ".$idInom ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                

   // Dias laborado proyectos
   public function getDiasLabProy($id)
   {
      $result=$this->adapter->query("select DATEDIFF( '2002-11-02', fecIng)
                             from a_empleados 
                               where id=".$id,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }            

   // Dias no laborados por inicio o fin de contrato
   public function getDifDiasCalContra( $idnn )
   {
      $result=$this->adapter->query("select case when a.idVac > 0 then 0 else
case when d.id is null then ( c.valor - a.dias ) else 
 case when d.diasDp=15 then 0 else ( ( c.valor - a.dias )  - ( d.diasAp + d.diasDp ) ) 
    end 
 end 
 end as valor 
            from n_nomina_e a 
               inner join n_nomina b on b.id = a.idNom
               left join n_nomina_e_i d on d.idNom = b.id and d.idEmp = a.idEmp  
              inner join n_tip_calendario c on c.id = b.idCal 
               where a.contra > 0 and a.id=".$idnn." group by a.id  ",Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }                         
   // Dias de convencionado en el año actual con el periodo de nomina actual sol primas
   // Solo para funciones de primas 
   public function getIngresoGrupoActual( $idnn )
   {
      $result=$this->adapter->query("select 
    case when c.fecha <= f.fechaI 
          then 179 else 


                                         ( ( month(f.fechaInfoF) - month(c.fecha) ) * 30 ) + 
                                         ( ( day(f.fechaInfoF)- day(c.fecha) )  ) 
       
       end + 1 as valor  

       from n_nomina_e a 
                   inner join a_empleados e on e.id = a.idEmp 
                   inner join n_tipemp b on b.id = e.idTemp and b.tipo = 0
                   inner join n_tipemp_p c on c.idTemp = b.id and c.idEmp = a.idEmp 
                   inner join n_nomina d on d.id = a.idNom 
                   inner join n_tip_calendario_d f on f.id = d.idIcal 
              where c.fecha <= d.fechaF and a.id=".$idnn,Adapter::QUERY_MODE_EXECUTE);
   
      $datos = $result->current();
      return $datos;
   } 
   // Ingreso a convencion no reportado para valor en formula 
   public function getGrupo( $idnn )
   {
      $result=$this->adapter->query("select  datediff(d.fechaI, c.fecha) as valor 
       from n_nomina_e a 
                   inner join a_empleados e on e.id = a.idEmp 
                   inner join n_tipemp b on b.id = e.idTemp and b.tipo = 0
                   inner join n_tipemp_p c on c.idTemp = b.id and c.idEmp = a.idEmp 
                   inner join n_nomina d on d.id = a.idNom 
              where c.reportado = 0 and a.id=".$idnn,Adapter::QUERY_MODE_EXECUTE);
   
      $datos = $result->current();
      return $datos;
   } 
   // Devolver dias que se debe descontar en dias para obtener dias calendario
   public function getDiasCalen( $mesI, $mesF, $fechaF )
   {
      $d = new AlbumTable($this->adapter);  
      // BUscar dia de corte
      $dat = $d->getGeneral1("select day('$fechaF') as diasF,
                                     day(last_day('$fechaF')) as diasFm");
      $diasF = $dat['diasF'];
      $diasFm = $dat['diasFm'];
      // BUscar ultimo dia de trabajo
      $dat = $d->getGeneral1("select day(last_day('2016-02-01')) as diasFeb");
      $diasFeb = $dat['diasFeb'];
      $diasR=0; 
      $diasS=0; 
      $diaU=0;
      for ($i=$mesI; $i <= $mesF ; $i++) 
      {
         switch ($i) {
             case 1: // Enero
               $diasR = $diasR + 1;
               if ( $mesF == 1 )
                  $diaU = 1;                   
               break;
             case 2: // Febrero 
               if ($diasFeb==28)
               {
                   $diasS = $diasS + 2;
                   if ( $mesF == 2 )
                     $diaU = 2;                   
               }    
               else
               {
                   $diasS = $diasS + 1;  
                   if ( $mesF == 2 )
                     $diaU = 1;                   
               }    
               break;             
             case 3: // Marzo
               $diasR = $diasR + 1;
               if ( $mesF == 3 )
                  $diaU = 1;
               break;                            
             case 5: // Mayo
               $diasR = $diasR + 1;
               if ( $mesF == 5 )
                  $diaU = 1;
               break;                                           
             case 7: // Julio
               $diasR = $diasR + 1;
               if ( $mesF == 7 )
                  $diaU = 1;
               break;                                                          
             case 8: // Agosto
               $diasR = $diasR + 1;
               if ( $mesF == 8 )
                  $diaU = 1;
               break;                                                                         
             case 10: // Octubre
               $diasR = $diasR + 1;
               if ( $mesF == 11 )
                  $diaU = 1;               
               break;                                                                                        
             case 12: // Diciembre
               $diasR = $diasR + 1;
               if ( $mesF == 12 )
                  $diaU = 1;
               break;                                                                                                       
         }          
      }
      // Se verifica si el dia de corte es menor al final de un mes
      // Se resta o suma el ultimo dia
      if ( $diasF == $diasFm )// Si el dia es menor al ultimo del mes debe restar o smar dia del mes
           $diaU = 0;
      else 
      {
         if ($mesF==2 ) 
            $diasS = $diasS - $diaU;
         else   
            $diasR = $diasR - $diaU;           
      }       
      
      return array("diasS"=>$diasS,
                   "diasR"=>$diasR,                   
                   );
   }                        
   // Dias calendario a descontar por rango de fecha
   public function getDiasCalenR( $fechaI, $fechaF )
   {
      $diasR=0; 
      $diasS=0;
      $d = new AlbumTable($this->adapter);  

      $result=$this->adapter->query("select year('".$fechaI."') as anoI, 
          month('".$fechaI."') as mesI,
       # Datos finales 
          year('".$fechaF."') as anoF, month('".$fechaF."') as mesF,
           day('".$fechaF."') as diasF",Adapter::QUERY_MODE_EXECUTE);
    $datos = $result->current();
    $anoI = $datos['anoI'];
    $anoF = $datos['anoF'];
    $mesF = $datos['mesF'];
    $diasF = $datos['diasF'];
    $sw=0;
    for ($i=$anoI; $i <= $anoF ; $i++) 
    {

       $dat = $d->getGeneral1("select day(last_day('".$i."-02-01')) as diasFeb");
       $diasFeb = $dat['diasFeb']; // Ubicar cuantos dias tuvo febrero en el año en curso
//echo 'Año'.$i.':'.$diasFeb.'<br />';
       if ( $sw==0 )
            $mesI = $datos['mesI'];       
       else 
            $mesI = 1;
       $sw = 1;                
       for ($y=$mesI; $y <= 12 ; $y++) 
       {      
           //echo $i.':'.$y.'<br />';

         switch ($y) {
             case 1: // Enero
               $diasR = $diasR + 1;
               break;
             case 2: // Febrero 
               if ($diasFeb==28)
                   $diasS = $diasS + 2;
               else
                   $diasS = $diasS + 1;  
               break;             
             case 3: // Marzo
               $diasR = $diasR + 1;
               break;                            
             case 5: // Mayo
               $diasR = $diasR + 1;
               break;                                           
             case 7: // Julio
               $diasR = $diasR + 1;
               break;                                                          
             case 8: // Agosto
               $diasR = $diasR + 1;
               break;                                                                         
             case 10: // Octubre
               $diasR = $diasR + 1;
               break;                                                                                        
             case 12: // Diciembre
               $diasR = $diasR + 1;
               break;                                                                                                       
         }      

           if ($i == $anoF)// Validar fin de año en consulta para dias calendario
           {
        //     echo 'año final ';
              if ($y == $mesF)
              {
//                if ($mesF!=2) 
                break; // Salir de ciclo
              }
           } 
       } 

       // 
       
    }
//    echo $diasS;
//    echo $diasR;
      return array("diasS"=>$diasS,
                   "diasR"=>$diasR);
   }                           
   // 17. Dias laborados por contrato empleado
   public function getDiasContratoActual($idnn)
   {
     $result=$this->adapter->query("Select 
                                  case when c.fechaI > e.fechaI then # Si el ingreso fuer posterior al periodo inicial de primas 
                                         DATEDIFF( e.fechaF , c.fechaI ) + 1

                                   else # Ingreso antes de periodo de primas 

                                         DATEDIFF( e.fechaF , e.fechaI ) + 2 
                                    end as valor1, 

# Buscamos los dias correspondientes al primer año de ingreso-----------------------------------------------------                     
( ( 12-(month( case when c.fechaI < e.fechaI then e.fechaI else c.fechaI end  ) )) * 30 )  +
( (30-day( case when c.fechaI < e.fechaI then e.fechaI else c.fechaI end )) +1 ) + 

#Buscamos los dias correspondientes a los años completos de trabajo ---------------------------------------------------------
( ( (year(e.fechaF) )-( year(case when c.fechaI < e.fechaI then e.fechaI else c.fechaI end)+1 ) ) * 12 ) * 30 + 


#Saco los dias correspondientes al año en curso para liquidacion segun fecha ------------------------------------------------
 ( ( (month( e.fechaF) - 1 )  * 30  ) + ( case when day( e.fechaF )=31 then day( e.fechaF )-1 else day( e.fechaF ) end )  ) 
 
 as valor,    
                                    b.fecIng, c.FechaI, 
                                  case when c.fechaI > e.fechaI then # Obtener el mes inicial para calculo de dias
                                         month( c.FechaI ) 

                                   else # Ingreso antes de periodo de primas 
                                         month( e.fechaI )                               
                                    end as mesI , month( e.fechaF ) as mesF , e.fechaF   
                              from n_nomina_e a 
                                  inner join a_empleados b on b.id = a.idEmp 
                                  inner join n_nomina e on e.id = a.idNom and a.idEmp = b.id  
                                  inner join n_emp_contratos c on c.idEmp = b.id and c.tipo = 1    
                                  left join n_nomina_nov d on d.estado = 0 and d.idCal = 7 and d.idEmp = a.idEmp # Verificar si tiene guardado dias de primas por modificacion 
                                  where a.id = ".$idnn."
                                     and ( c.fechaI < e.fechaF and c.tipo=1 ) # valdiar que sean empleados con fecha del periodo antes de nmina de primas 
                                  group by a.id  " ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      $dias = $datos['valor'];   
      //$datDcal = $this->getDiasCalen( $datos['mesI'] , $datos['mesF'], $datos['fechaF'] ); // Funcion apra deolver dias para descontar entr rango de fecha pra dias habiles
      //if ( ($datDcal['diasS']!=0) or ($datDcal['diasR']!=0) )
      //{
      //    $dias = $dias - $datDcal['diasR'];   
      //    $dias = $dias + $datDcal['diasS'];   
      //}
      return $dias;     
   }   
   // Dias laborado mes
   public function getDiasLabMes($idnn)
   {
      $result=$this->adapter->query("select 
      (
        select sum( bb.dias ) 
           from n_nomina aa
             inner join n_nomina_e bb on bb.idNom = aa.id
           where year(aa.fechaI) = year(a.fechaI) and month(aa.fechaI) = month(a.fechaI) and bb.idEmp = b.idEmp  
        ) as valor  
      from n_nomina a
        inner join n_nomina_e b on b.idNom = a.id
      where b.id = ".$idnn,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }
   // Salarios por escala salria 
   public function getEscala($id)
   {
      $result=$this->adapter->query("select 
      (
        select salario as valor 
           from n_salarios 
              where b.id = ".$id ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
      // Id de grupo
   } 
      public function getGrupoEmp($id)
      {
           $result=$this->adapter->query("select idGrup as valor from a_empleados where id=".$id,Adapter::QUERY_MODE_EXECUTE);
           $datos = $result->current();      
           return $datos;
      }                            

   // LIQUIDACION FINAL -----------------------------------
   // --------------------------------------------------------------------

   public function getLiqFinal($id)
   {
         $d = new AlbumTable($this->adapter);                 
         $g = new Gnominag($this->adapter);

         $pn = new Paranomina($this->adapter);
         $dp = $pn->getGeneral1(12);
         $topRetefuente = $dp['valorNum'];   // BASE RETEFUENTE 

         $datGen = $d->getConfiguraG(''); // CONFIGURACIONES GENERALES   
         $cgIncaCons = $datGen['incapaCons']; // Configuracion vista de incapacidades 
         // Buscar id de grupo
                       $d->modGeneral("update n_nomina 
                                        set fechaI = fechaF 
                                       where id=".$id );

         $datos  = $d->getPerNomina($id); // Periodo de nomina
         $idg    = $datos['idGrupo'];         
         $idTnomL = $datos['idTnomL']; // Nomina de liquidacion 
         $fechaI = $datos['fechaI'];         
         $fechaF = $datos['fechaF'];      
         $idIcal = $datos['idIcal'];         
         $mesNf = $datos['mesF'];         
         $anoNomina = $datos['ano'];         
         $mesNomina = $datos['mes'];                           
         $periodoNomina = $datos['periodo'];    
         $fechaIprimasCal = $datos['fechaIp'];  
         $mesIprimasCal   = $datos['mesIp'];   

         $anoCesantias = $datos['anoIc'];  
         $fechaIcesantias = $datos['fechaIc']; // Primera fecha para consulta de cesantias
         $fechaIcesantiasAnt = $datos['fechaIcAnt']; // Fecha anterior para no pagadas del año anterior
         $mesIcesantias   = $datos['mesIc'];                                   
         $diasCesantiasNuevo = $datos['diasCesantiaNuevo'];
         $e = '';  
         // NOTA DEBAJO HAY CAMPOS ASOCIADO A ESTA CONSULTA DATOS[]
         // INICIO DE TRANSACCIONES
         $connection = null;
         try 
         {
             $connection = $this->adapter->getDriver()->getConnection();
             $connection->beginTransaction();
         
         $d->modGeneral("update n_nomina set estado = 0 where id = ".$id);   
         $d->modGeneral("update n_nomina_e set diasVac = 0 where idNom = ".$id); 
         $d->modGeneral("delete from n_nomina_e_d where idNom = ".$id);  
         $d->modGeneral("update n_nomina_l set idNom = 0 where idNom = ".$id);  
         // ( POR TIPO DE AUTOMATICOS )
         $auto = 1; // Automatico general numero 1  
         $datos2 = $g->getNominaEtau($id,$idg, $auto );// Insertar nov automaticas (n_nomina_e_d ) por tipos de automaticos                              
   
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados
             $diasVac = $dato['diasVac'];    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = $dato["idFor"];   // Id de la formula 
             $diasLabC= $dato["diasLab"];   // Determinar si la afecta los dias laborados para convertir las horas laboradas
             $conVac  = $dato["vaca"];   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 1; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             // Llamado de funcion -------------------------------------------------------------------

             // Si es afectado por los dias laborados
             // y no tiene formula y tiene valor funciona esta forma
             if ( ($formula=='') and ($diasLabC==1) and ($dev > 0 ) and ($dato["horasCal"]==0) )
             {
                $valor = $dev;
                $formula = '($diasLab*('.$valor.'/30))';
                //echo 'SE DESCUENTA LA CUOTA DEL PERIODO COMPLETA ';
                // TENERE EN CUENTA QUE CUANDO SEA UNA NOMINA MENSUAL 
                $dev = 0;
                $idfor   = -1;   // Para ejecutar la formula 
             }
             // Buscar si tiene dias pagos en nomina del periodo
             $datPn = $d->getGeneral1("select pagNom from n_nomina_l where idNom = 0 and idEmp = ".$ide." order by id desc limit 1");
             if ($datPn['pagNom'] == 0)
             { 
                $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 1,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy); 
             }                         

         } // FIN TIPOS DE AUTOMATICOS         

         $auto = 2; // Automatico general numero 2  
         $datos2 = $g->getNominaEtau($id,$idg, $auto );// Insertar nov automaticas (n_nomina_e_d ) por tipos de automaticos                              
   
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados
             $diasVac = $dato['diasVac'];    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = $dato["idFor"];   // Id de la formula 
             $diasLabC= $dato["diasLab"];   // Determinar si la afecta los dias laborados para convertir las horas laboradas
             $conVac  = $dato["vaca"];   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 1; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             // Llamado de funcion -------------------------------------------------------------------

             // Si es afectado por los dias laborados
             // y no tiene formula y tiene valor funciona esta forma
             if ( ($formula=='') and ($diasLabC==1) and ($dev > 0 ) and ($dato["horasCal"]==0) )
             {
                $valor = $dev;
                $formula = '(15*('.$valor.'/30))';
                //echo 'ENTRO FORMULAS DIAS AFECTADOS ';
                $dev = 0;
                $idfor   = -1;   // Para ejecutar la formula 
             }
             $datPn = $d->getGeneral1("select pagNom from n_nomina_l where idNom = 0 and idEmp = ".$ide." order by id desc limit 1");
             if ($datPn['pagNom'] == 0)
             {                           
                 $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 1,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy);
            }                         

         } // FIN TIPOS DE AUTOMATICOS 2     
         $auto = 3; // Automatico general numero 3  
         $datos2 = $g->getNominaEtau($id,$idg, $auto );// Insertar nov automaticas (n_nomina_e_d ) por tipos de automaticos                              
   
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados
             $diasVac = $dato['diasVac'];    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = $dato["idFor"];   // Id de la formula 
             $diasLabC= $dato["diasLab"];   // Determinar si la afecta los dias laborados para convertir las horas laboradas
             $conVac  = $dato["vaca"];   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 1; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             // Llamado de funcion -------------------------------------------------------------------

             // Si es afectado por los dias laborados
             // y no tiene formula y tiene valor funciona esta forma
             if ( ($formula=='') and ($diasLabC==1) and ($dev > 0 ) and ($dato["horasCal"]==0) )
             {
                $valor = $dev;
                $formula = '(15*('.$valor.'/30))';
                //echo 'ENTRO FORMULAS DIAS AFECTADOS ';
                $dev = 0;
                $idfor   = -1;   // Para ejecutar la formula 
             }
                          
             $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 1,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy);                         

         } // FIN TIPOS DE AUTOMATICOS 3        
         $auto = 4; // Automatico general numero 4  
         $datos2 = $g->getNominaEtau($id,$idg, $auto );// Insertar nov automaticas (n_nomina_e_d ) por tipos de automaticos                              
   
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados
             $diasVac = $dato['diasVac'];    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = $dato["idFor"];   // Id de la formula 
             $diasLabC= $dato["diasLab"];   // Determinar si la afecta los dias laborados para convertir las horas laboradas
             $conVac  = $dato["vaca"];   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 1; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             // Llamado de funcion -------------------------------------------------------------------

             // Si es afectado por los dias laborados
             // y no tiene formula y tiene valor funciona esta forma
             if ( ($formula=='') and ($diasLabC==1) and ($dev > 0 ) and ($dato["horasCal"]==0) )
             {
                $valor = $dev;
                $formula = '(15*('.$valor.'/30))';
                //echo 'ENTRO FORMULAS DIAS AFECTADOS ';
                $dev = 0;
                $idfor   = -1;   // Para ejecutar la formula 
             }
                          
             $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 1,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy);                         

         } // FIN TIPOS DE AUTOMATICOS 4                                      
         // OTROS AUTOMATICOS POR EMPLEADOS LIQUIDACION FINAL 
         $datos2 = $g->getNominaEeua($id);// Insertar nov automaticas ( n_nomina_e_d ) por otros automaticos
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados 
             $diasVac = $dato['diasVac'];    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = -99;   // Id de la formula no tiene formula asociada, ya viene la formula 
             $diasLabC= 0;   // Dias laborados solo para calculados
             $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 1; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             // Fomrula para dais mas vacaciones en otros automaticos
             $valor = 0;
             $swD = 0;
             if ( $dev > 0 ) 
             {
                $valor = $dev;$dev=0;
                $swD = 1;
             }else
             {
                $valor = $ded;$ded=0;
             }
             if ( $dato['horasCal'] > 0 ) // Afectado por lso dias laborados
             {
                if ( $swD == 1 ) 
                    $formula = $diasLab.'*'.$valor; 
                else   
                   $formula = ' 15*'.$valor; // Concatenan para armar la formula
                $diasLabC = $dato['dias'] ;   // Dias laborados solo para calculados
                //$diasLabC = 15 ;   
             }else{
                if ( $swD == 1 ) 
                    $formula = $diasLab.'*'.$valor; 
                else   
                   $formula = ' 15*'.$valor; // Concatenan para armar la formula              
                //if ( $dato['idVac'] > 0 )
                //   $formula = ' 15*'.$valor; // Concatenan para armar la formula
                //else 
                //   $formula = ' 15*'.$valor; // Concatenan para armar la formula                  
                   //$formula = ' ($diasLab+$diasVac+$diasInca+$diasMod)*'.$valor; // Concatenan para armar la formula
             }    

             if ( $dato['formula']!='' )
                $formula = $dato['formula'];  
             //echo 'ifo  '.$formula;
             // Llamado de funion -------------------------------------------------------------------
             if ( ($dato['fecAct']==0) or ($dato['fecAct']==1) )
             {

                $datPn = $d->getGeneral1("select pagNom from n_nomina_l where idNom = 0 and idEmp = ".$ide." order by id desc limit 1");
                if ($datPn['pagNom'] == 0)
                {               
                   $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab,$diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 2,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId,$fechaEje,$idProy);              
                   $idInom = (int) $idInom;                   
                     $d->modGeneral("update n_nomina_e_d set nitTer='".$dato['nitTer']."' where id=".$idInom);             
                }     
             }
         } // FIN OTROS AUTOMATICOS POR EMPLEADOS
         // ( REGISTRO DE NOVEDADES MODIFICADAS ) ( n_nomina_nove ) Guardadas en las novedades anteriores
         $datos2 = $g->getRnovedadesN($id, "");// Insertar nov automaticas ( n_nomina_e_d ) por tipos de automaticos                              
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados
             $diasVac = 0;    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = $dato["idFor"];   // Id de la formula 
             $diasLabC= 0;   // Determinar si la afecta los dias laborados para convertir las horas laboradas
             $calc    = $dato["calc"];   // Instruccion para calcular o no calcular
             $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 0; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             $idIcal  = $dato["idIcal"];   // Instruccion para calcular o no calcular
             // Si es calculado en la novedad, debe permaneces su valor con los parametros del momento, sueldo, conf h extras ,ect
             // Llamado de funcion -------------------------------------------------------------------             
             if ( $dato["editado"] == 1) // Editar novedad en nomima_e_d
             {
                //if ( $dato["idInovN"] > 0 )
                //{
         //          echo 'ENTRO EN NOVEDADES EDITADAS';
                   $d->modGeneral("update n_nomina_e_d a 
                                     inner join n_nomina_e b on b.id = a.idInom  
                                   set a.devengado = ".$dev.", a.deducido = ".$ded."  
                                      where b.idEmp = ".$dato["idEmp"]." 
                                      and a.idNom = ".$id." and a.idConc = ".$dato["idCon"]
                                           );
             }
             else  
             { 
                $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,$calc,$conVac,$obId, $fechaEje, $idProy);              
             }
         } // FIN REGISTRO DE NOVEDADES MODIFICADAS POR OTROS AUTOMATICOS


         $datos2 = $g->getNominaEcau($id);
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados 
             $diasVac = 0;    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = 0;     // Devengado
             $ded     = 0;     // Deducido         
             $idfor   = $dato["idFor"];   // Id de la formula    
             $diasLabC= 0;   // Dias laborados solo para calculados 
             $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 0; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             // Llamado de funion -------------------------------------------------------------------
             //if ($dato["actVac"]==0)
             //{
             // 
              $sw = 0;
              if ( ($dato["idFpen"]==1) and ( $dato["fondo"]==2 ) ) // Si el concepto de pension no aplica no debe generarlo
                   $sw = 1;             

               if ($sw == 0)
                  $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 3,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId,$fechaEje, $idProy);              
              //}
         } // FIN CONCEPTOS AUTOMATICOS                  
            // -------------------------------------------------------------
            // ----- PROCEDIMIENTO PARA UBICAR LOS DIAS LABORADOS EN LIQUIDACION 
            // ----- VERIFICANDO SI ESTA EN QUINCENA O NO ------------------
            // -------------------------------------------------------------
            $datos2 = $d->getGeneral("select b.idGrup ,a.*, 
               month(a.fechaF) as mesF, 
          year(a.fechaIc) as anoIc, month(a.fechaIc) as mesIc,
    
            
            concat( year(a.fechaF) -1, '-', lpad( month(a.fechaF),2,'0'), '-', lpad( day(a.fechaF),2,'0') ) as fechaAntCesaI # Fecha de un año atras para calculo de cesantias 
                                         from n_nomina_l a 
                                            inner join a_empleados b on b.id = a.idEmp
                                        where a.idNom = 0");
          foreach ($datos2 as $datEmpL) 
          {
              $idGrupo = $datEmpL['idGrup']; 
              $idEmp   = $datEmpL['idEmp']; 
              $fechaF  = $datEmpL['fechaF']; 
              $fechaI  = $datEmpL['fechaI']; 
              $diasFin = $datEmpL['dias'];
              $diasCesantias = $datEmpL['diasCes']; 
              $diasCesantiasP = $datEmpL['diasPromC']; 
              $diasPrimas = $datEmpL['diasPrim']; 
              $diasPrimasP = $datEmpL['diasPromP']; 

                      // Buscar los dias promedio para el trabajador mas la liquidacion para sumarlas
                     $datDprom = $d->getGeneral1('Select 
                             (select sum( aa.dias ) + sum(aa.diasI) 
                                 + sum( case when bb.idTnom = 6 then 0 else aa.diasVac end ) 
                             from n_nomina_e aa 
                                    inner join n_nomina bb on bb.id = aa.idNom 
                               where bb.idTnom in (1,5,6) 
                     and aa.idEmp = k.idEmp and bb.fechaI>=concat( year(
                    case when k.fechaIngreso < k.fechaIc then k.fechaIc else k.fechaIngreso end 
                     ),"-", lpad(month( case when k.fechaIngreso < k.fechaIc then k.fechaIc else k.fechaIngreso end ),2,"0") ,"-" , (case when day( case when k.fechaIngreso < k.fechaIc then k.fechaIc else k.fechaIngreso end )<=15 then "01" else 15 end)  )
                          and bb.fechaF <= concat( year(k.fechaF),"-", lpad(month(k.fechaF),2,"0") ,"-" , (case when day(k.fechaF)<=15 then 15 else 30 end) ) )  as diasLabroados        
                         from n_nomina_l k 
                           where k.idNom = 0 and k.idEmp = '.$idEmp);    
                         //$diasPromedio = 0; 
                         //if ($datDprom['diasLabroados']>0)
                            //$diasPromedio = $datDprom['diasLabroados']; 
                          

              $mesF = $datEmpL['mesF']; 
              $fechaIc  = $datEmpL['fechaIc']; // Fecha inicio de cesantias ano actual
              $anoCesantias= $datEmpL['anoIc']; // Fecha inicio de cesantias ano actual
              $mesIc = $datEmpL['mesIc']; 
              $fechaIprimasCal = $datEmpL['fechaIp'];

              $fechaIngre = $datEmpL['fechaIngreso']; // Fecha de ingreso
              $fechaAnAnt = $datEmpL['fechaIConsulta']; // Fecha de consulta año anterior al retiro 
              $fechaIp  = $datEmpL['fechaIp']; // Fecha inicio de primas
              
              $tipo = $datEmpL['idTliq'];

              /// VACACIONES LIQUIDACION FINAL ( 2 )
              // Buscar dias reales para caculo de vacacicones
              $datVacD = $g->getVacasFinalLabor($id, $idEmp); 
              $diasVaca = $datVacD['diasTrabajadosPerVaca'];
              

            if ($diasVaca>0)
            {  
                // Buscar ausentismos
                $anoAus = $anoCesantias.'-01-01';                              
                $datAus = $d->getAusentismosDias($idEmp, $anoAus, $fechaF );
                $diasAus = 0;
                if ($datAus['dias']>0) # Si dias primas modificadas en la liquidacion es mayor a cero se toman esas
                {
                    $diasVaca = $diasVaca - $datAus['dias'];  
                    $diasAus = $datAus['dias'];
                }                

               $iddn    = $datVacD['id'];  // Id dcumento de novedad
              $idin    = 0;     // Id novedad
              $ide     = $datVacD['idEmp'];   // Id empleado
              $diasLab = 0;    // Dias laborados 
              $horas   = 0;
              $diasVac = 0;    // Dias vacaciones
              $formula = ''; // Formula
              $tipo    = 1;    // Devengado o Deducido  
              $idCcos  = $datVacD["idCcos"];  // Centro de costo   
              $idCon   = 257;   // Concepto

              $diasPromV = $datVacD['diasVaca'];
              //if ($datVacD['diasVaca']>360)
                //  $diasPromV = 360;

              //$diasPromV = $diasPromV + $diasFin; 

              $datVc   = $g->getVacasPromFinal( $idEmp, $fechaAnAnt , $fechaF, $diasPromV, $fechaAnAnt, 0 ); // Valor de prima a pagar
              $diasVaca =  round( ( ( ($diasVaca) * 15 ) / 360),2 );  
//$diasVaca = 13.5;
//echo 'Dias  vaca '.$diasVaca;
              // Actualizar dias de vaciones pendientes
              $d->modGeneral("update n_nomina_l  
                       set diasVaca=".$diasVaca.",
                           diasPromV=".$diasPromV." 
                      where idNom = 0 and idEmp=".$ide);              
              //echo '----------------------- Vacaciones <br />';                            
              //echo 'Dias vaca '.round( $diasVaca ,2).'<br />Valor base promedio vaca '.round($datVc["vlrBasePromedioVaca"],2).'= '.round($datVc["vlrBasePromedioVaca"],2)*round( $diasVaca ,2).'<br /><hr />';
              //echo round($datVc["total"],2);
              $dev     = round($datVc["vlrBasePromedioVaca"],2) *round( $diasVaca ,2) ; // Devengado  Dias trabajados eñn el semestre

              $dev     = round($datVc["vlrBasePromedioVaca"],2) *round( $diasVaca ,2) ; // Devengado  Dias trabajados eñn el semestre              

              $ded     = 0;     // Deducido         
              $idfor   = -99;   // Id de la formula    
              $diasLabC= 0;   // Dias laborados solo para calculados 
              $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
              $obId    = 1; // 1 para obtener el id insertado
              $fechaEje  = '';
              $idProy  = 0;
              //echo 'val '.$datVc["vlrBasePromedioVaca"];
              // Llamado de funion -------------------------------------------------------------------
              $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy);               
              $idInom = (int) $idInom;                   
              // LIBRO DE VAkCACIONES
              if ($dev > 0)
              {
                  $det = '';
                  if ( $diasAus > 0 )
                       $det = ' aus('.$diasAus.')'; 
                  $d->modGeneral("update n_nomina_e_d 
                       set detalle='VACACIONES (Dias ".number_format($diasVaca,2).' '.$det." ) ' where id=".$idInom);
                  // REGISTRO LIBRO DE PRIMAS
                  //$c->actRegistro($ide, $fechaI, $fechaF, $dev, $idInom , $id);
              }                                          

            }// Validacion dias mayor de vacaciones   
       
              // CESANTIAS E INTERESES (1)
              $fechaFc = $anoCesantias.'-12-31';                              
              $this->getCesantiasInt($fechaF, $fechaAnAnt ,$idEmp,  $idGrupo, $id, $fechaIc, $mesIc, $mesF, $diasCesantias, $tipo,$fechaIngre, $diasCesantiasP);

              // PRIMAS DE SERVICIO 
              $this->getPrimasInt($fechaF, $fechaAnAnt ,$idEmp,  $idGrupo, $id, $fechaIp, $mesIc, $mesF, $diasPrimas, $tipo, $diasPrimasP);

          }// Fin recorrido de empleados a liquidar
          
     // ( REGISTRO DE NOVEDADES MODIFICADAS ) ( n_nomina_nove ) Guardadas en las novedades anteriores
         $datos2 = $g->getRnovedadesN($id, "");// Insertar nov automaticas ( n_nomina_e_d ) por tipos de automaticos                              
         //print_r($datos2);
         foreach ($datos2 as $dato)
         {             
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = $dato['dias'];    // Dias laborados
             $diasVac = 0;    // Dias vacaciones
             $horas   = $dato["horas"];   // Horas laborados 
             $formula = $dato["formula"]; // Formula
             $tipo    = $dato["tipo"];    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = $dato["idCon"];   // Concepto
             $dev     = $dato["dev"];     // Devengado
             $ded     = $dato["ded"];     // Deducido
             $idfor   = $dato["idFor"];   // Id de la formula 
             $diasLabC= 0;   // Determinar si la afecta los dias laborados para convertir las horas laboradas
             $calc    = $dato["calc"];   // Instruccion para calcular o no calcular
             $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 0; // 1 para obtener el id insertado
             $fechaEje  = '';
             $idProy  = 0;
             $idIcal  = $dato["idIcal"];   // Instruccion para calcular o no calcular
             // Si es calculado en la novedad, debe permaneces su valor con los parametros del momento, sueldo, conf h extras ,ect
             // Llamado de funcion -------------------------------------------------------------------             
             if ( $dato["editado"] == 1) // Editar novedad en nomima_e_d
             {
                //if ( $dato["idInovN"] > 0 )
                //{
                   //echo 'ENTRO EN NOVEDADES EDITADAS';
                   $d->modGeneral("update n_nomina_e_d a 
                                     inner join n_nomina_e b on b.id = a.idInom  
                                   set a.devengado = ".$dev.", a.deducido = ".$ded."  
                                      where b.idEmp = ".$dato["idEmp"]." 
                                      and a.idNom = ".$id." and a.idConc = ".$dato["idCon"]
                                           );
             }
             else  
             { 
         //       $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,$calc,$conVac,$obId, $fechaEje, $idProy);              
             }
         } // FIN REGISTRO DE NOVEDADES MODIFICADAS POR OTROS AUTOMATICOS
         
        // PRESTAMOS 
         $periodoNomina = 0;
        $datos = $g->getPrestamos($id, $periodoNomina);// Prestamos 
        foreach ($datos as $dato2)
        {                      
           $idEmp = $dato2['idEmp'];            
           if ($dato2['dias'] >= 0){
              // Busqueda de cuotas de prestamos y descargue 
              //if ($dato2['vacAct']==0)
                 $datos2 = $g->getCprestamosL($id,$idEmp);
              //else // Calculo para el regreso de vacaciones
              //   $datos2 = $g->getCprestamosR($id,$idEmp);

              foreach ($datos2 as $dato)
              {

                $iddn    = $dato['id'];  // Id dcumento de novedad
                $idin    = 0;     // Id novedad
                $ide     = $dato['idEmp'];   // Id empleado
                $diasLab = $dato['dias'];    // Dias laborados 

                $diasVac = 0;    // Dias vacaciones
                $horas   = $dato["horas"];   // Horas laborados 
                $formula = $dato["formula"]; // Formula
                $tipo    = $dato["tipo"];    // Devengado o Deducido  
                $idCcos  = $dato["idCcos"];  // Centro de costo   
                $idCon   = $dato["idCon"];   // Concepto
                $dev     = 0;     // Devengado
                $ded     = $dato["valor"];     // Deducido         
                $idfor   = $dato["idFor"];   // Id de la formula    
                $diasLabC= 0;   // Dias laborados solo para calculados 
                $idCpres = $dato["idPres"];   // Id de la cuota del prestamo
                $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
                $obId    = 1; // 1 para obtener el id insertado
                $nitTer  = $dato['nitTer']; 
                $fechaEje  = '';
                $idProy  = 0;
                // Validar si hay una cuota modificada en la nomina activa
                if ( $dato['valorPresN'] > 0 )
                   $ded  = $dato["valorPresN"];// Deducido         
//if ($idEmp==179)
 //  echo 'Prestamo : '.$idCpres.': $ '.$ded.'<br />';
                // Llamado de funcion -------------------------------------------------------------------
                $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab,$diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 4,$dev,$ded,$idfor,$diasLabC,$idCpres,1,$conVac,$obId,$fechaEje,$idProy);                                           
                $idInom = (int) $idInom;                   
                // Colocar saldo del prestamo
                $d->modGeneral("update n_nomina_e_d set nitTer='".$nitTer."' where id=".$idInom);                
              }  
           }
        }// Prestamos 


           $datos2 = $g->getSolidaridad($id);   
           foreach ($datos2 as $dato)
           {             
                $ide     = $dato['idEmp'];   // Id empleado
                $ano     = $dato['ano'];   // Año
                $mes     = $dato['mes'];   // Mes                           
                $dat     = $this->getSolidaridad($ano, $mes, $ide); // Extraer los datos de solidaridad de la funcion
                $iddn    = $dato['id'];  // Id dcumento de novedad             
                $idin    = 0;     // Id novedad
                $diasLab = 0;    // Dias laborados 
                $diasVac = 0;    // Dias vacaciones
                $horas   = 0;   // Horas laborados 
                $formula = ''; // Formula
                $tipo    = 2;    // Devengado o Deducido  
                $idCcos  = $dato["idCcos"];  // Centro de costo   
                $idCon   = 21;   // Concepto
                $dev     = 0;     // Devengado
                $ded     = $dat['valor'];     // Deducido         
                $idfor   = -9;   // Id de la formula    
                $diasLabC= 0;   // Dias laborados solo para calculados 
                $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
                $obId    = 1; // 1 para obtener el id insertado
                $fechaEje  = '';
                $idProy  = 0;
                // Llamado de funion -------------------------------------------------------------------
                if ($ded>0)
                {
                   // Buscar valor de concepto pagado anterioremente en el mismo año y
                   // mes 
                   $datAnt  = $g->getFondSolAnt($ano, $mes,$ide, $id, 21);// Concepto de fondo de solidaridad
                   $dedAnt = 0;                
                   if ( $datAnt['deducido'] > 0 )
                   { 
                      $dedAct = $ded;                                                           
                      $ded = $ded - $datAnt['deducido'];
                      $dedAnt = $datAnt['deducido'];  
                   }                  
                   $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 3,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId,$fechaEje,$idProy);


                }
            }// FIN RECORRIDO FONDO DE SOLIDARIDAD               
        // RETENCION DE LA FUENTE
        $r = new Retefuente($this->adapter);
        $datos2 = $g->getRetFuente($id , 0);// 
        foreach ($datos2 as $dato)
        {       
          
           if ( ( $dato['valor'] > 0 ) or ( $dato['proce'] == 2 )   )
           {                                 
             $iddn    = $dato['id'];  // Id dcumento de novedad
             $idin    = 0;     // Id novedad
             $ide     = $dato['idEmp'];   // Id empleado
             $diasLab = 0;    // Dias laborados 
             $diasVac = 0;    // Dias vacaciones
             $horas   = 0;   // Horas laborados 
             $formula = ''; // Formula
             $tipo    = 2;    // Devengado o Deducido  
             $idCcos  = $dato["idCcos"];  // Centro de costo   
             $idCon   = 10;   // Concepto
             $dev     = 0;     // Devengado
             $ded     = 0; // Deducido   
             $idfor   = 0;   // Id de la formula    
             $diasLabC= 0;   // Dias laborados solo para calculados 
             $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
             $obId    = 1; // 1 para obtener el id insertado
             $calc    = 0;
             $ano     = $dato['ano'];   // Año
             $mes     = $dato['mes'];   // Mes                                 
             $ded = 0;
             //if ( $dato['dias']>0)
             $ded = $r->getReteConc($iddn, $ide); // Procedimiento para guardar la retencion
             $fechaEje  = '';
             $idProy  = 0;
             // Llamado de funion -------------------------------------------------------------------
             if ( $ded>0) 
             {
                // Buscar valor de concepto pagado anterioremente
                // en el mismo año y mes solo para procedimiento 1
                $dedAnt = 0;                
                if ( ( $dato['proce'] )==0 or ($dato['proce']==1) )
                { 
                   $datAnt  = $g->getFondSolAnt($ano, $mes,$ide, $id, 10);// RETEFUENTE
                   
                   if ( $datAnt['deducido'] > 0 )
                   { 
                      $dedAct = $ded;                     
                      $ded = $ded - $datAnt['deducido'];
                      $dedAnt = $datAnt['deducido'];  
                   }                  
                }
                $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,$calc,$conVac, $obId,$fechaEje, $idProy);              
                   $idInom = (int) $idInom;                   

                   $datPorRet = $d->getGeneral1('select a.porcentaje , a.uvtActual 
                                   from n_nomina_e_rete a 
                                      where a.idNom = '.$id.' and a.idEmp = '.$ide);
                   $d->modGeneral("update n_nomina_e_d 
                          set detalle='RETENCION EN LA FUENTE (".number_format($datPorRet['porcentaje'], 2)." %)' where id=".$idInom);
                  
                   // Colocar saldo anterior descontado en vacacione su otros 
                   if ( $dedAnt > 0 )
                       $d->modGeneral("update n_nomina_e_d 
                          set detalle='RETENCION EN LA FUENTE (ANT ".number_format($datAnt['deducido'])."- ACT ".number_format($dedAct)." ) ' where id=".$idInom);

              } // Fin validacion valor de deduccion                
            }// Fin validacion tope de retencion en la fuente           
         } // FIN RETENCION DE LA FUENTE                                   
              /// INDEMNIZACION  ( 2 )
              $datInd = $d->getGeneral("Select a.id, a.idEmp, b.idCcos,  round( ( b.sueldo / 30 ),2 ) as diasSal, 
           # para contratos indefinidos -------------------------------------------- (1)
              round( ( datediff( c.fechaF , ( select emp.fechaI    
                             from n_emp_contratos emp 
                                    where emp.tipo = 1 and emp.idEmp = c.idEmp order by id desc limit 1 ) ) + 1 ) , 2 ) as anosLabor,
           # para contratos fijos -------------------------------------------- (2)                                    
              ( datediff( ( select emp.fechaF    
                             from n_emp_contratos emp 
                                    where emp.tipo = 1 and emp.idEmp = c.idEmp order by id desc limit 1 ), c.fechaF   ) ) as diasRestantes,           
                                    ( select emp.idTcon 
                             from n_emp_contratos emp 
                                    where emp.tipo = 1 and emp.idEmp = c.idEmp order by id desc limit 1 ) as tipCon, 
                      c.fechaF , ( select emp.fechaI    
                             from n_emp_contratos emp 
                                    where emp.tipo = 1 and emp.idEmp = c.idEmp order by id desc limit 1 ) as fechaI 

                              from n_nomina_e a 
                                  inner join n_nomina aa on aa.id = a.idNom 
                                  inner join a_empleados b on b.id = a.idEmp 
                                  inner join n_nomina_l c on c.idEmp = b.id  
                               where c.idNom = 0 and c.idTliq = 3 and a.idNom = ".$id);
                              // print_r($datInd);  
             foreach ($datInd as $datVacD) 
             {
                $dev     = 0 ; // Devengado  Dias trabajados en el semestre
                $anoLabor = $datVacD['anosLabor'];
                $diasRestantes = $datVacD['diasRestantes'];
               
                $datDcal = $this->getDiasCalenR( $datVacD['fechaI'] , $datVacD['fechaF'] ); // Funcion apra deolver dias para descontar entr rango de fecha pra dias habiles
                //echo 'DIAS CONTRATO: '.$anoLabor.'<br />';
               if ( ($datDcal['diasS']!=0) or ($datDcal['diasR']!=0) )
               {
                    $anoLabor = $anoLabor - $datDcal['diasR'];   
                    $anoLabor = $anoLabor + $datDcal['diasS'];               
               }              
               //$diasVaca = $diasVaca +1;
               //$diasVaca = 930; 
               // echo 'DIAS CONTRATO: '.$anoLabor.'<br />';

                $diasSal = $datVacD['diasSal'];
                $tipCon = $datVacD['tipCon'];
                switch ($tipCon) {
                  case 1:
                     // Contratos indefinidos ---------- (1)
                     if ($anoLabor>360)
                     {
                        $anoLabor1 = 360; # resto un año
                        $dev = $diasSal * 30;                    

                        $anoLabor = $anoLabor - $anoLabor1;  
                        if ($anoLabor>0) // Restantes se pagan 20 dias de salario 
                        {
                            $dev = $dev + ( $diasSal * ( (20 * $anoLabor) / 360 ) );                   
                        }    
                     }else{// Es olo o menos de un año
                        $dev = $diasSal * 30;                    
                     }       
                     break;
                  case 4:
                     // Contratos terminos inferior a un año ---------- (1)
                        $dev = $diasSal * $diasRestantes;                    

                     break;                                       
                  case 5:
                     // Contratos terminos fijo ---------- (1)
                        $dev = $diasSal * $diasRestantes;                    

                     break;                                                            
                  default:
                    # code...
                    break;
                }

               $iddn    = $datVacD['id'];  // Id dcumento de novedad
               $idin    = 0;     // Id novedad
               $ide     = $datVacD['idEmp'];   // Id empleado
              $diasLab = 0;    // Dias laborados 
              $horas   = 0;
              $diasVac = 0;    // Dias vacaciones
              $formula = ''; // Formula
              $tipo    = 1;    // Devengado o Deducido  
              $idCcos  = $datVacD["idCcos"];  // Centro de costo   
              $idCon   = 9;   // Concepto

              $ded     = 0;     // Deducido         
              $idfor   = -99;   // Id de la formula    
              $diasLabC= 0;   // Dias laborados solo para calculados 
              $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
              $obId    = 1; // 1 para obtener el id insertado
              $fechaEje  = '';
              $idProy  = 0;
              //echo 'val '.$datVc["vlrBasePromedioVaca"];
              // Llamado de funion -------------------------------------------------------------------             

              // INDEMNIZACION
              if ($dev > 0)
              {
                  $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,0,$conVac,$obId, $fechaEje, $idProy);               
                  $idInom = (int) $idInom;                                   
                  //$d->modGeneral("update n_nomina_e_d 
                   //    set detalle='VACACIONES (Dias ".number_format($diasVaca,2)." ) ' where id=".$idInom);
                  // REGISTRO LIBRO DE PRIMAS
                  //$c->actRegistro($ide, $fechaI, $fechaF, $dev, $idInom , $id);
              }                                          

            }// Fin recorrido de empleados a liquidar


            // ---Activar liquidacion 
            $datos2 = $d->modGeneral("update n_nomina_l set idNom = ".$id."  
                                        where idNom = 0");          
             // Numero de empleados
             $con2 = 'select count(id)as num from n_nomina_e where idNom='.$id ;     
             $dato=$d->getGeneral1($con2);                                                  
             // Cambiar estado de nomina             
             $d->modGeneral('update n_nomina set estado=1, numEmp='.$dato['num'].' where id='.$id);                                         
             $e = 'Liquidacion final generada de forma correcta';
             $connection->commit();
         }// Fin try casth   
         catch (\Exception $e) 
         {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
                echo $e;
            }
         }// FIN TRANSACCION       
         return $e;  
   }   

    // Generacion de cesantias 
    public function getCesantiasInt($fechaF, $fechaAnAnt , $idEmp, $idg, $id, $fechaIcesantias, $mesIc, $mesFc, $diasCesantiasNuevo, $tipo, $fechaIngre, $diasPromedio)
    {
         // $mesIc, $mesfc se usan para traer los meses en los que deseo calcular 
         // $diasCesaPost dias calculados nuevo año 
         // dias habiles entre dos meses}
     // echo 'entro';

         $d = new AlbumTable($this->adapter);                 
         $g = new Gnominag($this->adapter);
         $datGen = $d->getConfiguraG(''); //------------- CONFIGURACIONES GENERALES (1)
         $promSubTrans = $datGen['promSubTrans'];
         $promPrimas = $datGen['promPrimas'];

            $c = new Cesantias($this->adapter);    
            // Dias entre fecha inicial y final primas cesantias 5 tipo cesantias
            $datos = $d->getDiasNomina($fechaF , 5) ;
//print_r($datos);
            //$diasCesantias = $datos['dias'];
            $idIcal = $datos['id'];
            //$fechaI = $datos['fechaI'];
            $mesI   = $datos['mesI'];   
            $fechaF = $datos['fechaC'];                                   
            $mesF   = $datos['mesC'];                                   
            $fechaCorte = $datos['fechaCorte'];
            // Bloque validacion dias calculados en el nuevo dia
            // --------------------------------------------------
            if ($mesIc > 0)
                $mesI   = $mesIc;   
            if ($mesFc > 0)
                $mesF   = $mesFc; 
            // Bloque validacion dias calculados en el nuevo dia
            // --------------------------------------------------

            $datos = $g->getDiasCesa( $idEmp , $id , $fechaIcesantias ); 
            //echo print_r($datos);
            foreach ($datos as $datoC)
            {              
                $idEmp = $datoC['idEmp'];
                $idTnom = $datoC['idTnom']; // 7 Interes / 3 cesantias 

                //$diasCesantias = $datoC['diasCes']; 
                $variable = $datoC['variable']; // Sueldo variable  

                if ($diasCesantiasNuevo>0) // Dias cesantias del periodo del año siguiente 
                    $diasCesantias = $diasCesantiasNuevo;      
               $diasAus = 0;
                // Buscar ausentismos solo cuando no se usa el promedio 
                if ($promPrimas == 0)
                {      
                   $datAus = $d->getAusentismosRegimenAnt($idEmp );
                   $diasAus = 0;
                   if ($datAus['dias']>0) # ausentismos regimen anterior
                   {
                      $diasCesantias = $diasCesantias - $datAus['dias'];  
                      $diasAus = $datAus['dias'];
                      //promedio del año regimen anterior
                      $datAusCurrent = $d->getAusentismosDias($idEmp, $fechaIcesantias, $fechaF );
                      if ($datAusCurrent['dias']>0) # dias de ausentiesmo en el año
                      {
                         $diasPromedio = $diasPromedio - $datAusCurrent['dias'];   
                      }
                   }else
                   { # ausentismos regimen nuevo
                      $datAus = $d->getAusentismosDias($idEmp, $fechaIcesantias, $fechaF );
                      if ($datAus['dias']>0) # Si dias primas modificadas en la liquidacion es mayor a cero se toman esas
                      {
                         $diasCesantias = $diasCesantias - $datAus['dias'];  
                         $diasPromedio = $diasPromedio - $datAus['dias'];   
                         $diasAus = $datAus['dias'];                         
                      }
                   }                         
                } // Fin validacion promedio para buscar ausentiemos  
                else
                {

                      $datAus = $this->getDiasContrato( $idEmp, $fechaIcesantias, $fechaF );
                      if ($datAus['diasAusNomRem']>0) # Si dias primas modificadas en la liquidacion es mayor a cero se toman esas
                      {
                         //$diasCesantias = $diasCesantias - $datAus['diasAusRem'];  
                         //$diasPromedio = $diasPromedio - $datAus['diasAusRem'];   
                         $diasAus = $datAus['diasAusNomRem'];

                      }                  
                } 
                // Verificar fecha del aumento de sueldo del empleados
                //$datFec = $d->getAsalariaF($idEmp, $fechaF); 
                $tipC = 0;
                //if ($idEmp==2038)
                  // echo $diasPromedio.' dob <br />';

                $dato = $g->getCesantiasS($idEmp, $fechaIcesantias , $fechaF, ($diasCesantias - $diasAus), $fechaAnAnt, $promSubTrans, $diasPromedio );

                   $tipC = 1;
                   
                   $tipC = 2;
                //}   
//if ($idEmp == 21063)
  // echo 'VALOR CESAS : '.round( $dato["valorCesantias"], 2).' - '.$diasCesantias.' - '.$diasPromedio.' ('.$fechaIcesantias.' - '.$fechaF.') <br />'; // 

                   $valorCesantias = round( $dato["valorCesantias"], 2); // Buscar subdisio de transporte
                   $promCesantias = round( $dato["promedioCesantias"], 2); // promerdio
                   //echo '----------------------- Cesantias <br />';                                               
                         
                   $id      = $datoC['idNom'];  // Id dcumento de novedad 
                   $iddn    = $datoC['id'];  // Id dcumento de novedad
                   $idin    = 0;     // Id novedad
                   $ide     = $idEmp;   // Id empleado
                   //$diasLab = $datoC['diasCes'];    // Dias laborados 
                   $diasLab = $diasCesantias;    // Dias laborados                    
                   $horas   = 0;   // Horas laborados 
                   $diasVac = 0;    // Dias vacaciones
                   $formula = ''; // Formula
                   $tipo    = $datoC["tipo"];    // Devengado o Deducido  
                   $idCcos  = $datoC["idCcos"];  // Centro de costo   
                   $regimen  = $dato["regimen"];  // Centro de costo   
                   $idCon   = 213;   // Concepto
                   //$idCon   = $datoC["idCon"];   // Concepto
                   // Buscar anticipos de cesantias 
                   $datAnt = $d->getAntCesantias($id, $idEmp );
                   $valAnt = 0;
                   $valInt = 0;
                   if ( $idTnom != 10 )// Solo cuando no sea nomina de consolidad
                   {
                      if ($datAnt['valor']>0) # Si dias primas modificadas en la liquidacion es mayor a cero se toman esas
                      {
                         $valAnt = $datAnt['valor'];
                         $valInt = $datAnt['interes'];
                      }   
                   }                   
                   //echo 'Anto ant : '.$valAnt.'<br />';
                   $dev     = $valorCesantias - $valAnt;   // Devengado
                   $valorCesantiasReal = $dev;
                   $ded     = 0;     // Deducido         
                   $idfor   = '';   // Id de la formula    
                   $diasLabC= 0;   // Dias laborados solo para calculados 
                   $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
                   $obId    = 1; // 1 para obtener el id insertado
                   $fechaEje  = 0;
                   $idProy  = 0;
                   //echo 'Cesantias guardar $ '.$dev.'<br />';
                   // Llamado de funion -------------------------------------------------------------------
                   $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,1,$conVac,$obId, $fechaEje, $idProy );              
                   $idInom = (int) $idInom;                   
                   $deta = "(".$diasCesantias.")";
                   if ($diasAus>0)
                       $deta = $deta."-(".$diasAus.'aus)';
                   $fechaDeta = $fechaIcesantias;   
                   if ($fechaIngre > $fechaIcesantias)
                       $fechaDeta = $fechaIngre;

                   $d->modGeneral("update n_nomina_e 
                              set baseCesantias = ".$dev ." where id=".$iddn);
                   //$d->modGeneral("update n_nomina_e 
                     //         set ausCesantias = ".$diasAus." where id=".$iddn);

                   //$d->modGeneral("update n_nomina_e 
                     //         set diasCesantias = ".$diasCesantias.",
                       //           diasPromCesa = ".$diasPromedio.",
                         //         ausCesantias = ".$diasAus." where id=".$iddn);                   

                      $d->modGeneral("update n_nomina_e_d 
                              set detalle='CESANTIAS ( Prom ".number_format($promCesantias).") - Dias ".$deta."'   where id=".$idInom);
                    // Buscar si tiene cesantias 
                    $datHist = $d->getGeneral1("select sum( a.valor ) as valor from n_cesantias_anticipos a where year( a.fecDoc ) = year( now() ) and a.idEmp = ".$ide);
                    if ( ($datHist['valor'] > 0) and ( $idTnom != 10 ) )
                    {
                       // $d->modGeneral("update n_nomina_e_d 
                       //       set devengado = devengado - ".$datHist['valor'].",
                       //          detalle='CESANTIAS (".$valorCesantias.") - Pagos [".$valAnt."] -> $ ".number_format(($valorCesantias - $valAnt) ,0)." Dias ".$deta."'    where id=".$idInom);
                    }
                    // Buscar pago realizado año anterior en consolidacion de cesantias
                    $datHist = $d->getGeneral1("select count(a.id) as num , b.devengadoAnt as valor 
                                    from n_nomina_e a  
                                       inner join n_nomina_e_d b on b.idInom = a.id   
                                       inner join n_nomina c on c.id = a.idNom and idTnom = 10 
                                 where b.idConc = 213 and year(c.fechaF) = year( now() ) - 1 
                                    and a.idNom != ".$id." and a.idEmp =".$ide);
                    if ( $datHist['num'] > 20000 )
                    {
                        $dev = $datHist['valor'];
                        $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,1,$conVac,$obId, $fechaEje, $idProy );              
                        $idInom = (int) $idInom;                                                             
                        $d->modGeneral("update n_nomina_e_d 
                              set detalle='CESANTIAS (2016-01-01-2016-12-31)'    where id=".$idInom);
                    }

                    if ($idTnom==7)
                    {
                      //  $d->modGeneral("update n_nomina_e_d 
                      //        set devengado = 0 where id=".$idInom);

                    }               
                    // Buscar ceasntias editadas
                    $datCesAnt = $d->getGeneral1("select a.devengado from n_nomina_nov a where idGrupo = 99 and idEmp = ".$ide." and idConc = 213 "); 
                    //print_r($datCesAnt) ;
                    if ($datCesAnt['devengado']>0)     
                        $valorCesantiasReal = $datCesAnt['devengado'];

                   // INTERESE DE CENSATIAS 
                    if ($regimen == 0)
                    {
                        $dev     = ( ( $valorCesantiasReal * ( 12/100 ) )/360 ) * $diasCesantias; // Devengado
                    }
                    else 
                    {
                        $dev     = ( ( $valorCesantiasReal * ( 12/100 ) ) ) ; // Devengado
                    }

                   $idCon   = 195; //
                   $obId    = 1; // 1 para obtener el id insertado
                   //echo '----------- Ineteres de cesantias <br                                                                                   
                   if ($valorCesantias > 0)
                   {
                       // Llamado de funion -------------------------------------------------------------------
                       $dev = $dev - $valInt;
                       $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,1,$conVac,$obId, $fechaEje, $idProy);                             
                       $idInom = (int) $idInom;                   
                       $d->modGeneral("update n_nomina_e_d 
                              set detalle='INT. DE CESANTIAS (".$fechaDeta."-".$fechaF.")' where id=".$idInom);

                       // Buscar si tiene cesantias 
                       $datHist = $d->getGeneral1("select sum( a.interes ) as valor from n_cesantias_anticipos a where year( a.fecDoc ) = year( now() ) and a.idEmp = ".$ide);
                       if ( ( $datHist['valor'] > 0 ) and ( $idTnom != 10 ) )
                       {
                           $d->modGeneral("update n_nomina_e_d 
                              set devengado = devengado - ".$datHist['valor'].",   
                                detalle='INT. DE CESANTIAS (".$fechaDeta."-".$fechaF.") -ant ' where id=".$idInom);                        
                       }
                    // Buscar pago realizado año anterior en consolidacion de cesantias
                    $datHist = $d->getGeneral1("select count(a.id) as num , b.devengado as valor 
                                    from n_nomina_e a  
                                       inner join n_nomina_e_d b on b.idInom = a.id   
                                       inner join n_nomina c on c.id = a.idNom and idTnom = 10 
                                 where b.idConc = 195 and year(c.fechaF) = year( now() ) - 1 
                                      and a.idNom != ".$id." and a.idEmp =".$ide);
                    if ( $datHist['num'] > 0 )
                    {
                        $dev = $datHist['valor'];
                        //$idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,1,$conVac,$obId, $fechaEje, $idProy );              
                        //$idInom = (int) $idInom;                                                             
                        //$d->modGeneral("update n_nomina_e_d 
                          //    set detalle='INT CESANTIAS  (2016-01-01-2016-12-31)'    where id=".$idInom);
                    }                                           
                       // Si es solo nomina de cesantias no incluye interes
                       if ($idTnom==3)
                       {
                          // $d->modGeneral("update n_nomina_e_d 
                          //    set devengado = 0 where id=".$idInom);

                       } 

                       // REGISTRO LIBRO DE CESANTIAS                   
                      // $c->actRegistro($ide, 213, 195, $fechaI, $fechaF, $diasLab, 0, $base, $valor, $dev , $idInom , $id);
                   }
                //} 
              } //-------- FIN CESANTIAS
    }

    // Generacion de Primas 
    public function getPrimasInt($fechaF, $fechaAnAnt , $idEmp, $idg, $id, $fechaIprimas, $mesIc, $mesFc, $diasPrimas, $tipo, $diasPromedio)
    {
         // $mesIc, $mesfc se usan para traer los meses en los que deseo calcular 
         // $diasCesaPost dias calculados nuevo año 
         // dias habiles entre dos meses}
     // echo 'entro';
         $d = new AlbumTable($this->adapter);                 
         $g = new Gnominag($this->adapter);

            $c = new Cesantias($this->adapter);    
            // Dias entre fecha inicial y final primas cesantias 5 tipo cesantias
            $datos = $d->getDiasNomina($fechaF , 5) ;
//print_r($datos);
            //$diasCesantias = $datos['dias'];
            $idIcal = $datos['id'];
            //$fechaI = $datos['fechaI'];
            $mesI   = $datos['mesI'];   
            $fechaF = $datos['fechaC'];                                   
            $mesF   = $datos['mesC'];                                   
            $fechaCorte = $datos['fechaCorte'];
            // Bloque validacion dias calculados en el nuevo dia
            // --------------------------------------------------
            if ($mesIc > 0)
                $mesI   = $mesIc;   
            if ($mesFc > 0)
                $mesF   = $mesFc; 
            // Bloque validacion dias calculados en el nuevo dia
            // --------------------------------------------------
//echo 'mes final '.$mesI.'-'.$mesF.'<br />';

            $datos = $g->getDiasCesa( $idEmp , $id , $fechaIprimas ); 
            //echo print_r($datos);
            foreach ($datos as $datoC)
            {            
                   $id      = $datoC['idNom'];  // Id dcumento de novedad 
                   $iddn    = $datoC['id'];  // Id dcumento de novedad
                   $idin    = 0;     // Id novedad
                   $idfor   = '';   // Id de la formula    
                   $diasLabC= 0;   // Dias laborados solo para calculados 
                   $conVac  = 0;   // Determinar si en caso de vacaciones formular con dias calendario
                   $obId    = 1; // 1 para obtener el id insertado
                   $fechaEje  = 0;
                   $idProy  = 0;
                   $idEmp = $datoC['idEmp'];
                   $idCcos = $datoC['idCcos'];
                   $ded = 0;
                   $diasLab = $diasPrimas;    // Dias laborados                    
                   $diasVac = 0; 
                   $horas = 0; 
                   $formula = ''; 
                   $ide     = $idEmp;   // Id empleado                              

                // Buscar ausentismos
                $datAus = $d->getAusentismosDias($idEmp, $fechaIprimas, $fechaF );
                $diasAus = 0;
                if ($datAus['dias']>0) # Si dias primas modificadas en la liquidacion es mayor a cero se toman esas
                {
                    //$diasAus = $datAus['dias'];
                }                

                //$diasCesantias = $datoC['diasCes']; 
                   $variable = $datoC['variable']; // Sueldo variable  
                   $deta = "(".$diasPrimas.")";
                       // PRIMAS PARA LIQUIDACION FINAL 
//echo 'DIAS PRIMAS'.$diasPrimas.'  promedio : '.$diasPromedio.'<br />';
//echo 'Fecha I'.$fechaIprimas.' fechw F '.$fechaF.'<br />';
                  
                          $datPr = $g->getPrimasS($idEmp, $fechaIprimas , $fechaF, $diasPrimas, $diasPromedio, $diasAus);
                          $dev     = $datPr['valorPrimas']; // Por revisar y desarrollar
                        
  //                      echo 'VALOR PRIMAS'.$datPr['valorPrimas'].'<br />';
                          $idCon   = 214; //                        
                          // Llamado de funion -------------------------------------------------------------------
                          $idInom = $this->getNomina($id, $iddn, $idin, $ide ,$diasLab, $diasVac ,$horas ,$formula ,$tipo ,$idCcos , $idCon, 0, 0,$dev,$ded,$idfor,$diasLabC,0,1,$conVac,$obId, $fechaEje, $idProy);                             
                          $idInom = (int) $idInom;                   
                          $d->modGeneral("update n_nomina_e_d 
                              set detalle='PRIMAS ( ".$fechaIprimas." - ".$fechaF." ) - ".$deta."'  where id=".$idInom);                   
                       }
                       // REGISTRO LIBRO DE CESANTIAS                   
                      // $c->actRegistro($ide, 213, 195, $fechaI, $fechaF, $diasLab, 0, $base, $valor, $dev , $idInom , $id);
   } //-------- FIN PRIMAS


   // dias laborados 
   public function getDiasContrato($idEmp, $fechaI, $fechaF) 
   {
      $result=$this->adapter->query("select ( 
(( (
      TIMESTAMPDIFF(month, case when CAST(DAYOFMONTH(d.fechaI) AS UNSIGNED) >1 
        then DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(d.fechaI)-1) DAY) 
           else d.fechaI end, '".$fechaF."' ))
           
      + 1)  * 30) - (case when CAST(DAYOFMONTH(d.fechaI) AS UNSIGNED) >1 then CAST(DAYOFMONTH(d.fechaI) - 1 AS UNSIGNED) else 0 end) )  as diasContrato, 


 d.fechaI as fechaIngreso, b.regimen ,
 # Dias laborados aÃ±o en curso 

 (

select ifnull( sum( bb.dias + bb.diasI + bb.diasVac ), 0 ) 

from n_nomina aa 
   inner join n_nomina_e bb on bb.idNom = aa.id     
  where aa.fechaI >= 

  ( case when (select x.fechaI from n_emp_contratos x where x.idEmp = b.id and x.tipo=1 ) >= '".$fechaI."' then 
    (select case when DAYOFMONTH(x.fechaI)>15 then DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(x.fechaI)-16) DAY) else DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(x.fechaI) - 1) DAY)  end from n_emp_contratos x where x.idEmp = b.id and x.tipo=1 )
  else 
     '".$fechaI."' end ) 
      
  and aa.idTnom in (1,5)  and aa.fechaF <= '".$fechaF."' and  bb.idEmp = b.id 

    ) as diasLabor ,
    ( 
  select  ifnull( sum(au.dias) , 0 )
  from n_nomina aa 
     inner join n_nomina_e bb on bb.idNom = aa.id   
     inner join n_nomina_e_a au on au.idNom = aa.id and au.idEmp = bb.idEmp 
           inner join n_ausentismos da on da.id = au.idAus 
           inner join n_tip_aus ta on ta.id = da.idTaus        
    where ta.tipo = 2 and aa.fechaI >= 

    ( case when (select x.fechaI from n_emp_contratos x where x.idEmp = b.id and x.tipo=1 ) >= '".$fechaI."' then 
      (select case when DAYOFMONTH(x.fechaI)>15 then DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(x.fechaI)-16) DAY) else DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(x.fechaI) - 1) DAY)  end from n_emp_contratos x where x.idEmp = b.id and x.tipo=1 )
    else 
       '".$fechaI."' end ) 
        
    and aa.idTnom in (1,5)  and aa.fechaF <= '".$fechaF."' and  bb.idEmp = b.id 

      )  as diasAusNomRem,
    ( 
  select  ifnull( sum(au.dias) , 0 )
  from n_nomina aa 
     inner join n_nomina_e bb on bb.idNom = aa.id   
     inner join n_nomina_e_a au on au.idNom = aa.id and au.idEmp = bb.idEmp 
           inner join n_ausentismos da on da.id = au.idAus 
           inner join n_tip_aus ta on ta.id = da.idTaus        
    where ta.tipo = 1 and aa.fechaI >= 

    ( case when (select x.fechaI from n_emp_contratos x where x.idEmp = b.id and x.tipo=1 ) >= '".$fechaI."' then 
      (select case when DAYOFMONTH(x.fechaI)>15 then DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(x.fechaI)-16) DAY) else DATE_ADD(d.fechaI,INTERVAL - (DAYOFMONTH(x.fechaI) - 1) DAY)  end from n_emp_contratos x where x.idEmp = b.id and x.tipo=1 )
    else 
       '".$fechaI."' end ) 
        
    and aa.idTnom in (1,5)  and aa.fechaF <= '".$fechaF."' and  bb.idEmp = b.id 

      )  as diasAusRem                    
 

                    from a_empleados b 
                                            inner join n_emp_contratos d on d.idEmp = b.id and d.tipo = 1 
                                        where b.id = ".$idEmp ,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();      
      return $datos;
   } // Fin dias laborados 

   // Salario integral 
   public function getSalarioIntegral($idEmp)
   {
      $result=$this->adapter->query("select a.integral as valor 
                from a_empleados a 
                   where id = ".$idEmp,Adapter::QUERY_MODE_EXECUTE);
      $datos = $result->current();
      return $datos;
   }   
}




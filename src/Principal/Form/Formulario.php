<?php
/**
 * Standar formularios NISSI
 * @copyright 2013
 */
namespace Principal\Form;

use Zend\Captcha; 
use Zend\Form\Form;
use Zend\Form\Element;
use Zend\Form\Factory;

// CAMPOS GENERICOS *-----------------------------------------------------
// FECHA DOCUMENTO
// FECHA INICIAL
// FECHA FINAL 
// ID GENERICO
// ID GENERICO 2
// ID GENERICO 3
// ID GENERICO 4
// CEDULA
// NOMBRE GENERICO
// TELEFONOS
// CODIGO
// DIRECCION GENERICO
// TIPO GENERICO
// TIPO GENERICO MULTIPLE
// TIPO GENERICO 2
// TIPO GENERICO 3
// NUMERO GENERICO
// VALOR
// VALOR SEPARADOR DE MILES
// VALOR MATRICES 
// COMENTARIOS
// COMENTARIOS SIN EDITOR
// ENVIAR GENERICO
// AGREGAR ITEMS GENERICO
// AGREGAR NOVEDAD
// GENERAR REPORTE
// CERRAR PROCESO
// GENERAR NOMINA
// VALOR O FORMULA
// BUSCAR 
// CHECK 1
// CHECK 2
// CHECK 3
// GENERAR MINI
// LINK
// ROLES
// PASSWORD 1
// PASSWORD 2
// SEXO
// ESTADO CIVIL
// EMAIL
// SUBIR ARCHIVO
// ENVIAR ARCHIVO 
// VALDIACION DEL PERIODO A GUARDAR
 
       
// CAMPOS DIRECTOS *-----------------------------------------------------

// NOMBRE 1
// NOMBRE 2
// APELLIDO 1
// APELLIDO 2
// OCUPACION 
// DENOMINACION
// NIVEL DEL ASPECTO
// CARGOS
// RESPONSABILIDADES
// MISION
// LISTADO DE DEPARTAMENTOS
// LISTADO DE DEPARTAMENTOS MULTI 
// LISTADO DE GRUPO DE DOTACIONES
// SEDES
// ESTADO INICIAL DE DOCUMENTOS
// CERRAR EMPLEADOS
// SUELDO
// ID SALUD
// ID PENSION
// ID ARP
// ID CESANTIAS
// ID CAJA DE COMPENSACION
// GRUPOS
// GRUPOS MULTIPLE
// SUBGRUPOS
// CALENDARIO DE NOMINA
// CALENDARIO DE NOMINA MULTIPLE
// CENTRO DE COSTO
// CENTRO DE COSTO BUSCAR
// CENTRO DE COSTOS MULTI
// FONDO DE APORTES VOLUNTARIOS
// FONDO DE APORTES AFC
// HORA
// FORMULA
// TIPO DE AUTOMATICOS
// TIPO DE AUTOMATICOS 2
// TIPO DE AUTOMATICOS 3
// TIPO DE AUTOMATICOS 4
// CONCEPTOS
// CONCEPTOS MUTIPLES
// TIPOS DE NOMINA
// TIPOS DE NOMINA MULTIPLE
// CHECK HORAS 
// FECHA INICIO CONTRATO
// TIPO DE EMPLEADO
// ALIAS 
// VALORES EN VARIABLE 
// BUSCAR EMPLEADO
// OPCION DE MODULO (MENUES)
// DIAS
// DEVENGADO
// DEDUCIDO
// AUSENTISMO
// INCAPACIDADES
// NUMERO CUOTAS
// CUOTAS
// FECHA DE INGRESO
// MESES
// SEXO
// NIT
// TIPOS DE FONDOS
// CAMPOS CONSULTAS
// COLUMNAS
// FILTROS
// DETALLE CONS
// DIAS PAGO VACACIONES
// DIAS PAGO VACACIONES PENDIENTE       
// TIPOS DE PRESTAMOS
// ESCALA SALARIAL DEL CARGO
// NIVEL DE ESTUDIOS
// TIPO DE SANGRE
// AREA DE CAPACITACIONES
// ESTATURA
// INSTITUCION
// PARENTESCO
// BANCOS
// FORMA DE PAGO

class Formulario extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('album');
        $this->setAttribute('method', 'post');   
        $this->setAttribute('enctype','multipart/form-data');
        
        // CAMPOS GENERICOS --------------------------------------------------------------------------------
        // ID GENERICO
        $this->add(array(
            'name' => 'id',            
            'attributes' => array(
                'type'  => 'hidden',
                'id'   => 'id',
            ),
        ));        
        // ID GENERICO 2
        $this->add(array(
            'name' => 'id2',            
            'attributes' => array(
                'type'  => 'hidden',
                'id'   => 'id2',
            ),
        ));                
        // ID GENERICO 3
        $this->add(array(
            'name' => 'id3',            
            'attributes' => array(
                'type'  => 'hidden',
                'id'   => 'id3',
            ),
        ));                        
        // ID GENERICO 4
        $this->add(array(
            'name' => 'id4',            
            'attributes' => array(
                'type'  => 'hidden',
                'id'   => 'id4',
            ),            
        ));        
        // CEDULA
        $this->add(array(
            'name' => 'cedula',            
            'attributes' => array(
                'type'  => 'text',
                'id'   => 'cedula',
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Cedula'
            ),                        
        ));                
        // FECHA DOCUMENTO
        $this->add(array(
            'name' => 'fecDoc',            
            'attributes' => array(
                'type'  => 'Date',
                'id'   => 'fecDoc',     
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Fecha del documento'
            ),            
        ));  

        // DOCUMENTO
        $this->add(array(
            'name' => 'docRef',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Documento de referencia',
            ),
        ));                        
        // VALDIACION DEL PERIODO A GUARDAR
        $this->add(array(
            'name' => 'verPer',            
            'attributes' => array(
                'type'  => 'text',
                'id'   => 'verPer',
            ),
            'options' => array(
                'label' => 'Cedula'
            ),                        
        ));                
        // FECHA INICIO
        $this->add(array(
            'name' => 'fechaIni',            
            'attributes' => array(
                'type'  => 'Date',
                'requerid'  => 'requerid',
                'id'   => 'fechaIni',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Dese el:'
            ),            
        ));          
        // FECHA DE NACIMIENTO
        $this->add(array(
            'name' => 'fechaNac',            
            'attributes' => array(
                'type'  => 'Date',
                'requerid'  => 'requerid',
                'id'   => 'fechaNac',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Dese el:'
            ),            
        ));                  
        // FECHA INI
        $this->add(array(
            'name' => 'fechaIni',            
            'attributes' => array(
                'type'  => 'Date',
                'requerid'  => 'requerid',
                'id'   => 'fechaIni',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Desde el:'
            ),            
        ));                  
        // FECHA FIN
        $this->add(array(
            'name' => 'fechaFin',            
            'attributes' => array(
                'type'  => 'Date',
                'requerid'  => 'requerid',
                'id'   => 'fechaFin',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Hasta el:'
            ),            
        ));          
        // FECHA INICIO
        $this->add(array(
            'name' => 'fecha',            
            'attributes' => array(
                'type'  => 'Date',
                'requerid'  => 'requerid',
                'id'   => 'fecha',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Fecha:'
            ),            
        ));                          
        // NUMERO GENERICO
        $this->add(array(
            'name' => 'numero',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // NUMERO GENERICO 1
        $this->add(array(
            'name' => 'numero1',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // NUMERO GENERICO 2
        $this->add(array(
            'name' => 'numero2',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        

        // AÑOS
        $this->add(array(
            'name' => 'ano',
            'attributes' => array(
                'type'  => 'numeric',
            ),
            'options' => array(
                'label' => '',
                'id'    => 'ano'
            ),
        ));                
        // VALOR
        $this->add(array(
            'name' => 'valor',
            'attributes' => array(
                'type'   => 'text',
                'id'     => 'valor',
                'class'  => 'span3',
            ),
            'options' => array(
                'label' => 'Numero',
            ),
        ));                
        // VALOR REQUERIDO
        $this->add(array(
            'name' => 'valorR',
            'attributes' => array(
                'type'   => 'number',
                'id'     => 'valor',
                'requerid'  => 'requerid',
                'class'  => 'span3',
            ),
            'options' => array(
                'label' => 'Numero',
            ),
        ));                        
        // VALOR SEPARADOR DE MILES
        $this->add(array(
            'name' => 'valorS',
            'attributes' => array(
                'type'   => 'numeric',
                'id'     => 'valorS',
            ),
            'options' => array(
                'label' => 'Valor',
            ),
        ));                        
        // NOMBRE GENERICO
        $this->add(array(
            'name' => 'nombre',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Nombre',
            ),
        ));                
        // NOMBRE GENERICO 2
        $this->add(array(
            'name' => 'nombre2',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Nombre',
            ),
        ));                        
        // NOMBRE GENERICO 3
        $this->add(array(
            'name' => 'nombre3',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Nombre',
            ),
        ));                                
        
        // TELEFONOS
        $this->add(array(
            'name' => 'telefonos',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class'    => 'form-control',
            ),
            'options' => array(
                'label' => 'Telefonos',
            ),
        ));                        
        // TELEFONOS 2
        $this->add(array(
            'name' => 'telefonos2',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Telefonos',
            ),
        ));                                
        // TELEFONOS 3
        $this->add(array(
            'name' => 'telefonos3',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Telefonos',
            ),
        ));                                        
        // CODIGO
        $this->add(array(
            'name' => 'codigo',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Codigo de referencia',
            ),
        ));                        
        // DIRECCION GENERICO
        $this->add(array(
            'name' => 'dir',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class' => 'form-control',
            ),
            'options' => array(
                'label' => 'Direccion',                
            ),
        ));
        // DIRECCION GENERICO
        $this->add(array(
            'name' => 'dir2',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
            ),
            'options' => array(
                'label' => 'Direccion',
            ),
        ));        
        // DIRECCION GENERICO
        $this->add(array(
            'name' => 'dir3',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
            ),
            'options' => array(
                'label' => 'Direccion',
            ),
        ));                
        // TIPO GENERICO 
        $select = new Element\Select('tipo');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'tipo');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);              
        // TIPO GENERICO 1
        $select = new Element\Select('tipo1');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                      
        // TIPO GENERICO 2
        $select = new Element\Select('tipo2');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                         
        // TIPO GENERICO 3
        $select = new Element\Select('tipo3');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                                  
        // TIPO GENERICO 4
        $select = new Element\Select('tipo4');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                                  
        // TIPO GENERICO 5
        $select = new Element\Select('tipo5');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                                          

        // TIPO GENERICO 6
        $select = new Element\Select('tipo6');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                                                  
        // TIPO GENERICO 7
        $select = new Element\Select('tipo7');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                                                          
        // TIPO GENERICO MULTIPLE
        $select = new Element\Select('tipoM');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'tipoM');
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                      
        // TIPO GENERICO SIN CLASE
        $select = new Element\Select('tipoC');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'tipoC');
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);              

        // TIPO GENERICO SIN CLASE 2
        $select = new Element\Select('tipoC2');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'tipoC');
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);              

        // TIPO GENERICO SIN CLASE
        $select = new Element\Select('tipoS');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'tipoC');
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);              

        // OPCION DE MODULO (MENUES)        
        $select = new Element\Select('idM');
        $select->setLabel('Modulo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idM');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione opcion...'); // Agregar en el controlador las opciones
        $this->add($select);                      
        
        // TIPO INFORMES
        $select = new Element\Select('tipoI');
        $select->setLabel('Modulo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'tipoI');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione opcion...'); // Agregar en el controlador las opciones
        $this->add($select);                              
        
        // COMENTARIOS
        $this->add(array( 
            'name' => 'comen', 
            'type' => 'textarea', 
            'attributes' => array( 
                'class'    => 'ckeditor',
                'id'       => 'comen',
            ), 
            'options' => array( 
                'label' => 'Comentarios',
            ), 
        ));                         
        // COMENTARIOS SIN EDITOR
        $this->add(array( 
            'name' => 'comenN', 
            'type' => 'textarea', 
            'attributes' => array( 
                'class'    => 'form-control',
                'id'       => 'comenN', 
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                  
        // COMENTARIOS SIN EDITOR
        $this->add(array( 
            'name' => 'comenN12', 
            'type' => 'textarea', 
            'attributes' => array( 
                'class'    => 'form-control',
                'id'       => 'comenN', 
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                 
        // COMENTARIOS SIN EDITOR
        $this->add(array( 
            'name' => 'comenN11', 
            'type' => 'textarea', 
            'attributes' => array( 
                'class'    => 'form-control',
                'id'       => 'comenN', 
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                   
        // COMENTARIOS CON EDITOR n2
        $this->add(array( 
            'name' => 'cabezado', 
            'type' => 'textarea', 
            'attributes' => array( 
                'class'    => 'ckeditor',
                'id'       => 'cabezado',
            ), 
            'options' => array( 
                'label' => 'Documento',
            ), 
        ));                                                 
        // COMENTARIOS SIN EDITOR 2
        $this->add(array( 
            'name' => 'comenN2', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'form-control',
                'id'       => 'comenN2', 
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                         
        // COMENTARIOS SIN EDITOR 3
        $this->add(array( 
            'name' => 'comenN3', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN3', 
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                 
        // ENVIAR GENERICO
        $this->add(array(
            'name' => 'send',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Actualizar datos',
                'id'    => 'submitbutton',
                'class' => 'btn btn-info'
            ),
        ));
        // ORDENAR
        $this->add(array(
            'name' => 'orden',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Ordenar',
                'id'    => 'submitbutton',
                'class' => 'btn btn-sm btn-danger'
            ),
        ));        
        // CONTRATAR
        $this->add(array(
            'name' => 'contratar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'CONTRATAR',
                'id'    => 'submitbutton',
                'class' => 'btn btn-sm btn-danger'
            ),
        ));                
        // AGREGAR ITEMS GENERICO
        $this->add(array(
            'name' => 'agregar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Agregar items',
                'id' => 'submitbutton',
                'class' => 'btn btn-purple'
            ),
        ));
        // GENERAR REPORTE        
        $this->add(array(
            'name' => 'reporte',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Generar consulta',
                'id' => 'reporte',
                'class' => 'btn btn-white btn-info btn-bold'
            ),
        ));         
        // GENERAR NOMINA
        $this->add(array(
            'name' => 'GenerarN',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Generar nomina',
                'id' => 'generarnom',
                'class' => 'btn btn-purple'
            ),
        ));                 
        // GENERAR PLANILLA
        $this->add(array(
            'name' => 'GenerarP',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Generar planilla',
                'id' => 'generarnom',
                'class' => 'btn btn-purple'
            ),
        ));                         
        // AGREGAR NOVEDAD
        $this->add(array(
            'name' => 'agregarnov',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Agregar novedad',
                'id' => 'submitbutton',
                'class' => 'btn btn-mini btn-danger'
            ),
        ));        
        // GENERAR MINI
        $this->add(array(
            'name' => 'generarM',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Generar',
                'id' => 'generarnom',
                'class' => 'btn btn-mini btn-info'
            ),
        ));                         
        // BUSCAR
        $this->add(array(
            'name' => 'buscar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Buscar',
                'id' => 'submitbutton',
                'class' => 'btn btn-white btn-info btn-bold'
            ),
        ));        
        // BUSCAR
        $this->add(array(
            'name' => 'calcular',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Calcular',
                'id' => 'submitbutton',
                'class' => 'btn btn-white btn-info btn-bold'
            ),
        ));                
        // BUSCAR 2
        $this->add(array(
            'name' => 'agregar2',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Agregar',
                'id' => 'agregar2',
                'class' => 'btn btn-white btn-info btn-bold'
            ),
        ));        
        // GUARDAR
        $this->add(array(
            'name' => 'guardar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Guardar',
                'id' => 'guardar',
                'class' => 'btn btn-white btn-info btn-bold'
            ),
        ));        
        // CERRAR PROCESO
        $this->add(array(
            'name' => 'cerrar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Cerrar proceso',
                'id' => 'submitbutton',
                'class' => 'btn btn-danger'
            ),
        ));        
        // CERRAR EMPLEADOS
        $this->add(array(
            'name' => 'crear',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Crear empleado',
                'id' => 'submitbutton',
                'class' => 'btn btn-primary'
            ),
        ));   
        // COMFIRMAR
        $this->add(array(
            'name' => 'confirmar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Confirmar',
                'id' => 'confirmar',
                'class' => 'btn btn-white btn-purple'
            ),
        ));        
        // REGISTRAR
        $this->add(array(
            'name' => 'registrar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Registrar',
                'id' => 'guardar',
                'class' => 'btn btn-white btn-purple btn-sm'
            ),
        ));           
        // ENVIAR EMAIL
        $this->add(array(
            'name' => 'envioEmail',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'enviar e-mail',
                'id' => 'envioEmail',
                'class' => 'btn btn-white btn-purple btn-sm'
            ),
        ));                                             
        // CERRAR CASO
        $this->add(array(
            'name' => 'cerrar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Cerrar caso',
                'id' => 'cerrar',
                'class' => 'btn btn-white btn-danger btn-sm'
            ),
        ));                                
        // CHECK 1
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check1',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // CHECK 2
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check2',
           'attributes' => array('id'=>'check2', 'class' => 'check2' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));                
        // CHECK 3
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check3',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));    
        // CHECK 4
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check4',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));            
        // CHECK 5
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check5',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));  
        // CHECK 6
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check6',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));                 
        // CHECK 7
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check7',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));                     
        // CHECK 8
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check8',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));  
        // CHECK 9
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check9',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        )); 
        // CHECK 10
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check10',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));                                                                      
        // CHECK 11
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check11',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));    
        // CHECK 12
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'check12',
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
               'checked_value' => '1',
               'unchecked_value' => '0'
           )
        ));                                                                                                                                                                      
        // LINK
        $this->add(array( 
            'name' => 'link', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
            ), 
            'options' => array( 
                'label' => 'Link',
            ), 
        ));                                 
        // PASWORD 1
        $this->add(array(
            'name' => 'clave1',            
            'attributes' => array(
                'type'  => 'password',
                'id'   => 'clave',
            ),
            'options' => array(
                'label' => 'Clave'
            ),                        
        ));                
        // PASWORD 2
        $this->add(array(
            'name' => 'clave2',            
            'attributes' => array(
                'type'  => 'password',
                'id'   => 'clave2',
            ),
            'options' => array(
                'label' => 'Confirmar clave'
            ),                        
        ));                        
        $this->add(array(
            'name' => 'clave3',            
            'attributes' => array(
                'type'  => 'text',
                'id'   => 'clave2',
            ),
            'options' => array(
                'label' => 'Confirmar clave'
            ),                        
        ));                                
        // EMAIL
        $this->add(array(
            'name' => 'email',
            'attributes' => array(
                'type'  => 'email',
                'class'    => 'form-control',
            ),
            'options' => array(
                'label' => 'e-mail',
            ),
        ));                        
        // EMAIL 2
        $this->add(array(
            'name' => 'email2',
            'attributes' => array(
                'type'  => 'email',
                'class'    => 'form-control',
            ),
            'options' => array(
                'label' => 'e-mail',
            ),
        ));                                
        // Subir archivo
        $file = new Element\File('image-file');
        $file->setLabel('-')
             ->setAttribute('id', 'image-file');
        $this->add($file);
        
        // ENVIAR ARCHIVO
        $this->add(array(
            'name' => 'enviarArchivo',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Enviar',
                'id' => 'enviarArchivo',
                'class' => 'btn btn-warning'
            ),
        ));                
        
        // CAMPOS UNICOS --------------------------------------------------------------------------------

        // NOMBRE 1
        $this->add(array(
            'name' => 'nombre1',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Primer nombre',
            ),
        ));                        
        // NOMBRE 2
        $this->add(array(
            'name' => 'nombre2',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Segundo nombre',
            ),
        ));                          
        // APELLIDO 1
        $this->add(array(
            'name' => 'apellido1',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Primer apellido',
            ),
        ));                                
        // APELLIDO 2
        $this->add(array(
            'name' => 'apellido2',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Segundo apellido',
            ),
        ));        
        // OCUPACION 
        $this->add(array(
            'name' => 'ocupacion',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => '',
            ),
        ));                
        // OCUPACION 
        $this->add(array(
            'name' => 'ocupacion2',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => '',
            ),
        ));                        
        // SUELDO
        $this->add(array(
            'name' => 'sueldo',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Sueldo',
            ),
        ));                
        // UBICACION
        $select = new Element\Select('ubicacion');
        $select->setLabel('Ubicación');
        $select->setAttribute('multiple', false);
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        
        // DENOMINACION
        $this->add(array(
            'name' => 'deno',
            'attributes' => array(
                'type'  => 'text',
                'class'    => 'form-control'
            ),
            'options' => array(
                'label' => 'Denominación',
            ),
        ));        
        // RESPONSABILIDADES
        $this->add(array(
            'name' => 'respo',
            'attributes' => array(
                'type'  => 'textarea',
                'class'    => 'ckeditor'
            ),
            'options' => array(
                'label' => 'Responsabilidades',
            ),
        ));                
        // MISION
        $this->add(array(
            'name' => 'mision',
            'attributes' => array(
                'type'  => 'textarea',
                'class'    => 'ckeditor'
            ),
            'options' => array(
                'label' => 'Misión',
            ),
        ));                        
        // CARGO
        $select = new Element\Select('idCar');
        $select->setLabel('Cargo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");        
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        // VARIOS CARGO
        $select = new Element\Select('idCarM');
        $select->setLabel('Cargo');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");        
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        
        // NIVEL DE ASPECTOS
        $select = new Element\Select('idNasp');
        $select->setLabel('Nivel de aspectos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                     
        // NIVEL DEL CARGO
        $select = new Element\Select('idNcar');
        $select->setLabel('Nivel del cargo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                     
        // LISTADO DE DEPARTAMENTOS
        $select = new Element\Select('idDep');
        $select->setLabel('Departamento');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idDep');
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                           
        // LISTADO DE DEPARTAMENTOS MULTI
        $select = new Element\Select('idDepM');
        $select->setLabel('Departamento');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'idDepM');
        $select->setAttribute('class', "chosen-select"); 
        $this->add($select);                           
        // LISTADO DE GRUPO DE DOTACIONES
        $select = new Element\Select('idGdot');
        $select->setLabel('Grupo de dotaciones');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('No aplica...'); // Agregar en el controlador las opciones
        $this->add($select);                    
        // LISTADO DE GRUPO DE DOTACIONES SIN FORMATO
        $select = new Element\Select('idGdot2');
        $select->setLabel('Grupo de dotaciones');
        $select->setAttribute('multiple', false);
        $select->setEmptyOption('Seleccione ...'); // Agregar en el controlador las opciones
        $this->add($select);                            
        // SEDES
        $select = new Element\Select('idSed');
        $select->setLabel('Sede');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);      
        // ZONAS
        $select = new Element\Select('idZon');
        $select->setLabel('Zona');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                                          
        // ID SALUD
        $select = new Element\Select('idSal');
        $select->setLabel('Salud');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        // ID PENSION
        $select = new Element\Select('idPen');
        $select->setLabel('Pensión');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        // ID ARP
        $select = new Element\Select('idArp');
        $select->setLabel('ARP');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);        
        // ID CESANTIAS
        $select = new Element\Select('idCes');
        $select->setLabel('Cesantias');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                        
        // ID CAJA DE COMPENSACION
        $select = new Element\Select('idCaja');
        $select->setLabel('Caja de compensacion familiar');
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('multiple', false);
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                
        // GRUPOS
        $select = new Element\Select('idGrupo');
        $select->setLabel('Grupo de nomina');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione un grupo...'); // Agregar en el controlador las opciones
        $this->add($select);                
        // LINEA
        $select = new Element\Select('idLin');
        $select->setLabel('Grupo de nomina');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione una linea...'); // Agregar en el controlador las opciones
        $this->add($select);                        
        // GRUPOS MULTIPLE
        $select = new Element\Select('idGrupoM');
        $select->setLabel('Grupos de nomina');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', 'idGrupoM');
        $this->add($select);                        
        // SUBGRUPOS
        $select = new Element\Select('idSubgrupo');
        $select->setLabel('Sub grupo de nomina');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                
        $select = new Element\Select('idSucursal');
        $select->setLabel('Sucursal de nomina');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                        
        // CALENDARIO DE NOMINA
        $select = new Element\Select('idCal');
        $select->setLabel('Calendario de nomina');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idCal");
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        // CALENDARIO DE NOMINA MULTIPLE
        $select = new Element\Select('idCalM');
        $select->setLabel('Calendarios de nomina');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                                
        // CENTRO DE COSTO
        $select = new Element\Select('idCencos');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");        
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                  
        // CENTRO DE COSTO 
        $select = new Element\Select('idCcos');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");        
        $this->add($select);                                          
        
        $select = new Element\Select('idCcosM1');
        $select->setLabel('');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");      
        $this->add($select);                                            
        $select = new Element\Select('idCcosM2');
        $select->setLabel('');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");      
        $this->add($select);                                            

        // CENTRO DE COSTO VARIOS
        $select = new Element\Select('idCcosM');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");        
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                          
        
        // CENTRO DE COSTO SIMPLRE
        $select = new Element\Select('idCencosS');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                          

        
        // CENTRO DE COSTO BUSCAR
        $select = new Element\Select('idCcosB');
        $select->setLabel('Centro de costo');
        $select->setAttribute('class', "chosen-select");        
        $select->setAttribute('multiple', false);
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);              
        
        // CENTRO DE COSTO MULTI
        $select = new Element\Select('idCcosM');
        $select->setLabel('Centro de costo');
        $select->setAttribute('class', "chosen-select");        
        $select->setAttribute('id', "idCcosM");        
        $select->setAttribute('multiple', true);
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);              
        
        
        // FONDO DE APORTES VOLUNTARIOS
        $select = new Element\Select('idFav');
        $select->setLabel('Fondo de aportes voluntario');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('requerid', "requerid"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                                        
        // FONDO DE APORTES AFC
        $select = new Element\Select('idFafc');
        $select->setLabel('Fondo de aportes AFC');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                                                
        // PREFIJOS
        $select = new Element\Select('idPrej');
        $select->setLabel('Prefijo contable');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                                                        
        // ESTADO INICIAL DE DOCUMENTOS       
        $select = new Element\Select('estado');
        $select->setLabel('Estado del documento');
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        //$select->setValueOptions($val); // Agregar en el controlador las opciones        
        $this->add($select);   
        // VALIDAR ITEMS LISTA DE CHEQUEO
        $this->add(array(
            'name' => 'validar',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Validar',
                'id' => 'submitbutton',
                'class' => 'btn btn-success'
            ),
        ));        
        // VALOR O FORMULA
        $this->add(array(
            'name' => 'formula',
            'attributes' => array(
                'class'    => 'form-control',                
                'type'  => 'textarea',
            ),
            'options' => array(
                'label' => 'Formula o valor',
            ),
        ));    
        // VALIDACION
        $this->add(array(
            'name' => 'validacion',
            'attributes' => array(
                'class'    => 'form-control',                
                'type'  => 'textarea',
            ),
            'options' => array(
                'label' => 'Validacion',
            ),
        ));                        
        // VALIDACION SI
        $this->add(array(
            'name' => 'si',
            'attributes' => array(
                'class'    => 'form-control',                
                'type'  => 'textarea',
            ),
            'options' => array(
                'label' => 'Tome este valor',
            ),
        ));                                
        // VALIDACION NO
        $this->add(array(
            'name' => 'no',
            'attributes' => array(
                'class'    => 'form-control',                
                'type'  => 'textarea',
            ),
            'options' => array(
                'label' => 'si no tome este valor',
            ),
        ));                                        
        // HORA
        $this->add(array(
            'name' => 'hora',
            'attributes' => array(
                'type'  => 'time',
                'id'    => 'hora',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => '',
            ),
        ));        
        // HORA 2
        $this->add(array(
            'name' => 'hora2',
            'attributes' => array(
                'type'  => 'time',
                'id'    => 'hora2',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => '',
            ),
        ));                
        // HORA
        $this->add(array(
            'name' => 'horaG',
            'attributes' => array(
                'type'  => 'numeric',
                'id'    => 'horaG',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => '',
            ),
        ));                
        // FORMULA
        $select = new Element\Select('idFor');
        $select->setLabel('Formulas contables');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                                                         
        // TIPO DE AUTOMATICOS
        $select = new Element\Select('idTau');
        $select->setLabel('Tipo de automatico');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones       
        $this->add($select);                     
        // TIPO DE AUTOMATICOS 2
        $select = new Element\Select('idTau2');
        $select->setLabel('Tipo de automatico 2');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Opcional...'); // Agregar en el controlador las opciones       
        $this->add($select);                     
        // TIPO DE AUTOMATICOS 3
        $select = new Element\Select('idTau3');
        $select->setLabel('Tipo de automatico 3');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Opcional...'); // Agregar en el controlador las opciones       
        $this->add($select);                             
        // TIPO DE AUTOMATICOS 4
        $select = new Element\Select('idTau4');
        $select->setLabel('Tipo de automatico 4');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Opcional...'); // Agregar en el controlador las opciones       
        $this->add($select);                                     
        // CONCEPTOS
        $select = new Element\Select('idConc');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idConc");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                             
        // CONCEPTOS MUTIPLES
        $select = new Element\Select('idConcM');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idConcM");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select); 
        $select = new Element\Select('idConcM2');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idConcM2");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select); 
        // CONCEPTOS SIN CLASE
        $select = new Element\Select('idConc2');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idConc2");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                             
        // TIPOS DE NOMINA
        $select = new Element\Select('idTnom');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idTnom");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                                     
        // TIPOS DE NOMINA MULTIPLE
        $select = new Element\Select('idTnomm');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);           
        // CHECK HORAS  
        $this->add(array( 
            'name' => 'horasC', 
            'type' => 'Zend\Form\Element\MultiCheckbox', 
            'attributes' => array( 
                'required' => 'required', 
                'value' => '0', 
            ), 
            'options' => array( 
                'label' => 'Checkboxes Label', 
                'value_options' => array(
                    '0' => 'debo', 
                ),
            ), 
        )); 
        // FECHA INICIO CONTRATO
        $this->add(array(
            'name' => 'fecCon',            
            'attributes' => array(
                'type'  => 'text',
                'id'   => 'id',
                'required'  => 'required',
            ),
            'options' => array(
                'label' => 'Fecha inicio de labores'
            ),            
        ));  
        // FECHA ULTIMO PAGO DE NOMINA
        $this->add(array(
            'name' => 'fecPnom',            
            'attributes' => array(
                'type'  => 'text',
                'id'   => 'id',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Fecha inicio de labores'
            ),            
        ));          
        // FECHA ULTIMO PAGO DE VACACIONES
        $this->add(array(
            'name' => 'fecPvac',            
            'attributes' => array(
                'type'  => 'text',
                'id'   => 'id',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => 'Fecha inicio de labores'
            ),            
        ));                
     
        // TIPO DE EMPLEADO
        $select = new Element\Select('idTemp');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                   

        // TIPO DE EMPLEADO MULTIPLEX
        $select = new Element\Select('idTempM');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                           
        
        // ALIAS
        $this->add(array(
            'name' => 'alias',
            'attributes' => array(
                'type'  => 'text',
                'size'  => '4',
                'class' => 'span10'
            ),
            'options' => array(
                'label' => 'Alias',
            ),
        ));         
        // VALORES EN VARIABLE 
        $this->add(array( 
            'name' => 'valorvar', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'ckeditor'
            ), 
            'options' => array( 
                'label' => 'Valor',
            ), 
        ));                         
        // BUSCAR EMPLEADO
        $select = new Element\Select('idEmp');
        $select->setLabel('Empleado');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEmp");
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                   
        
        // BUSCAR EMPLEADO MULTI
        $select = new Element\Select('idEmpM');
        $select->setLabel('Empleados');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEmpM");
        $this->add($select);   

        // BUSCAR EMPLEADO MULTI
        $select = new Element\Select('idEmpM2');
        $select->setLabel('Empleados');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEmpM2");        
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);   
        
        // BUSCAR EMPLEADO MULTI
        $select = new Element\Select('idEmp2');
        $select->setLabel('Empleados');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEmp2");
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);   

        // BUSCAR EMPLEADO MULTI
        $select = new Element\Select('idEmp3');
        $select->setLabel('Empleados');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEmp2");
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);           
        
        // PERIODOS
        $select = new Element\Select('periodo');
        $select->setLabel('Numero de cuotas');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "periodo");
        $select->setValueOptions(array('0'=>'Todos' , '1'=>'1','2'=>'2') ); 
        $this->add($select);        
                
        // CUOTAS
        $this->add(array( 
              'name' => 'vcuotas', 
              'type' => 'text', 
              'attributes'  => array( 
                  'class'   => 'vcuotas'
              ), 
              'options' => array( 
                 'label' => '',
              ), 
        ));                                  
        // CUOTAS DIGITADAS
        $this->add(array( 
              'name' => 'ncuotas', 
              'type' => 'text', 
              'attributes'  => array( 
                  'class'   => 'numeric',
                  'requerid'  => 'requerid',
              ), 
              'options' => array( 
                 'label' => '',
              ), 
        ));                                          
        // ROLES
        $select = new Element\Select('idRol');
        $select->setLabel('Roles');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                     
        
         // DIAS
        $this->add(array(
            'name' => 'dias',
            'attributes' => array(
                'type'  => 'text',
                'size'  => '2',
                'class' => 'col-sm-2',
                'id'    => 'dias'
            ),
            'options' => array(
                'label' => '',
            ),
        ));               
        // DEVENGADOS
        $this->add(array(
            'name' => 'devengado',
            'attributes' => array(
                'type'  => 'text',
                'size'  => '2',
                'class' => 'devengados'
            ),
            'options' => array(
                'label' => '',
            ),
        ));                       
        // DEDUCIDOS
        $this->add(array(
            'name' => 'deducido',
            'attributes' => array(
                'type'  => 'text',
                'size'  => '2',
                'class' => 'deducidos'
            ),
            'options' => array(
                'label' => '',
            ),
        ));                               
        // AUSENTISMO
        $select = new Element\Select('idAus');
        $select->setLabel('Ausentismo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                             
         // AUSENTISMOS MULTIPLES
        $select = new Element\Select('idAusM');
        $select->setLabel('Tipos de ausentismos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', 'idAusM');
        $this->add($select);                        

        // INCAPACIDADES
        $select = new Element\Select('idInc');
        $select->setLabel('Incapacidades');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idInc');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                             
 
         // INCAPACIDADES MULTIPLES
        $select = new Element\Select('idTincM');
        $select->setLabel('Tipo de incapacidedes');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', 'idTincM');
        $this->add($select);                        

        // FECHA DE INGRESO
        $this->add(array(
            'name' => 'fecIng',            
            'attributes' => array(
                'type'  => 'Date',
                'id'   => 'id',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Fecha de ingreso'
            ),            
        ));  
        // FECHA DE INGRESO 2
        $this->add(array(
            'name' => 'fecIng2',            
            'attributes' => array(
                'type'  => 'Date',
                'id'   => 'fecIng2',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Fecha de ingreso'
            ),            
        ));  

         // MESES
        $select = new Element\Select('meses');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "meses");
        $select->setValueOptions(array('0'=>'---','01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo',
                                       '06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre'
                                      ,'11'=>'Noviembre','12'=>'Diciembre')); // Agregar en el controlador las opciones
        $this->add($select);     
 
        // MES
        $select = new Element\Select('mes');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "mes");
        $select->setValueOptions(array('0'=>'---','1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo',
                                       '6'=>'Junio','7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre'
                                      ,'11'=>'Noviembre','12'=>'Diciembre')); // Agregar en el controlador las opciones
        $this->add($select);     

        // MESES MAS
        $select = new Element\Select('mesesM');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "mesesM");
        $select->setValueOptions(array('01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo',
                                       '06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre'
                                      ,'11'=>'Noviembre','12'=>'Diciembre')); // Agregar en el controlador las opciones
        $this->add($select);     

        // SEXO
        $select = new Element\Select('sexo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "sexo");
        $select->setValueOptions(array('1'=>'Masculino','2'=>'Femenino')); // Agregar en el controlador las opciones
        $this->add($select);     
        // SEXO 2
        $select = new Element\Select('sexo2');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "sexo2");
        $select->setValueOptions(array('1'=>'Masculino','2'=>'Femenino')); // Agregar en el controlador las opciones
        $this->add($select);             

        // SEXO 3
        $select = new Element\Select('sexo3');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "sexo3");
        $select->setValueOptions(array('1'=>'Masculino','2'=>'Femenino')); // Agregar en el controlador las opciones
        $this->add($select);                     
        // ESTADO CIVIL
        $select = new Element\Select('estCivil');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "estCivil");
        $select->setValueOptions(array('1'=>'Soltero(a)',
                                       '2'=>'Casado(a)',
                                       '3'=>'Divorciado(a)',
                                       '4'=>'Viudo(a)',
                                       '5'=>'Unión libre')); // Agregar en el controlador las opciones
        $this->add($select);             
         // NIT
        $this->add(array(
            'name' => 'nit',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'NIT',
            ),
        ));                              
        // TIPOS DE FONDOS
        $select = new Element\Select('idTfonM');
        $select->setLabel('Tipo de fondo');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'idTfonM');
        $select->setAttribute('class', "chosen-select");
        $select->setValueOptions(array('1'=>'EPS','2'=>'Pensión','3'=>'ARP','4'=>'Cesantias','5'=>'Caja de compensación'));       
        $this->add($select);
        
        // CAMPOS CONSULTAS 2
        $this->add(array( 
            'name' => 'comen2', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'rows'   => 100,                 
                'cols'   => 200,             
                'class'  => 'form-control'
            ), 
            'options' => array( 
                'label'  => 'Función',                
            ), 
        ));                         
        // COLUMNAS
        $this->add(array( 
            'name' => 'columnas', 
            'type' => 'textarea', 
            'attributes' => array( 
                'rows'   => 100,                 
                'cols'   => 200,             
                'class'  => 'span7'
            ), 
            'options' => array( 
                'label'  => 'Columnas',                
            ), 
        ));                                 
        // FILTROS 
        $this->add(array( 
            'name' => 'filtros', 
            'type' => 'textarea', 
            'attributes' => array( 
                'rows'   => 100,                 
                'cols'   => 200,             
                'class'  => 'span4'
            ), 
            'options' => array( 
                'label'  => 'Filtros',                
            ), 
        ));                                 
        // DETALLE CONS
        $this->add(array( 
            'name' => 'detalleC', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'rows'   => 100,                 
                'cols'   => 200,             
                'class'  => 'form-control'
            ), 
            'options' => array( 
                'label'  => 'Cuerpo',                
            ), 
        ));                                 
        // DIAS PAGO VACACIONES       
         $this->add(array(
            'name' => 'diasVac',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'span5',
            ),
            'options' => array(
                'label' => '',
            ),
        ));               
        // DIAS PAGO VACACIONES PENDIENTE       
         $this->add(array(
            'name' => 'diasVacP',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'span6',
            ),
            'options' => array(
                'label' => '',
            ),
        ));                        
                     
        // Tipos de prestamos
        $select = new Element\Select('idTpres');
        $select->setLabel('Tipo dev prestamo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione el tipo de prestamo...'); // Agregar en el controlador las opciones
        $this->add($select);                          
        // VALOR MATRICES
        $this->add(array(
            'name' => 'valorM',
            'attributes' => array(
                'type'   => 'text',
                'id'     => 'valor',
            ),
            'options' => array(
                'label' => 'Numero',
            ),
        ));                
        // ESCALA SALARIAL DEL CARGO
        $select = new Element\Select('idEsal');
        $select->setLabel('Escala salarial');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione salario...'); // Agregar en el controlador las opciones
        $this->add($select);                          
        

        // NIVEL DE ESTUDIOS
        $select = new Element\Select('idNest');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idNest');
//        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                

        // NIVEL DE ESTUDIOS MULTIPLE
        $select = new Element\Select('idNestM');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'idNestM');
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                

        // NIVEL DE ESTUDIOS 2
        $select = new Element\Select('idNest2');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idNest2');
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                
        
        // TIPO DE SANGRE   
        $select = new Element\Select('sangre');
        $select->setLabel('Tipo de sangre');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        $select->setValueOptions(array("0"=>"O-", "1"=>"O+", "2"=>"A-", "7"=>"A+", "4"=>"B-", "3"=>"B+", "5"=>"AB-", "6"=>"AB+")); // Agregar en el controlador las opciones        
        $this->add($select);
        // TIPO DE SANGRE MULTIPLE   
        $select = new Element\Select('sangreM');
        $select->setLabel('Tipo de sangre');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('id', "estado");
        $select->setValueOptions(array("0"=>"O-", "1"=>"O+", "2"=>"A-", "7"=>"A+", "4"=>"B-", "3"=>"B+", "5"=>"AB-", "6"=>"AB+")); // Agregar en el controlador las opciones                
        $this->add($select);
        // AREA DE CAPACITACIONES        
        $select = new Element\Select('idArea');
        $select->setLabel('Area de capacitación');
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        $this->add($select);        
        
        // AREA DE CAPACITACIONES        
        $select = new Element\Select('idAreaM');
        $select->setLabel('Area de capacitación');
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', "estado");
        $this->add($select);                

        // TIPOS DE CAPACITACIONES        
        $select = new Element\Select('idTeveM');
        $select->setLabel('Tipos de eventos');
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', "estado");
        $this->add($select);                

        // MODALIDAD DE EVENTOS
        $select = new Element\Select('idModM');
        $select->setLabel('Modalidad de eventos');
        $select->setAttribute('class', "chosen-select"); 
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', "estado");
        $this->add($select);                

        // ESTADO FISICO
        $this->add(array(
            'name' => 'estatura',
            'attributes' => array(
                'type'   => 'number',
                'id'     => 'estatura',
                'required' => 'required',
                "step",'any',
                "min",'1',                  
            ),
            'options' => array(
                'label' => 'Estatura',
            ),  
        ));            

        // ALERGIAS
        $this->add(array( 
            'name' => 'alergias', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'alergias',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                      
        // OPERACIONES
        $this->add(array( 
            'name' => 'operaciones', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'operaciones',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                      
        // ENFERMEDADES
        $this->add(array( 
            'name' => 'enfermedades', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'enfermedades',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                              
        // LIMITACION FISICA
        $this->add(array( 
            'name' => 'limitacion', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'limitacion',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // LIMITACION FISICA 2
        $this->add(array( 
            'name' => 'limitacion2', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'limitacion',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                              
        // LENTES
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'lentes',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // LENTES 2
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'lentes2',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                        
        // FUMA
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'fuma',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // BEBE
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'bebe',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // DEPORTES
        $this->add(array( 
            'name' => 'deportes', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'deportes',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                              
        // CLUB SOCIAL
        $this->add(array( 
            'name' => 'clubSocial', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'clubSocial',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                              
        // LIBROS
        $this->add(array( 
            'name' => 'libros', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'libros',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // MUSICA
        $this->add(array( 
            'name' => 'musica', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'musica',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // OTRAS ACTIVIADES
        $this->add(array( 
            'name' => 'otrasAct', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'otrasAct',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // INSTITUCION
        $this->add(array( 
            'name' => 'instituto', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
            ), 
            'options' => array( 
                'label'  => '',                
            ), 
        ));    
        // PARENTESCO
        $select = new Element\Select('parentesco');
        $select->setLabel('Parentesco');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        $select->setValueOptions(array("1"=>"Mama", 
                                       "2"=>"Papa",
                                       "3"=>"Esposo(a)", 
                                       "4"=>"Hijo(a)", 
                                       "5"=>"Abuelo(a)", 
                                       "6"=>"Hermano(a)",
                                       "7"=>"Tio(a)",
                                       "8"=>"Primo(a)", 
                                       )); // Agregar en el controlador las opciones        
        $this->add($select);

        // BANCOS
        $select = new Element\Select('idBan');
        $select->setLabel('Seleccione el banco');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idBan");
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select);        
        
        // BANCOS
        $select = new Element\Select('idBanco');
        $select->setLabel('Seleccione el banco');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idBanco");
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select);        
 
         // BANCO PLANO DE MONTAJE
        $select = new Element\Select('idBancoP');
        $select->setLabel('Seleccione el banco');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idBancoP");
        $select->setEmptyOption('BANCO TITULAR...'); // Agregar en el controlador las opciones        
        $this->add($select);        
        
        // FORMA DE PAGO
        $select = new Element\Select('formaPago');
        $select->setLabel('Forma de pago');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'formaPago');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $select->setValueOptions(array("0"=>"Sin definir",
                                       "1"=>"Transferencia",
                                       "2"=>"Cheque", 
                                       "3"=>"Efectivo" )); // Agregar en el controlador las opciones                
        $this->add($select);       
        // NUMERO DE CUENTA
        $this->add(array(
            'name' => 'numCuenta',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Denominación',
            ),
        ));               
        // ENTIDAD
        $select = new Element\Select('idEnt');
        $select->setLabel('Entidad');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEnt");
        $this->add($select);                    
        // ENTIDAD
        $select = new Element\Select('idEntM');
        $select->setLabel('Entidad');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idEnt");
        $this->add($select);                            
        // MOTIVOS DE CONTRATACION
        $select = new Element\Select('idMot');
        $select->setLabel('Motivo de contratación');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);       
        
         // TERCEROS
        $select = new Element\Select('idTer');
        $select->setLabel('Tercero');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'Tercero');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);                
        
         // TERCEROS MULTIPLE
        $select = new Element\Select('idTerM');
        $select->setLabel('Tercero');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'Terceros');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);                        
        
        // CODIGO DE CUENTA
        $select = new Element\Select('codCta');
        $select->setLabel('Codigo de cuenta');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'codCta');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);                
        
        // NATURALEZA DE LA CUENTA
        $select = new Element\Select('natCta');
        $select->setLabel('Naturaleza de cuenta');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'natCta');
        $select->setAttribute('class', "chosen-select");
        $select->setValueOptions(array("0" => "Debito", "1" => "Credito"));                         
        $this->add($select);                        
        // ID SALARIO
        $select = new Element\Select('idSalario');
        $select->setLabel('Sueldo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        // TARIFAS ARL
        $select = new Element\Select('idTar');
        $select->setLabel('Tarifa');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select"); 
        $select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                             
         // CIUDADES
        $select = new Element\Select('idCiu');
        $select->setLabel('Ciudades');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idCiu');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);                     

         // CIUDADES 2
        $select = new Element\Select('idCiu2');
        $select->setLabel('Ciudades');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idCiu');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);   

         // CIUDADES 3
        $select = new Element\Select('idCiu3');
        $select->setLabel('Ciudades');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idCiu');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);  

         // CIUDADES 4
        $select = new Element\Select('idCiu4');
        $select->setLabel('Ciudades');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idCiu');
        $select->setAttribute('class', "chosen-select");
        $select->setEmptyOption('Seleccione...'); 
        $this->add($select);                                            

        // TIPO DE VINCU
        $select = new Element\Select('idTvin');
        $select->setLabel('Ciudades');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idCiu');
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); 
        $select->setValueOptions(array("9" => "Seleccione", "0" => "Directa", "1" => "Indirecta", "2" => "Independiente"));                         
        $this->add($select);                     

        // Puesto de trabajo 
        $select = new Element\Select('idPtra');
        $select->setAttribute('multiple', false);
        $select->setEmptyOption('Sin puesto ...'); 
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                      

        // Tipo de renuncia
        $select = new Element\Select('idTliq');
        $select->setAttribute('multiple', false);
        $select->setEmptyOption('Seleccionar ...'); 
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                      

        // Horarios
        $select = new Element\Select('idHor');
        $select->setAttribute('multiple', false);
        $select->setEmptyOption('Seleccionar ...'); 
        //$select->setAttribute('class', "chosen-select");
        $this->add($select);                      




        // TRAJETA MILITAR
        $this->add(array(
            'name' => 'libreta',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));
        // DISTRITO
        $this->add(array(
            'name' => 'distrito',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // claibre
        $this->add(array(
            'name' => 'claibre',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        

        // peso
        $this->add(array(
            'name' => 'peso',
            'attributes' => array(
                'type'  => 'number',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // cicatrices
        $this->add(array(
            'name' => 'cicatrices',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // tatuajes
        $this->add(array(
            'name' => 'tatuajes',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // tatuajes
        $this->add(array(
            'name' => 'tatuajes',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // tallac
        $this->add(array(
            'name' => 'tallac',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // tallap
        $this->add(array(
            'name' => 'tallap',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));        
        // tallab
        $this->add(array(
            'name' => 'tallab',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));                
        // tcalzado
        $this->add(array(
            'name' => 'tcalzado',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));               

        // TIPO GENERICO 
        $select = new Element\Select('tipoVehi');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'tipo');
        $select->setAttribute('class', "chosen-select");
        $select->setValueOptions(array("0"=>"No", "1"=>"Si")); // Agregar en el controlador las
        $this->add($select);              

        // MOTO
        $this->add(array(
            'name' => 'lmoto',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));               
        // catmoto
        $this->add(array(
            'name' => 'catmoto',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));               
        // licveh
        $this->add(array(
            'name' => 'licveh',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));               
        // catveh
        $this->add(array(
            'name' => 'catveh',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));               

        // nompare
        $this->add(array(
            'name' => 'nompare',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));               

        // regant
        $this->add(array(
            'name' => 'regant',
            'attributes' => array(
                'type'  => 'text',
            ),
            'options' => array(
                'label' => 'Numero',
                'id'    => 'numero'
            ),
        ));                                       

        // PREGUNA PARIENTE EN EMPRESA  
        $select = new Element\Select('paremple');
        $select->setLabel('Sabe conducir ?');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        $select->setValueOptions(array("0"=>"No", "1"=>"Si")); // Agregar en el controlador las opciones        
        $this->add($select);

        // CONDUCCION  
        $select = new Element\Select('sabecon');
        $select->setLabel('Sabe conducir ?');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        $select->setValueOptions(array("0"=>"No", "1"=>"Si")); // Agregar en el controlador las opciones        
        $this->add($select);

        // CAMPOS SELECCION MAESTRO DE EMPLEADOS 
        $select = new Element\Select('tipoCampo');
        $select->setLabel('Campos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idConcM");
        $this->add($select);

        // DOCUMENTOS ESPECIALES
        $select = new Element\Select('idTdoc');
        $select->setLabel('Documento especial');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);

        // TIPOS DE CONTRATOS
        $select = new Element\Select('idTcon');
        $select->setLabel('Tipos de contratos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $this->add($select);

        // CONTRATOS 
        $select = new Element\Select('idCont');
        $select->setLabel('Contratos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idCont");
        $select->setEmptyOption('Sin seleccion ...'); // Agregar en el controlador las
        $this->add($select);

        // CONCEPTOS DE GASTOS EVENTOS MULTIPLE
        $select = new Element\Select('idCgasM');
        $select->setLabel('Conceptos de gastos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', 'idGrupoM');
        $this->add($select);                    


        //VOCACION FAMILIAR
          $select = new Element\Select('famTip');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idVocF');
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);


           //CONTROL DE MUJERES EMBARAZADAS
        $select = new Element\Select('condFis');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idLinFis");
        $select->setValueOptions(array(
                                     '0'=>"NO",
                                     '1'=>"SI"
                              ));
        $this->add($select); 
        //TIPO DE LIMITACION FISICA 
        
           $select = new Element\Select('tipLimf');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idLim');
        $select->setAttribute('class', "chosen-select"); 
        $this->add($select);
        
        //NIVEL DE ESTUDIOS TECNICOS
        $select = new Element\Select('tipGrado');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "tipo");
        $select->setAttribute('class', "chosen-select"); 

        //TIPO DE DISCAPASIDA
            
        // Agregar en el controlador las opciones        
        $this->add($select);
        //tipo de grado del familiar del empledo.
          $select = new Element\Select('grado');
        $select->setLabel('Tipo');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idGra");
        $select->setValueOptions(array(
                                 "1"=>"1",
                                 "2"=>"2",
                                 "3"=>"3",
                                 "4"=>"4",
                                 "5"=>"5",
                                 "6"=>"6",
                                 "7"=>"7",
                                 "8"=>"8",
                                 "9"=>"9",
                                 "10"=>"10",
                                 "11"=>"11"
                               )); // Agregar en el controlador las opciones        
        $this->add($select);
        //calendario del familiar.
        $select = new Element\Select('tipCal');
        $select->setLabel('Tipo calendario');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idCal");
        $select->setValueOptions(array(
                                 "A"=>"A",
                                 "B"=>"B"
                               
                               )); // Agregar en el controlador las opciones 

        $this->add($select);
        //Etiqueta de textarea para nucleo familiar. 
        //hobbis.
           $this->add(array( 
            'name' => 'comenN4', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN4', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        )); 
        //Entrevita 1
               $this->add(array( 
            'name' => 'comenN5', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN5', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        )); 
               //entrevita 2 
                $this->add(array( 
            'name' => 'comenN6', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN6', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        )); 

        //entrevista 3 
        
             $this->add(array( 
            'name' => 'comenN7', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN7', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        )); 
         //MUJER CAVEZA DE FAMILIA
        $select = new Element\Select('condicion');
        $select->setLabel('Cabeza de Familia');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "tipo");
        $select->setValueOptions(array(
                                 "0"=>"NO",
                                 "1"=>"SI"
                               )); // Agregar en el controlador las opciones        
        $this->add($select);

        //nombre familiar
        $this->add(array(
            'name' => 'nombre3',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class'  => 'form-control',
                ),
            'options' => array(
                'label' => 'Nombre',
               ),
           ));  
             //apellido familiar 
             $this->add(array(
            'name' => 'apellido3',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class'  => 'form-control',
                ),
            'options' => array(
                'label' => 'Nombre',
               ),
           ));  
               $this->add(array(
            'name' => 'cedula3',
            'attributes' => array(
                'type'  => 'text',
                'required'  => 'required',
                'class'  => 'form-control',
                ),
            'options' => array(
                'label' => 'cedula',
               ),
           ));  

        //VALORACION SOCIO FAMILIAR
        //TIPO DE VIVIENDA
       
    
         $select = new Element\Select('vivienda');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idTipV');
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        //TIPO DE FAMILIA 
       
        $select = new Element\Select('idVocF');
        $select->setLabel('Nivel de estudios');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idVocF');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);
        //TIPO DE ESTRATO
        $select = new Element\Select('estFam');
        $select->setLabel('Estrato');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idEst");
        $select->setAttribute('class', "chosen-select");
        $this->add($select);

        //numero de personas
         $select = new Element\Select('numPer');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idNumPer");
        $select->setValueOptions(array(
                              
                                
                               ));
        $this->add($select);

         $select = new Element\Select('numPer');
        $select->setLabel('Numero de personas');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idNumPer");
        $select->setValueOptions(array(
                                 "1"=>"1",
                                 "2"=>"2",
                                 "3"=>"3",
                                 "4"=>"4",
                                 "5"=>"5",
                                 "6"=>"6",
                                 "7"=>"7",
                                 "8"=>"8",
                                 "9"=>"9",
                                 "10"=>"10",
                                 "11"=>"11",
                                 "12"=>"12",
                                 "13"=>"13",
                                 "14"=>"14",
                                 "15"=>"15",
                                 "16"=>"16",
                                 "17"=>"17"
                                
                                
                               ));
        $this->add($select); 

        //CONTROL DE MUJERES EMBARAZADAS
        $select = new Element\Select('numHij');
        $select->setLabel('Numero de Hijos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idEst");
        $select->setValueOptions(array(
                                 '1'=>"1",
                                 '2'=>"2",
                                 '3'=>"3",
                                 '4'=>"4",
                                 '5'=>"5",
                                 '6'=>"6",
                                 '7'=>"7",
                                 '8'=>"4",
                                 '9"=>"9",
                                 "10'=>"10",
                                 '11'=>"11"
                                
                                
                               ));
        $this->add($select); 

        /*Proyectos imediatos*/
        $select = new Element\Select('idPro');
        $select->setLabel('Estrato');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idPro");
        $select->setAttribute('class', "chosen-select");
        $this->add($select);
        
        //relacion familiar
        
        $select = new Element\Select('tipRel');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idRel");
         $select->setAttribute('class', "chosen-select");
        $this->add($select); 
         
        //comentario 6
          $this->add(array( 
            'name' => 'comenN6', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN6', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        )); 
          //comentario 1
           $this->add($select); 
          $this->add(array( 
            'name' => 'comenN9', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN9', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));
          //concepto del entrevistador
           $this->add($select); 

          $this->add(array( 
            'name' => 'comenN10', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN10', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));

          $this->add(array( 
            'name' => 'comenN11', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN10', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));     


          $this->add(array( 
            'name' => 'comenN12', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN12', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));     

          $this->add(array( 
            'name' => 'comenN13', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN12', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));      

        $this->add(array( 
            'name' => 'comenN14', 
            'type' => 'textarea', 
            'attributes' => array( 
               // 'required' => 'required', 
                'class'    => 'form-control',
                'id'       => 'comenN4', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));
             

    $this->add(array( 
            'name' => 'comenN15', 
            'type' => 'textarea', 
            'attributes' => array( 
               // 'required' => 'required', 
                'class'    => 'form-control',
                'id'       => 'comenN15', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));
             
        $this->add(array( 
            'name' => 'comenN16', 
            'type' => 'textarea', 
            'attributes' => array( 
               // 'required' => 'required', 
                'class'    => 'form-control',
                'id'       => 'comenN15', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));
                          

              //fecha de registros
               $this->add(array(
            'name' => 'fecReg',            
            'attributes' => array(
                'type'  => 'Date',
                'id'   => 'fecReg',     
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));  

           /*Esta etiqueta pertenece 
           a control mujeres embarazadas*/

          //fecha ancheta
               $this->add(array(
            'name' => 'fecDoc3',            
            'attributes' => array(
                'type'  => 'Date',
                'id'   => 'fecDoc3',     
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));  

          //fecha provable de partos
               $this->add(array(
            'name' => 'fecDoc4',            
            'attributes' => array(
                'type'  => 'Date',
                'id'   => 'fecDoc4',     
                'required'  => 'required',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));  

        //DEFUNCIONES 
        //comentario 
        $this->add(array( 
            'name' => 'comenN8', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'span4',
                'id'       => 'comenN8', 
            ), 
            'options' => array( 

                
                'label' => '',
            ), 
        ));  
         //fecha novedad
           $this->add(array(
            'name' => 'fechaNov',            
            'attributes' => array(
                'type'  => 'Date',
                'requerid'  => 'requerid',
                'id'   => 'fecNov1',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => ''
            ),            
        )); 

       //Nombre en defunciones
           $this->add(array(
            'name' => 'nombre4',            
            'attributes' => array(
                'type'  => 'text',
                'requerid'  => 'requerid',
                'id'   => 'nombre4',
                'class'   => '',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));

            $this->add(array(
            'name' => 'nombre5',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Nombre',
            ),
        ));  
        $this->add(array(
            'name' => 'nombre6',
            'attributes' => array(
                'type'  => 'text',
                'class'  => 'form-control',
            ),
            'options' => array(
                'label' => 'Nombre',
            ),
        ));              
            //apellido en defunciones

            $this->add(array(
            'name' => 'apellido4',            
            'attributes' => array(
                'type'  => 'text',
                'requerid'  => 'requerid',
                'id'   =>'apellido4',
                'class'   => 'apellido4',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));

        //fin de defunciones
        //SUB MENU DE VALORACION SOCIO FAMILIAR

        //Concepto personal
        $select = new Element\Select('conPerl');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idConPl');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);
        //conceptos economicos
        $select = new Element\Select('conEcon');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idConEcon');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);
    
        
        //Concepto ambiental
           $this->add(array(
            'name' => 'conAmb',            
            'attributes' => array(
                'type'  => 'textarea',
                'requerid'  => 'requerid',
                'id'   =>'idConAmb',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));
           //Concepto ambiental
           $this->add(array(
            'name' => 'conOper',            
            'attributes' => array(
                'type'  => 'textarea',
                'requerid'  => 'requerid',
                'id'   =>'idConOper',
                'class'   => 'form-control',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));
        //Concepto vivienda
           $this->add(array(
            'name' => 'conVivi',            
            'attributes' => array(
                'type'  => 'textarea',
                'requerid'  => 'requerid',
                'id'   =>'idConViv',
                'class'   => 'claseViv',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));
           //Ingresos adicionales
             $this->add(array(
            'name' => 'ingAdi',            
            'attributes' => array(
                'type'  => 'textarea',
                'requerid'  => 'requerid',
                'id'   =>'idGreAdi',
                'class'   => 'ingAdi',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));

        //Concepto social
        $select = new Element\Select('conSoc');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idconSoc');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);


            //CONVENIOS
         //comentario 
         $this->add(array( 
            'name' => 'comConv', 
            'type' => 'textarea', 
            'attributes' => array( 
                'class'    => 'form-control',
                'id'       => 'comConv',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        )); 

         //valor convenios
             $this->add(array(
            'name' => 'valCon',            
            'attributes' => array(
                'type'  => 'text',
                'requerid'  => 'requerid',
                'id'   =>'valCon',
                'class'   => 'valCon',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));

           $this->add(array(
            'name' => 'entidad',            
            'attributes' => array(
                'type'  => 'text',
                'requerid'  => 'requerid',
                
                'class'   => 'idEnt4',
            ),
            'options' => array(
                'label' => ''
            ),            
        ));
        $select = new Element\Select('convenios');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idDef');
        $select->setAttribute('class', "chosen-select");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);

        //fin de convenios
        // ESTADO FISICO
        $this->add(array(
            'name' => 'estatura',
            'attributes' => array(
                'type'   => 'number',
                'id'     => 'estatura',
                'required' => 'required',
                "step",'any',
                "min",'1',                  
            ),
            'options' => array(
                'label' => 'Estatura',
            ),  
        ));            

        // ALERGIAS
        $this->add(array( 
            'name' => 'alergias', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'alergias',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                      
        // OPERACIONES
        $this->add(array( 
            'name' => 'operaciones', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'operaciones',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                      
        // ENFERMEDADES
        $this->add(array( 
            'name' => 'enfermedades', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'enfermedades',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));      

        // LIMITACION FISICA
        $this->add(array( 
            'name' => 'limitacion', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'limitacion',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // LIMITACION FISICA 2
        $this->add(array( 
            'name' => 'limitacion2', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'limitacion',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                              
        // LENTES
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'lentes',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // LENTES 2
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'lentes2',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                        
        // FUMA
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'fuma',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // BEBE
        $this->add(array(
           'type' => 'Checkbox',
           'name' => 'bebe',
           'attributes' => array('id'=>'check', 'class' => 'check' ), 
           'options' => array(
              'label' => 'A checkbox',
              'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
           )
        ));                
        // DEPORTES
        $this->add(array( 
            'name' => 'deportes', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'deportes',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                              
        // CLUB SOCIAL
        $this->add(array( 
            'name' => 'clubSocial', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'clubSocial',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                              
        // LIBROS
        $this->add(array( 
            'name' => 'libros', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'libros',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // MUSICA
        $this->add(array( 
            'name' => 'musica', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'musica',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // OTRAS ACTIVIADES
        $this->add(array( 
            'name' => 'otrasAct', 
            'type' => 'textarea', 
            'attributes' => array( 
                'id'       => 'otrasAct',
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                                      
        // INSTITUCION
        $this->add(array( 
            'name' => 'instituto', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
            ), 
            'options' => array( 
                'label'  => '',                
            ), 
        ));    
        // PARENTESCO
        $select = new Element\Select('parentesco');
        $select->setLabel('Parentesco');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "estado");
        $select->setValueOptions(array("1"=>"Mama", 
                                       "2"=>"Papa",
                                       "3"=>"Esposo(a)", 
                                       "4"=>"Hijo(a)", 
                                       "5"=>"Abuelo(a)", 
                                       "6"=>"Hermano(a)" 
                                       )); // Agregar en el controlador las opciones        
        $this->add($select);
       //[Secion submenu en bienestar laboral] 
         /*Concepto Personal*/ 
        $select = new Element\Select('conPer');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idConPer");
        $select->setAttribute('class', "chosen-select");
          
        $this->add($select); 

        $select = new Element\Select('coggffPer');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idConPer");
        $select->setAttribute('class', "chosen-select");
          
        $this->add($select);     
         /*Fin del submenu*/

        //factores de riesgos.
        $select = new Element\Select('idFries');
        $select->setLabel('Factores de riesgos');
        $select->setAttribute('multiple',false);
        $select->setAttribute('class', "chosen-select"); 

        //Lugar
        $select = new Element\Select('idLugar');
        $select->setLabel('Lugar');
        $select->setAttribute('multiple',false);
        $select->setAttribute('class', "chosen-select");  
        $this->add($select);       

        //Fuente generadora
        $select = new Element\Select('idFuente');
        $select->setLabel('Fuente generadora');
        $select->setAttribute('multiple',false);
        $select->setAttribute('class', "chosen-select");  
        $this->add($select);       

        //Actividad
        $select = new Element\Select('idActividad');
        $select->setLabel('Actividades');
        $select->setAttribute('multiple',false);
        $select->setAttribute('class', "chosen-select");                 
    
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        //procesos de riesgo
        $select = new Element\Select('idPries');
        $select->setLabel('');
        $select->setAttribute('multiple',true);
        $select->setAttribute('class', "chosen-select");  
        $this->add($select);
        /*CONTROLES DE RIESGOS*/
         /*responsables*/
        $select = new Element\Select('idRpon');
        $select->setLabel('Factores de riesgos');
        $select->setAttribute('multiple',true);
        $select->setAttribute('class', "chosen-select"); 

        $this->add($select);

        //PELIGROS.
        $select = new Element\Select('idPelP');
        $select->setLabel('Tipo de peligros');
        $select->setAttribute('multiple',true);
       
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);  
        //RIESGOS
        //Nivel de riesgos
          $select = new Element\Select('idNries');
        $select->setLabel('Nivel de riesgos');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idNries");
        $select->setValueOptions(array('1'=>'Bajo' , 
                                       '2'=>'Medio',
                                       '3'=>'Alto',
                                       '4'=>'Critico') ); 
        $this->add($select); 

         //Tipo de riesgo
          $select = new Element\Select('idTiries');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idNries");
        $select->setValueOptions(array('1'=>'Aceptable' , 
                                       '2'=>'No aceptable',
                                       ) ); 
        $this->add($select); 
        //Actividad
          $select = new Element\Select('idActv');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idActv");
        $select->setValueOptions(array('1'=>'Operacional' , 
                                       '2'=>'No operacional',) ); 
        $this->add($select); 
        //Probabilidad
        $select = new Element\Select('idProb');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idProb");
        $select->setValueOptions(array('1'=>'Muy alta' , 
                                       '2'=>'Alta' , 
                                       '3'=>'Media',
                                       '4'=>'Baja') ); 
        $this->add($select); 
       //Consecuencias
        $select = new Element\Select('idCons');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idCons");
        $select->setValueOptions(array('1'=>'Insignificante' , 
                                       '2'=>'Moderada' , 
                                       '3'=>'Dañina',
                                       '4'=>'Extrema') ); 
        $this->add($select); 
         
        //factores de riesgos.
        $select = new Element\Select('idFries');
        $select->setLabel('Factores de riesgos');
        $select->setAttribute('multiple',false);
        $select->setAttribute('class', "chosen-select"); 
    
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
        //procesos de riesgo
        $select = new Element\Select('idPries');
        $select->setLabel('');
        $select->setAttribute('multiple',true);
        $select->setAttribute('class', "chosen-select");  
        $this->add($select);
        /*CONTROLES DE RIESGOS*/
         /*responsables*/
        $select = new Element\Select('idRpon');
        $select->setLabel('Factores de riesgos');
        $select->setAttribute('multiple',true);
        $select->setAttribute('class', "chosen-select");     
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);
         
        //COMITE
        //Tipo comite
        $select = new Element\Select('idTipCmit');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idCons");
        $select->setValueOptions(array('0'=>'Copasst' , 
                                       '1'=>'Comité de convivencia')
                                        ); 
        $this->add($select); 
        //Responsables
        $select = new Element\Select('idRcomit');
        $select->setLabel('Factores de riesgos');
        $select->setAttribute('multiple',false);
        $select->setAttribute('class', "chosen-select");     
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);

        // EFECTOS
        $this->add(array( 
            'name' => 'efectos', 
            'type' => 'textarea', 
            'attributes' => array( 
                'required' => 'required', 
                'class'    => 'form-control',
                'id'       => 'efectos', 
            ), 
            'options' => array( 
                'label' => '',
            ), 
        ));                                         
        //Familiares en defunciones.
        $select = new Element\Select('idDefFami');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idDefFami");
         $select->setAttribute('class', "chosen-select");
        $this->add($select); 
         //CONDICION ACADEMICA
        $select = new Element\Select('conAcdem');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idConAcdem");
        $select->setValueOptions(array(
                                     '0'=>"NO",
                                     '1'=>"SI"
                              ));
        $this->add($select); 

        $select = new Element\Select('clasAcdem');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "clasAcdem");
        $select->setValueOptions(array(
                                     '0'=>"Educación preescolar",
                                     '1'=>"Educación básica",
                                     '2'=>"Educación media",
                                     '3'=>"Educación universitaria"
                              ));
        $this->add($select); 

         //Conyugue del empleado
        $select = new Element\Select('conyEmp');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idCnyuEmp");
         $select->setAttribute('class', "chosen-select");
        $this->add($select); 
        

        $select = new Element\Select('idTipVivi');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idTipVivi");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select); 

        $select = new Element\Select('idDisc');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idDisc");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select); 

        $select = new Element\Select('idVocFil');
        $select->setLabel('Conceptos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', "idVocFil");
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones        
        $this->add($select); 
        
        // TAREAS
        $select = new Element\Select('idTarea');
        $select->setLabel('Tareas');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', 'idTarea');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);                             

        // OBJETIVOS 
        $select = new Element\Select('idObjM');
        $select->setLabel('Objetivos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'idObjM');
        $select->setAttribute('class', "chosen-select");
        $this->add($select); 

        // PROCESOS 
        $select = new Element\Select('idProcM');
        $select->setLabel('Procesos');
        $select->setAttribute('multiple', true);
        $select->setAttribute('id', 'idProcM');
        $select->setAttribute('class', "chosen-select");
        $this->add($select);         

        // NIVEL DEL CARGO
        $select = new Element\Select('idNcarM');
        $select->setLabel('Nivel del cargo');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select"); 
        //$select->setEmptyOption('Seleccione...'); // Agregar en el controlador las opciones
        $this->add($select);                     



$this->add($select);   
        //Empleados multiples.
        $select = new Element\Select('idEmpRee');
        $select->setLabel('');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");        
        $this->add($select);   

        //Tipo de solicitud     
        //
        $select = new Element\Select('idTsoli');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");        
        $this->add($select); 
        /*Muchas sulicitude*/
        $select = new Element\Select('idTsoliM');
        $select->setLabel('');
        $select->setAttribute('multiple', true);
        $select->setAttribute('class', "chosen-select");        
        $this->add($select);                                         
        $select = new Element\Select('idTipoVivHo');
        $select->setLabel('Tipo de vivienda');
        $select->setAttribute('multiple', false);
        $select->setAttribute('class', "chosen-select");
        $select->setAttribute('id', 'idTipVivHo');
        $select->setValueOptions(array('0'=>'Propia','1'=>'Areiendada','2'=>'Familiar')); 
        $this->add($select);

         //Incuido en inforomacion social hoja de vida.
        $select = new Element\Select('siNo1');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idLinFis");
        $select->setValueOptions(array(
                                     '0'=>"NO",
                                     '1'=>"SI"
                              ));
        $this->add($select);
         //
        $select = new Element\Select('siNo2');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idLinFis");
        $select->setValueOptions(array(
                                     '0'=>"NO",
                                     '1'=>"SI"
                              ));
        $this->add($select);
         //
        $select = new Element\Select('siNo3');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idLinFis");
        $select->setValueOptions(array(
                                     '0'=>"NO",
                                     '1'=>"SI"
                              ));
        $this->add($select);
         //Condicion general.
        $select = new Element\Select('siNo4');
        $select->setLabel('');
        $select->setAttribute('multiple', false);
        $select->setAttribute('id', "idLinFis");
        $select->setValueOptions(array(
                                     '0'=>"NO",
                                     '1'=>"SI"
                              ));
        $this->add($select);

        $this->add(array( 
            'name' => 'csrf', 
            'type' => 'Zend\Form\Element\Csrf', 
        ));              
    }// Fin funcion    
}
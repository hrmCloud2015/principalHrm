<?php
/**
 * Standar formularios NISSI
 * @copyright 2013
 */
namespace Principal\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Validator\Digits;

// LISTADO DE VALIDACIONES --------------------
// Id
// Nombre
// Numero
// Valor
// Valor separador de miles
// Cedula o Codigo
// hora
// Tipos de nomina 
// Grupo de nomina
// Clave 1
// Clave 2
// dias
// Tipo 
// id empleado
// Fecha ini
// Fecha fin 

class ValFormulario implements InputFilterAwareInterface
{
    protected $inputFilter;
         
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }
     
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory     = new InputFactory();
            // id
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'id',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );   
            // Salud
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idSal',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Nombre  
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'nombre',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'StringLength',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => 1,
                                'max'      => 100,
                            ),
                        ),
                    ),
                ))
            );
            // Numero
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'numero',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                                'max'      => '20',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Valor
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'valor',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                                'max'      => '8',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Valor separador de miles  
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'valorS',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'StringLength',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => 1,
                                'max'      => 100,
                            ),
                        ),
                    ),
                ))
            );            
            // Hora
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'hora',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                                'max'      => '3',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Tipos de nomina
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idTnomm',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );                                    
            // Grupo de nomina
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idGrupo',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );                                    
            // Cedula / codigo
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'cedula',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                                'max'      => '15',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Clave 1
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'clave1',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'StringLength',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => 1,
                                'max'      => 100,
                            ),
                        ),
                    ),
                ))
            );                        
            // Clave 2
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'clave2',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Identical',
                            'options' => array(
                                'token' => 'clave1',
                            ),
                        ),
                    ),
                ))
            );                        
            // Dias
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'dias',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                                'max'      => '3',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Tipo
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'tipo',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );                                                
            // Empleado
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idEmp',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );                
             // Fecha inicial
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'fechaIni',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Date',

                        ),
                    ),
                ))
            );                        
            // Fecha fin 
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'fechaFin',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Date',

                        ),
                    ),
                ))
            );                                   
            
            // Centros de costos 
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idCcos',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );                                    
            // Motivos de contratacion
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idMot',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );      
            // Escala salarial 
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idEsal',
                    'required' => true,
                    'validators' => array(
                      array(
                        'name'    => 'NotEmpty',
                      ),
                    ),
                ))
            );                                                                       
            // Empleado
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idEmp',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );
            // Tipo de incapacidad
            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'idInc',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                        array(
                            'name'    => 'Digits',
                            'options' => array(
                                'encoding' => 'UTF-8',
                                'min'      => '1',
                            ),
                        ),
                        array ( 
                          'name' => 'digits', 
                        ),                         
                    ),
                ))
            );


            $inputFilter->add(
                $factory->createInput(array(
                    'name'     => 'fileupload',
                    'required' => true,
                ))
            );
             
            $this->inputFilter = $inputFilter;
        }
         
        return $this->inputFilter;
    }
}
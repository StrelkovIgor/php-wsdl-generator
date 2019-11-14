<?php


namespace Koreychenko\PWG;


use ReflectionClass;
use ReflectionProperty;

class WsdlParam
{

    private $baseTypes = [
        'boolean',
        'string',
        'int',
        'dateTime',
    ];

    private $type;
    private $variable;
    private $minOccurs;
    private $maxOccurs;
    private $elements = [];
    private $wsdlGeneratorService;

    /**
     * WsdlParam constructor.
     *
     * @param $type
     * @param $variable
     * @param $minOccurs
     * @param $maxOccurs
     */
    public function __construct($type, $variable, $minOccurs, $maxOccurs, WsdlGeneratorService &$wsdlGeneratorService)
    {
        $this->type                 = $type;
        $this->variable             = $variable;
        $this->wsdlGeneratorService = $wsdlGeneratorService;

        if (!$minOccurs && !$maxOccurs) {
            $this->minOccurs = 0;
            $this->maxOccurs = 1;
        } else {
            $this->minOccurs = $minOccurs ?? "0";
            $this->maxOccurs = $maxOccurs ?? "1";
        }
    }

    public function formatType()
    {
        if ($this->isComplex()) {
            return $this->parseComplexType();
        } else {
            if ($this->isArray()) {
                $variable        = $this->reduceVariablePlural();
                $complexTypeName = $this->getComplexTypeName('ArrayOf' . ucfirst($this->getType()));
                $this->wsdlGeneratorService->addExtraTypes($complexTypeName, sprintf($this->getArrayTemplate(), $complexTypeName, $this->minOccurs, $this->maxOccurs, $variable, $this->getType()));

                return sprintf($this->getComplexTypeElementTemplate(), $this->minOccurs, $this->maxOccurs, $this->variable, $complexTypeName);
            } else {
                return sprintf($this->getSimpleTypeElementTemplate(), $this->minOccurs, $this->maxOccurs, $this->variable, $this->getType());
            }
        }
    }

    public function isComplex()
    {
        if (in_array($this->getType(), $this->baseTypes)) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return str_replace('[]', '', $this->type);
    }

    /**
     * @return string|void
     * @throws \ReflectionException
     */
    public function parseComplexType()
    {
        if (!class_exists($this->getType())) {
            return;
        }

        $reflectionClass = new ReflectionClass($this->getType());

        $complexTypeName = $this->getComplexTypeName($reflectionClass->getShortName());

        $this->wsdlGeneratorService->addExtraTypes($complexTypeName, '');

        $return = sprintf($this->getComplexTypeElementTemplate(), $this->minOccurs, $this->maxOccurs, $this->variable, $complexTypeName);

        $complexType = '';

        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $annotation = $property->getDocComment();
            if ($annotation) {
                $annotationParser = new WsdlAnnotationParser();
                $annotationParser->setAnnotation($annotation);
                $properties = $annotationParser->getProperty();

                if ($properties[0]) {
                    $propertyType      = $properties[1][0];
                    $propertyVariable  = $properties[2][0];
                    $propertyMinOccurs = $properties[3][0] ? $properties[4][0] : '';
                    $propertyMaxOccurs = $properties[3][0] ? $properties[5][0] : '';

                    $propertyParam = new WsdlParam($propertyType, $propertyVariable, $propertyMinOccurs, $propertyMaxOccurs, $this->wsdlGeneratorService);
                    $complexType   .= $propertyParam->formatType();
                }
            }
        }

        $complexType = sprintf($this->getComplexTypeTemplate(), $complexTypeName, $complexType);

        $this->wsdlGeneratorService->addExtraTypes($complexTypeName, $complexType);

        return $return;
    }

    /**
     *
     * Генерирует уникальное имя комплексного класса
     *
     * @param string $typeName
     *
     * @return string
     */
    public function getComplexTypeName(string $typeName): string
    {
        $complexTypes = $this->wsdlGeneratorService->getExtraTypes();

        if (!array_key_exists($typeName, $complexTypes)) {
            return $typeName;
        }

        $i = 1;
        while (array_key_exists($typeName . $i, $complexTypes)) {
            $i++;
        }

        return $typeName . $i;
    }

    /**
     * @return string
     */
    private function getComplexTypeElementTemplate()
    {
        return '<s:element minOccurs="%s" maxOccurs="%s" name="%s" type="tns:%s" />';
    }

    /**
     * @return string
     */
    private function getComplexTypeTemplate()
    {
        return <<<EOF
<s:complexType name="%s">
        <s:sequence>
          %s
        </s:sequence>
      </s:complexType>
EOF;
    }

    /**
     * @return bool
     */
    private function isArray()
    {
        if (!(strpos($this->getTypeRaw(), '[]') === false)) {
            return true;
        }

        if ((($this->minOccurs) && ($this->maxOccurs)) && ($this->minOccurs != $this->maxOccurs)) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    private function getTypeRaw()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    private function reduceVariablePlural(): string
    {
        return rtrim($this->variable, 's');
    }

    /**
     * @return string
     */
    private function getArrayTemplate()
    {
        return <<<EOF
<s:complexType name="%s">
        <s:sequence>
          <s:element minOccurs="%s" maxOccurs="%s" name="%s" type="s:%s" />
        </s:sequence>
      </s:complexType>
EOF;
    }

    /**
     * @return string
     */
    private function getSimpleTypeElementTemplate()
    {
        return '<s:element minOccurs="%s" maxOccurs="%s" name="%s" type="s:%s" />';
    }

    /**
     * @return array
     */
    public function getElements(): array
    {
        return $this->elements;
    }

}
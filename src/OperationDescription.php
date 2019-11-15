<?php


namespace WsdlGenerator;


use ReflectionMethod;

class OperationDescription
{

    use AnnotationParsingTrait;

    public    $wsdlGeneratorService;
    protected $reflection;
    private   $annotationParser;

    public function __construct(ReflectionMethod $reflectionMethod)
    {
        $this->reflection = $reflectionMethod;
        $annotationParser = new WsdlAnnotationParser();
        $annotationParser->setAnnotation($this->reflection->getDocComment());
        $this->annotationParser = $annotationParser;
    }

    public function isWebMethod(): bool
    {
        return $this->annotationParser->checkMethod();
    }

    public function getOperations(): string
    {
        $name        = $this->reflection->getName();
        $description = $this->annotationParser->getDescription();
        $nameIn      = $name . 'SoapIn';
        $nameOut     = $name . 'SoapOut';

        return sprintf($this->getOpeartionsTemplate(), $name, $description, $nameIn, $nameOut);
    }

    public function getOpeartionsTemplate(): string
    {
        return <<<EOF
<wsdl:operation name="%s">
      <wsdl:documentation xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/">%s</wsdl:documentation>
      <wsdl:input message="tns:%s" />
      <wsdl:output message="tns:%s" />
    </wsdl:operation>
EOF;
    }

    public function getBindings(): string
    {
        $name = $this->reflection->getName();

        return sprintf($this->getBindingsTemplate(), $name, $name);
    }

    private function getBindingsTemplate(): string
    {
        return <<<EOF
<wsdl:operation name="%s">
      <soap:operation soapAction="%s" style="document" />
      <wsdl:input>
        <soap:body use="literal" />
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal" />
      </wsdl:output>
    </wsdl:operation>
EOF;
    }

    public function getMessage()
    {
        $template = <<<EOF
<wsdl:message name="%s">
    <wsdl:part name="parameters" element="tns:%s" />
  </wsdl:message>
  <wsdl:message name="%s">
    <wsdl:part name="parameters" element="tns:%s" />
  </wsdl:message>
EOF;

        $name = $this->reflection->getName();

        return sprintf($template, $name . 'SoapIn', $name, $name . 'SoapOut', $name . 'Response');
    }

    public function getTypes(WsdlGeneratorService &$wsdlGeneratorService)
    {
        $this->wsdlGeneratorService = $wsdlGeneratorService;

        $template = <<<EOF
    <s:element name="%s">
    %s            
      </s:element>
      <s:element name="%s">
      %s
        </s:element>        
EOF;

        $name = $this->reflection->getName();

        $inputType  = $this->getInputType();
        $outputType = $this->getOutputType();

        return sprintf($template, $name, $inputType, $name . 'Response', $outputType);
    }

    private function getInputType()
    {
        $params = $this->annotationParser->getParams();

        return $this->getComplexType($params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getComplexType(array $params): string
    {
        if ($params[0]) {
            $return = '<s:complexType><s:sequence>';
            foreach ($params[0] as $i => $paramItem) {
                $type     = $params[1][$i];
                $name     = $params[2][$i];
                $minOccur = $params[3][$i] ? $params[4][$i] : '';
                $maxOccur = $params[3][$i] ? $params[5][$i] : '';
                $param    = new WsdlParam($type, $name, $minOccur, $maxOccur, $this->wsdlGeneratorService);
                $return   .= $param->formatType();
            }
            $return .= '</s:sequence></s:complexType>';

            return $return;
        } else {
            return '<s:complexType />';
        }
    }

    private function getOutputType()
    {
        $params = $this->annotationParser->getReturn();

        return $this->getComplexType($params);
    }

}
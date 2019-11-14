<?php


namespace Koreychenko\PWG;


use Exception;

class WsdlGeneratorService
{

    private $className;
    private $nameSpace;
    private $url;
    private $extraTypes = [];

    /**
     * WsdlGeneratorService constructor.
     *
     * @param string $className
     * @param string $url
     * @param string $nameSpace
     *
     * @throws Exception
     */
    public function __construct(string $className, string $url, string $nameSpace)
    {
        if (class_exists($className)) {
            $this->className = $className;
        } else {
            throw new Exception('Class not exists');
        }

        $this->nameSpace = $nameSpace;
        $this->url       = $url;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        $result = $this->getHeader();

        $result .= $this->processClass();

        $result .= $this->getFooter();

        return $result;
    }

    /**
     * @return string
     */
    private function getHeader()
    {
        $nameSpace = $this->nameSpace;

        $header = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<wsdl:definitions xmlns:tm="http://microsoft.com/wsdl/mime/textMatching/" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" 
xmlns:mime="http://schemas.xmlsoap.org/wsdl/mime/" xmlns:tns="{$nameSpace}" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" 
xmlns:s="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://schemas.xmlsoap.org/wsdl/soap12/" xmlns:http="http://schemas.xmlsoap.org/wsdl/http/" 
targetNamespace="{$nameSpace}" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/">
EOF;

        return $header;
    }

    /**
     * @return string
     */
    private function processClass()
    {
        $result = '';

        $serviceDescription = new ServiceDescription($this->className);

        $result .= $this->formatDescription($serviceDescription->getDescription());

        $operations = $serviceDescription->getOperations();

        $result .= $this->formatTypes($operations);
        $result .= $this->formatMessages($operations);
        $result .= $this->formatOperations($serviceDescription->getName(), $operations);
        $result .= $this->formatBindings($serviceDescription->getName(), $operations);

        $result .= $this->formatService($serviceDescription->getName(), $serviceDescription->getDescription(), $this->url);

        return $result;
    }

    /**
     * @param string $description
     *
     * @return string
     */
    private function formatDescription(string $description)
    {
        $template = <<<EOF
<wsdl:documentation xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/">%s</wsdl:documentation>
EOF;

        return sprintf($template, $description);
    }

    /**
     * @param array $operations
     *
     * @return string
     */
    private function formatTypes(array $operations): string
    {
        $template = <<<EOF
<wsdl:types>
    <s:schema elementFormDefault="qualified" targetNamespace="%s">
    %s
    </s:schema>
  </wsdl:types>
EOF;

        foreach ($operations as $operation) {
            $types[] = $operation->getTypes($this);
        }

        $types = implode("\n", $types);
        $types .= implode("\n", $this->getExtraTypes());

        return sprintf($template, $this->nameSpace, $types);
    }

    /**
     * @return array
     */
    public function getExtraTypes()
    {
        return $this->extraTypes;
    }

    /**
     * @param array $operations
     *
     * @return string
     */
    private function formatMessages(array $operations)
    {
        $return = '';
        foreach ($operations as $operation) {
            $return .= $operation->getMessage();
        }

        return $return;
    }

    /**
     * @param string                 $name
     * @param OperationDescription[] $operations
     *
     * @return string
     */
    private function formatOperations(string $name, array $operations)
    {
        $template = <<<EOF
<wsdl:portType name="%s">
%s
</wsdl:portType>
EOF;

        $formattedOperation = [];

        foreach ($operations as $operation) {
            $formattedOperation[] = $operation->getOperations();
        }

        return sprintf($template, $name . 'Soap', implode("\n", $formattedOperation));
    }

    /**
     * @param string $name
     * @param array  $operations
     *
     * @return string
     */
    private function formatBindings(string $name, array $operations)
    {
        $template = <<<EOF
<wsdl:binding name="%s" type="tns:%s">
<soap:binding transport="http://schemas.xmlsoap.org/soap/http" />
%s
</wsdl:binding>
EOF;

        $formattedBindings = [];

        foreach ($operations as $operation) {
            $formattedBindings[] = $operation->getBindings();
        }

        return sprintf($template, $name . 'Soap', $name . 'Soap', implode("\n", $formattedBindings));
    }

    /**
     * @param $name
     * @param $description
     * @param $url
     *
     * @return string
     */
    private function formatService($name, $description, $url)
    {
        $template = <<<EOF
<wsdl:service name="%s">
    <wsdl:documentation xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/">%s</wsdl:documentation>
    <wsdl:port name="%s" binding="tns:%s">
      <soap:address location="%s" />
    </wsdl:port>    
  </wsdl:service>
EOF;

        return sprintf($template, $name, $description, $name . 'Soap', $name . 'Soap', $url);
    }

    /**
     * @return string
     */
    private function getFooter()
    {
        return <<<EOF
</wsdl:definitions>
EOF;
    }

    /**
     * @param array $extraTypes
     */
    public function addExtraTypes($key, $value): void
    {
        $this->extraTypes[$key] = $value;
    }

}
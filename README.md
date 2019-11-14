# php-wsdl-generator
Yet another PHP WSDL generator

## Main webwervice description class

```php

/**
 * @desc    Webservice description
 * 
 */
class TestWebserviceDescription
{

    /**
     * @WebMethod
     * @desc First Webservice Method
     *
     * @return \Class\Namespace\DataClass $data minOccurs="1" maxOccurs="1"
     */
    public function GetData()
    {
        $data = new \Class\Namespace\DataClass();

        return $data;
    }
}
```

## Get WSDL from webservice description class

```php
$path = 'http://example.com';
$nameSpace = 'http://example.com';
$wsdl = new WsdlGeneratorService(TestWebserviceDescription::class, $path, $nameSpace);
$result = $wsdl->getXml();
header("Content-Type: text/xml");
echo $result;
```

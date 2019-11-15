<?php


namespace WsdlGenerator;


use ReflectionClass;
use ReflectionMethod;

class ServiceDescription
{

    use AnnotationParsingTrait;

    protected $operations = [];
    protected $reflection;

    /**
     * ServiceDescription constructor.
     *
     * @param string $className
     *
     * @throws \ReflectionException
     */
    public function __construct(string $className)
    {
        $this->reflection       = new ReflectionClass($className);
        $this->annotationParser = new WsdlAnnotationParser();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->reflection->getShortName();
    }

    /**
     * @return OperationDescription[]
     */
    public function getOperations(): array
    {
        $operations = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($operations as $operation) {
            $operation = new OperationDescription($operation);
            if ($operation->isWebMethod()) {
                $this->operations[] = $operation;
            }
        }

        return $this->operations;
    }
}
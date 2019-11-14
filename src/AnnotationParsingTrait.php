<?php


namespace Koreychenko\PWG;

trait AnnotationParsingTrait
{
    public function getDescription(): string
    {
        $this->annotationParser->setAnnotation($this->reflection->getDocComment());

        return $this->annotationParser->getDescription();
    }
}
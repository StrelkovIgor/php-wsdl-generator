<?php


namespace Koreychenko\PWG;


class WsdlAnnotationParser
{
    private $annotation;

    /**
     * @param string $annotation
     */
    public function setAnnotation(string $annotation)
    {
        $this->annotation = $annotation;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        $matches = [];
        $pattern = '/\@desc\s+(.+)$/im';
        preg_match($pattern, $this->annotation, $matches);

        return $matches[1];
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->parseAnnotationParameterString('param');
    }

    /**
     * @param $type
     *
     * @return array
     */
    private function parseAnnotationParameterString($type)
    {
        $matches = [];
        $pattern = '/\@' . $type . '\s+(\S+)\s+\$(\S+)(\s+minOccurs="(\d+)"\s+maxOccurs="(\S+)")?$/im';
        preg_match_all($pattern, $this->annotation, $matches);

        return $matches;
    }

    /**
     * @return array
     */
    public function getReturn()
    {
        return $this->parseAnnotationParameterString('return');
    }

    /**
     * @return array
     */
    public function getProperty()
    {
        return $this->parseAnnotationParameterString('var');
    }

    /**
     * @return bool
     */
    public function checkMethod()
    {
        if (!(strpos($this->annotation, '@WebMethod') === false)) {
            return true;
        }

        return false;
    }
}
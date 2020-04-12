<?php


namespace SwaggerBake\Lib\Factory;

use Cake\Routing\Route\Route;
use Cake\Utility\Inflector;
//use Doctrine\Common\Annotations\AnnotationReader;
//use ReflectionClass;
//use ReflectionMethod;
use SwaggerBake\Lib\OpenApi\Path;
use SwaggerBake\Lib\OpenApi\Parameter;
use SwaggerBake\Lib\OpenApi\Schema;

/**
 * Class SwaggerPath
 */
class PathFactory
{
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function create(Route $route) : ?Path
    {
        $path = new Path();
        $defaults = (array) $route->defaults;

        if (empty($defaults['_method'])) {
            return null;
        }

        foreach ((array) $defaults['_method'] as $method) {

            $path
                ->setType(strtolower($method))
                ->setPath($this->createPath($route))
                ->setOperationId($route->getName())
                ->setSummary('@todo')
                ->setTags([
                    Inflector::humanize(Inflector::underscore($defaults['controller']))
                ])
                ->setParameters($this->createParameters($route))
            ;

        }

        return $path;
    }

    private function createPath(Route $route) : string
    {
        $pieces = array_map(
            function ($piece) {
                if (substr($piece, 0, 1) == ':') {
                    return '{' . str_replace(':', '', $piece) . '}';
                }
                return $piece;
            },
            explode('/', $route->template)
        );

        $length = strlen($this->prefix);

        return substr(implode('/', $pieces), $length);
    }

    private function createParameters(Route $route) : array
    {
        return array_merge(
            $this->getPathParameters($route),
            []//$this->getQueryParameters($route)
        );
    }

    private function getPathParameters(Route $route) : array
    {
        $return = [];

        $pieces = explode('/', $route->template);
        $results = array_filter($pieces, function ($piece) {
           return substr($piece, 0, 1) == ':' ? true : null;
        });

        if (empty($results)) {
            return $return;
        }

        foreach ($results as $result) {

            $schema = new Schema();
            $schema
                ->setType('string')
            ;

            $name = strtolower($result);

            if (substr($name, 0, 1) == ':') {
                $name = substr($name, 1);
            }

            $parameter = new Parameter();
            $parameter
                ->setName($name)
                ->setAllowEmptyValue(false)
                ->setDeprecated(false)
                ->setRequired(true)
                ->setIn('path')
                ->setSchema($schema)
            ;
            $return[] = $parameter;
        }

        return $return;
    }

/*    private function getQueryParameters(DashedRoute $route) : array
    {
        $defaults = (array) $route->defaults;
        $className = $defaults['controller'] . 'Controller';
        $methodName = $defaults['action'];

        $controller = '\App\Controller\\' . $className;
        $instance = new $controller;

        $reflection = new ReflectionClass(get_class($instance));
        $reflectionMethod = new ReflectionMethod(get_class($instance), 'index');

        $annotationReader = new AnnotationReader();
        echo '<pre>' . __FILE__ . ':' . __LINE__;
        print_r($annotationReader->getMethodAnnotations($reflectionMethod));
        echo '</pre>';
        die();

        return [];
    }

    private function getPaginatorQueryParameters(DashedRoute $route)
    {

    }*/
}
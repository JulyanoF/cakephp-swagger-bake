<?php

namespace SwaggerBake\Test\TestCase\Lib\Attribute;

use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;
use Cake\TestSuite\TestCase;
use SwaggerBake\Lib\Model\ModelScanner;
use SwaggerBake\Lib\Route\RouteScanner;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Swagger;

class OpenApiOperationTest extends TestCase
{
    /**
     * @var string[]
     */
    public $fixtures = [
        'plugin.SwaggerBake.Employees',
    ];

    private Router $router;

    private array $config;

    private Swagger $swagger;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $router = new Router();
        $router::scope('/', function (RouteBuilder $builder) {
            $builder->setExtensions(['json']);
            $builder->resources('Operations', [
                'map' => [
                    'isVisible' => [
                        'action' => 'isVisible',
                        'method' => 'GET',
                        'path' => 'is-visible'
                    ],
                    'tagNames' => [
                        'action' => 'tagNames',
                        'method' => 'GET',
                        'path' => 'tag-names'
                    ],
                    'deprecated' => [
                        'action' => 'deprecated',
                        'method' => 'GET',
                        'path' => 'deprecated'
                    ],
                    'externalDocs' => [
                        'action' => 'externalDocs',
                        'method' => 'GET',
                        'path' => 'external-docs'
                    ],
                    'descriptions' => [
                        'action' => 'descriptions',
                        'method' => 'GET',
                        'path' => 'descriptions'
                    ],
                ]
            ]);
            $builder->resources('Departments', function (RouteBuilder $routes) {
                $routes->resources('DepartmentEmployees');
            });
            $builder->resources('EmployeeSalaries');
        });
        $this->router = $router;

        $this->config = [
            'prefix' => '/',
            'yml' => '/config/swagger-bare-bones.yml',
            'json' => '/webroot/swagger.json',
            'webPath' => '/swagger.json',
            'hotReload' => false,
            'exceptionSchema' => 'Exception',
            'requestAccepts' => ['application/x-www-form-urlencoded'],
            'responseContentTypes' => ['application/json'],
            'namespaces' => [
                'controllers' => ['\SwaggerBakeTest\App\\'],
                'entities' => ['\SwaggerBakeTest\App\\'],
                'tables' => ['\SwaggerBakeTest\App\\'],
            ]
        ];

        $configuration = new Configuration($this->config, SWAGGER_BAKE_TEST_APP);
        $cakeRoute = new RouteScanner($this->router, $configuration);
        $this->swagger = new Swagger(new ModelScanner($cakeRoute, $configuration));
    }

    public function test_descriptions(): void
    {
        $arr = json_decode($this->swagger->toString(), true);
        $this->assertEquals('summary...', $arr['paths']['/operations/descriptions']['get']['summary']);
        $this->assertEquals('desc...', $arr['paths']['/operations/descriptions']['get']['description']);
    }

    public function test_is_visible(): void
    {
        $arr = json_decode($this->swagger->toString(), true);
        $this->assertArrayNotHasKey('/operations/is-visible', $arr['paths']);
    }

    public function test_tags_names(): void
    {
        $arr = json_decode($this->swagger->toString(), true);
        $this->assertCount(4, $arr['paths']['/operations/tag-names']['get']['tags']);
    }

    public function test_is_deprecated(): void
    {
        $arr = json_decode($this->swagger->toString(), true);
        $this->assertTrue($arr['paths']['/operations/deprecated']['get']['deprecated']);
    }

    public function test_external_docs(): void
    {
        $arr = json_decode($this->swagger->toString(), true);
        $externalDocs = $arr['paths']['/operations/external-docs']['get']['externalDocs'];
        $this->assertEquals('http://localhost', $externalDocs['url']);
        $this->assertEquals('desc...', $externalDocs['description']);
    }
}
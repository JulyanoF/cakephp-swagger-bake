<?php

namespace SwaggerBake\Test\TestCase\Lib\Attribute;

use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;
use Cake\TestSuite\TestCase;
use SwaggerBake\Lib\Model\ModelScanner;
use SwaggerBake\Lib\Route\RouteScanner;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Swagger;

class OpenApiDtoTest extends TestCase
{
    /**
     * @var string[]
     */
    public $fixtures = [
        'plugin.SwaggerBake.Employees',
    ];

    private Router $router;

    private Configuration $config;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $router = new Router();
        $router::scope('/', function (RouteBuilder $builder) {
            $builder->setExtensions(['json']);
            $builder->resources('Employees', [
                'only' => ['dtoPost','dtoQuery', 'dtoPublic','dtoPostLegacy','dtoQueryLegacy', 'dtoPublicLegacy'],
                'map' => [
                    'dtoPost' => [
                        'action' => 'dtoPost',
                        'method' => 'POST',
                        'path' => 'dto-post'
                    ],
                    'dtoPublic' => [
                        'action' => 'dtoPublic',
                        'method' => 'POST',
                        'path' => 'dto-public'
                    ],
                    'dtoQuery' => [
                        'action' => 'dtoQuery',
                        'method' => 'GET',
                        'path' => 'dto-query'
                    ],
                    'dtoPostLegacy' => [
                        'action' => 'dtoPostLegacy',
                        'method' => 'POST',
                        'path' => 'dto-post-legacy'
                    ],
                    'dtoPublicLegacy' => [
                        'action' => 'dtoPublicLegacy',
                        'method' => 'POST',
                        'path' => 'dto-public-legacy'
                    ],
                    'dtoQueryLegacy' => [
                        'action' => 'dtoQueryLegacy',
                        'method' => 'GET',
                        'path' => 'dto-query-legacy'
                    ],
                ]
            ]);
        });
        $this->router = $router;

        $this->config = new Configuration([
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
        ], SWAGGER_BAKE_TEST_APP);
    }

    /**
     * @todo update in v3.0.0
     */
    public function test_openapi_dto_query(): void
    {
        $cakeRoute = new RouteScanner($this->router, $this->config);
        $swagger = new Swagger(new ModelScanner($cakeRoute, $this->config));
        $arr = json_decode($swagger->toString(), true);

        foreach (['dto-query', 'dto-query-legacy'] as $path) {
            $operation = $arr['paths']['/employees/' . $path]['get'];

            $this->assertEquals('first_name', $operation['parameters'][0]['name']);
            $this->assertEquals('last_name', $operation['parameters'][1]['name']);
            $this->assertEquals('title', $operation['parameters'][2]['name']);
            $this->assertEquals('age', $operation['parameters'][3]['name']);
            $this->assertEquals('date', $operation['parameters'][4]['name']);
        }
    }

    /**
     * @todo update in v3.0.0
     */
    public function test_openapi_dto_post(): void
    {
        $cakeRoute = new RouteScanner($this->router, $this->config);
        $swagger = new Swagger(new ModelScanner($cakeRoute, $this->config));
        $arr = json_decode($swagger->toString(), true);

        foreach (['dto-post' ,'dto-post-legacy'] as $route) {
            $operation = $arr['paths']['/employees/' . $route]['post'];
            $properties = $operation['requestBody']['content']['application/x-www-form-urlencoded']['schema']['properties'];

            $this->assertArrayHasKey('last_name', $properties);
            $this->assertArrayHasKey('first_name', $properties);
            $this->assertArrayHasKey('title', $properties);
            $this->assertArrayHasKey('age', $properties);
            $this->assertArrayHasKey('date', $properties);
        }
    }

    /**
     * @deprecated remove in v3.0.0
     */
    public function test_openapi_dto_post_with_public_schema(): void
    {
        $cakeRoute = new RouteScanner($this->router, $this->config);
        $swagger = new Swagger(new ModelScanner($cakeRoute, $this->config));
        $arr = json_decode($swagger->toString(), true);

        $parameterized = [
            'dto-public' => 'EmployeeDataRequestPublicSchema',
            'dto-public-legacy' => 'EmployeeDataRequestPublicSchemaLegacy'
        ];

        foreach ($parameterized as $path => $class) {
            $this->assertArrayHasKey($class, $arr['components']['schemas']);

            $properties = $arr['components']['schemas'][$class]['properties'];
            $this->assertArrayHasKey('last_name', $properties);
            $this->assertArrayHasKey('first_name', $properties);
            $this->assertArrayHasKey('title', $properties);
            $this->assertArrayHasKey('age', $properties);
            $this->assertArrayHasKey('date', $properties);

            $operation = $arr['paths']['/employees/' . $path]['post'];
            $ref = $operation['requestBody']['content']['application/x-www-form-urlencoded']['schema']['$ref'];
            $this->assertEquals('#/components/schemas/' . $class, $ref);
        }
    }

}
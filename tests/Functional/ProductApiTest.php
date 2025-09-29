<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ProductApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private FakerGenerator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();

        $this->faker  = FakerFactory::create();
        $this->client = static::createClient();

        $em   = static::getContainer()->get('doctrine')->getManager();
        $tool = new SchemaTool($em);
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    public function testPostCreatesProduct201(): void
    {
        $name  = $this->faker->words(2, true);
        $price = $this->faker->randomFloat(2, 1, 999.99);

        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $name, 'price' => $price], JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/i', $data['id']);
        $this->assertSame($name, $data['name']);
        $this->assertMatchesRegularExpression(sprintf('/^PROD-%s-[0-9a-f]{7}$/i', self::skuPrefix($name)), $data['sku']);
        $this->assertEqualsWithDelta((float)$price, (float)$data['price'], 0.01);
        $this->assertNotEmpty($data['createdAt']);
        $this->assertNull($data['updatedAt']);
    }

    public function testPostValidationErrors422(): void
    {
        // negative price
        $this->client->request('POST','/api/products',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$this->faker->words(2, true), 'price'=>-1], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);

        // empty name
        $this->client->request('POST','/api/products',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>'', 'price'=>$this->faker->randomFloat(2, 1, 50)], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPostNameTooLong422(): void
    {
        $tooLong = str_repeat('A', 256); 
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $tooLong, 'price' => $this->faker->randomFloat(2, 1, 100)], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPostPriceNonNumeric422(): void
    {
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $this->faker->words(2, true), 'price' => $this->faker->word()], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPostPriceStringTotallyInvalid422(): void
    {
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $this->faker->words(2, true), 'price' => $this->faker->word()], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPostPriceMissing422(): void
    {
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $this->faker->words(2, true)], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testGet200And404(): void
    {
        $name  = $this->faker->words(2, true);
        $price = $this->faker->randomFloat(2, 1, 200);

        // create
        $this->client->request('POST','/api/products',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$name,'price'=>$price], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['id'];

        // 200
        $this->client->request('GET', "/api/products/$id");
        $this->assertResponseStatusCodeSame(200);

        // 404 valid UUID but not found
        $this->client->request('GET', '/api/products/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->assertResponseStatusCodeSame(404);

        // 404 invalid UUID
        $this->client->request('GET', '/api/products/not-a-uuid');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPutUpdates200AndUpdatedAt(): void
    {
        $initialName  = $this->faker->words(2, true);
        $initialPrice = $this->faker->randomFloat(2, 1, 100);

        // create
        $this->client->request('POST','/api/products',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$initialName,'price'=>$initialPrice], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['id'];

        // update
        $newName  = $this->faker->words(2, true) . ' v2';
        $newPrice = $this->faker->randomFloat(2, 5, 150);

        $this->client->request('PUT', "/api/products/$id",
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$newName,'price'=>$newPrice], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($newName, $data['name']);
        $this->assertEqualsWithDelta((float)$newPrice, (float)$data['price'], 0.01);
        $this->assertNotEmpty($data['updatedAt']);
    }

    public function testPutNotFoundAndValidation422(): void
    {
        // 404 valid UUID but not found
        $this->client->request('PUT','/api/products/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$this->faker->word(), 'price'=>1], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(404);

        // 404 invalid UUID
        $this->client->request('PUT','/api/products/not-a-uuid',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$this->faker->word(), 'price'=>1], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(404);

        // created then 422 (negative price)
        $name = $this->faker->words(2, true);
        $this->client->request('POST','/api/products',
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['name'=>$name,'price'=>$this->faker->randomFloat(2, 1, 50)], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $this->client->request('PUT', "/api/products/$id",
            server:['CONTENT_TYPE'=>'application/json'],
            content: json_encode(['price'=>-5], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutNameTooLong422(): void
    {
        // seed
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $this->faker->words(2, true), 'price' => $this->faker->randomFloat(2, 1, 50)], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['id'];

        // name > 255
        $tooLong = str_repeat('B', 256);
        $this->client->request(
            'PUT',
            "/api/products/$id",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $tooLong], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutPriceNonNumeric422(): void
    {
        // seed
        $this->client->request(
            'POST',
            '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => $this->faker->words(2, true), 'price' => $this->faker->randomFloat(2, 1, 50)], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $this->client->request(
            'PUT',
            "/api/products/$id",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['price' => $this->faker->word()], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCodeSame(422);
    }

    /** Expected SKU prefix */
    private static function skuPrefix(string $name): string
    {
        return mb_strtoupper(mb_substr(trim($name), 0, 4));
    }
}

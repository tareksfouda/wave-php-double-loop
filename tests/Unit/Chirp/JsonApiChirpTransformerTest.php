<?php declare(strict_types=1);

namespace Test\Unit\Chirp;

use Chirper\Chirp\Chirp;
use Chirper\Chirp\JsonApiChirpTransformer;
use Chirper\Json\InvalidJsonApiException;
use Chirper\Json\InvalidJsonException;
use Test\TestCase;

class JsonApiChirpTransformerTest extends TestCase
{
    public function testToChirpReturnsChirpWithPropertiesFromJson()
    {
        $uuid      = $this->faker->uuid;
        $chirpText = $this->faker->realText(50);
        $author    = $this->faker->userName;

        $attributes = (object)[
            "text"   => $chirpText,
            "author" => $author
        ];
        $data       = (object)[
            "type"       => "chirp",
            "id"         => $uuid,
            "attributes" => $attributes
        ];

        $json = json_encode((object)['data' => $data]);

        $now           = (new \DateTime())->format('Y-m-d H:i:s');
        $expectedChirp = new Chirp($uuid, $chirpText, $author, $now);

        $transformer = new JsonApiChirpTransformer();
        $chirp       = $transformer->toChirp($json);

        $this->assertEquals($expectedChirp, $chirp);
    }

    public function testToChirpThrowsInvalidJsonExceptionWhenJsonInvalid()
    {
        $this->expectException(InvalidJsonException::class);
        $json        = '{"data":"}';
        $transformer = new JsonApiChirpTransformer();
        $transformer->toChirp($json);
    }

    /**
     * @dataProvider  invalidJsonProvider
     */
    public function testToChirpThrowsInvalidJsonApiExceptionWhenJsonApiInvalid(string $json)
    {
        $this->expectException(InvalidJsonApiException::class);
        $transformer = new JsonApiChirpTransformer();
        $transformer->toChirp($json);
    }

    public function invalidJsonProvider()
    {
        return [
            'missingData'            => ['{}'],
            'missingType'            => ['{"data":{}}'],
            'missingId'              => ['{"data":{"type":"chirp"}}'],
            'missingAttributes'      => ['{"data":{"type":"chirp","id":"uuid"}}'],
            'missingTextAttribute'   => ['{"data":{"type":"chirp","id":"uuid","attributes":{}}}'],
            'missingAuthorAttribute' => ['{"data":{"type":"chirp","id":"uuid","attributes":{"text":"sometext"}}}'],
        ];
    }

    public function testToJsonReturnsJsonString()
    {
        $uuid   = $this->faker->uuid;
        $text   = $this->faker->realText(50);
        $author = $this->faker->userName;
        $date   = $this->faker->date('Y-m-d H:i:s');
        $chirp  = new Chirp($uuid, $text, $author, $date);

        $expectedJson =
            <<<JSON
            {
                "data":{
                    "id":"{$uuid}",
                    "type":"chirp",
                    "attributes":{
                        "text":"{$text}",
                        "author":"{$author}",
                        "created_at":"{$date}"
                    }
                }
            }
JSON;

        $transformer = new JsonApiChirpTransformer();
        $json        = $transformer->toJson($chirp);

        $this->assertJsonStringEqualsJsonString($expectedJson, $json);
    }
}

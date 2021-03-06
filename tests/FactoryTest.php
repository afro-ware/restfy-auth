<?php

/*
 * This file is part of jwTauth.
 *
 * (c) Afroware <contact@afroware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Afroware\JwTauth\Test;

use Mockery;
use Afroware\JwTauth\Factory;
use Afroware\JwTauth\Payload;
use Afroware\JwTauth\Claims\JwTId;
use Afroware\JwTauth\Claims\Custom;
use Afroware\JwTauth\Claims\Issuer;
use Afroware\JwTauth\Claims\Subject;
use Afroware\JwTauth\Claims\IssuedAt;
use Afroware\JwTauth\Claims\NotBefore;
use Afroware\JwTauth\Claims\Collection;
use Afroware\JwTauth\Claims\Expiration;
use Afroware\JwTauth\Validators\PayloadValidator;
use Afroware\JwTauth\Claims\Factory as ClaimFactory;

class FactoryTest extends AbstractTestCase
{
    /**
     * @var \Mockery\MockInterface|\Afroware\JwTauth\Claims\Factory
     */
    protected $claimFactory;

    /**
     * @var \Mockery\MockInterface|\Afroware\JwTauth\Validators\PayloadValidator
     */
    protected $validator;

    /**
     * @var \Afroware\JwTauth\Factory
     */
    protected $factory;

    public function setUp()
    {
        parent::setUp();

        $this->claimFactory = Mockery::mock(ClaimFactory::class);
        $this->validator = Mockery::mock(PayloadValidator::class);
        $this->factory = new Factory($this->claimFactory, $this->validator);
    }

    /** @test */
    public function it_should_return_a_payload_when_passing_an_array_of_claims()
    {
        $expTime = $this->testNowTimestamp + 3600;

        // these are added from default claims
        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('exp')->andReturn(new Expiration($expTime));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwTId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt(123));

        // custom claims that override
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->twice()->with('jti', 'foo')->andReturn(new JwTId('foo'));
        $this->claimFactory->shouldReceive('get')->twice()->with('nbf', 123)->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('get')->twice()->with('iat', 123)->andReturn(new IssuedAt(123));

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);

        // once
        $claims = $this->factory->customClaims([
            'sub' => 1,
            'jti' => 'foo',
            'iat' => 123,
            'nbf' => 123,
        ])->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->customClaims(['sub' => 1, 'jti' => 'foo', 'iat' => 123, 'nbf' => 123])->make();

        $this->assertSame($payload->get('sub'), 1);
        $this->assertSame($payload->get('iat'), 123);
        $this->assertSame($payload['exp'], $expTime);
        $this->assertSame($payload['jti'], 'foo');

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /** @test */
    public function it_should_return_a_payload_when_chaining_claim_methods()
    {
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->twice()->with('foo', 'baz')->andReturn(new Custom('foo', 'baz'));

        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('exp')->andReturn(new Expiration($this->testNowTimestamp + 3600));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt($this->testNowTimestamp));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwTId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore($this->testNowTimestamp));

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);

        // once
        $claims = $this->factory->sub(1)->foo('baz')->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->sub(1)->foo('baz')->make();

        $this->assertSame($payload['sub'], 1);
        $this->assertSame($payload->get('jti'), 'foo');
        $this->assertSame($payload->get('foo'), 'baz');

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /** @test */
    public function it_should_return_a_payload_when_passing_miltidimensional_array_as_custom_claim_to_make_method()
    {
        // these are added from default claims
        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('exp')->andReturn(new Expiration($this->testNowTimestamp + 3600));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwTId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt(123));

        // custom claims that override
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->twice()->with('foo', ['bar' => [0, 0, 0]])->andReturn(new Custom('foo', ['bar' => [0, 0, 0]]));

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);

        // once
        $claims = $this->factory->sub(1)->foo(['bar' => [0, 0, 0]])->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->sub(1)->foo(['bar' => [0, 0, 0]])->make();

        $this->assertSame($payload->get('sub'), 1);
        $this->assertSame($payload->get('jti'), 'foo');
        $this->assertSame($payload->get('foo'), ['bar' => [0, 0, 0]]);
        $this->assertSame($payload->get('foo.bar'), [0, 0, 0]);

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /** @test */
    public function it_should_exclude_the_exp_claim_when_setting_ttl_to_null()
    {
        // these are added from default claims
        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwTId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt(123));

        // custom claims that override
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));

        $this->claimFactory->shouldReceive('setTTL')->with(null)->andReturn($this->claimFactory);
        $this->claimFactory->shouldReceive('getTTL')->andReturn(null);

        // once
        $claims = $this->factory->setTTL(null)->sub(1)->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->setTTL(null)->sub(1)->make();

        $this->assertNull($payload->get('exp'));

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /** @test */
    public function it_should_exclude_claims_from_previous_payloads()
    {
        $fooClaim = new Custom('foo', 'bar');
        $barClaim = new Custom('baz', 'qux');

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);
        $this->claimFactory->shouldReceive('get')->with('foo', 'bar')->twice()->andReturn($fooClaim);
        $this->claimFactory->shouldReceive('get')->with('baz', 'qux')->twice()->andReturn($barClaim);
        $this->validator->shouldReceive('setRefreshFlow->check')->once()->andReturn(new Collection([$fooClaim, $barClaim]));

        $payload = $this->factory->setDefaultClaims([])
            ->customClaims([
                'foo' => 'bar',
                'baz' => 'qux',
            ])->make();

        $this->assertSame($payload->get('foo'), 'bar');
        $this->assertSame($payload->get('baz'), 'qux');

        $this->validator->shouldReceive('setRefreshFlow->check')->once()->andReturn(new Collection([$fooClaim]));

        $payload = $this->factory->setDefaultClaims([])->customClaims(['foo' => 'bar'])->make(true);

        $this->assertSame($payload->get('foo'), 'bar');
        $this->assertFalse($payload->hasKey('baz'));
    }

    /** @test */
    public function it_should_set_the_default_claims()
    {
        $this->factory->setDefaultClaims(['sub', 'iat']);

        $this->assertSame($this->factory->getDefaultClaims(), ['sub', 'iat']);
    }

    /** @test */
    public function it_should_get_payload_with_a_predefined_collection_of_claims()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwTId('foo'),
        ];

        $collection = Collection::make($claims);
        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = $this->factory->withClaims($collection);

        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertSame($payload->get('sub'), 1);
    }

    /** @test */
    public function it_should_get_the_validator()
    {
        $this->assertInstanceOf(PayloadValidator::class, $this->factory->validator());
    }
}

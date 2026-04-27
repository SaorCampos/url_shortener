<?php

namespace Tests\Unit\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Application\ShortUrl\Handlers\CreateShortUrlCommandHandler;
use App\Domain\Shared\Services\IdGenerator;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\Base62Encoder;
use App\Infrastructure\Cache\BloomFilterService;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class CreateShortUrlCommandHandlerTest extends TestCase
{
    private $repository;
    private $encoder;
    private $idGenerator;
    private $bloomFilter;
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ShortUrlRepository::class);
        $this->encoder = Mockery::mock(Base62Encoder::class);
        $this->idGenerator = Mockery::mock(IdGenerator::class);
        $this->bloomFilter = Mockery::mock(BloomFilterService::class);
        $this->handler = new CreateShortUrlCommandHandler(
            $this->repository,
            $this->encoder,
            $this->idGenerator,
            $this->bloomFilter
        );
    }

    public function test_should_return_existing_url_if_already_shortened()
    {
        $url = 'https://meuprojeto.com';
        $existing = Mockery::mock(ShortUrl::class);
        $command = new CreateShortUrlCommand($url);
        $this->bloomFilter->shouldReceive('mightExist')
            ->with("url:{$url}")
            ->andReturn(true);
        $this->repository->shouldReceive('findByOriginalUrl')
            ->once()
            ->with($url)
            ->andReturn($existing);
        $result = $this->handler->handle($command);
        $this->assertSame($existing, $result);
    }

    public function test_should_create_new_short_url_and_trigger_side_effects()
    {
        $url = 'https://laravel.com';
        $command = new CreateShortUrlCommand($url);
        $code = 'larav1';
        $this->bloomFilter->shouldReceive('mightExist')->with("url:{$url}")->andReturn(false);
        $this->bloomFilter->shouldReceive('mightExist')->with("code:{$code}")->andReturn(false);
        $this->idGenerator->shouldReceive('generate')->andReturn('ulid-123');
        $this->encoder->shouldReceive('generate')->andReturn($code);
        $this->repository->shouldNotReceive('findByCode');
        $this->repository->shouldReceive('findByOriginalUrl')->andReturn(null);
        $this->repository->shouldReceive('save')->once();
        Redis::shouldReceive('setex')->once();
        $this->bloomFilter->shouldReceive('add')->once()->with("url:{$url}");
        $this->bloomFilter->shouldReceive('add')->once()->with("code:{$code}");
        $this->handler->handle($command);
    }
}

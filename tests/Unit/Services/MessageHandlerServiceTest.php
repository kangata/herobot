<?php

namespace Tests\Unit\Services;

use App\Models\Balance;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Team;
use App\Services\AIResponseService;
use App\Services\MediaProcessingService;
use App\Services\MessageHandlerService;
use App\Services\TokenPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class MessageHandlerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessageHandlerService $messageHandlerService;
    protected $aiResponseService;
    protected $tokenPricingService;
    protected $mediaProcessingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->aiResponseService = Mockery::mock(AIResponseService::class);
        $this->tokenPricingService = Mockery::mock(TokenPricingService::class);
        $this->mediaProcessingService = Mockery::mock(MediaProcessingService::class);

        // Create the service with mocked dependencies
        $this->messageHandlerService = new MessageHandlerService(
            $this->aiResponseService,
            $this->tokenPricingService,
            $this->mediaProcessingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_message_without_channel_id_throws_exception()
    {
        // When no channel ID is provided, bot is null, which causes a type error
        // This test verifies that the service properly handles this case
        $this->expectException(\TypeError::class);

        $this->messageHandlerService->handleMessage(
            null,
            'user123',
            'Hello'
        );
    }

    public function test_handle_message_with_valid_channel()
    {
        // Set self-hosted mode to skip credit checks
        config(['app.edition' => 'self-hosted']);
        
        // Create test data
        $team = Team::factory()->create();
        $bot = Bot::factory()->create(['team_id' => $team->id]);
        $channel = Channel::factory()->create(['team_id' => $team->id]);
        
        // Associate bot with channel
        $channel->bots()->attach($bot->id);

        // Mock AI response
        $expectedResponse = 'Hello from bot!';
        $this->aiResponseService
            ->shouldReceive('generateResponse')
            ->once()
            ->with(Mockery::type(Bot::class), Mockery::type(Channel::class), 'Hello', 'user123', null, 'html')
            ->andReturn($expectedResponse);

        $result = $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'Hello'
        );

        $this->assertEquals($expectedResponse, $result['response']);
        $this->assertEquals($channel->id, $result['channel']->id);
        $this->assertEquals($bot->id, $result['bot']->id);
        $this->assertNull($result['media']);
    }

    public function test_handle_message_throws_exception_when_no_bot_found()
    {
        $team = Team::factory()->create();
        $channel = Channel::factory()->create(['team_id' => $team->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No bot found for this channel');

        $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'Hello'
        );
    }

    public function test_handle_message_with_media_file()
    {
        // Set self-hosted mode to skip credit checks
        config(['app.edition' => 'self-hosted']);
        
        Storage::fake('local');
        
        $team = Team::factory()->create();
        $bot = Bot::factory()->create(['team_id' => $team->id]);
        $channel = Channel::factory()->create(['team_id' => $team->id]);
        $channel->bots()->attach($bot->id);

        // Create fake uploaded file
        $mediaFile = UploadedFile::fake()->image('test.jpg');
        $processedMedia = ['type' => 'image', 'url' => 'processed_url'];

        // Mock media processing
        $processedMedia = null; // MediaProcessingService returns ChatMedia|null
        $this->mediaProcessingService
            ->shouldReceive('process')
            ->once()
            ->with(Mockery::type(UploadedFile::class), 'Check this image')
            ->andReturn($processedMedia);

        // Mock AI response
        $expectedResponse = 'I can see the image!';
        $this->aiResponseService
            ->shouldReceive('generateResponse')
            ->once()
            ->with(Mockery::type(Bot::class), Mockery::type(Channel::class), 'Check this image', 'user123', null, 'html')
            ->andReturn($expectedResponse);

        $result = $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'Check this image',
            $mediaFile
        );

        $this->assertEquals($expectedResponse, $result['response']);
        $this->assertNull($result['media']);
    }

    public function test_handle_message_with_insufficient_credits_in_cloud_mode()
    {
        // Set cloud mode
        config(['app.edition' => 'cloud']);

        $team = Team::factory()->create();
        $team->balance()->create([
            'amount' => 0 // Zero credits
        ]);
        $bot = Bot::factory()->create([
            'team_id' => $team->id,
            'openai_api_key' => null,
            'gemini_api_key' => null
        ]);
        $channel = Channel::factory()->create(['team_id' => $team->id]);
        $channel->bots()->attach($bot->id);

        // Set up mock to prevent any calls to AI service
        $this->aiResponseService->shouldNotReceive('generateResponse');
        $this->mediaProcessingService->shouldNotReceive('process');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient credits/');

        $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'This is a long message that would require more credits than available'
        );
    }

    public function test_handle_message_skips_credit_check_with_custom_api_keys()
    {
        // Set cloud mode
        config(['app.edition' => 'cloud']);

        $team = Team::factory()->create();
        $team->balance()->create([
            'amount' => 1000 // Very low credits
        ]);
        $bot = Bot::factory()->create([
            'team_id' => $team->id,
            'openai_api_key' => 'custom-key'
        ]);
        $channel = Channel::factory()->create(['team_id' => $team->id]);
        $channel->bots()->attach($bot->id);

        // Mock AI response
        $expectedResponse = 'Response with custom API key';
        $this->aiResponseService
            ->shouldReceive('generateResponse')
            ->once()
            ->andReturn($expectedResponse);

        $result = $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'This message should work with custom API keys'
        );

        $this->assertEquals($expectedResponse, $result['response']);
    }

    public function test_handle_message_skips_credit_check_in_self_hosted_mode()
    {
        // Set self-hosted mode
        config(['app.edition' => 'self-hosted']);

        $team = Team::factory()->create();
        $bot = Bot::factory()->create(['team_id' => $team->id]);
        $channel = Channel::factory()->create(['team_id' => $team->id]);
        $channel->bots()->attach($bot->id);

        // Mock AI response
        $expectedResponse = 'Response in self-hosted mode';
        $this->aiResponseService
            ->shouldReceive('generateResponse')
            ->once()
            ->andReturn($expectedResponse);

        $result = $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'This message should work in self-hosted mode'
        );

        $this->assertEquals($expectedResponse, $result['response']);
    }

    public function test_validate_message_data_with_valid_data()
    {
        $data = [
            'channelId' => 1,
            'sender' => 'user123',
            'message' => 'Hello world',
        ];

        $result = $this->messageHandlerService->validateMessageData($data);

        $this->assertEquals($data, $result);
    }

    public function test_validate_message_data_with_media_file()
    {
        Storage::fake('local');
        $mediaFile = UploadedFile::fake()->image('test.jpg', 100, 100)->size(1024); // 1MB

        $data = [
            'channelId' => 1,
            'sender' => 'user123',
            'message' => 'Check this image',
            'media_file' => $mediaFile,
        ];

        $result = $this->messageHandlerService->validateMessageData($data);

        $this->assertEquals(1, $result['channelId']);
        $this->assertEquals('user123', $result['sender']);
        $this->assertEquals('Check this image', $result['message']);
        $this->assertInstanceOf(UploadedFile::class, $result['media_file']);
    }

    public function test_validate_message_data_throws_exception_for_missing_channel_id()
    {
        $data = [
            'sender' => 'user123',
            'message' => 'Hello world',
        ];

        $this->expectException(ValidationException::class);

        $this->messageHandlerService->validateMessageData($data);
    }

    public function test_validate_message_data_throws_exception_for_missing_sender()
    {
        $data = [
            'channelId' => 1,
            'message' => 'Hello world',
        ];

        $this->expectException(ValidationException::class);

        $this->messageHandlerService->validateMessageData($data);
    }

    public function test_validate_message_data_throws_exception_for_invalid_channel_id()
    {
        $data = [
            'channelId' => 'not-a-number',
            'sender' => 'user123',
            'message' => 'Hello world',
        ];

        $this->expectException(ValidationException::class);

        $this->messageHandlerService->validateMessageData($data);
    }

    public function test_validate_message_data_throws_exception_for_oversized_file()
    {
        Storage::fake('local');
        $mediaFile = UploadedFile::fake()->image('large.jpg')->size(25000); // 25MB (over 20MB limit)

        $data = [
            'channelId' => 1,
            'sender' => 'user123',
            'message' => 'Check this large image',
            'media_file' => $mediaFile,
        ];

        $this->expectException(ValidationException::class);

        $this->messageHandlerService->validateMessageData($data);
    }

    public function test_validate_message_data_throws_exception_for_invalid_file_type()
    {
        Storage::fake('local');
        $mediaFile = UploadedFile::fake()->create('test.exe', 1024, 'application/x-executable');

        $data = [
            'channelId' => 1,
            'sender' => 'user123',
            'message' => 'Check this file',
            'media_file' => $mediaFile,
        ];

        $this->expectException(ValidationException::class);

        $this->messageHandlerService->validateMessageData($data);
    }

    public function test_validate_message_data_allows_null_message()
    {
        $data = [
            'channelId' => 1,
            'sender' => 'user123',
            'message' => null,
        ];

        $result = $this->messageHandlerService->validateMessageData($data);

        $this->assertEquals($data, $result);
    }

    public function test_validate_message_data_allows_various_media_types()
    {
        Storage::fake('local');
        
        $validMimeTypes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
        ];

        foreach ($validMimeTypes as $extension => $mimeType) {
            $mediaFile = UploadedFile::fake()->create("test.{$extension}", 1024, $mimeType);

            $data = [
                'channelId' => 1,
                'sender' => 'user123',
                'message' => "Check this {$extension} file",
                'media_file' => $mediaFile,
            ];

            $result = $this->messageHandlerService->validateMessageData($data);
            $this->assertInstanceOf(UploadedFile::class, $result['media_file']);
        }
    }

    public function test_handle_message_with_custom_format()
    {
        // Set self-hosted mode to skip credit checks
        config(['app.edition' => 'self-hosted']);
        
        $team = Team::factory()->create();
        $bot = Bot::factory()->create(['team_id' => $team->id]);
        $channel = Channel::factory()->create(['team_id' => $team->id]);
        $channel->bots()->attach($bot->id);

        // Mock AI response
        $expectedResponse = 'Markdown response';
        $this->aiResponseService
            ->shouldReceive('generateResponse')
            ->once()
            ->with(Mockery::type(Bot::class), Mockery::type(Channel::class), 'Hello', 'user123', null, 'markdown')
            ->andReturn($expectedResponse);

        $result = $this->messageHandlerService->handleMessage(
            $channel->id,
            'user123',
            'Hello',
            null,
            null,
            'markdown'
        );

        $this->assertEquals($expectedResponse, $result['response']);
    }
}

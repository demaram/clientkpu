<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\PiketController;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class PiketControllerApproveTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[RunInSeparateProcess]
    public function test_approve_piket_success_when_waiting_approval(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) [
                'id_client' => null,
            ]);

        $piket = Mockery::mock();
        $piket->client_id = 1;
        $piket->status = 'waiting_approval';
        $piket->shouldReceive('save')->once()->andReturn(true);

        $lemburKaryawanMock = Mockery::mock('alias:App\\Models\\LemburKaryawan');
        $lemburKaryawanMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with(99)
            ->andReturn($piket);

        $response = (new PiketController())->approve(99);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Piket berhasil di-approve', $payload['message']);
        $this->assertSame('approved', $piket->status);
    }

    #[RunInSeparateProcess]
    public function test_approve_piket_returns_403_for_unauthorized_client(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) [
                'id_client' => 2,
            ]);

        $piket = Mockery::mock();
        $piket->client_id = 1;
        $piket->status = 'waiting_approval';
        $piket->shouldNotReceive('save');

        $lemburKaryawanMock = Mockery::mock('alias:App\\Models\\LemburKaryawan');
        $lemburKaryawanMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with(100)
            ->andReturn($piket);

        $response = (new PiketController())->approve(100);
        $payload = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Unauthorized access', $payload['message']);
    }

    #[RunInSeparateProcess]
    public function test_approve_piket_returns_error_when_status_already_processed(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) [
                'id_client' => null,
            ]);

        $piket = Mockery::mock();
        $piket->client_id = 1;
        $piket->status = 'approved';
        $piket->shouldNotReceive('save');

        $lemburKaryawanMock = Mockery::mock('alias:App\\Models\\LemburKaryawan');
        $lemburKaryawanMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with(101)
            ->andReturn($piket);

        $response = (new PiketController())->approve(101);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Piket sudah diproses sebelumnya', $payload['message']);
    }
}

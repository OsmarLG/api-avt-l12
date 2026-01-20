<?php

use App\Models\Person;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Api\PersonFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create person with files happy path', function () {
    Storage::fake('public');

    $user = \App\Models\User::factory()->create();

    $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
    $file2 = UploadedFile::fake()->image('img1.png');

    $data = [
        'nombres' => 'Juan',
        'apellido_paterno' => 'Perez',
        'sexo' => 'masculino',
        'fecha_nacimiento' => '1990-01-01',
        'edad' => 30,
        'nacionalidad' => 'mexicana',
        'estado_civil' => 'soltero',
        'files' => [
            [
                'file' => $file1,
                'title' => 'Documento 1',
                'visibility' => 'public',
            ],
            [
                'file' => $file2,
                'title' => 'Imagen 1',
                'visibility' => 'private',
            ]
        ]
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/people/with-files', $data);

    $response->assertCreated();

    $person = Person::first();
    expect($person)->not->toBeNull();
    expect($person->nombres)->toBe('Juan');

    // Check files handling
    $files = $person->files;
    expect($files)->toHaveCount(2);

    $dbFile1 = $files->where('original_name', 'doc1.pdf')->first();
    $dbFile2 = $files->where('original_name', 'img1.png')->first();

    expect($dbFile1)->not->toBeNull();
    expect($dbFile2)->not->toBeNull();

    // Check storage presence
    Storage::disk('public')->assertExists($dbFile1->path);
    Storage::disk('public')->assertExists($dbFile2->path);
});

test('create person with files rollback on error', function () {
    Storage::fake('public');
    $user = \App\Models\User::factory()->create();

    // Mock Service to fail on second upload
    // We can't easily mock the partial service call inside the transaction without complex mocking setup.
    // Instead, we'll try to trigger a failure by ensuring the second file is invalid or by forcing an exception via a mocked FileService if possible.
    // Given the difficulty of mocking the "app(PersonFileService::class)" call inside the service method dynamically for just one call...
    // Let's try to mock the specific method on the service container.

    $mockFileService = Mockery::mock(PersonFileService::class);
    // Partial mock: delegate the first call to real implementation (hard with Mockery over class).
    // Better approach: Let's use a "bad" file that might fail? Or just rely on the logic review.

    // Simulating failure by forcing a throw inside the transaction is key.
    // We can use a spy or mock, but the service is instantiated inside the usecase via app().

    // Let's rely on `partialMock` on the PersonFileService.
    $this->mock(PersonFileService::class, function ($mock) {
        $mock->shouldReceive('upload')
            ->andReturnUsing(function ($person, $file, $meta, $userId) {
                if ($file->getClientOriginalName() === 'fail.png') {
                    throw new \Exception('Simulated Upload Error');
                }

                // Real-ish implementation for the first file to ensure it writes to disk (so we can test deletion)
                // Since we are mocking, we have to fake the write and return a dummy File model.
                $path = 'people/test/' . $file->getClientOriginalName();
                Storage::disk('public')->put($path, 'content');

                return File::factory()->make([
                    'path' => $path,
                    'disk' => 'public',
                ]);
            });
    });

    $file1 = UploadedFile::fake()->create('good.pdf', 100);
    $file2 = UploadedFile::fake()->create('fail.png', 100);

    $data = [
        'nombres' => 'Rollback',
        'apellido_paterno' => 'Test',
        'sexo' => 'masculino',
        'fecha_nacimiento' => '1990-01-01',
        'edad' => 30,
        'nacionalidad' => 'mexicana',
        'estado_civil' => 'soltero',
        'files' => [
            ['file' => $file1],
            ['file' => $file2],
        ]
    ];

    try {
        $this->actingAs($user)
            ->postJson('/api/people/with-files', $data);
    } catch (\Exception $e) {
        // Expected
    }

    // Assert Person was NOT created
    expect(count(Person::where('nombres', 'Rollback')->get()))->toBe(0);

    // Assert File 1 was deleted from disk
    // In our mock, we wrote 'people/test/good.pdf'.
    // The rollback logic calls Storage::disk('public')->delete($path).
    Storage::disk('public')->assertMissing('people/test/good.pdf');
});

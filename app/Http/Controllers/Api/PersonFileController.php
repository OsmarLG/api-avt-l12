<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\People\StorePersonFileRequest;
use App\Http\Resources\Api\FileResource;
use App\Models\File;
use App\Models\Person;
use App\Services\Api\PersonFileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonFileController extends Controller
{
    public function __construct(
        private readonly PersonFileService $service
    ) {
    }

    /**
     * List files for a person.
     *
     * Returns all files attached to the person.
     */
    public function index(Person $person)
    {
        $files = $this->service->list($person);

        return ApiResponse::ok(FileResource::collection($files), 'OK', Response::HTTP_OK);
    }

    /**
     * Upload and attach a file to a person.
     *
     * Stores the file and creates a related File record (morph).
     */
    public function store(StorePersonFileRequest $request, Person $person)
    {
        $data = $request->validated();

        $file = $this->service->upload(
            person: $person,
            uploaded: $request->file('file'),
            meta: $data,
            userId: $request->user()?->id
        );

        return ApiResponse::ok(new FileResource($file), 'Archivo subido', Response::HTTP_CREATED);
    }

    /**
     * Delete a file.
     *
     * Removes the physical file and deletes the File record.
     */
    public function destroy(Request $request, File $file)
    {
        // opcional: validar permisos / que sea fileable Person, etc.
        $this->service->delete($file);

        return ApiResponse::ok(null, 'Archivo eliminado', Response::HTTP_OK);
    }
}

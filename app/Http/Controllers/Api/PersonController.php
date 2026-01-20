<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\People\IndexPeopleRequest;
use App\Http\Requests\Api\People\StorePersonRequest;
use App\Http\Requests\Api\People\StorePersonWithFilesRequest;
use App\Http\Requests\Api\People\UpdatePersonRequest;
use App\Http\Resources\Api\PersonResource;
use App\Http\Resources\Api\PersonSelectResource;
use App\Models\Person;
use App\Services\Api\PersonService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonController extends Controller
{
    public function __construct(
        private readonly PersonService $service
    ) {
    }

    /**
     * Get a listing of the resource.
     *
     * Returns a paginated list of people with optional filters and sorting.
     */
    public function index(IndexPeopleRequest $request)
    {
        $filters = $request->validated();
        $paginator = $this->service->paginate($filters);

        return ApiResponse::ok([
            'items' => PersonResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'OK', Response::HTTP_OK);
    }

    /**
     * Get a select list of people.
     *
     * Provides a list formatted for select inputs, with optional search and limit.
     */
    public function select(Request $request)
    {
        $search = $request->string('search')->toString();
        $limit = (int) ($request->input('limit', 20));

        $items = $this->service->selectList($search ?: null, $limit);

        return ApiResponse::ok(PersonSelectResource::collection($items));
    }

    /**
     * Display the specified resource.
     *
     * Get detailed information about a specific person by ID.
     */
    public function show(Person $person)
    {
        return ApiResponse::ok(
            new PersonResource($this->service->find($person)),
            'OK',
            Response::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * Create a new person (and optional related phones/emails/references).
     */
    public function store(StorePersonRequest $request)
    {
        $person = $this->service->create($request->validated());

        return ApiResponse::ok(
            new PersonResource($person),
            'Persona creada',
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * Update an existing person (and optional related phones/emails/references).
     */
    public function update(UpdatePersonRequest $request, Person $person)
    {
        $person = $this->service->update($person, $request->validated());

        return ApiResponse::ok(
            new PersonResource($person),
            'Persona actualizada',
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * Soft-delete a person and (optionally) clean related records.
     */
    public function destroy(Person $person)
    {
        $this->service->delete($person);

        // OJO: si usas 204 no debería venir body; por consistencia con tu ApiResponse mejor 200.
        return ApiResponse::ok(null, 'Persona eliminada', Response::HTTP_OK);
    }

    /**
     * Get options for select inputs.
     *
     * Alias for the select method.
     */
    public function options(Request $request)
    {
        return $this->select($request);
    }

    /**
     * Create a new person with files attached.
     */
    public function storeWithFiles(StorePersonWithFilesRequest $request)
    {
        $data = $request->validated();

        // Separate files from other data
        $files = $data['files'] ?? [];
        unset($data['files']);

        $person = $this->service->createWithFiles($data, $files, $request->user()?->id);

        return ApiResponse::ok(
            new PersonResource($person),
            'Persona creada con archivos',
            Response::HTTP_CREATED
        );
    }
}

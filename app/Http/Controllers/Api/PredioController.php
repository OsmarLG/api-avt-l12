<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Predio\IndexPredioRequest;
use App\Http\Requests\Api\Predio\StorePredioRequest;
use App\Http\Requests\Api\Predio\UpdatePredioRequest;
use App\Http\Requests\Api\Predio\ImportPrediosRequest;
use App\Http\Resources\Api\PredioResource;
use App\Models\Predio;
use App\Services\Api\PredioService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PredioController extends Controller
{
    public function __construct(protected PredioService $predioService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexPredioRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $paginator = $this->predioService->paginate($validated);

        return ApiResponse::ok([
            'items' => PredioResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Predio $predio): JsonResponse
    {
        return ApiResponse::ok(new PredioResource($this->predioService->find($predio)));
    }

    /**
     * Import Predios from GeoJSON.
     */
    public function import(ImportPrediosRequest $request): JsonResponse
    {
        ini_set('memory_limit', '-1');

        $validated = $request->validated();
        $imported = $this->predioService->importPredios($validated['claves_catastrales']);

        return ApiResponse::ok(
            PredioResource::collection($imported),
            count($imported) . ' Predios imported successfully.'
        );
    }

    /**
     * Get predios by distance (spatial search).
     */
    public function byDistance(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'distance' => 'nullable|numeric|min:0',
        ]);

        $predios = $this->predioService->getByDistance($request->all());

        return ApiResponse::ok(PredioResource::collection($predios));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePredioRequest $request): JsonResponse
    {
        $predio = $this->predioService->create($request->validated());

        return ApiResponse::ok(new PredioResource($predio), 'Predio creado', Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePredioRequest $request, Predio $predio): JsonResponse
    {
        $predio = $this->predioService->update($predio, $request->validated());

        return ApiResponse::ok(new PredioResource($predio), 'Predio actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Predio $predio): JsonResponse
    {
        $this->predioService->delete($predio);

        return ApiResponse::ok(null, 'Predio eliminado');
    }

    /**
     * Get options for select inputs.
     */
    public function select(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $limit = (int) ($request->input('limit', 20));

        $items = $this->predioService->selectList($search ?: null, $limit);

        return ApiResponse::ok($items);
    }

    /**
     * Alias for select.
     */
    public function options(Request $request): JsonResponse
    {
        return $this->select($request);
    }
}


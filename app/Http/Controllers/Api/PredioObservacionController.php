<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PredioObservacion\StorePredioObservacionRequest;
use App\Http\Requests\Api\PredioObservacion\UpdatePredioObservacionRequest;
use App\Http\Resources\Api\PredioObservacionResource;
use App\Models\Predio;
use App\Models\PredioObservacion;
use App\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class PredioObservacionController extends Controller
{
    /**
     * List all observations for a predio.
     */
    public function index(Predio $predio)
    {
        $observaciones = $predio->observaciones()->latest()->get();

        return ApiResponse::ok(PredioObservacionResource::collection($observaciones));
    }

    /**
     * Store a new observation for a predio.
     */
    public function store(StorePredioObservacionRequest $request, Predio $predio)
    {
        $observacion = $predio->observaciones()->create($request->validated());

        return ApiResponse::ok(
            new PredioObservacionResource($observacion),
            'Observación registrada correctamente',
            Response::HTTP_CREATED
        );
    }

    /**
     * Show a single observation.
     */
    public function show(Predio $predio, PredioObservacion $observacion)
    {
        return ApiResponse::ok(new PredioObservacionResource($observacion));
    }

    /**
     * Update an observation.
     */
    public function update(UpdatePredioObservacionRequest $request, Predio $predio, PredioObservacion $observacion)
    {
        $observacion->update($request->validated());

        return ApiResponse::ok(
            new PredioObservacionResource($observacion),
            'Observación actualizada correctamente'
        );
    }

    /**
     * Delete an observation.
     */
    public function destroy(Predio $predio, PredioObservacion $observacion)
    {
        $observacion->delete();

        return ApiResponse::ok(null, 'Observación eliminada correctamente');
    }
}

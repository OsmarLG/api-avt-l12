<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Venta\UpdateLetraRequest;
use App\Http\Requests\Api\Venta\BatchCreateLetraDiscountRequest;
use App\Http\Requests\Api\Venta\GetLetraInteresDescuentosByVentaRequest;
use App\Http\Requests\Api\Venta\GetLetraInteresDescuentosByFolioRequest;
use App\Http\Resources\Api\LetraResource;
use App\Http\Resources\Api\LetraInteresDescuentoResource;
use App\Models\Letra;
use App\Models\LetraInteresDescuento;
use App\Services\Api\LetraService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class LetraController extends Controller
{
    public function __construct(
        private readonly LetraService $service
    ) {}

    /**
     * Get a listing of installments.
     */
    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->all());

        return ApiResponse::ok([
            'items' => LetraResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection(),
            ],
        ]);
    }

    /**
     * Display the specified installment.
     */
    public function show(Letra $letra)
    {
        return ApiResponse::ok(new LetraResource($this->service->find($letra)));
    }

    /**
     * Update an installment.
     *
     * Allows changing basic data like description, amount, or due date.
     */
    public function update(UpdateLetraRequest $request, Letra $letra)
    {
        $letra = $this->service->update($letra, $request->validated());

        return ApiResponse::ok(
            new LetraResource($letra),
            'Letra actualizada correctamente'
        );
    }

    /**
     * Create discounts for multiple letras.
     *
     * Accepts multiple discounts and applies them to their respective letras.
     */
    public function batchCreateDiscounts(BatchCreateLetraDiscountRequest $request)
    {
        $letras = $this->service->batchCreateDiscounts($request->validated('discounts'));

        return ApiResponse::ok(
            LetraResource::collection($letras),
            'Descuentos aplicados correctamente'
        );
    }

    /**
     * Get all interest discounts grouped by folio for a specific sale.
     */
    public function getInteresDescuentosByVenta(GetLetraInteresDescuentosByVentaRequest $request)
    {
        $descuentos = $this->service->getInteresDescuentosByVenta($request->validated('venta_id'));

        return ApiResponse::ok($descuentos, 'Descuentos obtenidos correctamente');
    }

    /**
     * Get all interest discounts for a specific folio (detail view).
     */
    public function getInteresDescuentosByFolio(GetLetraInteresDescuentosByFolioRequest $request)
    {
    
        $descuentos = $this->service->getInteresDescuentosByFolio($request->validated('folio'));
 
        return ApiResponse::ok(
            LetraInteresDescuentoResource::collection(collect($descuentos)),
            'Descuentos del folio obtenidos correctamente'
        );
    }
}

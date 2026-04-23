<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Venta\CambiarCompradorRequest;
use App\Http\Requests\Api\Venta\CancelVentaRequest;
use App\Http\Requests\Api\Venta\StoreVentaRequest;
use App\Http\Requests\Api\Venta\UpdateVentaRequest;
use App\Http\Resources\Api\VentaResource;
use App\Models\Venta;
use App\Services\Api\VentaService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VentaController extends Controller
{
    public function __construct(
        private readonly VentaService $service
    ) {}

    /**
     * Get a listing of sales.
     *
     * Returns a paginated list of sales with filters for person, property, and state.
     */
    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->all());

        return ApiResponse::ok([
            'items' => VentaResource::collection($paginator),
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
     * Store a newly created sale.
     *
     * Registers a property sale and automatically generates installments if "meses" is selected.
     */
    public function store(StoreVentaRequest $request)
    {
        $venta = $this->service->create($request->validated(), $request->user()->id);

        return ApiResponse::ok(
            new VentaResource($venta),
            'Venta registrada correctamente',
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified sale.
     *
     * Shows detailed information about a sale, including its installments and their payment status.
     */
    public function show(Venta $venta)
    {
        return ApiResponse::ok(new VentaResource($this->service->find($venta)));
    }

    /**
     * Update the specified sale.
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        $venta = $this->service->update($venta, $request->validated());

        return ApiResponse::ok(
            new VentaResource($venta),
            'Venta actualizada correctamente'
        );
    }

    /**
     * Cancel a sale.
     *
     * Marks a sale as cancelled and records the user and reason for cancellation.
     */
    public function cancel(CancelVentaRequest $request, Venta $venta)
    {
        $venta = $this->service->cancel($venta, $request->comentario_cancelacion, $request->user()->id);

        return ApiResponse::ok(
            new VentaResource($venta),
            'Venta cancelada correctamente'
        );
    }

    /**
     * Change the buyer (comprador) of a sale.
     *
     * Updates the person_id and optionally the aval_id of the sale.
     */
    public function cambiarComprador(CambiarCompradorRequest $request, Venta $venta)
    {
        $venta = $this->service->cambiarComprador(
            $venta,
            $request->comprador_id,
            $request->aval_id,
        );

        return ApiResponse::ok(
            new VentaResource($venta),
            'Comprador actualizado correctamente'
        );
    }

    public function detalleInteresMoratorio(Venta $venta)
    {
        $detalle = $this->service->detalleInteresMoratorio($venta);
        return ApiResponse::ok($detalle, 'Detalle de intereses moratorio');
    }
}

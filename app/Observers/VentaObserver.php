<?php

namespace App\Observers;

use App\Models\Venta;
use App\Services\Api\VentaDocumentService;

class VentaObserver
{
    public $afterCommit = true;

    public function __construct(
        protected VentaDocumentService $documentService
    ) {
    }

    /**
     * Handle the Venta "created" event.
     */
    public function created(Venta $venta): void
    {
        $this->documentService->generateContract($venta);
        $this->documentService->generateReceipt($venta);
    }
}

<?php

namespace App\Services;

use App\Models\Person;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonReportService
{
    public function generate(Person $person): string
    {
        $person->load(['phones', 'emails', 'references', 'files']);

        $referenceId = 'REF-PER-' . str_pad($person->id, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');
        $pdf = Pdf::loadView('reports.person-profile', [
            'person' => $person,
            'referenceId' => $referenceId,
        ])
            ->setPaper('a4', 'portrait');

        $fileName = 'ficha_' . $person->id . '_' . Str::slug($person->nombres) . '.pdf';
        $path = 'reports/people/' . $fileName;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

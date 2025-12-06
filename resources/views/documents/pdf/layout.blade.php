<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->reference }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e293b', // Slate 800
                        secondary: '#64748b', // Slate 500
                    }
                }
            }
        }
    </script>
    <style>
        /* Ajustements pour l'impression */
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body class="bg-white font-sans text-sm text-slate-800 antialiased">

<div class="max-w-[210mm] mx-auto p-12 bg-white h-full relative">

    <div class="flex justify-between items-start mb-12">
        <div>
            {{-- <img src="{{ asset('img/logo.png') }}" class="h-10 mb-4"> --}}
            <h1 class="text-2xl font-bold text-primary uppercase tracking-wide">{{ $document->company->name }}</h1>
            <div class="text-secondary mt-1 text-xs leading-relaxed">
                {{ $document->company->address_line1 }}<br>
                {{ $document->company->zip_code }} {{ $document->company->city }}<br>
                {{ $document->company->email }}
            </div>
        </div>

        <div class="text-right">
                <span class="inline-block bg-slate-100 text-slate-700 text-xs px-3 py-1 rounded-full font-bold uppercase mb-2">
                    {{ $document->type->getLabel() }}
                </span>
            <h2 class="text-3xl font-bold text-slate-900"># {{ $document->reference }}</h2>
            <p class="text-secondary mt-1">Date : {{ $document->date->format('d/m/Y') }}</p>
            @if($document->type === \App\Enums\SalesDocumentType::Quote)
                <p class="text-red-500 text-xs font-medium mt-1">Valide jusqu'au : {{ $document->validity_date?->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-12 mb-12 border-t border-b border-slate-100 py-8">
        <div>
            <h3 class="text-xs font-bold text-secondary uppercase tracking-wider mb-2">Chantier</h3>
            @if($document->project)
                <p class="font-semibold text-lg text-primary">{{ $document->project->name }}</p>
                <p class="text-slate-500">{{ $document->project->full_address }}</p>
            @else
                <p class="text-slate-400 italic">Non spécifié</p>
            @endif
        </div>

        <div class="bg-slate-50 p-6 rounded-lg">
            <h3 class="text-xs font-bold text-secondary uppercase tracking-wider mb-2">Facturé à</h3>
            <p class="font-bold text-lg text-primary">{{ $document->tier->name }}</p>
            <div class="text-slate-600">
                {{ $document->tier->address_line1 }}<br>
                {{ $document->tier->zip_code }} {{ $document->tier->city }}
            </div>
            @if($document->tier->vat_number)
                <p class="text-xs text-slate-400 mt-2">TVA : {{ $document->tier->vat_number }}</p>
            @endif
        </div>
    </div>

    <table class="w-full mb-8">
        <thead>
        <tr class="border-b-2 border-slate-800 text-left">
            <th class="py-3 font-bold text-slate-800 w-1/2">Désignation</th>
            <th class="py-3 font-bold text-slate-800 text-center">Qté</th>
            <th class="py-3 font-bold text-slate-800 text-right">P.U. HT</th>
            <th class="py-3 font-bold text-slate-800 text-right">Total HT</th>
        </tr>
        </thead>
        <tbody class="text-slate-600">
        @foreach($document->lines as $line)
            @if($line->type === \App\Enums\SalesDocumentLineType::Section)
                <tr class="bg-slate-100 border-b border-white">
                    <td colspan="4" class="py-2 px-3 font-bold text-slate-800 text-xs uppercase tracking-wide">
                        {{ $line->label }}
                    </td>
                </tr>
            @else
                <tr class="border-b border-slate-100 last:border-0">
                    <td class="py-4 align-top">
                        <p class="font-medium text-slate-800">{{ $line->label }}</p>
                        @if($line->description)
                            <p class="text-xs text-slate-400 mt-1 whitespace-pre-line">{{ $line->description }}</p>
                        @endif
                    </td>
                    <td class="py-4 text-center align-top">{{ +$line->quantity }} {{ $line->unit }}</td>
                    <td class="py-4 text-right align-top">{{ number_format($line->unit_price, 2, ',', ' ') }} €</td>
                    <td class="py-4 text-right align-top font-medium text-slate-800">{{ number_format($line->total_ht, 2, ',', ' ') }} €</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>

    <div class="flex justify-end mb-20">
        <div class="w-1/3">
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="text-slate-500">Total HT</span>
                <span class="font-medium">{{ number_format($document->total_ht, 2, ',', ' ') }} €</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="text-slate-500">TVA ({{ +$document->vat_rate }}%)</span>
                <span class="font-medium">{{ number_format($document->total_vat, 2, ',', ' ') }} €</span>
            </div>
            <div class="flex justify-between py-4 border-b-2 border-slate-800 text-lg">
                <span class="font-bold text-slate-900">Net à Payer</span>
                <span class="font-bold text-primary">{{ number_format($document->total_ttc, 2, ',', ' ') }} €</span>
            </div>
        </div>
    </div>

    <div class="absolute bottom-12 left-12 right-12 text-center text-xs text-slate-400 border-t border-slate-100 pt-8">
        <p class="mb-1">{{ $document->company->name }} - SIRET {{ $document->company->tax_number }}</p>
        @if($document->type === \App\Enums\SalesDocumentType::Invoice)
            <p>Échéance au {{ $document->due_date?->format('d/m/Y') }} - IBAN : {{ $document->company->iban }}</p>
        @endif
    </div>

</div>
</body>
</html>

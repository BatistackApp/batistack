<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        /* Reset basique pour mobile */
        @media only screen and (max-width: 620px) {
            table.body h1 { font-size: 28px !important; margin-bottom: 10px !important; }
            table.body p, table.body ul, table.body ol, table.body td, table.body span, table.body a { font-size: 16px !important; }
            table.body .wrapper, table.body .article { padding: 10px !important; }
            table.body .content { padding: 0 !important; }
            table.body .container { padding: 0 !important; width: 100% !important; }
            table.body .main { border-left-width: 0 !important; border-radius: 0 !important; border-right-width: 0 !important; }
        }
    </style>
</head>
<body style="background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;">
    <tr>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
        <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto;">

            <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">

                <div style="text-align: center; margin-bottom: 20px;">
                    <span style="font-weight: bold; font-size: 20px; color: #333;">{{ $document->company->name }}</span>
                </div>

                <table role="presentation" class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #ffffff; border-radius: 3px; width: 100%;">
                    <tr>
                        <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">

                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
                                            Bonjour {{ $document->tier->contacts->first()?->first_name ?? 'Monsieur/Madame' }},
                                        </p>

                                        @if($document->type === \App\Enums\Facturation\SalesDocumentType::Quote)

                                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
                                                Veuillez trouver ci-joint notre proposition commerciale <strong>N° {{ $document->reference }}</strong>.
                                            </p>

                                            @if($document->project)
                                                <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
                                                    Concerne le chantier : <em>{{ $document->project->name }}</em>
                                                </p>
                                            @endif

                                            <div style="background-color: #f0fdf4; border-left: 4px solid #16a34a; padding: 10px; margin-bottom: 15px; color: #166534;">
                                                Montant de l'offre : <strong>{{ number_format($document->total_ttc, 2, ',', ' ') }} € TTC</strong><br>
                                                <span style="font-size: 12px;">Valable jusqu'au {{ $document->validity_date?->format('d/m/Y') }}</span>
                                            </div>

                                        @elseif($document->type === \App\Enums\Facturation\SalesDocumentType::Invoice)

                                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
                                                Veuillez trouver ci-joint votre facture <strong>N° {{ $document->reference }}</strong> datée du {{ $document->date->format('d/m/Y') }}.
                                            </p>

                                            <div style="background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 10px; margin-bottom: 15px; color: #1e40af;">
                                                Net à payer : <strong>{{ number_format($document->total_ttc, 2, ',', ' ') }} € TTC</strong><br>
                                                <span style="font-size: 12px;">Échéance le : {{ $document->due_date?->format('d/m/Y') }}</span>
                                            </div>

                                        @else
                                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
                                                Veuillez trouver ci-joint le document <strong>{{ $document->reference }}</strong>.
                                            </p>
                                        @endif

                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">
                                            Le document complet est disponible en pièce jointe (PDF).
                                        </p>

                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px; margin-top: 30px;">
                                            Cordialement,<br>
                                            <strong>L'équipe {{ $document->company->name }}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="footer" style="clear: both; margin-top: 10px; text-align: center; width: 100%;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                        <tr>
                            <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;">
                                    <span class="apple-link" style="color: #999999; font-size: 12px; text-align: center;">
                                        {{ $document->company->address_line1 }}, {{ $document->company->zip_code }} {{ $document->company->city }}
                                    </span>
                            </td>
                        </tr>
                    </table>
                </div>

            </div>
        </td>
        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">&nbsp;</td>
    </tr>
</table>
</body>
</html>

<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\Generator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ApiDocumentationPdfController extends Controller
{
    // Vygeneruje aktualne PDF API dokumentacie zo spolocnej OpenAPI specifikacie.
    // Pouziva sa v route /api-docs/pdf pri otvoreni alebo stiahnuti PDF dokumentacie.
    public function __invoke(OpenApiController $openApi, Generator $generator): Response
    {
        $specification = $openApi->specification($generator);
        $documentTitle = 'TomTib Lab API dokumentacia';

        $html = view('pdf.api-docs', [
            'documentTitle' => $documentTitle,
            'language' => 'sk',
            'specification' => $specification,
            'operations' => $this->operations($specification),
            'securitySchemes' => $specification['components']['securitySchemes'] ?? [],
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Cislo strany doplnime az po renderovani, lebo az vtedy pozname celkovy pocet stran.
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas = $dompdf->getCanvas();
        $pageLabel = 'Strana {PAGE_NUM}/{PAGE_COUNT}';
        $canvas->page_text(260, 782, $pageLabel, $font, 9, [0, 0, 0]);

        $fileName = Str::slug($documentTitle) . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    // Vytiahne zo specifikacie vsetky API operacie v tvare vhodnom pre PDF sablonu.
    // Pouziva sa pri renderovani PDF v metode __invoke.
    private function operations(array $specification): array
    {
        $operations = [];
        $methods = ['get', 'post', 'put', 'patch', 'delete'];
        $components = $specification['components'] ?? [];

        foreach (($specification['paths'] ?? []) as $path => $pathDefinition) {
            foreach ($methods as $method) {
                if (! isset($pathDefinition[$method])) {
                    continue;
                }

                $operation = $pathDefinition[$method];
                $operations[] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'summary' => $operation['summary'] ?? '',
                    'description' => $operation['description'] ?? '',
                    'tags' => $operation['tags'] ?? [],
                    'parameters' => $this->resolveParameters($operation['parameters'] ?? [], $components),
                    'requestBody' => $operation['requestBody'] ?? null,
                    'responses' => $this->resolveResponses($operation['responses'] ?? [], $components),
                    'security' => $operation['security'] ?? [],
                ];
            }
        }

        return $operations;
    }

    // Nahradi referencovane OpenAPI parametre ich skutocnou definiciou.
    // Pouziva sa pri skladani zoznamu operacii pre PDF dokumentaciu.
    private function resolveParameters(array $parameters, array $components): array
    {
        // Spracuje jeden parameter a v pripade $ref vrati jeho definiciu z components.
        // Pouziva sa v array_map nad parametrami OpenAPI operacie.
        return array_map(function (array $parameter) use ($components): array {
            if (! isset($parameter['$ref'])) {
                return $parameter;
            }

            return $this->resolveReference($parameter['$ref'], $components) ?? $parameter;
        }, $parameters);
    }

    // Nahradi referencovane OpenAPI odpovede ich skutocnou definiciou.
    // Pouziva sa pri skladani zoznamu operacii pre PDF dokumentaciu.
    private function resolveResponses(array $responses, array $components): array
    {
        foreach ($responses as $status => $response) {
            if (isset($response['$ref'])) {
                $responses[$status] = $this->resolveReference($response['$ref'], $components) ?? $response;
            }
        }

        return $responses;
    }

    // Najde OpenAPI definiciu podla lokalnej #/components/... referencie.
    // Pouziva sa pri rozbalovani parametrov a odpovedi do PDF exportu.
    private function resolveReference(string $reference, array $components): ?array
    {
        $prefix = '#/components/';

        if (! str_starts_with($reference, $prefix)) {
            return null;
        }

        $parts = explode('/', substr($reference, strlen($prefix)));
        $value = $components;

        foreach ($parts as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return is_array($value) ? $value : null;
    }
}

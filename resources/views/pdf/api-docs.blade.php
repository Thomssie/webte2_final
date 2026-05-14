<!DOCTYPE html>
<html lang="{{ $language }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @page {
            margin: 70px 42px 54px 42px;
        }

        body {
            margin: 0;
            color: #172033;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5px;
            line-height: 1.42;
        }

        .pdf-header {
            position: fixed;
            top: -46px;
            right: 0;
            left: 0;
            height: 30px;
            border-bottom: 1px solid #b7e7ec;
        }

        .pdf-header-title {
            margin: 0;
            color: #2f9fa8;
            font-size: 14px;
            font-weight: 700;
        }

        .pdf-footer-line {
            position: fixed;
            right: 0;
            bottom: -27px;
            left: 0;
            height: 16px;
            border-top: 1px solid #b7e7ec;
        }

        h1 {
            margin: 0 0 8px;
            color: #2f9fa8;
            font-size: 21px;
            line-height: 1.2;
        }

        h2 {
            margin: 20px 0 8px;
            color: #2f9fa8;
            font-size: 14px;
            page-break-after: avoid;
        }

        h3 {
            margin: 0 0 7px;
            color: #172033;
            font-size: 12px;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 7px;
        }

        .muted {
            color: #5b6777;
        }

        .operation {
            margin: 0 0 12px;
            padding: 10px 11px;
            border: 1px solid #b7e7ec;
            border-radius: 4px;
            background: #fbfeff;
            page-break-inside: avoid;
        }

        .operation-title {
            margin-bottom: 6px;
        }

        .method {
            display: inline-block;
            min-width: 42px;
            margin-right: 8px;
            padding: 3px 6px;
            border-radius: 3px;
            background: #57c4ce;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
        }

        .method-get {
            background: #2563eb;
        }

        .method-post {
            background: #22a06b;
        }

        .method-put,
        .method-patch {
            background: #f59e0b;
        }

        .method-delete {
            background: #dc2626;
        }

        .path {
            color: #111827;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 10px;
            word-break: break-all;
        }

        .label {
            margin: 8px 0 4px;
            color: #2f9fa8;
            font-size: 10px;
            font-weight: 700;
        }

        table {
            width: 100%;
            margin: 5px 0 8px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            padding: 5px 6px;
            border: 1px solid #d7eff2;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #e7f8fa;
            color: #1f7f86;
            font-size: 9px;
            text-align: left;
        }

        code {
            color: #0f172a;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9.2px;
        }

        .small {
            font-size: 9px;
        }
    </style>
</head>
<body>
@php
    $labels = $language === 'en'
        ? [
            'version' => 'Version',
            'server' => 'Server',
            'security' => 'Security',
            'parameters' => 'Parameters',
            'requestBody' => 'Request body',
            'responses' => 'Responses',
            'schema' => 'Schema',
            'description' => 'Description',
            'status' => 'Status',
            'type' => 'Type',
            'required' => 'Required',
            'name' => 'Name',
            'location' => 'Location',
            'yes' => 'yes',
            'no' => 'no',
            'none' => 'none',
            'securitySchemes' => 'Security',
            'endpoints' => 'Endpoints',
        ]
        : [
            'version' => 'Verzia',
            'server' => 'Server',
            'security' => 'Zabezpečenie',
            'parameters' => 'Parametre',
            'requestBody' => 'Telo požiadavky',
            'responses' => 'Odpovede',
            'schema' => 'Schéma',
            'description' => 'Popis',
            'status' => 'Stav',
            'type' => 'Typ',
            'required' => 'Povinné',
            'name' => 'Názov',
            'location' => 'Umiestnenie',
            'yes' => 'áno',
            'no' => 'nie',
            'none' => 'žiadne',
            'securitySchemes' => 'Zabezpečenie',
            'endpoints' => 'Endpointy',
        ];

    $documentDescription = $language === 'en'
        ? 'REST API for CAS calculations, dynamic system simulations, logs, and statistics.'
        : $specification['info']['description'];

    $englishOperations = [
        'POST /cas/execute' => [
            'summary' => 'Execute an Octave command',
            'description' => 'Runs an Octave command with the current session history so helper variables can be reused.',
        ],
        'GET /cas/history' => [
            'summary' => 'Session command history',
            'description' => 'Returns commands stored under the selected session token.',
        ],
        'POST /cas/history/reset' => [
            'summary' => 'Reset session history',
            'description' => 'Deletes stored commands for the selected session token.',
        ],
        'GET /cas/logs' => [
            'summary' => 'CAS request logs',
            'description' => 'Returns logs of CAS and simulation requests.',
        ],
        'GET /cas/logs/export' => [
            'summary' => 'Export logs to CSV',
            'description' => 'Returns a CSV file with all stored CAS logs.',
        ],
        'POST /simulations/ball-beam' => [
            'summary' => 'Ball and beam simulation',
            'description' => 'Returns time, ball position, and beam angle data for the animation.',
        ],
        'POST /simulations/inverted-pendulum' => [
            'summary' => 'Inverted pendulum simulation',
            'description' => 'Returns time, cart position, and pendulum angle data for the animation.',
        ],
        'GET /statistics/animations' => [
            'summary' => 'Animation usage summary',
            'description' => 'Returns aggregated usage counts for each animation type.',
        ],
        'GET /statistics/animations/{animationType}' => [
            'summary' => 'Animation usage details',
            'description' => 'Returns detailed usage records for the selected animation type.',
        ],
    ];

    $englishParameterDescriptions = [
        'header:Session-Token' => 'CAS session identifier.',
        'query:all' => 'When true, returns all logs without a limit.',
        'query:limit' => 'Maximum number of logs in the standard listing.',
        'path:animationType' => 'Animation type.',
    ];

    // Prevedie OpenAPI schemu na kratky textovy nazov vhodny do PDF.
    // Pouziva sa pri vypise request body schem pri jednotlivych endpointoch.
    $schemaLabel = function (?array $schema): string {
        if (! $schema) {
            return '-';
        }

        if (isset($schema['$ref'])) {
            return str_replace('#/components/schemas/', '', $schema['$ref']);
        }

        if (($schema['type'] ?? null) === 'array') {
            $item = $schema['items']['type'] ?? ($schema['items']['$ref'] ?? 'item');
            return 'array<' . str_replace('#/components/schemas/', '', $item) . '>';
        }

        return $schema['type'] ?? '-';
    };
@endphp

<div class="pdf-header">
    <p class="pdf-header-title">{{ $documentTitle }}</p>
</div>
<div class="pdf-footer-line"></div>

<h1>{{ $documentTitle }}</h1>
<p>{{ $documentDescription }}</p>
<p class="muted">
    {{ $labels['version'] }}: {{ $specification['info']['version'] }}
    &nbsp;|&nbsp;
    {{ $labels['server'] }}: {{ $specification['servers'][0]['url'] ?? '-' }}
</p>

<h2>{{ $labels['securitySchemes'] }}</h2>
<table>
    <thead>
    <tr>
        <th style="width: 24%">{{ $labels['name'] }}</th>
        <th style="width: 18%">{{ $labels['type'] }}</th>
        <th>{{ $labels['description'] }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($securitySchemes as $name => $scheme)
        <tr>
            <td><code>{{ $name }}</code></td>
            <td>{{ $scheme['type'] ?? '-' }}</td>
            <td>
                {{ $language === 'en' && $name === 'ApiKeyAuth'
                    ? 'API key defined in the application configuration.'
                    : ($scheme['description'] ?? '-') }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>{{ $labels['endpoints'] }}</h2>
@foreach ($operations as $operation)
    @php
        $operationKey = $operation['method'] . ' ' . $operation['path'];
        $translatedOperation = $language === 'en'
            ? ($englishOperations[$operationKey] ?? null)
            : null;
        $operationSummary = $translatedOperation['summary'] ?? $operation['summary'];
        $operationDescription = $translatedOperation['description'] ?? $operation['description'];
    @endphp

    <div class="operation">
        <div class="operation-title">
            <span class="method method-{{ strtolower($operation['method']) }}">{{ $operation['method'] }}</span>
            <span class="path">{{ $operation['path'] }}</span>
        </div>

        <h3>{{ $operationSummary }}</h3>

        @if ($operationDescription)
            <p>{{ $operationDescription }}</p>
        @endif

        @if (count($operation['security']))
            <p class="small">{{ $labels['security'] }}: <code>{{ implode(', ', array_map(fn ($item) => implode(', ', array_keys($item)), $operation['security'])) }}</code></p>
        @endif

        @if (count($operation['parameters']))
            <div class="label">{{ $labels['parameters'] }}</div>
            <table>
                <thead>
                <tr>
                    <th style="width: 24%">{{ $labels['name'] }}</th>
                    <th style="width: 14%">{{ $labels['location'] }}</th>
                    <th style="width: 14%">{{ $labels['required'] }}</th>
                    <th>{{ $labels['description'] }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($operation['parameters'] as $parameter)
                    @php
                        $parameterKey = ($parameter['in'] ?? '') . ':' . ($parameter['name'] ?? '');
                        $parameterDescription = $language === 'en'
                            ? ($englishParameterDescriptions[$parameterKey] ?? ($parameter['description'] ?? '-'))
                            : ($parameter['description'] ?? '-');
                    @endphp

                    <tr>
                        <td><code>{{ $parameter['name'] ?? '-' }}</code></td>
                        <td>{{ $parameter['in'] ?? '-' }}</td>
                        <td>{{ ($parameter['required'] ?? false) ? $labels['yes'] : $labels['no'] }}</td>
                        <td>{{ $parameterDescription }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @if ($operation['requestBody'])
            <div class="label">{{ $labels['requestBody'] }}</div>
            @foreach (($operation['requestBody']['content'] ?? []) as $contentType => $content)
                <p class="small"><code>{{ $contentType }}</code>: {{ $labels['schema'] }} <code>{{ $schemaLabel($content['schema'] ?? null) }}</code></p>
            @endforeach
        @endif

        <div class="label">{{ $labels['responses'] }}</div>
        <table>
            <thead>
            <tr>
                <th style="width: 18%">{{ $labels['status'] }}</th>
                <th>{{ $labels['description'] }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($operation['responses'] as $status => $response)
                <tr>
                    <td><code>{{ $status }}</code></td>
                    <td>{{ $response['description'] ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>

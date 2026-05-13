<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasLog;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use App\Services\OctaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CasCommandHistory;
use Illuminate\Support\Str;



// Metadata pre Scramble zaradia CAS endpointy do spolocnej sekcie dokumentacie.
#[Group('CAS', 'CAS prikazy v Octave, historia a logy')]
class CasController extends Controller
{
    // Endpoint overi dostupnost CAS API a zapise kontrolny log.
    #[Endpoint(title: 'Overenie dostupnosti CAS API', description: 'Vrati informaciu, ci je CAS API pripravene na spracovanie poziadaviek.')]
    public function ping(Request $request): JsonResponse
    {
        CasLog::create([
            'source' => 'api_ping',
            'command' => 'ping',
            'success' => true,
            'output' => 'CAS API is ready',
            'error_message' => null,
            'ip_address' => CasLog::hashIp($request->ip()),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'CAS API is ready',
        ]);
    }


    // Endpoint spusti prikaz v Octave a pouzije historiu prikazov aktualnej relacie.
    #[Endpoint(title: 'Spustenie prikazu v Octave', description: 'Spusti prikaz spolu s historiou aktualnej relacie, aby bolo mozne pouzivat pomocne premenne.')]
    #[HeaderParameter('Session-Token', description: 'Identifikator CAS relacie.', required: false, type: 'string', default: 'default-session')]
    public function execute(Request $request, OctaveService $octave): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $sessionToken = (string) $request->header('Session-Token', 'default-session');
        $command = $validated['command'];
        $source = $validated['source'] ?? 'form';

        $historyCommands = CasCommandHistory::where('session_token', $sessionToken)
            ->orderBy('sequence')
            ->pluck('command')
            ->all();

        $silentHistoryCommands = array_map(function (string $historyCommand): string {
            return rtrim($historyCommand, " \t\n\r\0\x0B;") . ';';
        }, $historyCommands);

        $outputMarker = '__CURRENT_OUTPUT_START_' . str_replace('-', '_', (string) Str::uuid()) . '__';

        $script = implode("\n", array_merge(
            $silentHistoryCommands,
            ["disp('{$outputMarker}');", $command]
        ));

        $result = $octave->run($script);

        if ($result['success']) {
            $markerPosition = strpos($result['output'], $outputMarker);

            if ($markerPosition !== false) {
                $result['output'] = trim(substr(
                    $result['output'],
                    $markerPosition + strlen($outputMarker)
                ));
            }
        }


        if ($result['success']) {
            $nextSequence = CasCommandHistory::where('session_token', $sessionToken)->max('sequence') + 1;

            CasCommandHistory::create([
                'session_token' => $sessionToken,
                'sequence' => $nextSequence,
                'command' => $command,
            ]);
        }



        CasLog::create([
            'source' => $source,
            'command' => $command,
            'success' => $result['success'],
            'output' => $result['output'],
            'error_message' => $result['error'],
            'ip_address' => CasLog::hashIp($request->ip()),
        ]);

        return response()->json([
            'ok' => $result['success'],
            'command' => $command,
            'output' => $result['output'],
            'error' => $result['error'],
            'exit_code' => $result['exit_code'],
        ], $result['success'] ? 200 : 422);
    }

    // Endpoint vymaze historiu prikazov pre aktualnu CAS relaciu.
    #[Endpoint(title: 'Vymazanie historie aktualnej relacie', description: 'Odstrani ulozene prikazy patriace k zadanemu session tokenu.')]
    #[HeaderParameter('Session-Token', description: 'Identifikator CAS relacie.', required: false, type: 'string', default: 'default-session')]
    public function resetHistory(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->header('Session-Token', 'default-session');

        $deletedCount = CasCommandHistory::where('session_token', $sessionToken)->delete();

        CasLog::create([
            'source' => 'history_reset',
            'command' => 'reset history',
            'success' => true,
            'output' => "Deleted {$deletedCount} history commands.",
            'error_message' => null,
            'ip_address' => CasLog::hashIp($request->ip()),
        ]);

        return response()->json([
            'ok' => true,
            'deleted_count' => $deletedCount,
        ]);
    }


    // Endpoint vrati prikazy ulozene v historii aktualnej CAS relacie.
    #[Endpoint(title: 'Zoznam prikazov v historii relacie', description: 'Vrati prikazy ulozene pod zadanym session tokenom v poradi ich vykonania.')]
    #[HeaderParameter('Session-Token', description: 'Identifikator CAS relacie.', required: false, type: 'string', default: 'default-session')]
    public function history(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->header('Session-Token', 'default-session');

        $commands = CasCommandHistory::where('session_token', $sessionToken)
            ->orderBy('sequence')
            ->get(['sequence', 'command', 'created_at']);

        return response()->json([
            'ok' => true,
            'commands' => $commands,
        ]);
    }


    // Endpoint vrati CAS logy bud s limitom, alebo kompletne pri parametri all.
    #[Endpoint(title: 'Zoznam logov CAS poziadaviek', description: 'Vrati logy poziadaviek spracovanych cez CAS a simulacne endpointy.')]
    #[QueryParameter('all', description: 'Ak je true, vrati vsetky logy bez limitu.', required: false, type: 'bool')]
    #[QueryParameter('limit', description: 'Maximalny pocet logov pri beznom vypise.', required: false, type: 'int', default: 50)]
    public function logs(Request $request): JsonResponse
    {
        $query = CasLog::query()->latest();

        // Vrati vsetky logy bez limitu pre samostatnu stranku logov.
        // Pouziva sa pri volani /api/cas/logs?all=1.
        if ($request->boolean('all')) {
            $logs = $query->get([
                'id',
                'source',
                'command',
                'success',
                'output',
                'error_message',
                'ip_address',
                'created_at',
            ]);

            return response()->json([
                'ok' => true,
                'logs' => $logs,
            ]);
        }

        $limit = (int) $request->query('limit', 50);

        if ($limit < 1) {
            $limit = 1;
        }

        if ($limit > 200) {
            $limit = 200;
        }

        // Vrati posledne logy s limitom pre bezne API volania.
        // Pouziva sa pri volani /api/cas/logs bez parametra all.
        $logs = $query
            ->limit($limit)
            ->get([
                'id',
                'source',
                'command',
                'success',
                'output',
                'error_message',
                'ip_address',
                'created_at',
            ]);

        return response()->json([
            'ok' => true,
            'logs' => $logs,
        ]);
    }

    // Endpoint exportuje CAS logy do CSV suboru.
    #[Endpoint(title: 'Export logov do CSV', description: 'Vrati CSV subor so vsetkymi ulozenymi CAS logmi.')]
    public function exportLogs()
    {
        $fileName = 'cas_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $columns = [
            'id',
            'created_at',
            'source',
            'command',
            'success',
            'output',
            'error_message',
            'ip_hash',
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            fputcsv($file, $columns);

            CasLog::query()
                ->orderBy('created_at')
                ->chunk(100, function ($logs) use ($file, $columns) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->id,
                            $log->created_at,
                            $log->source,
                            $log->command,
                            $log->success ? 'true' : 'false',
                            $log->output,
                            $log->error_message,
                            $log->ip_address,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }



}

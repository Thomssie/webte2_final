<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasLog;
use App\Services\OctaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CasCommandHistory;



class CasController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        CasLog::create([
            'source' => 'api_ping',
            'command' => 'ping',
            'success' => true,
            'output' => 'CAS API is ready',
            'error_message' => null,
            'ip_address' => CasLog::hashIp($request->ip()),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'CAS API is ready',
        ]);
    }


    public function execute(Request $request, OctaveService $octave): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $sessionToken = (string) $request->header('X-Session-Token', 'default-session');
        $command = $validated['command'];
        $source = $validated['source'] ?? 'form';

        $historyCommands = CasCommandHistory::where('session_token', $sessionToken)
            ->orderBy('sequence')
            ->pluck('command')
            ->all();

        $silentHistoryCommands = array_map(function (string $historyCommand): string {
            return rtrim($historyCommand, " \t\n\r\0\x0B;") . ';';
        }, $historyCommands);

        $script = implode("\n", array_merge($silentHistoryCommands, [$command]));

        $result = $octave->run($script);

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
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'ok' => $result['success'],
            'command' => $command,
            'output' => $result['output'],
            'error' => $result['error'],
            'exit_code' => $result['exit_code'],
        ], $result['success'] ? 200 : 422);
    }

    public function resetHistory(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->header('X-Session-Token', 'default-session');

        $deletedCount = CasCommandHistory::where('session_token', $sessionToken)->delete();

        CasLog::create([
            'source' => 'history_reset',
            'command' => 'reset history',
            'success' => true,
            'output' => "Deleted {$deletedCount} history commands.",
            'error_message' => null,
            'ip_address' => CasLog::hashIp($request->ip()),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'ok' => true,
            'deleted_count' => $deletedCount,
        ]);
    }


    public function history(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->header('X-Session-Token', 'default-session');

        $commands = CasCommandHistory::where('session_token', $sessionToken)
            ->orderBy('sequence')
            ->get(['sequence', 'command', 'created_at']);

        return response()->json([
            'ok' => true,
            'commands' => $commands,
        ]);
    }


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
                'user_agent',
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
                'user_agent',
                'created_at',
            ]);

        return response()->json([
            'ok' => true,
            'logs' => $logs,
        ]);
    }

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
            'user_agent',
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
                            $log->user_agent,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }



}

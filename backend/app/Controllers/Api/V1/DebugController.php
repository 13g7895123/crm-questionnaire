<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

class DebugController extends BaseApiController
{
    /**
     * POST /api/v1/debug/log-match
     * Receive match debug info from frontend
     */
    public function logMatch(): ResponseInterface
    {
        $data = $this->request->getJson(true);
        $this->saveDebugLog('match_debug', $data);

        return $this->successResponse(['status' => 'logged']);
    }

    /**
     * Helper to save debug logs from frontend to a specific file
     */
    protected function saveDebugLog(string $filename, array $data)
    {
        $logPath = WRITEPATH . 'logs/' . $filename . '.log';

        // Ensure directory exists
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $content = "[{$timestamp}] " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('=', 50) . PHP_EOL;
        file_put_contents($logPath, $content, FILE_APPEND);
    }
}

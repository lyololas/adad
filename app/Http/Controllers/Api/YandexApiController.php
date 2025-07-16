<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class YandexApiController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/upload",
     *     summary="Upload form data as Excel to Yandex Disk",
     *     description="Receives JSON form data, generates an Excel file, uploads it to Yandex Disk, and returns the public URL.",
     *     tags={"Yandex"},
     *     security={{"api_key": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 example={"forms": {{"name": "John Doe", "email": "john@example.com", "checkbox": "yes"}}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="url", type="string"),
     *             @OA\Property(property="file_name", type="string"),
     *             @OA\Property(property="action", type="string"),
     *             @OA\Property(property="attempts", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized or missing Yandex token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Upload failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Header(
     *         header="X-API-KEY",
     *         description="API key for authentication",
     *         @OA\Schema(type="string")
     *     )
     * )
     */
    public function upload(Request $request)
    {
        $data = $request->validate([
            'forms' => 'required|array',
            'forms.*' => 'array',
        ]);

        // If X-API-KEY header is present, use the user associated with that API key
        if ($request->hasHeader('X-API-KEY')) {
            $apiKey = $request->header('X-API-KEY');
            $user = \App\Models\User::findByApiKey($apiKey);
            if (!$user || !$user->yandex_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API key or Yandex token not available for this user.'
                ], 401);
            }
        } else {
            // Otherwise, use the admin user (your Yandex Disk)
            $user = \App\Models\User::where('email', 'yandex_162371647@placeholder.com')->first();
            if (!$user || !$user->yandex_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin Yandex token not available. Please reauthenticate.'
                ], 401);
            }
        }

        $fileName = 'data.xlsx';
        $remotePath = '/Documents/' . $fileName;
        $debug['remote_path'] = $remotePath;

        // Try to download the existing file from Yandex Disk
        $existingRows = [];
        $headers = [];
        $debug['download'] = [];
        $downloadTriedPublic = false;
        try {
            Log::info("[DOWNLOAD] Attempting to download and read existing Excel for user {$user->id} at $remotePath");
            $existingRows = $this->downloadAndReadExcel($user->yandex_token, $remotePath, $headers, $debug['download']);
            $debug['existing_rows'] = $existingRows;
            $debug['existing_headers'] = $headers;
        } catch (\Exception $e) {
            Log::info("[DOWNLOAD] No existing file or failed to read at $remotePath: " . $e->getMessage());
            $debug['download_error'] = $e->getMessage();
            // Try to get public URL and download from there
            try {
                Log::info("[DOWNLOAD] Trying to get public URL for $remotePath");
                $meta = Http::withHeaders([
                    'Authorization' => 'OAuth ' . $user->yandex_token,
                    'Accept' => 'application/json',
                ])->get('https://cloud-api.yandex.net/v1/disk/resources', [
                    'path' => $remotePath,
                    'fields' => 'public_url',
                ]);
                $publicUrl = $meta->json('public_url') ?? null;
                $debug['download']['public_url'] = $publicUrl;
                if ($publicUrl) {
                    $downloadTriedPublic = true;
                    $tempPath = sys_get_temp_dir() . '/yandex_public_' . uniqid() . '.xlsx';
                    $fileData = file_get_contents($publicUrl);
                    file_put_contents($tempPath, $fileData);
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = [];
                    $headers = [];
                    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);
                        $rowData = [];
                        foreach ($cellIterator as $cell) {
                            $rowData[] = $cell->getValue();
                        }
                        if ($rowIndex == 1) {
                            $headers = $rowData;
                        } else {
                            $assoc = [];
                            foreach ($headers as $i => $header) {
                                $assoc[$header] = $rowData[$i] ?? '';
                            }
                            $rows[] = $assoc;
                        }
                    }
                    unlink($tempPath);
                    $existingRows = $rows;
                    $debug['existing_rows'] = $existingRows;
                    $debug['existing_headers'] = $headers;
                }
            } catch (\Exception $e2) {
                Log::info("[DOWNLOAD] Failed to download from public URL: " . $e2->getMessage());
                $debug['download']['public_url_error'] = $e2->getMessage();
            }
        }

        // Merge headers and rows
        $newRows = $data['forms'];
        $allRows = $existingRows;
        // Collect all unique headers
        foreach ($newRows as $row) {
            foreach ($row as $key => $value) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }
        // Append new rows
        foreach ($newRows as $row) {
            $allRows[] = $row;
        }
        $debug['merged_headers'] = $headers;
        $debug['merged_rows'] = $allRows;

        // Generate Excel file from merged data
        $tempFilePath = $this->generateExcelFromRows($headers, $allRows);

        // Log upload path
        Log::info("[UPLOAD] Uploading file for user {$user->id} to $remotePath");
        $debug['upload'] = ['remote_path' => $remotePath];
        $result = $this->attemptUploadWithRetry($user, $tempFilePath, $remotePath);
        $debug['upload']['result'] = $result->getData(true);

        // Publish the file after upload to ensure it's accessible
        try {
            Log::info("[PUBLISH] Publishing file for user {$user->id} at $remotePath");
            $publishResponse = Http::withHeaders([
                'Authorization' => 'OAuth ' . $user->yandex_token,
                'Accept' => 'application/json',
            ])->put('https://cloud-api.yandex.net/v1/disk/resources/publish', [
                'path' => $remotePath
            ]);
            $debug['publish'] = [
                'status' => $publishResponse->status(),
                'body' => $publishResponse->json(),
            ];
        } catch (\Exception $e) {
            Log::info("[PUBLISH] Failed to publish file at $remotePath: " . $e->getMessage());
            $debug['publish_error'] = $e->getMessage();
        }

        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }

        // For debugging: return the current contents as JSON
        $resultData = $result->getData(true);
        $resultData['debug'] = $debug;
        $resultData['current_excel'] = $allRows;
        return response()->json($resultData, $result->status());
    }

    private function generateExcelFromData(array $forms): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Collect all unique headers
        $headers = [];
        foreach ($forms as $form) {
            foreach ($form as $key => $value) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }
        // Write headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }
        // Write rows
        foreach ($forms as $rowIdx => $form) {
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $form[$header] ?? '');
            }
        }
        // Save to temp file
        $tempPath = sys_get_temp_dir() . '/yandex_upload_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        return $tempPath;
    }

    private function attemptUploadWithRetry($user, $tempFilePath, $remotePath, $maxAttempts = 5, $delaySeconds = 10)
    {
        $attempt = 1;
        $lastError = null;

        while ($attempt <= $maxAttempts) {
            try {
                $this->ensureDirectory($user->yandex_token, '/Documents');
                $uploadUrl = $this->getUploadUrl($user->yandex_token, $remotePath);
                $uploadResponse = Http::withHeaders([
                    'Authorization' => 'OAuth ' . $user->yandex_token,
                    'Content-Type' => 'application/octet-stream',
                ])->withBody(
                    fopen($tempFilePath, 'r')
                )->put($uploadUrl);

                if ($uploadResponse->failed()) {
                    throw new \Exception('File upload failed: ' . $uploadResponse->body());
                }

                $publicUrl = $this->publishFile($user->yandex_token, $remotePath);

                return response()->json([
                    'success' => true,
                    'url' => $publicUrl,
                    'file_name' => basename($remotePath),
                    'action' => 'updated',
                    'attempts' => $attempt
                ]);

            } catch (\Exception $e) {
                $lastError = $e;
                Log::warning("Yandex upload attempt $attempt failed", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'next_attempt_in' => "$delaySeconds seconds"
                ]);

                if ($attempt < $maxAttempts) {
                    sleep($delaySeconds);
                }
                $attempt++;
            }
        }

        Log::error('Yandex upload failed after all attempts', [
            'error' => $lastError->getMessage(),
            'user_id' => $user->id,
            'attempts' => $maxAttempts
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Upload failed after ' . $maxAttempts . ' attempts: ' . $lastError->getMessage()
        ], 500);
    }

    private function storeTempFile($file)
    {
        $tempPath = sys_get_temp_dir() . '/yandex_upload_' . uniqid() . '.xlsx';
        file_put_contents($tempPath, file_get_contents($file->getRealPath()));
        return $tempPath;
    }

    private function ensureDirectory($token, $path)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $client->put('https://cloud-api.yandex.net/v1/disk/resources', [
                'headers' => [
                    'Authorization' => 'OAuth ' . $token,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'path' => $path
                ]
            ]);
            return true;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 409) {
                return true;
            }
            throw new \Exception('Directory creation failed');
        }
    }

    private function getUploadUrl($token, $path)
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth ' . $token,
            'Accept' => 'application/json',
        ])->get('https://cloud-api.yandex.net/v1/disk/resources/upload', [
            'path' => $path,
            'overwrite' => 'true'
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get upload URL: ' . $response->body());
        }

        return $response->json('href');
    }

    private function publishFile($token, $path)
    {
        Http::withHeaders(['Authorization' => 'OAuth ' . $token])
            ->put('https://cloud-api.yandex.net/v1/disk/resources/publish', [
                'path' => $path
            ]);

        $meta = Http::withHeaders(['Authorization' => 'OAuth ' . $token])
                    ->get('https://cloud-api.yandex.net/v1/disk/resources', [
                        'path' => $path,
                        'fields' => 'public_url',
                    ]);
        return $meta->json('public_url') ?? 'https://disk.yandex.ru/client/disk' . $path;
    }

    private function downloadAndReadExcel($token, $remotePath, &$headers = [], &$debug = null)
    {
        // Get download URL
        $response = Http::withHeaders([
            'Authorization' => 'OAuth ' . $token,
            'Accept' => 'application/json',
        ])->get('https://cloud-api.yandex.net/v1/disk/resources/download', [
            'path' => $remotePath
        ]);
        if ($debug !== null) {
            $debug['download_url_response'] = $response->json();
            $debug['download_url_status'] = $response->status();
        }
        if ($response->failed()) {
            throw new \Exception('Failed to get download URL: ' . $response->body());
        }
        $downloadUrl = $response->json('href');
        // Download file
        $tempPath = sys_get_temp_dir() . '/yandex_download_' . uniqid() . '.xlsx';
        $fileData = file_get_contents($downloadUrl);
        file_put_contents($tempPath, $fileData);
        if ($debug !== null) {
            $debug['downloaded_file'] = $tempPath;
        }
        // Read Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $headers = [];
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            if ($rowIndex == 1) {
                $headers = $rowData;
            } else {
                $assoc = [];
                foreach ($headers as $i => $header) {
                    $assoc[$header] = $rowData[$i] ?? '';
                }
                $rows[] = $assoc;
            }
        }
        unlink($tempPath);
        return $rows;
    }

    private function generateExcelFromRows(array $headers, array $rows): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Write headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }
        // Write rows
        foreach ($rows as $rowIdx => $row) {
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $row[$header] ?? '');
            }
        }
        // Save to temp file
        $tempPath = sys_get_temp_dir() . '/yandex_upload_' . uniqid() . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);
        return $tempPath;
    }
} 
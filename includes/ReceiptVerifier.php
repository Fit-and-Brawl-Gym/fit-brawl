<?php
class ReceiptVerifier
{
  private $apiKey;
  private $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

  // 2025 UPDATED MODEL LIST (Priority Order)
  // Groq replaced llama-3.2-vision-preview with Llama 4 Scout
  private $models = [
    'meta-llama/llama-4-scout-17b-16e-instruct', // NEW: Primary Vision Model (Fast/Smart)
    'meta-llama/llama-4-maverick-17b-128e-instruct', // Backup Vision Model
    'llama-3.2-90b-vision-instruct', // Stable version (if available)
    'llama-3.2-11b-vision-preview', // Legacy fallback
  ];

  public function __construct($apiKey)
  {
    $this->apiKey = trim($apiKey, " \"'");
  }

  public function classify($filePath)
  {
    if (!file_exists($filePath)) {
      return [
        'is_receipt' => false,
        'confidence' => 0,
        'reason' => 'File not found on server.',
      ];
    }

    $mimeType = mime_content_type($filePath);
    $base64Data = base64_encode(file_get_contents($filePath));
    $dataUrl = "data:$mimeType;base64,$base64Data";

    // Loop through models until one works
    foreach ($this->models as $model) {
      $result = $this->callGroq($model, $dataUrl);

      if ($result['success']) {
        return $result['data'];
      }

      // If error is 404 (Model Not Found) or 400 (Decommissioned), try next model
      if (in_array($result['http_code'], [400, 404])) {
        continue;
      }

      // If other error (e.g. 401 Auth, 500 Server), stop immediately
      return [
        'is_receipt' => false,
        'confidence' => 0,
        'reason' => 'Groq Error: ' . $result['error'],
      ];
    }

    return [
      'is_receipt' => false,
      'confidence' => 0,
      'reason' =>
        'All AI models failed or are deprecated. Please contact admin.',
    ];
  }

  private function callGroq($model, $dataUrl)
  {
    $prompt = "You are a receipt classifier. Check if this image is a valid proof of payment (Receipt, Bank Transfer, GCash/Maya screenshot).
        
        Return ONLY a JSON object:
        {
            \"is_receipt\": boolean,
            \"confidence\": float (0.0-1.0),
            \"reason\": \"short explanation\"
        }
        
        Rules:
        - TRUE if: Official receipt, bank transfer screenshot, mobile wallet confirmation (GCash/Maya) with amount/date.
        - FALSE if: Selfie, random photo, blurry unreadable text, or gym equipment.";

    $payload = [
      'model' => $model,
      'messages' => [
        [
          'role' => 'user',
          'content' => [
            ['type' => 'text', 'text' => $prompt],
            [
              'type' => 'image_url',
              'image_url' => ['url' => $dataUrl],
            ],
          ],
        ],
      ],
      'temperature' => 0.1,
      'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init($this->apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
      return ['success' => false, 'http_code' => 0, 'error' => $curlError];
    }

    // Handle Model Decommissioned (400) or Not Found (404)
    if ($httpCode === 400 || $httpCode === 404) {
      return [
        'success' => false,
        'http_code' => $httpCode,
        'error' => "Model $model deprecated",
      ];
    }

    if ($httpCode !== 200) {
      return [
        'success' => false,
        'http_code' => $httpCode,
        'error' => "HTTP $httpCode: " . substr($response, 0, 100),
      ];
    }

    try {
      $json = json_decode($response, true);
      $content = $json['choices'][0]['message']['content'] ?? '{}';
      $data = json_decode($content, true);

      if (isset($data['is_receipt'])) {
        return ['success' => true, 'data' => $data];
      }
      return [
        'success' => false,
        'http_code' => 200,
        'error' => 'Invalid JSON',
      ];
    } catch (Exception $e) {
      return ['success' => false, 'http_code' => 200, 'error' => 'Parse Error'];
    }
  }
}
?>

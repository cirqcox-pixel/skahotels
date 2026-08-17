<?php
/**
 * Minimal Supabase PostgREST client for optional PHP server-side use.
 * GitHub Pages uses assets/js/ska-api.js instead.
 */
class SupabaseClient
{
    private string $url;
    private string $key;

    public function __construct(string $url, string $key)
    {
        $this->url = rtrim($url, '/');
        $this->key = $key;
    }

    public function insert(string $table, array $row): array
    {
        return $this->request('POST', $table, $row, ['Prefer: return=representation']);
    }

    public function select(string $table, array $query = []): array
    {
        $qs = http_build_query($query);
        return $this->request('GET', $table . ($qs ? '?' . $qs : ''));
    }

    private function request(string $method, string $path, ?array $body = null, array $extraHeaders = []): array
    {
        $ch = curl_init($this->url . '/rest/v1/' . ltrim($path, '/'));
        $headers = array_merge([
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new RuntimeException('Supabase request failed: ' . $err);
        }

        $data = json_decode($response ?: '[]', true);
        if ($code >= 400) {
            $msg = is_array($data) ? ($data['message'] ?? json_encode($data)) : $response;
            throw new RuntimeException('Supabase HTTP ' . $code . ': ' . $msg);
        }

        return is_array($data) ? $data : [];
    }
}

<?php

class ExternalApiController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function agentsAction(): void
    {
        $agents = [];
        $error  = null;

        $apiUrl = 'https://valorant-api.com/v1/agents?isPlayableCharacter=true';
        $result = $this->makeGetRequest($apiUrl);

        if ($result === null) {
            $error = 'No s\'ha pogut carregar la API de Valorant ara mateix.';
        } else {
            $json = json_decode($result, true);

            if (
                !is_array($json) ||
                empty($json['status']) ||
                (int)$json['status'] !== 200 ||
                empty($json['data']) ||
                !is_array($json['data'])
            ) {
                $error = 'La resposta de la API no té el format esperat.';
            } else {
                $agents = $json['data'];
            }
        }

        $pageTitle = 'Valomen.gg | Valorant Agents';
        $pageCss   = 'valorant_agents.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/valorant_agents.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    private function makeGetRequest(string $url): ?string
    {
        // si no hi ha cURL, faig servir file_get_contents
        if (!function_exists('curl_init')) {
            $context = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 10,
                    'header'  => "Accept: application/json\r\n",
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            return $response !== false ? $response : null;
        }

        // si hi ha cURL, la petició és més fiable
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        return $response;
    }
}
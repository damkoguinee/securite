<?php 

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrangeSmsService
{
    private $httpClient;
    private $clientId;
    private $clientSecret;
    private $accessToken;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        // Récupération des identifiants directement depuis $_ENV
        $this->clientId = $_ENV['ORANGE_CLIENT_ID'] ?? null;
        $this->clientSecret = $_ENV['ORANGE_CLIENT_SECRET'] ?? null;

        // Débogage des identifiants
        // dd($this->clientId, $this->clientSecret);

        if (!$this->clientId || !$this->clientSecret) {
            throw new \InvalidArgumentException('Les identifiants ORANGE_CLIENT_ID et ORANGE_CLIENT_SECRET doivent être définis dans le fichier .env.');
        }
    }

    public function authenticate(): void
    {
        // dd("Authentification en cours...");

        $response = $this->httpClient->request('POST', 'https://api.orange.com/oauth/v3/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $data = $response->toArray();

        // Débogage de la réponse d'authentification
        // dd($data);

        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
        } else {
            throw new \Exception('Authentication failed: ' . json_encode($data));
        }

        // dd("Access token: " . $this->accessToken);
    }

    public function sendSms(string $recipient, string $sender, string $message): array
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }
    
        // Valider et formater les numéros de téléphone
        $recipient = preg_replace('/[^0-9]/', '', $recipient);
        $sender = preg_replace('/[^0-9]/', '', $sender);
    
        // Construire le payload
        $payload = [
            'outboundSMSMessageRequest' => [
                'address' => "tel:+$recipient",
                'senderAddress' => "tel:+$sender", 
                'outboundSMSTextMessage' => ['message' => $message],
                'senderName' => 'SMS 138978',  // Utilisez le nom d'expéditeur par défaut
            ],
        ];
    
        $url = "https://api.orange.com/smsmessaging/v1/outbound/tel:+$sender/requests";
    
        try {
            // Envoi de la requête
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
    
            // Traiter la réponse
            $responseData = $response->toArray(false); // Désactiver la conversion automatique des erreurs HTTP en exceptions
            // Vérification de la réponse d'erreur
            if (isset($responseData['requestError'])) {
                // $error = $responseData['requestError']['policyException'] ?? null;
                // if ($error) {
                //     $errorMessage = $error['text'] ?? 'Erreur inconnue';
                //     // Vous pouvez afficher l'erreur à l'utilisateur ici
                //     throw new \Exception("Erreur de politique : " . $errorMessage);
                // }
            }
    
            return $responseData;
    
        } catch (\Exception $e) {
            // Capturer les erreurs et afficher les messages
            throw $e;
        }
    }
    
    


}

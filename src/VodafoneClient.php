<?php
namespace A2PVodafone;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class VodafoneClient
{
    const TOKEN_FILE_NAME = 'a2p_token';
    protected string $validityPeriod = '000000000200000R'; // 2 min
    protected string $baseUrl = 'https://a2p.vodafone.ua';
    private mixed $username;
    private mixed $password;
    private mixed $authorization;
    private mixed $distribution_id;
    private Client $client;

    public function __construct()
    {
        $this->username      = config('vodafone_a2p.username');
        $this->password      = config('vodafone_a2p.password');
        $this->authorization = config('vodafone_a2p.authorization');
        $this->distribution_id = config('vodafone_a2p.distribution_id');
        $this->client = new Client();
    }

    private function getAccessToken(): string
    {
        $this->syncToken();

        return $this->getTokenContent()['access_token'];
    }

    private function syncToken(): void
    {
        $json = $this->getJson();

        if(!empty($json)) {
            Storage::put(self::TOKEN_FILE_NAME, $json);
        }
    }

    private function getJson(): string
    {
        if(!$this->isTokenExists() || !$this->isTokenHasValidStructure()) {
            return $this->newAccessTokenRequest();
        }

        if($this->isTokenExpired() && $this->tokenCanBeUpdated()) {
            return $this->refreshAccessTokenRequest($this->getTokenContent()['refresh_token']);
        } else {
            return $this->newAccessTokenRequest();
        }
    }

    private function getTokenContent(): array
    {
        return json_decode(Storage::get(self::TOKEN_FILE_NAME), true);
    }

    private function tokenCanBeUpdated(): bool
    {
        $content = $this->getTokenContent();
        $difference = (now()->timestamp - $content['createTokenTime'] / 1000) / 60;

        if($difference >= $content['refresh_token_expires_in']) {
            return false;
        }

        return true;
    }

    private function isTokenExpired(): bool
    {
        $content = $this->getTokenContent();
        $difference = (now()->timestamp - $content['createTokenTime'] / 1000) / 60;

        if($difference >= $content['expires_in']) {
            return false;
        }

        return true;
    }

    private function isTokenHasValidStructure(): bool
    {
        $content = $this->getTokenContent();

        if(
            !array_key_exists('createTokenTime', $content)
            || !array_key_exists('expires_in', $content)
            || !array_key_exists('refresh_token_expires_in', $content)
            || !array_key_exists('access_token', $content)
            || !array_key_exists('refresh_token', $content)
        ) {
            return false;
        }

        return true;
    }

    private function isTokenExists(): bool
    {
        if(Storage::missing(self::TOKEN_FILE_NAME)) {
            return false;
        }

        return true;
    }

    private function newAccessTokenRequest(): string
    {
        $response = $this->client->post($this->baseUrl . '/uaa/oauth/token?' . http_build_query([
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
        ]), ['headers' => ['Authorization'=> $this->authorization]]);

        return $response->getBody()->getContents();
    }

    private function refreshAccessTokenRequest(string $refresh_token): string
    {
        $response = $this->client->post($this->baseUrl . '/uaa/oauth/token?' . http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
                'password' => $this->password,
            ]), ['headers' => ['Authorization'=> $this->authorization]]);

        return $response->getBody()->getContents();
    }

    public function send($to, $content)
    {
        $response = $this->client->post(
            $this->baseUrl . '/communication-event/api/communicationManagement/v2/communicationMessage/send',
            [
                'headers' => [
                    'Content-Type'=> 'application/json',
                    'Accept'=> '*/*',
                    'Authorization'=> 'bearer' . $this->getAccessToken(),
                ],
                'json' => [
                    'type' => 'SMS',
                    'content' => $content,
                    'receiver' => [
                        [
                            'id' => 0,
                            'phoneNumber' => $to
                        ],
                    ],
                    'sender' => [
                        'id' => 'DrovaE'
                    ],
                    'characteristic' => [
                        [
                            'name' => 'DISTRIBUTION.ID',
                            'value' => $this->distribution_id
                        ],
                        [
                            'name' => 'VALIDITY.PERIOD',
                            'value' => $this->validityPeriod
                        ],
                    ],
                ],
            ]
        );

        return $response->getBody()->getContents();
    }
}
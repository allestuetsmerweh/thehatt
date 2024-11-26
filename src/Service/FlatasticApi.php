<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FlatasticApi {
    public $baseUrl = 'https://api.flatastic-app.com/index.php/api';

    private $client;
    private $authData;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getLoginUrl(): string {
        return "{$this->baseUrl}/auth/login";
    }

    public function getChoresUrl(): string {
        return "{$this->baseUrl}/chores";
    }

    public function getChoresStatsUrl(): string {
        return "{$this->baseUrl}/chores/statistics";
    }

    public function getFlatUrl(): string {
        return "{$this->baseUrl}/wg";
    }

    public function getShoppingListUrl(): string {
        return "{$this->baseUrl}/shoppinglist";
    }

    public function getShoutsUrl(): string {
        return "{$this->baseUrl}/shouts";
    }


    public function authenticateFromRequest($request) {
        $basic_username = $request->headers->get('php-auth-user');
        $basic_password = $request->headers->get('php-auth-pw');
        $get_username = $request->query->get('username');
        $get_password = $request->query->get('password');

        $username = $get_username ?? $basic_username ?? null;
        $password = $get_password ?? $basic_password ?? null;

        return $this->authenticate($username, $password);
    }

    public function authenticate($username, $password) {
        $login_url = $this->getLoginUrl();
        $response = $this->client->request(
            'POST',
            $login_url,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'email' => $username,
                    'password' => $password,
                ],
            ],
        );
        $this->authData = $response->toArray();
        return $this->authData;
    }

    public function getHeaders() {
        $api_key = $this->authData['X-API-KEY'] ?? null;
        if (!$api_key) {
            throw new Exception('No flatastic API key');
        }
        return [
            'X-Api-Key' => $api_key,
            'X-Api-Version' => '2.0.0',
            'X-Client-Version' => 'home-assistant-flatastic v0.0.1',
        ];
    }

    public function getFlatmates() {
        $flatmates = $this->authData['wg']['flatmates'] ?? [];
        $flatmate_by_id = [];
        foreach ($flatmates as $flatmate) {
            $flatmate_by_id[strval($flatmate['id'])] = $flatmate;
        }
        return $flatmate_by_id;
    }

    public function getChores() {
        $response = $this->client->request(
            'GET',
            $this->getChoresUrl(),
            [
                'headers' => [
                    ...$this->getHeaders(),
                    'Accept' => 'application/json',
                ],
            ],
        );
        $chores = $response->toArray() ?? [];
        $chore_by_id = [];
        foreach ($chores as $chore) {
            $chore_by_id[strval($chore['id'])] = $chore;
        }
        return $chore_by_id;
    }

    public function getChoresStats() {
        $response = $this->client->request(
            'GET',
            $this->getChoresStatsUrl(),
            [
                'headers' => [
                    ...$this->getHeaders(),
                    'Accept' => 'application/json',
                ],
            ],
        );
        $chores_stats = $response->toArray() ?? [];
        return $chores_stats;
    }

    public function getShoppingList() {
        $response = $this->client->request(
            'GET',
            $this->getShoppingListUrl(),
            [
                'headers' => [
                    ...$this->getHeaders(),
                    'Accept' => 'application/json',
                ],
            ],
        );
        $shopping_list = $response->toArray() ?? [];
        return $shopping_list;
    }

    public function getFlat() {
        return $this->authData['wg'];
    }

    public function getBotUser() {
        return $this->authData['user'];
    }

    public function forceGetFlat() {
        $response = $this->client->request(
            'GET',
            $this->getFlatUrl(),
            [
                'headers' => [
                    ...$this->getHeaders(),
                    'Accept' => 'application/json',
                ],
            ],
        );
        $flat = $response->toArray() ?? [];
        return $flat;
    }

    public function createShout($text) {
        $response = $this->client->request(
            'POST',
            $this->getShoutsUrl(),
            [
                'headers' => [
                    ...$this->getHeaders(),
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'shout' => $text,
                ],
            ],
        );
        $shout = $response->toArray() ?? [];
        return $shout;
    }
}
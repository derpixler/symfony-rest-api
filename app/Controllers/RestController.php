<?php
namespace DerPixler\SymfonyRestApi\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RestController
{
    protected Request $request;
    protected string $client;

    public function __invoke(
        Request $request,
        string $client,
        string $action,
    )
    {
        $this->request = $request;
        $this->client = $client;

        $action = !empty($action) ? $action : 'index';

        return $this->$action();
    }

    protected function index(): JsonResponse
    {
        return new JsonResponse([
            'content' => sprintf('Hello, %s!', $this->client)
        ]);
    }

    protected function create(): JsonResponse
    {
        return new JsonResponse([
            'create' => sprintf('create request for client %s!', $this->client)
        ]);
    }
}
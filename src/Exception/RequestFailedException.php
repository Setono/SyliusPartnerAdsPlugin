<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class RequestFailedException extends RuntimeException
{
    public function __construct(private readonly RequestInterface $request, private readonly ResponseInterface $response, private readonly int $statusCode)
    {
        parent::__construct(sprintf('Request failed with status code %d', $this->statusCode));
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

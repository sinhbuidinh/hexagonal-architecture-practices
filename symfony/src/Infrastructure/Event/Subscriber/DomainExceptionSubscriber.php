<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Subscriber;

use App\Infrastructure\Event\DomainExceptionHandler;
use App\Infrastructure\Http\AuditHttp;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class DomainExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly DomainExceptionHandler $exceptionHandler)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $mapped = $this->exceptionHandler->handle(
            $event->getThrowable(),
            auditRequest: AuditHttp::merge($event->getRequest()),
        );
        if ($mapped === null) {
            return;
        }

        $event->setResponse(new JsonResponse($mapped->body, $mapped->status));
    }
}

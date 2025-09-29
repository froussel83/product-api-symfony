<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        if ($e instanceof \JsonException) {
            $event->setResponse(new JsonResponse(['message' => 'Invalid JSON'], 400));
            return;
        }

        if ($e instanceof ValidationException) {
            $event->setResponse(new JsonResponse(['errors' => $e->getMessage()], 422));
            return;
        }

        if ($e instanceof NotFoundException) {
            $event->setResponse(new JsonResponse(['message' => 'Not Found'], 404));
            return;
        }
    }
}

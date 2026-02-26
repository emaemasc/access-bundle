<?php

namespace Ema\AccessBundle\EventListener;

use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Role\AccessRoleFormatter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

#[AsEventListener(ControllerArgumentsEvent::class, '__invoke')]
class AccessAttributeListener
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authChecker,
        private ?ExpressionLanguage                    $expressionLanguage = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => ['__invoke', 20]];
    }

    public function __invoke(ControllerArgumentsEvent $event): void
    {
        if (!\is_array($event->getController()) || empty($event->getAttributes(Access::class))) {
            return;
        }

        $controller = $event->getController();
        $request = $event->getRequest();
        $arguments = $event->getNamedArguments();

        $controllerObject = $controller[0];
        $methodName = $controller[1];

        $reflectionClass = new \ReflectionClass($controllerObject);
        $reflectionMethod = new \ReflectionMethod($controllerObject, $methodName);

        $methodAttributes = $reflectionMethod->getAttributes(Access::class);
        $classAttributes = $reflectionClass->getAttributes(Access::class);

        foreach ($methodAttributes as $methodAttribute) {
            /** @var Access $attribute */
            $attribute = $methodAttribute->newInstance();
            $subject = $this->getAccessSubject($attribute, $request, $arguments);
            $this->check(AccessRoleFormatter::from(\get_class($controllerObject), $methodName), $attribute, $subject);
        }

        foreach ($classAttributes as $classAttribute) {
            /** @var Access $attribute */
            $attribute = $classAttribute->newInstance();
            $subject = $this->getAccessSubject($attribute, $request, $arguments);
            $this->check(AccessRoleFormatter::from(\get_class($controllerObject)), $attribute, $subject);
        }
    }

    private function check(string $role, Access $attribute, $subject): void
    {
        if (!$this->authChecker->isGranted($role, $subject)) {
            $message = $attribute->message ?: 'Access Denied by #[Access] on controller';

            if ($statusCode = $attribute->statusCode) {
                throw new HttpException($statusCode, $message, code: $attribute->exceptionCode ?? 0);
            }

            $accessDeniedException = new AccessDeniedException($message, code: $attribute->exceptionCode ?? 403);
            $accessDeniedException->setAttributes($role);
            $accessDeniedException->setSubject($subject);

            throw $accessDeniedException;
        }
    }

    private function getAccessSubject(Access $attribute, Request $request, array $arguments)
    {
        $subject = null;

        if ($subjectRef = $attribute->subject) {
            if (\is_array($subjectRef)) {
                foreach ($subjectRef as $refKey => $ref) {
                    $subject[\is_string($refKey) ? $refKey : (string) $ref] = $this->getAccessSubjectArgument($ref, $request, $arguments);
                }
            } else {
                $subject = $this->getAccessSubjectArgument($subjectRef, $request, $arguments);
            }
        }

        return $subject;
    }

    private function getAccessSubjectArgument(string|Expression $subjectRef, Request $request, array $arguments): mixed
    {
        if ($subjectRef instanceof Expression) {
            $this->expressionLanguage ??= new ExpressionLanguage();

            return $this->expressionLanguage->evaluate($subjectRef, [
                'request' => $request,
                'args' => $arguments,
            ]);
        }

        if (!\array_key_exists($subjectRef, $arguments)) {
            throw new RuntimeException(sprintf('Could not find the subject "%s" for the #[Access] attribute. Try adding a "$%s" argument to your controller method.', $subjectRef, $subjectRef));
        }

        return $arguments[$subjectRef];
    }
}

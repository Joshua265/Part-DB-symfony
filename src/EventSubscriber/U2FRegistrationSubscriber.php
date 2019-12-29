<?php


namespace App\EventSubscriber;


use App\Entity\UserSystem\U2FKey;
use Doctrine\ORM\EntityManagerInterface;
use R\U2FTwoFactorBundle\Event\RegisterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class U2FRegistrationSubscriber implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $router;

    protected $em;

    public function __construct(UrlGeneratorInterface $router, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->em = $entityManager;
    }

    // ..

    /** @return string[] **/
    public static function getSubscribedEvents(): array
    {
        return array(
            'r_u2f_two_factor.register' => 'onRegister',
        );
    }

    public function onRegister(RegisterEvent $event): void
    {
        $user = $event->getUser();
        $registration = $event->getRegistration();
        $newKey = new U2FKey();
        $newKey->fromRegistrationData($registration);
        $newKey->setUser($user);
        $newKey->setName($event->getKeyName());

        // persist the new key
        $this->em->persist($newKey);
        $this->em->flush();

        // generate new response, here we redirect the user to the fos user
        // profile
        $response = new RedirectResponse($this->router->generate('user_settings'));
        $event->setResponse($response);
    }
}
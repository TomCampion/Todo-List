<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->userRepository = self::$container->get('doctrine')->getRepository('App:User');
        $this->createFixtures();
    }

    public function testIndexWhenNotConnected()
    {
        $this->client->request('GET', '/');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
    }

    public function testIndex()
    {
        $this->logIn();
        $this->client->request('GET', '/');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewall = 'main';

        $user = $this->userRepository->findOneBy(['id'=>1]);
        $token = new UsernamePasswordToken($user, $user->getPassword(), $firewall, $user->getRoles());
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    private function createFixtures(){
        $purger = new ORMPurger($this->em);
        $purger->purge();
        $connection = $this->em->getConnection();
        $connection->exec("ALTER TABLE user AUTO_INCREMENT = 1;");

        $user = new User();
        $user->setEmail('test@mail.com');
        $user->setUsername('test');
        $user->setPassword('test');

        $this->em->persist($user);

        $this->em->flush();
    }
}

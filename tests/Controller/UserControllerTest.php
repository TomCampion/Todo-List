<?php

namespace App\Tests;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $userRepository;
    private $encoder;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->userRepository = self::$container->get('doctrine')->getRepository('App:User');
        $this->encoder = self::$container->get('security.password_encoder');
        $this->createFixtures();
    }

    public function testListAction()
    {
        $this->logIn();
        $this->client->request('GET', '/users');
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $this->logInAsAdmin();
        $this->client->request('GET', '/users');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAction()
    {
        $this->logIn();
        $this->client->request('GET', '/users/create');
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $this->logInAsAdmin();
        $crawler = $this->client->request('GET', '/users/create');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $username = 'username';
        $password = 'password';
        $mail = 'testmail@mail.com';
        $roles = array('ROLE_USER');
        $form = $crawler->selectButton('Ajouter')->form();
        $form['user[username]']->setValue($username);
        $form['user[password][first]']->setValue($password);
        $form['user[password][second]']->setValue($password);
        $form['user[email]']->setValue($mail);
        $form['user[roles]']->setValue($roles);

        $this->client->submit($form);

        $user = $this->userRepository->findOneBy(['id'=>3]);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals(true, $this->encoder->isPasswordValid($user, $password));
        $this->assertEquals($mail, $user->getEmail());
        $this->assertEquals($roles, $user->getRoles());
    }

    public function testEditAction()
    {
        $this->logIn();
        $this->client->request('GET', '/users/1/edit');
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $this->logInAsAdmin();
        $crawler = $this->client->request('GET', '/users/1/edit');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $username = 'edit_username';
        $password = 'edit_password';
        $mail = 'edit_testmail@mail.com';
        $roles = array('ROLE_ADMIN');
        $form = $crawler->selectButton('Modifier')->form();
        $form['user[username]']->setValue($username);
        $form['user[password][first]']->setValue($password);
        $form['user[password][second]']->setValue($password);
        $form['user[email]']->setValue($mail);
        $form['user[roles]']->setValue($roles);

        $this->client->submit($form);

        $user = $this->userRepository->findOneBy(['id'=>1]);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals(true, $this->encoder->isPasswordValid($user, $password));
        $this->assertEquals($mail, $user->getEmail());
        $this->assertEquals(array('ROLE_ADMIN', 'ROLE_USER'), $user->getRoles());
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

    private function logInAsAdmin()
    {
        $session = $this->client->getContainer()->get('session');

        $firewall = 'main';

        $user = $this->userRepository->findOneBy(['id'=>2]);
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
        $connection->exec("ALTER TABLE task AUTO_INCREMENT = 1;");

        $user = new User();
        $user->setEmail('test@mail.com');
        $user->setUsername('test');
        $user->setPassword('test');
        $user->setRoles(array('ROLE_USER'));

        $user2 = new User();
        $user2->setEmail('test2@mail.com');
        $user2->setUsername('test2');
        $user2->setPassword('test');
        $user2->setRoles(array('ROLE_ADMIN'));

        $task = new Task();
        $task->setUser($user);
        $task->setTitle('first task');
        $task->setContent('task content');

        $this->em->persist($user);
        $this->em->persist($user2);
        $this->em->persist($task);

        $this->em->flush();
    }
}

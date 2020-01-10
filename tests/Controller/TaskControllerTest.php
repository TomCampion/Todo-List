<?php

namespace App\Tests;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TaskControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $userRepository;
    private $taskRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->userRepository = self::$container->get('doctrine')->getRepository('App:User');
        $this->taskRepository = self::$container->get('doctrine')->getRepository('App:Task');
        $this->createFixtures();
    }

    public function testListAction()
    {
        $this->logIn();
        $this->client->request('GET', '/tasks');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAction()
    {
        $this->logIn();
        $crawler = $this->client->request('GET', '/tasks/create');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $title = 'Tâche 2';
        $content = 'second task !';
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]']->setValue($title);
        $form['task[content]']->setValue($content);
        $this->client->submit($form);

        $task = $this->taskRepository->findOneBy(['id'=>2]);
        $this->assertEquals($title, $task->getTitle());
        $this->assertEquals($content, $task->getContent());
    }

    public function testEditAction()
    {
        $this->logInAsUser2();
        $this->client->request('GET', '/tasks/1/edit');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Vous n\'êtes pas l\'auteur de cette tâche !', $crawler->filter('.alert-danger')->html());

        $this->logIn();
        $crawler = $this->client->request('GET', '/tasks/1/edit');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $title = 'Tâche 1 modifié';
        $content = 'La première tâche modifié !';
        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]']->setValue($title);
        $form['task[content]']->setValue($content);
        $this->client->submit($form);

        $task = $this->taskRepository->findOneBy(['id'=>1]);
        $this->assertEquals($title, $task->getTitle());
        $this->assertEquals($content, $task->getContent());
    }

    public function testToggleTaskAction()
    {
        $this->logIn();
        $this->client->request('GET', '/tasks/1/toggle');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $task = $this->taskRepository->findOneBy(['id'=>1]);
        $this->assertEquals(true, $task->getIsDone());
    }

    public function testDeleteTaskAction()
    {
        //not the author
        $this->logInAsUser2();
        $this->client->request('GET', '/tasks/1/delete');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Vous n\'êtes pas l\'auteur de cette tâche !', $crawler->filter('.alert-danger')->html());

        $task = $this->taskRepository->findOneBy(['id'=>1]);
        $this->assertEquals('first task', $task->getTitle());

        $this->logIn();
        $this->client->request('GET', '/tasks/1/delete');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $task = $this->taskRepository->findOneBy(['id'=>1]);
        $this->assertEquals(false, $task);
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

    private function logInAsUser2()
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
        $this->em->persist($task);
        $this->em->persist($user2);

        $this->em->flush();
    }
}

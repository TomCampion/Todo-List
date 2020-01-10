<?php


namespace App\Tests\Controller;


use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $em;
    private $encoder;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->encoder = self::$container->get('security.password_encoder');
        $this->createFixtures();
    }

    public function testLogin()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $username = 'test';
        $password = 'testpassword';
        $form = $crawler->selectButton('Se connecter')->form();

        //wrong username
        $form['username']->setValue('wrong_username');
        $form['password']->setValue($password);
        $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Username could not be found', $crawler->html());

        $session = unserialize($this->client->getRequest()->getSession()->get('_security_main'));

        $this->assertEquals(false, $session);

        //wrong password
        $form['username']->setValue($username);
        $form['password']->setValue('wrong_password');
        $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Invalid credentials', $crawler->html());

        $session = unserialize($this->client->getRequest()->getSession()->get('_security_main'));

        $this->assertEquals(false, $session);

        //wrong csrf token
        $form['username']->setValue($username);
        $form['password']->setValue($password);
        $form['_csrf_token']->setValue('wrong_token');
        $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Invalid CSRF token', $crawler->html());

        $session = unserialize($this->client->getRequest()->getSession()->get('_security_main'));

        $this->assertEquals(false, $session);

        //valid credentials
        $form = $crawler->selectButton('Se connecter')->form();
        $form['username']->setValue($username);
        $form['password']->setValue($password);
        $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Bienvenue sur Todo List', $crawler->html());

        $session = unserialize($this->client->getRequest()->getSession()->get('_security_main'));

        $this->assertEquals($username, $session->getUser()->getUsername());
    }

    public function testLogout()
    {
        //login
        $this->testLogin();
        //logout
        $this->client->request('GET', '/logout');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $session = unserialize($this->client->getRequest()->getSession()->get('_security_main'));

        $this->assertEquals(false, $session);
    }

    private function createFixtures(){
        $purger = new ORMPurger($this->em);
        $purger->purge();
        $connection = $this->em->getConnection();
        $connection->exec("ALTER TABLE user AUTO_INCREMENT = 1;");

        $user = new User();
        $user->setEmail('test@mail.com');
        $user->setUsername('test');
        $user->setPassword($this->encoder->encodePassword($user, 'testpassword'));
        $user->setRoles(array('ROLE_USER'));

        $this->em->persist($user);

        $this->em->flush();
    }
}
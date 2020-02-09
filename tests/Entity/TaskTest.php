<?php


namespace App\Tests\Entity;


use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskTest extends WebTestCase
{
    private $client;
    private $em;
    private $taskRepository;
    private $encoder;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::$container->get('doctrine')->getManager();
        $this->taskRepository = self::$container->get('doctrine')->getRepository('App:Task');
        $this->encoder = self::$container->get('security.password_encoder');
        $this->createFixtures();
    }

    public function testGetCreatedAt(){
        $task = $this->taskRepository->findOneBy(['id'=>1]);
        $this->assertEquals(date('d-m-Y'), $task->getCreatedAt()->format('d-m-Y'));
    }

    public function testSetIsDone(){
        $task = $this->taskRepository->findOneBy(['id'=>1]);
        $task->setIsDone(true);
        $this->assertEquals(true, $task->isDone());
        $task->setIsDone(false);
        $this->assertEquals(false, $task->isDone());
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
        $user->setPassword($this->encoder->encodePassword($user, 'testpassword'));
        $user->setRoles(array('ROLE_USER'));

        $task = new Task();
        $task->setUser($user);
        $task->setTitle('first task');
        $task->setContent('task content');

        $this->em->persist($user);
        $this->em->persist($task);

        $this->em->flush();
    }
}
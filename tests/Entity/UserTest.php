<?php


namespace App\Tests\Entity;


use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserTest extends WebTestCase
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

    public function testGetTasks(){
        $user = $this->userRepository->findOneBy(['id'=>1]);

        $tasks = $user->getTasks();
        $this->assertEquals('first task', $tasks->get(0)->getTitle());
        $this->assertEquals('second task', $tasks->get(1)->getTitle());

        $task = new Task();
        $task->setUser($user);
        $task->setTitle('new task');
        $task->setContent('new content');

        $user->addTask($task);
        $tasks = $user->getTasks();
        $this->assertEquals(true, $tasks->contains($task));

        $user->removeTask($task);
        $tasks = $user->getTasks();
        $this->assertEquals(false, $tasks->contains($task));
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

        $task2 = new Task();
        $task2->setUser($user);
        $task2->setTitle('second task');
        $task2->setContent('task content 2');

        $user->addTask($task);
        $user->addTask($task2);

        $this->em->persist($user);
        $this->em->persist($task);
        $this->em->persist($task2);

        $this->em->flush();
    }
}
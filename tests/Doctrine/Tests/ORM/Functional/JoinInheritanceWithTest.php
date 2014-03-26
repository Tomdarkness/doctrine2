<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests that when you join on a parent entity that uses class table inheritance that the WITH clause is applied
 * to the parent entity.
 *
 * @author Tomdarkness
 */
class JoinInheritanceWithTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\JoinTestEmail'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\JoinTestEvent'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\JoinTestEventOpen')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testJoinInheritanceWith()
    {
        $email = new joinTestEmail();
        $this->_em->persist($email);
        $this->_em->flush();

        $openOne = new joinTestEventOpen();
        $openOne->browser = "Awesome Browser";
        $openOne->email = $email;
        $openOne->awesome = true;

        $this->_em->persist($openOne);
        $this->_em->flush();

        $openTwo = new joinTestEventOpen();
        $openTwo->browser = "Uncool Browser";
        $openTwo->email = $email;
        $openTwo->awesome = false;

        $this->_em->persist($openTwo);
        $this->_em->flush();

        $dql = "SELECT email, events FROM Doctrine\Tests\ORM\Functional\JoinTestEmail email LEFT JOIN email.events events WITH events.awesome = true";
        $resultEmail = $this->_em->createQuery($dql)->getSingleResult();

        $this->assertCount(1, $resultEmail->events, 'WITH condition not applied to parent entity');
        $this->assertEquals('Awesome Browser', $resultEmail->events[0]->browser, 'WITH condition not or incorrectly applied to parent entity');
    }
}

/**
 * @Entity
 * @Table(name="jointestemail")
 */
class JoinTestEmail
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="JoinTestEvent", mappedBy="email")
     */
    public $events;
}

/**
 * @Entity
 * @Table(name="jointestevent")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "open" = "JoinTestEventOpen"
 * })
 */
class JoinTestEvent
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="JoinTestEmail")
     */
    public $email;

    /**
     * @Column(type="boolean")
     */
    public $awesome;
}

/**
 * @Entity
 * @Table(name="jointesteventopen")
 */
class JoinTestEventOpen extends joinTestEvent
{
    /**
     * @Column(type="string")
     */
    public $browser;
}
<?php
namespace ActiveCollab\DatabaseConnection\Test;

use ActiveCollab\DatabaseConnection\Connection;
use ActiveCollab\DatabaseConnection\Record\ValueCaster;
use DateTime;

/**
 * @package ActiveCollab\DatabaseConnection\Test
 */
class DeleteTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = new Connection($this->link);

        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        $create_table = $this->connection->execute("CREATE TABLE `writers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            `birthday` date NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->assertTrue($create_table);

        $this->connection->execute('INSERT INTO `writers` (`name`, `birthday`) VALUES (?, ?), (?, ?), (?, ?)', 'Leo Tolstoy', new DateTime('1828-09-09'), 'Alexander Pushkin', new DateTime('1799-06-06'), 'Fyodor Dostoyevsky', new DateTime('1821-11-11'));

        $this->assertEquals(3, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
    }

    /**
     * Tear down the test environment
     */
    public function tearDown()
    {
        $this->connection->execute('DROP TABLE IF EXISTS `writers`');

        parent::tearDown();
    }

    /**
     * Test delete all records
     */
    public function testDelete()
    {
        $affected_rows = $this->connection->delete('writers');

        $this->assertEquals(3, $affected_rows);
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Anton Chekhov'));
    }

    /**
     * Test delete with prepared conditions
     */
    public function testDeleteWithPreparedConditions()
    {
        $affected_rows = $this->connection->delete('writers', "`name` = 'Leo Tolstoy'");

        $this->assertEquals(1, $affected_rows);
        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
    }

    /**
     * Test delete prepared conditions, as array
     */
    public function testDeleteWithPreparedConditionsAsOnlyElement()
    {
        $affected_rows = $this->connection->delete('writers', [ "`name` = 'Leo Tolstoy'" ]);

        $this->assertEquals(1, $affected_rows);
        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
    }

    /**
     * Test delete where conditions are an array that needs to be prepared
     */
    public function testDeleteWithConditionsThatNeedToBePrepared()
    {
        $affected_rows = $this->connection->delete('writers', [ '`name` = ?', 'Leo Tolstoy' ]);

        $this->assertEquals(1, $affected_rows);
        $this->assertEquals(2, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers`'));
        $this->assertEquals(0, $this->connection->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `writers` WHERE `name` = ?', 'Leo Tolstoy'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionDueToEmptyConditionsArray()
    {
        $this->connection->delete('writers', []);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionDueToInvalidConditions()
    {
        $this->connection->delete('writers', 123);
    }
}
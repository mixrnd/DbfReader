<?php
/**
 * Created by PhpStorm.
 * User: mix
 * Date: 16.04.2017
 * Time: 22:18
 */

namespace tests;

use PHPUnit\Framework\TestCase;

use mixrnd\DbfReader\Table;

class TableTest extends TestCase
{
    /**
     * @param $fileName
     * @param array $config
     * @return Table
     */
    protected function createTable($fileName, $config = [])
    {
        return new Table(dirname(__FILE__) . '/files/' . $fileName, $config);
    }

    public function testRecordsNumber()
    {
        $table = $this->createTable('3064_21N.DBF');
        $this->assertEquals(10, $table->getRecordsNumber());
    }

    public function testColumnNames()
    {
        $table = $this->createTable('3064_21N.DBF');
        $this->assertEquals(["NUM_","OPR_","DBF_","VKEY_","FIELD_","NEW_","OLD_"], $table->getColumnNames());
    }

    public function testReadRecords()
    {
        $table = $this->createTable('3064_21N.DBF');
        $this->assertEquals([ '', '2', 'PRIM', '3UiBhT!M', 'PRIM3', '24.03.2017,28-8-01/32247', '',], $table->getRecords()[0]);
    }

    public function testConvertTypesSettings()
    {
        $table = $this->createTable('types1.dbf', ['convert' => true, 'encoding' => 'CP10007']);
        $records =  $table->getRecords();
        $this->assertEquals('simple char', $records[0][0]);

        $d = date_create_from_format('d.m.Y H:i:s', '12.12.2014  00:00:00');
        $this->assertEquals($d,  $records[0][1]);
//
        $d = date_create_from_format('d.m.Y H:i:s', '12.10.2004 12:15:48');
        $this->assertEquals($d,  $records[0][2]);
//
        $this->assertEquals(1.5, $records[0][3]);
        $this->assertEquals(7123, $records[0][4]);
        $this->assertEquals(true, $records[0][5]);
    }

    public function testReadRecordsAsAssocArray()
    {
        $table = $this->createTable('3064_21N.DBF');
        $this->assertEquals(
            ["NUM_" => '',"OPR_"  => '2',"DBF_" => 'PRIM', "VKEY_"  => '3UiBhT!M', "FIELD_"  => 'PRIM3', "NEW_"  => '24.03.2017,28-8-01/32247', "OLD_"  => ''],
            $table->getRecordsAssoc()[0]);
    }
}
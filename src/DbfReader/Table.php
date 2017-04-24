<?php
/**
 * Created by PhpStorm.
 * User: mix
 * Date: 16.04.2017
 * Time: 22:31
 */

namespace mixrnd\DbfReader;

/**
 * @see http://www.dbase.com/KnowledgeBase/int/db7_file_fmt.htm
 * @see http://www.autopark.ru/ASBProgrammerGuide/DBFSTRUC.HTM
 */
class Table
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var resource
     */
    private $filePointer;

    /**
     * @var integer
     */
    private $currentFilePos;

    /**
     * @var integer
     */
    private $recordsNumber;

    /**
     * @var integer
     */
    private $recordLengthInBytes;

    /**
     * @var array
     */
    private $columnNames = [];

    /**
     * [[length => 123, type => C],[length => 321, type => M] ..]
     *
     * @var array
     */
    private $columnMetaInfo = [];

    /**
     * @var bool
     */
    private $trimRecordValue;

    /**
     * @var bool
     */
    private $convertTypes;

    /**
     * @var ValueConverter
     */
    private $converter;

    /**
     * Table constructor.
     * @param $tableName
     *
     *  [
     *    'trim' => true/false // trim each value or not, default true
     *    'convert' => true/false // do perform record value type conversion based on record metadata type or not, default false
     *    'encoding' => 'cp1251' // encoding for convert from
     *  ]
     * @param array $config
     */
    public function __construct($tableName, $config = [])
    {
        $this->tableName = $tableName;
        $this->trimRecordValue = isset($config['trim']) ? $config['trim']: true;
        $this->convertTypes = isset($config['convert']) ? $config['convert']: false;

        $this->converter = new ValueConverter(isset($config['encoding']) ? $config['encoding']: false);

        $this->open();
    }

    public function getRecordsAssoc()
    {
       return $this->getRecordsImpl(true);
    }

    public function getRecords()
    {
        return $this->getRecordsImpl();
    }

    protected function getRecordsImpl($assoc = false)
    {
        $data = [];
        for ($i = 0; $i < $this->getRecordsNumber(); $i++) {
            $buf = $this->readBytes($this->recordLengthInBytes);
            $c = 1;
            $row = [];
            $idx = 0;
            foreach ($this->columnMetaInfo as $item) {
                $value = substr($buf, $c, $item['length']);
                if ($this->trimRecordValue) {
                    $value = trim($value);
                }

                if ($this->convertTypes) {
                    $value = $this->converter->convert($item['type'], $value);
                }

                if ($assoc) {
                    $row[$this->columnNames[$idx]] = $value;
                } else {
                    $row[] = $value;
                }

                $c += $item['length'];
                $idx++;
            }
            $data[] = $row;
        }
        return $data;
    }

    public function getRecordsNumber()
    {
        return $this->recordsNumber;
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return $this->columnNames;
    }

    protected function open()
    {
        if (!file_exists($this->tableName)) {
            throw new \Exception('Can not find' . $this->tableName);
        }

        $this->filePointer = fopen($this->tableName, 'rb');
        $this->readHeader();
    }


    protected function readHeader()
    {
        $version = $this->readChar();
        $isDbase7 = in_array($version, [4, 140]);

        $this->setFilePointerPos(4);
        $this->recordsNumber = $this->readInt();
        $headerLength = $this->readShort();
        $this->recordLengthInBytes = $this->readShort();

        $columnsDescriptionOffset = $isDbase7? 64:32;
        $columnDescriptionLength = $isDbase7? 48:32;

        $this->setFilePointerPos($columnsDescriptionOffset);

        $this->columnNames = [];
        while (!feof($this->filePointer)) { // read fields:
            $buf = fread($this->filePointer, 11);
            if (substr($buf, 0, 1) == chr(13)) {
                break;
            }

            $this->columnNames[] = (strpos($buf, 0x00) !== false ) ? substr($buf, 0, strpos($buf, 0x00)) : $buf;//unpack('C*', $buf);
            $type = $this->readBytes(1);      // type
            $this->readInt();       // memAddress
            $length =  $this->readChar();      // length
            $this->columnMetaInfo[] = [
                'length' => $length,
                'type' => $type
            ];
            fread($this->filePointer, $columnDescriptionLength - 17);
        }
        $this->setFilePointerPos($headerLength);
    }

    protected function readShort()
    {
        $buf = unpack('S', $this->readBytes(2));

        return $buf[1];
    }

    protected function readChar()
    {
        $buf = unpack('C', $this->readBytes(1));
        return $buf[1];
    }

    protected function readInt()
    {
        $buf = unpack('I', $this->readBytes(4));

        return $buf[1];
    }

    protected function readBytes($number)
    {
        $this->currentFilePos += $number;
        return fread($this->filePointer, $number);
    }

    private function setFilePointerPos($offset)
    {
        $this->currentFilePos = $offset;
        fseek($this->filePointer, $this->currentFilePos);
    }
}
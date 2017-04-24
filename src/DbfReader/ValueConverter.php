<?php
/**
 * Created by PhpStorm.
 * User: mix
 * Date: 18.04.2017
 * Time: 23:35
 */

namespace mixrnd\DbfReader;


class ValueConverter
{
    const FIELD_TYPE_MEMO = 'M';     // Memo type field
    const FIELD_TYPE_CHAR = 'C';     // Character field
    const FIELD_TYPE_DOUBLE = 'B';   // Double
    const FIELD_TYPE_NUMERIC = 'N';  // Numeric
    const FIELD_TYPE_FLOATING = 'F'; // Floating point
    const FIELD_TYPE_DATE = 'D';     // Date
    const FIELD_TYPE_LOGICAL = 'L';  // Logical - ? Y y N n T t F f (? when not initialized).
    const FIELD_TYPE_DATETIME = 'T'; // DateTime
    const FIELD_TYPE_INDEX = 'I';    // 4 bytes. Leftmost bit used to indicate sign, 0 negative.
    const FIELD_TYPE_TIMESTAMP = '@'; //Timestamp	8 bytes - two longs, first for date, second for time.  The date is the number of days since  01/01/4713 BC. Time is hours * 3600000L + minutes * 60000L + Seconds * 1000L
    const FIELD_TYPE_AUTOINCREMENT = '+'; //Autoincrement	Same as a Long

    private $encoding;

    public function __construct($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * @param $type
     * @param $value
     * @return integer|string|\DateTime|float|null|boolean
     */
    public function convert($type, $value)
    {
        $value = trim($value);
        switch ($type){
            case self::FIELD_TYPE_DATETIME:
                return $this->toDateTime($value);
            case self::FIELD_TYPE_DATE:
                return $this->toDate($value);
            case self::FIELD_TYPE_DOUBLE:
            case self::FIELD_TYPE_NUMERIC:
            case self::FIELD_TYPE_FLOATING:
                return $this->toFloat($value);
            case self::FIELD_TYPE_LOGICAL;
                return $this->toBoolean($value);
            case self::FIELD_TYPE_CHAR:
                return $this->toString($value);

        }
        return $value;
    }

    protected function toDate($value)
    {
        if (!$value) {
            return null;
        }

        $d = date_create_from_format('Ymd', $value);

        if ($d) {
            $d->setTime(0, 0);
            return $d;
        }

        return null;
    }

    protected function toDateTime($value)
    {
        if (!$value) {
            return null;
        }

        //ГГГГММДДЧЧММСС
        $d = date_create_from_format('YmdHis', $value);
        if ($d) {
            return $d;
        }

        $buf = unpack('i', substr($value, 0, 4));
        $julianDate = $buf[1];
        $buf = unpack('i', substr($value, 4, 4));
        $microsecond = $buf[1];

        $d = date_create_from_format('m/d/Y H:i:s', jdtogregorian($julianDate) . ' ' . gmdate('H:i:s', $microsecond/1000));
        if ($d){
            return $d;
        }

        return null;
    }

    protected function toFloat($value)
    {
        return floatval($value);
    }

    protected function toString($value)
    {
        if ($this->encoding) {
            return iconv($this->encoding, 'UTF-8', $value);
        }
        return $value;
    }

    protected function toBoolean($value)
    {
        if (in_array($value, ['T', 'Y', 'J', '1'])) {
            return true;
        }

        return false;
    }

}
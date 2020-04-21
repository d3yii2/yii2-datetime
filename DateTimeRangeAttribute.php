<?php

namespace omnilight\datetime;

use yii\base\Arrayable;
use yii\base\BaseObject;
use yii\helpers\FormatConverter;


/**
 * Class DateTimeAttribute
 * @property string $value
 */
class DateTimeRangeAttribute extends BaseObject
{
    /**
     * @var DateTimeBehavior
     */
    public $behavior;
    /**
     * @var string
     */
    public $originalAttribute;
    /**
     * @var string|array
     */
    public $originalFormat;
    /**
     * @var string|array
     */
    public $targetFormat;
    /**
     * @var string
     */
    public $nullValue;
    /**
     * @var string
     */
    protected $_value;

    private const RANGE_SEPARATOR = ' - ';

    /**
     * @return string
     */
    function __toString()
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    function __invoke()
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function getValue()
    {
        try {
            if ($this->_value !== null) {
                return $this->_value;
            } else {
                $originalValue = $this->behavior->owner->{$this->originalAttribute};
                if ($originalValue === null)
                    return $this->nullValue;
                else {
                    // No range string detected. Just return default conversion
                    if (!$this->isRangeString($originalValue)) {
                        return $this->behavior->formatter->format($originalValue, $this->targetFormat);
                    }

                    $startDate = $this->getStartDate($value);
                    $formatedStartDate = $this->behavior->formatter->format($startDate, $this->targetFormat);

                    $endDate = $this->getEndDate($value);
                    $formatedEndDate = $this->behavior->formatter->format($endDate, $this->targetFormat);

                    $formatedDate = $formatedStartDate . self::RANGE_SEPARATOR . $formatedEndDate;

                    return $formatedDate;
                }
            }
        } catch (\Exception $e) {
            return $this->nullValue;
        }
    }

    /**
     * @param $value
     * @return bool
     */
    public function setValue($value)
    {
        $this->_value = $value;
        $normalizedFormat = DateTimeBehavior::normalizeIcuFormat($this->targetFormat, $this->behavior->formatter);
        $phpFormat = FormatConverter::convertDateIcuToPhp($normalizedFormat[1], $normalizedFormat[0], \Yii::$app->language);

        if (!$this->isRangeString($value)) {
            $dateTime = date_create_from_format($phpFormat, $value);
            $this->behavior->owner->{$this->originalAttribute} = $this->behavior->formatter->format($dateTime, $this->originalFormat);
        } else {
            $formatedDate = '';

            $startDate = $this->getStartDate($value);
            $startDateTime = date_create_from_format($phpFormat, $startDate);
            if ($startDateTime) {
                $formatedStartDate = $this->behavior->formatter->format($startDateTime, $this->originalFormat);
            } else {
                return false;
            }

            $endDate = $this->getEndDate($value);
            $endDateTime = date_create_from_format($phpFormat, $endDate);
            if ($endDateTime) {
                $formatedEndDate = $this->behavior->formatter->format($endDateTime, $this->originalFormat);
            } else {
                return false;
            }
            $formatedDate = $formatedStartDate . self::RANGE_SEPARATOR . $formatedEndDate;

            $this->behavior->owner->{$this->originalAttribute} = $formatedDate;
        }
    }

    /**
     * @param string $val
     * @return mixed
     */
    private function getStartDate(string $val)
    {
        $dates = $this->splitRangeStr($val);
        return $dates[0];
    }

    /**
     * @param string $val
     * @return mixed
     */
    private function getEndDate(string $val)
    {
        $dates = $this->splitRangeStr($val);
        return $dates[1];
    }

    /**
     * @param string $str
     * @return false|int
     */
    private function isRangeString(string $str)
    {
        return strpos($str, self::RANGE_SEPARATOR);
    }

    /**
     * @param string $str
     * @return array
     */
    private function splitRangeStr(string $str): array
    {
        return explode(self::RANGE_SEPARATOR, $str);
    }
}
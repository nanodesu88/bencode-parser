<?php

namespace nanodesu88\bencode;

class BencodeDictionary extends BencodeCollection
{
    /**
     * @var BencodeDictionaryPair[]
     */
    protected $value = [];

    /**
     * BencodeDictionary constructor.
     *
     * @param iterable $source
     * @throws BencodeException
     */
    public function __construct(iterable $source = []) {
        foreach ($source as $key => $val) {
            $this->checkKey($key);

            $this->set($key, $val);
        }
    }

    /**
     * @inheritDoc
     */
    public function encode() {
        usort($this->value, function (BencodeDictionaryPair $pair1, BencodeDictionaryPair $pair2) {
            return $pair1->key->unMorph() > $pair2->key->unMorph();
        });

        $data = 'd';

        foreach ($this->value as $item) {
            $data .= $item->key->encode() . $item->value->encode();
        }

        return $data . 'e';
    }

    /**
     * @var BencodeElement
     */
    protected $_smart;

    /**
     * @param BencodeElement $e
     * @return mixed|void
     * @throws BencodeException
     */
    public function smartAdd(BencodeElement $e) {
        if ($this->_smart === null) {
            $this->_smart = $e;
        } else {
            $this->set($this->_smart, $e);
            $this->_smart = null;
        }

        $e->parent = $this;
    }

    /**
     * @inheritDoc
     */
    public function getIterator() {
        return new \ArrayIterator($this->value);
    }

    /**
     * @inheritDoc
     */
    public function exists($offset) {
        $offset = static::morph($offset);

        foreach ($this->value as $index => $pair) {
            if ($pair->key->compare($offset)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     * @return BencodeElement|BencodeCollection|BencodeDictionary|BencodeList|BencodeString
     */
    public function getValue($key) {
        $offset = static::morph($key);

        foreach ($this->value as $item) {
            if ($item->key->compare($offset)) {
                return $item->value;
            }
        }

        return null;
    }

    public function get($key) {
        return $this->getValue($key);
    }

    /**
     * @param string|BencodeString $key
     * @param mixed|BencodeElement $value
     * @throws BencodeException
     */
    public function setValue($key, $value) {
        $this->checkKey($key);

        $offset = static::morph($key);
        $value  = static::morph($value);

        if ($this->exists($offset)) {
            foreach ($this->value as $key => $item) {
                if ($item->key->compare($offset)) {
                    $item->value = $value;
                    // Некоторые клиенты требуют отсортированный список
                    ksort($this->value);

                    break;
                }
            }
        } else {
            $this->value[] = new BencodeDictionaryPair($offset, $value);
        }

        $value->parent = $this;
    }

    /**
     * @param $key
     * @param $value
     * @throws BencodeException
     */
    public function set($key, $value) {
        return $this->setValue($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function count() {
        // TODO: Implement count() method.
    }

    /**
     * @inheritDoc
     */
    public function compare(BencodeElement $element) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function unMorph() {
        $result = [];

        foreach ($this->value as $item) {
            $result[$item->key->unMorph()] = $item->value->unMorph();
        }

        return $result;
    }

    /**
     * @param $key
     * @throws BencodeException
     */
    protected function checkKey($key) {
        if (($key instanceof BencodeElement && !($key instanceof BencodeString)) && !is_string($key)) {
            throw new BencodeException('dictionary keys must be string');
        }
    }
}




















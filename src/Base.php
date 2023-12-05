<?php
namespace Npc\Entity;

use Nette\Utils\Strings;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Object_;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Npc\Helper\Reflection\TypeParser;

class Base implements \ArrayAccess,\IteratorAggregate
{
    protected $_data = [];

    public function __construct($data = [])
    {
        $this->fill($data);
    }

    public function getGetter($name): string
    {
        return 'get' . Strings::capitalize($name);
    }

    public function getSetter($name): string
    {
        return 'set' . Strings::capitalize($name);
    }

    /**
     * @param $name
     * @param $value
     * @throws \ReflectionException
     */
    public function __set($name, $value)
    {
        $setter = $this->getSetter($name);

        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
            return;
        }
        // 转换类型
        if (is_array($value) || is_object($value)) {
            $value = $this->translate($name, $value);
        }
        $this->_data[$name] = $value;
    }

    public function __isset($name)
    {
        $getter = $this->getGetter($name);
        if (method_exists($this, $getter)) {
            return true;
        }
        if (array_key_exists($name, $this->_data)) {
            return !($this->_data[$name] === null);
        }

        return isset($this->{$name});
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function &__get($name)
    {
        $res = null;
        $getter = $this->getGetter($name);
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        return $res;
    }

    public function fill($data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        return $this;
    }

    public function toArray($deep= false,$columns =[]): array
    {
        $data = [];
        foreach ($this->_data as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param $name
     * @param $value
     * @return array|mixed|string
     * @throws \ReflectionException
     */
    private function translate($name, $value)
    {
        $type = $this->guessType($name);
        // 转化object
        if (isset($type[0]) && $type[0] instanceof Object_) {
            $objType = ltrim((string)$type[0], '\\');
            if (!$value instanceof $objType) {
                $value = new $objType($value);
            }
        }

        // 如果是object数组 批量转换
        if (isset($type[0]) && $type[0] instanceof Array_ && $type[0]->getValueType() instanceof Object_) {
            $objType = ltrim((string)$type[0]->getValueType(), '\\');
            $value = array_map(function ($item) use ($objType) {
                return new $objType($item);
            }, $value);
        }

        return $value;
    }

    /**
     * @param $name
     * @return mixed|null
     * @throws \ReflectionException
     */
    private function guessType($name)
    {
        if (!TypeParser::instance()->hasTypes(get_called_class())) {
            $reflectionClass = ReflectionClass::createFromInstance($this);
            $types = TypeParser::instance()->__invoke(
                $reflectionClass,
                $reflectionClass->getDeclaringNamespaceAst()
            );
        } else {
            $types = TypeParser::instance()->getTypes(get_called_class());
        }
        return $types[$name] ?? null;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    public function offsetExists($offset): bool
    {
        return key_exists($offset,$this->_data);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }
}
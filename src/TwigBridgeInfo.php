<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 29/08/2017
 * Time: 3:55 PM
 */

namespace Oasis\SlimVue;

class TwigBridgeInfo implements SlimVueBridgeInterface
{
    private $data = [];
    
    public function __construct($data = [])
    {
        $this->data = $data;
    }
    
    protected function getPlainValue($data)
    {
        if (is_array($data)) {
            $list = [];
            foreach ($data as $k => $item) {
                $list[$k] = $this->getPlainValue($item);
            }

            return $list;
        }
        else {
            if ($data instanceof \JsonSerializable) {
                return $data->jsonSerialize();
            }
            else {
                return $data;
            }
        }

    }

    public function add($key, $value)
    {
        $this->data[$key] = $this->getPlainValue($value);
    }

    public function getExecTwig($pageTwig)
    {
        $exec = preg_replace('#^slimvue/pages/#', 'slimvue/controllers/', (string)$pageTwig);
        
        return $exec;
    }
    
    public function render()
    {
        $result = \json_encode($this->data);
        if ($result === false) {
            throw new \InvalidArgumentException(\json_last_error_msg());
        }
        
        return $result;
    }
}

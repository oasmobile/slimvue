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
    
    protected function put($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function add($key, $value)
    {
        if (is_array($value)) {
            $arrVal = [];
            foreach ($value as $k => $item) {
                if ($item instanceof \JsonSerializable) {
                    $arrVal[$k] = $item->jsonSerialize();
                }
                else {
                    $arrVal[$k] = $item;
                }
            }
            $this->put($key, $arrVal);
        }
        else {
            if ($value instanceof \JsonSerializable) {
                $this->put($key, $value->jsonSerialize());
            }
            else {
                $this->put($key, $value);
            }
        }
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

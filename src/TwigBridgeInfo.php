<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 29/08/2017
 * Time: 3:55 PM
 */

namespace Oasis\SlimVue;

class TwigBridgeInfo
{
    public function __construct()
    {
    }
    
    public function getExecTwig($pageTwig)
    {
        $exec = preg_replace('#^slimvue/pages/#', 'slimvue/controllers/', (string)$pageTwig);
        
        return $exec;
    }
    
    public function render()
    {
        return \json_encode([]);
    }
}

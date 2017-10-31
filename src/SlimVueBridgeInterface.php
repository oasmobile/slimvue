<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 30/09/2017
 * Time: 6:53 PM
 */

namespace Oasis\SlimVue;

interface SlimVueBridgeInterface
{
    public function getExecTwig($pageTwig);
    
    public function add($key, $value);

    public function render();
}

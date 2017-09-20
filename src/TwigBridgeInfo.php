<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 29/08/2017
 * Time: 3:55 PM
 */

namespace Oasis\SlimVue;

use Oasis\Mlib\Http\SilexKernel;
use Symfony\Component\Security\Core\Role\Role;

class TwigBridgeInfo
{
    private $constants;
    
    public function __construct($constants = [])
    {
        $this->constants = $constants;
    }
    
    public function expandRoles(SilexKernel $kernel, $roleHierarchy)
    {
        $user = $kernel->getUser();
        if (!$user) {
            return [];
        }
        $roles = \array_map(
            function ($orig) {
                return ($orig instanceof Role ? $orig->getRole() : $orig);
            },
            $user->getRoles()
        );
        
        //$roleHierarchy = $kernel->getParameter('app.roles', []);
        
        $result = $roles;
        do {
            foreach ($roles as $role) {
                $children = isset($roleHierarchy[$role]) ? $roleHierarchy[$role] : [];
                $result   = \array_merge($result, $children);
            }
            $result = \array_unique($result);
            if (count($result) <= count($roles)) {
                break;
            }
            $roles = $result;
        } while (true);
        
        return $result;
    }
    
    /**
     * @return array
     */
    public function getConstants()
    {
        return $this->constants;
    }
    
    public function getExecTwig($pageTwig)
    {
        return \dirname($pageTwig) . "/exec-" . \basename($pageTwig);
    }
}

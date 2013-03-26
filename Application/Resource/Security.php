<?php
/**
 * Ooba_Application_Resource_Security
 *
 * @category   Ooba
 * @package    Application_Resource
 * @copyright (c) Copyright 2010-2011, Michael Faletti. All Rights Reserved.
 */
class Ooba_Application_Resource_Security extends Ooba_Application_Resource_Abstract
{
	public function init()
	{
		include APPLICATION_PATH . '/configs/roles.php';
		$constantsKeeper = ConstantsKeeper::getInstance();

		if (!empty($roles)) {
			$this->_createRoles($roles);
		}
				
		if (!empty($permissions)) {
			$this->_createPermissions($permissions);
		}
		
		if (!empty($acls)) {
			$this->_createAcls($acls);
		}
		
		if (isset($constantsKeeper->guestAuth)) {
            $guestAuth = explode(',', $constantsKeeper->guestAuth);
			$guestAuth = array('callable' => $guestAuth[0], 'args' => $guestAuth[1]);
           	Ooba_Security_Auth::setGuestIdentity($guestAuth);
        }
	}
	
	/**
     * Create acls from an array
     *
     * @param  array $data Array of data
     * @return void
     */
	protected function _createAcls(array $data)
	{
		foreach ($data as $key => $value) {
            if (mb_substr($key, -2) === '.*') {
                $key = mb_substr($key, 0, -2);
            }
            
            $acl = Ooba_Security_Acl::factory($key);
            $this->_addRulesToAcl($acl, $value);
        }
	}
	
	/**
     * Add roles to an Acl
     *
     * @param  Ooba_Security_Acl $acl   ACL object
     * @param  string            $rules Comma-separated rules
     * @return void
     */
	protected function _addRulesToAcl(Ooba_Security_Acl $acl, $rules)
	{
		$rules = explode(',', $rules);
        if (is_array($rules) === false) {
            continue;
        }
        
        foreach ($rules as $rule) {
            $rule = trim($rule);
            if (empty($rule) === true) {
                continue;
            }
			
            //list($type, $id) = explode('.', $rule);
			$k = explode('.', $rule);
            
            if ($k[0] === 'permission') {
                $acl->addPermission(Ooba_Security_Permission::factory($k[1]));
            }
            
            if ($k[0] === 'role') {
                $acl->addRole(Ooba_Security_Role::factory($k[1]));
            }
        }
	}
	
	/**
     * Create roles from an array
     *
     * @param  array $data Array of data
     * @return void
     */
	protected function _createRoles(array $data)
	{
        foreach ($data as $role => $permissions) {
            $roleObject  = Ooba_Security_Role::factory($role);
            $permissions = explode(',', $permissions);
            if (is_array($permissions) === false) {
                continue;
            }
            
            foreach ($permissions as $permission) {
                $permission = trim($permission);
                if (empty($permission) === true) {
                    continue;
                }
                
                list($type, $perm) = explode('.', $permission);
                if ($type === 'permission') {
                    $roleObject->addPermission(Ooba_Security_Permission::factory($perm));
                }
            }
        }
	}
	
	/**
     * Create permissions from an array
     *
     * @param  array $data Array of data
     * @return void
     */
	protected function _createPermissions(array $data)
	{
		foreach ($data as $permission => $val) {
            Ooba_Security_Permission::factory($permission);
        }
	}
}
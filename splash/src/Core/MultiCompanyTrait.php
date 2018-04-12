<?php
/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Splash\Local\Core;

use Exception;
use Splash\Core\SplashCore  as Splash;

/**
 * @abstract    MultiCompany Module Manager
 * @author      B. Paquier <contact@splashsync.com>
 */
trait MultiCompanyTrait
{
    
    private static $DEFAULT_ENTITY    =   1;
    

    public function isMultiCompany()
    {
        return (bool) Splash::local()->getParameter("MAIN_MODULE_MULTICOMPANY");
    }
    
    protected function isMultiCompanyDefaultEntity()
    {
        return $this->isMultiCompany() && ( $this->getMultiCompanyEntityId() == static::$DEFAULT_ENTITY );
    }
    
    protected function isMultiCompanyChildEntity()
    {
        return $this->isMultiCompany() && ( $this->getMultiCompanyEntityId() != static::$DEFAULT_ENTITY );
    }
    
    
    protected function getMultiCompanyEntityId()
    {
        global $conf;
        return $conf->entity;
    }

    protected function setupMultiCompany()
    {
        global $conf, $db, $user;
        
        //====================================================================//
        // Detect MultiCompany Module
        if (!$this->isMultiCompany()) {
            return;
        }
        //====================================================================//
        // Detect Required to Switch Entity
        if (empty(Splash::input("Entity", INPUT_GET))
                || ( Splash::input("Entity", INPUT_GET) == static::$DEFAULT_ENTITY)) {
            return;
        }
        //====================================================================//
        // Switch Entity
        $conf->entity   =   (int)   Splash::input("Entity", INPUT_GET);
        $conf->setValues($db);
        $user->entity   =   $conf->entity;

        return $conf->entity;
    }
    
    protected function getMultiCompanyServerPath()
    {
        
        $ServerRoot     =   realpath(Splash::input("DOCUMENT_ROOT"));
        $Prefix         =   isMultiCompanyChildEntity ? ( "?Entity=" . $this->getMultiCompanyEntityId() ) : "";
        $FullPath       =   dirname(dirname(__DIR__)) . "/vendor/splash/phpcore/soap.php" . $Prefix;
        $RelativePath   =   explode($ServerRoot, $FullPath);
        
        if (isset($RelativePath[1])) {
            return  $RelativePath[1];
        }
        
        return   null;
    }
    
    /**
     * @abstract    Ensure Dolibarr Object Access is Allowed from this Entity
     *
     * @param   object  $Subject    Focus on a specific object
     *
     * @return  bool                False if Error was Found
     */
    public function isMultiCompanyAllowed($Subject = null)
    {
        
        global $langs;
        
        //====================================================================//
        // Detect MultiCompany Module
        if (!$this->isMultiCompany()) {
            return true;
        }
        //====================================================================//
        // Check Object
        if (is_null($Subject)) {
            return false;
        }
        //====================================================================//
        // Load Object Entity
        if (isset($Subject->entity)) {
            $EntityId   =   $Subject->entity;
        } else {
            $EntityId   =   $Subject->getValueFrom($Subject->table_element, $Subject->id, "entity");
        }
        //====================================================================//
        // Check Object Entity
        if ($EntityId != $this->getMultiCompanyEntityId()) {
            $Trace = (new Exception())->getTrace()[1];
            $langs->load("errors");
            return  Splash::log()->err(
                "ErrLocalTpl",
                $Trace["class"],
                $Trace["function"],
                html_entity_decode($langs->trans('ErrorForbidden'))
            );
        }
        
        return true;
    }
}

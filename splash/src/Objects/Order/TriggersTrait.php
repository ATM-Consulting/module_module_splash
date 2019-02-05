<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Order;

use Splash\Client\Splash;

/**
 * Order Dolibarr Trigger trait
 */
trait TriggersTrait
{
    /**
     * Prepare Object Commit for Order
     *
     * @param string $action Code de l'evenement
     * @param object $object Objet concerne
     *
     * @return bool Commit is required
     */
    protected function doOrderCommit($action, $object)
    {
        global $db;

        //====================================================================//
        // Check if Commit is Requierd
        if (!$this->isOrderCommitRequired($action)) {
            return false;
        }
        
        //====================================================================//
        // Commit Last Changes done On DataBase
        $db->Commit();
        
        //====================================================================//
        // Store Global Action Parameters
        $this->setOrderObjectId($object);
        $this->setOrderParameters($action);
        
        return true;
    }

    /**
     * Check if Commit is Requiered
     *
     * @param string $action Code de l'evenement
     *
     * @return bool
     */
    private function isOrderCommitRequired($action)
    {
        //====================================================================//
        // Filter Triggered Actions
        return in_array($action, array(
            // Order Actions
            'ORDER_CREATE',
            'ORDER_VALIDATE',
            'ORDER_MODIFY',
            'ORDER_UPDATE',
            'ORDER_DELETE',
            'ORDER_CLOSE',
            'ORDER_REOPEN',
            'ORDER_CLASSIFY_BILLED',
            'ORDER_CANCEL',
            // Order Line Actions
            'LINEORDER_INSERT',
            'LINEORDER_UPDATE',
            'LINEORDER_DELETE',
            // Order Contacts Actions
            'COMMANDE_ADD_CONTACT',
            'COMMANDE_DELETE_CONTACT',
        ), true);
    }
    
    /**
     * Identify Order Id from Given Object
     *
     * @param object $object Objet concerne
     *
     * @return void
     */
    private function setOrderObjectId($object)
    {
        //====================================================================//
        // Identify Order Id
        if (is_a($object, "OrderLine")) {
            if ($object->fk_commande) {
                $this->Id        = $object->fk_commande;
            } else {
                $this->Id        = $object->oldline->fk_commande;
            }
        } else {
            $this->Id        = $object->id;
        }
    }
    
    /**
     * Prepare Object Commit for Product
     *
     * @param string $action Code de l'evenement
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setOrderParameters($action)
    {
        //====================================================================//
        // Store Global Action Parameters
        $this->Type      = "Order";
        
        switch ($action) {
            case 'ORDER_CREATE':
                $this->Action       = SPL_A_CREATE;
                $this->Comment      = "Order Created on Dolibarr";

                break;
            case 'ORDER_VALIDATE':
            case 'ORDER_MODIFY':
            case 'ORDER_UPDATE':
            case 'ORDER_CLOSE':
            case 'ORDER_REOPEN':
            case 'ORDER_CLASSIFY_BILLED':
            case 'ORDER_CANCEL':
            case 'LINEORDER_INSERT':
            case 'LINEORDER_UPDATE':
            case 'LINEORDER_DELETE':
            case 'COMMANDE_ADD_CONTACT':
            case 'COMMANDE_DELETE_CONTACT':
                $this->Action       = (Splash::object("Order")->isLocked() ?   SPL_A_CREATE : SPL_A_UPDATE);
                $this->Comment      = "Order Updated on Dolibarr";

                break;
            case 'ORDER_DELETE':
                $this->Action       = SPL_A_DELETE;
                $this->Comment      = "Order Deleted on Dolibarr";

                break;
        }
        
        return true;
    }
}

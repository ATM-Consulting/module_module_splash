<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Invoice;

use Splash\Core\SplashCore      as Splash;

/**
 * Dolibarr Customer Invoice Status Field
 */
trait StatusTrait
{
    /**
     * Build Customer Order Status Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStatusFields()
    {
        global $langs;

        //====================================================================//
        // Invoice Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("status")
            ->Name($langs->trans("Status"))
            ->Group(html_entity_decode($langs->trans("Status")))
            ->MicroData("http://schema.org/Invoice", "paymentStatus")
            ->AddChoice("PaymentDraft", $langs->trans("BillStatusDraft"))
            ->AddChoice("PaymentDue", $langs->trans("BillStatusNotPaid"))
            ->AddChoice("PaymentComplete", $langs->trans("BillStatusConverted"))
            ->AddChoice("PaymentCanceled", $langs->trans("BillStatusCanceled"))
            ->isNotTested();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStatusFields(string $key, string $fieldName)
    {
        if ('status' != $fieldName) {
            return;
        }

        if (0 == $this->object->statut) {
            $this->out[$fieldName] = "PaymentDraft";
        } elseif (1 == $this->object->statut) {
            $this->out[$fieldName] = "PaymentDue";
        } elseif (2 == $this->object->statut) {
            $this->out[$fieldName] = "PaymentComplete";
        } elseif (3 == $this->object->statut) {
            $this->out[$fieldName] = "PaymentCanceled";
        } else {
            $this->out[$fieldName] = "Unknown";
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setStatusFields(string $fieldName, $fieldData)
    {
        global $conf, $langs, $user;

        if ('status' != $fieldName) {
            return true;
        }
        unset($this->in[$fieldName]);

        //====================================================================//
        // Safety Check
        if (empty($this->object->id)) {
            return false;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate invoice, we must provide warehouse id
        if (!empty($conf->stock->enabled) && 1 == $conf->global->STOCK_CALCULATE_ON_BILL) {
            if (empty($conf->global->SPLASH_STOCK)) {
                return Splash::log()->errTrace($langs->trans("WarehouseSourceNotDefined"));
            }
        }
        $initialStatut = $this->object->statut;
        switch ($fieldData) {
            //====================================================================//
            // Status Draft
            //====================================================================//
            case "Unknown":
            case "PaymentDraft":
                //====================================================================//
                // Whatever => Set Draft
                if ((0 != $this->object->statut) && (!$this->setStatusDraft())) {
                    return false;
                }
                $this->object->statut = \Facture::STATUS_DRAFT;

                break;
            //====================================================================//
            // Status Validated
            //====================================================================//
            case "PaymentDue":
            case "PaymentDeclined":
            case "PaymentPastDue":
                //====================================================================//
                // If Already Paid => Set Draft
                // If Already Canceled => Set Draft
                if (in_array($this->object->statut, array(2,3), true) && (!$this->setStatusDraft())) {
                    return false;
                }
                //====================================================================//
                // If Not Validated => Set Validated
                if ((1 != $this->object->statut)) {
                    if (1 != $this->object->validate($user, "", $conf->global->SPLASH_STOCK)) {
                        return $this->catchDolibarrErrors();
                    }
                    //====================================================================//
                    // Mark Main Document Download Url as Updated
                    $this->setDownloadUrlsUpdated();
                }
                $this->object->paye = 0;
                $this->object->statut = \Facture::STATUS_VALIDATED;

                break;
            //====================================================================//
            // Status Paid
            //====================================================================//
            case "PaymentComplete":
                //====================================================================//
                // If Draft => Set Validated
                if (0 == $this->object->statut) {
                    if (1 != $this->object->validate($user, "", $conf->global->SPLASH_STOCK)) {
                        return $this->catchDolibarrErrors();
                    }
                    //====================================================================//
                    // Mark Main Document Download Url as Updated
                    $this->setDownloadUrlsUpdated();
                }
                //====================================================================//
                // If Validated => Set Paid
                if ((1 == $this->object->statut) && (1 != $this->object->set_paid($user))) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 1;
                $this->object->statut = \Facture::STATUS_CLOSED;

                break;
            //====================================================================//
            // Status Canceled
            //====================================================================//
            case "PaymentCanceled":
                //====================================================================//
                // Whatever => Set Canceled
                if ((3 != $this->object->statut) && (!$this->setStatusCancel())) {
                    return $this->catchDolibarrErrors();
                }
                $this->object->paye = 0;
                $this->object->statut = \Facture::STATUS_ABANDONED;

                break;
        }
        if ($initialStatut != $this->object->statut) {
            $this->needUpdate();
        }

        return true;
    }

    /**
     * Set Invoice State as Draft
     *
     * @return bool
     */
    private function setStatusDraft(): bool
    {
        global $conf, $user;

        if (method_exists($this->object, "set_draft")
                && (1 != $this->object->set_draft($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }
        if (method_exists($this->object, "setDraft")
                && (1 != $this->object->setDraft($user, $conf->global->SPLASH_STOCK))) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }

    /**
     * Set Invoice State as Cancelled
     *
     * @return bool
     */
    private function setStatusCancel(): bool
    {
        global $user;

        if (method_exists($this->object, "set_canceled")
            && (1 != $this->object->set_canceled($user))) {
            return $this->catchDolibarrErrors();
        }
        if (method_exists($this->object, "setCanceled")
            && (1 != $this->object->setCanceled($user))) {
            return $this->catchDolibarrErrors();
        }

        return true;
    }
}

<?php
/*
 * Copyright (C) 2011 Bernard Paquier       <bernard.paquier@gmail.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * 
 *  \Id 	$Id: ConfigMain.php 493 2016-03-24 23:01:42Z Nanard33 $
 *  \version    $Revision: 493 $
 *  \ingroup    Splash - Dolibarr Synchronisation via WebService
 *  \brief      Display Module Tests Results
*/


//====================================================================//
// Create Setup Form
echo    '<form name="MainSetup" action="'.  filter_input(INPUT_SERVER, "php_self").'" method="POST">';
echo    '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo    '<input type="hidden" name="action" value="UpdateMain">';

//====================================================================//
// Open Main Configuration Tab
dol_fiche_head(array(), Null, $langs->trans("SPL_Main_Config") , 0, null);


echo '<table class="noborder" width="100%"><tbody>';
//====================================================================//
// Node Id Parameter
echo '  <tr class="pair">';
echo '      <td>' . $form->textwithpicto($langs->trans("SPL_SiteId"), $langs->trans("SPL_SiteId_Tooltip")) . '</td>';
echo '      <td width="30%"><input type="text"  name="WsId" value="' . $conf->global->SPLASH_WS_ID . '" maxlength="32" size="50"></td>';
echo '  </tr>';
//====================================================================//
// Node Ws Key Parameter
echo '  <tr class="impair">';
echo '      <td>' . $form->textwithpicto($langs->trans("SPL_WsKey"), $langs->trans("SPL_WsKey_Tooltip")) . '</td>';
echo '      <td><input type="text"  name="WsKey" value="' . $conf->global->SPLASH_WS_KEY . '" size="50"></td>';
echo '  </tr>';
//====================================================================//
// Ws Host Url Parameter
if (SPLASH_DEBUG) {
    echo '  <tr class="pair">';
    echo '      <td>' . $form->textwithpicto($langs->trans("SPL_WsHost"), $langs->trans("SPL_WsHost_Tooltip")) . '</td>';
    echo '      <td><input type="text"  name="WsHost" value="' . $conf->global->SPLASH_WS_HOST . '" size="50"></td>';
    echo '  </tr>';
}
echo '</tbody></table>';

//====================================================================//
// Close Main Configuration Tab
echo "</div>";

//====================================================================//
// Display Form Submit Btn
echo    '<div class="tabsAction">';
echo    '   <input type="submit" class="butAction" align="right" value="'.$langs->trans("Save").'">';
echo    '</div>';

//====================================================================//
// Close Main Configuration Form
echo    "</form>";

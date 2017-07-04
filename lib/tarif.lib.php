<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/mymodule.lib.php
 *	\ingroup	mymodule
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function tarifAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("tarif@tarif");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/tarif/admin/index.php", 1);
    $head[$h][1] = $langs->trans("Configuration");
    $head[$h][2] = 'settings';
    $h++;
   /* $head[$h][0] = dol_buildpath("/tarif/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;*/

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'tarif');

    return $head;
}

function _getTypePrice($idPriceCondi, $type_object='TTarif'){
	global $langs;
	
	$TPDOdb = new TPDOdb;
	
	$TTarif = new $type_object;
	
	return $langs->trans($TTarif->TType_price[$idPriceCondi]);
}

function _getNomURLSoc($id_soc) {
	
	global $db;
	
	$s = new Societe($db);
	$s->fetch($id_soc);
	
	if($s->id > 0) {
		return $s->getNomUrl(1);
	}
	
}

function _getMotif($motif) {
	if(strlen($motif) > 20) return '<span title="...'.substr($motif, 20).'">'.substr($motif, 0, 20).'...</span>';
	return $motif;
}


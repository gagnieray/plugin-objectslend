<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Print as PDF all known operations for a pilot.
 * An admin can see operations for an other pilote.
 *
 * PHP version 5
 *
 * Copyright © 2013-2016 Mélissa Djebel
 * Copyright © 2017 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * ObjectsLend is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ObjectsLend is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugins
 * @package   ObjectsLend
 *
 * @author    Mélissa Djebel <melissa.djebel@gmx.net>
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2013-2016 Mélissa Djebel
 * Copyright © 2017 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   0.7
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7
 */

use Galette\IO\Pdf;
use GaletteObjectsLend\LendObject;
use GaletteObjectsLend\LendCategory;
use GaletteObjectsLend\Preferences;
use GaletteObjectsLend\LendPDF;

define('GALETTE_BASE_PATH', '../../');
require_once GALETTE_BASE_PATH . 'includes/galette.inc.php';
if (!$login->isLogged()) {
    header('location: ' . GALETTE_BASE_PATH . 'index.php');
    die();
}

// Import des classes de notre plugin
require_once '_config.inc.php';

$lendsprefs = new Preferences($zdb);

/**
 * Cut a string?
 *
 * @param string  $str    Original string
 * @param integer $length Max length
 *
 * @return string
 */
function cut($str, $length)
{
    $l = intval($length / 1.8);
    if (strlen($str) > $l) {
        return substr($str, 0, $l);
    }
    return $str;
}

$pdf = new LendPDF($preferences);

// Set document information
$pdf->SetTitle(_T("Objects list"));
$pdf->SetSubject('');
$pdf->SetKeywords('');

$pdf->AddPage('L');

$category_id = array_key_exists(LEND_PREFIX . 'category_id', $session) ? $session[LEND_PREFIX . 'category_id'] : -1;
$tri = filter_has_var(INPUT_GET, 'tri') ? filter_input(INPUT_GET, 'tri') : 'name';
$search = array_key_exists(LEND_PREFIX . 'search', $session) ? $session[LEND_PREFIX . 'search'] : '';
$ids = filter_has_var(INPUT_GET, 'ids') ? explode(',', filter_input(INPUT_GET, 'ids')) : array();

if ($lendsprefs->{Preferences::PARAM_VIEW_CATEGORY} && $category_id == 0) {
    $tri = 'category_name';
}

/**
 * Récupération des objets
 */
if (count($ids) == 0) {
    $objects = LendObject::getPaginatedObjects($tri, 'asc', $search, intval($category_id), $login->isStaff() || $login->isAdmin(), 0, 5000);
    $nb_objects = LendObject::getNbObjects($category_id, $search, $login->isStaff() || $login->isAdmin());
} else {
    $objects = LendObject::getMoreObjectsByIds($ids);
    $nb_objects = count($objects);
}

$pdf->SetFont(Pdf::FONT, 'B', 14);
$pdf->Cell(275, 0, _T("Objects list"), '', 0, 'C');
$pdf->Ln();

$pdf->SetFont(Pdf::FONT, '', 9);
$pdf->Cell(0, 0, str_replace('%date', date(_T("Y-m-d")), _T("Printed on %date")));
$pdf->Ln();
if ($category_id > 0) {
    $categ = new LendCategory(intval($category_id));
    $pdf->Cell(0, 0, str_replace('%category', $categ->name, _T("Selected category: %category")));
    $pdf->Ln();
}
if ($nb_objects > 1) {
    $pdf->Cell(0, 0, $nb_objects . ' ' . _T("objects"));
} else {
    $pdf->Cell(0, 0, $nb_objects . ' ' . _T("object"));
}
$pdf->Ln();
$pdf->Ln();

$pdf->SetFont(Pdf::FONT, 'B', 9);

$w_checkbox = 5;
$w_name = 33;
$w_description = 45;
$w_serial = 21;
$w_price = 17;
$w_dimension = 28;
$w_weight = 16;
$w_status = 26;
$w_date = 22;
$w_adherent = 26;
$w_location = 21;

$pdf->Cell($w_checkbox, 0, '', 'B');
$pdf->Cell($w_name, 0, cut(_T("Name"), $w_name), 'B');
$pdf->Cell($w_description, 0, cut(_T("Description"), $w_description), 'B');
$pdf->Cell($w_serial, 0, cut(_T("Serial"), $w_serial), 'B');
$pdf->Cell($w_price, 0, cut(_T("Price"), $w_price), 'B');
$pdf->Cell($w_price, 0, cut(_T("Borrow price"), $w_price), 'B');
$pdf->Cell($w_dimension, 0, cut(_T("Dimensions"), $w_dimension), 'B');
$pdf->Cell($w_weight, 0, cut(_T("Weight"), $w_weight), 'B');
$pdf->Cell($w_status, 0, cut(_T("Status"), $w_status), 'B');
$pdf->Cell($w_date, 0, cut(_T("Since"), $w_date), 'B');
$pdf->Cell($w_adherent, 0, cut(_T("Member"), $w_adherent), 'B');
$pdf->Cell($w_location, 0, cut(_T("Return"), $w_location), 'B');
//$pdf->Cell($w_location, 0, _T("Location"), 'B');
$pdf->Ln();

$pdf->SetFont(Pdf::FONT, '', 9);

$old_category_name = '';
$sum_price = 0;
$grant_total = 0;
$row = 0;

foreach ($objects as $obj) {
    if ($lendsprefs->{Preferences::PARAM_VIEW_CATEGORY} && $old_category_name !== $obj->category_name) {
        $pdf->SetFont(Pdf::FONT, 'B', 9);

        if (($login->isAdmin() || $login->isStaff()) && $sum_price > 0) {
            $pdf->Cell($w_checkbox + $w_name + $w_description + $w_serial + $w_price, 0, number_format($sum_price, 2, ',', ''), '', 0, 'R');
            $sum_price = 0;
            $pdf->Ln();
        }

        $pdf->Cell($w_checkbox, 0, '', 'B');
        $pdf->Cell(0, 0, $obj->category_name, 'B');
        $pdf->Ln();
        $pdf->SetFont(Pdf::FONT, '', 9);
    }

    if ($row++ % 2 == 0) {
        $pdf->SetFillColor(255, 189, 64);
    } else {
        $pdf->SetFillColor(255, 214, 135);
    }

    $pdf->Cell($w_checkbox, 0, '□', 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_name, 0, cut($obj->name, $w_name), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_description, 0, cut($obj->description, $w_description), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_serial, 0, cut($obj->serial_number, $w_serial), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_price, 0, cut($obj->price, $w_price), 'B', 0, 'R', $obj->is_home_location);
    $pdf->Cell($w_price, 0, cut($obj->rent_price, $w_price).$obj->getCurrency(), 'B', 0, 'R', $obj->is_home_location);
    $pdf->Cell($w_dimension, 0, cut($obj->dimension, $w_dimension), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_weight, 0, cut($obj->weight, $w_weight), 'B', 0, 'R', $obj->is_home_location);
    $pdf->Cell($w_status, 0, cut($obj->status_text, $w_status), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_date, 0, cut($obj->date_begin_short, $w_date), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_adherent, 0, cut($obj->nom_adh . ' ' . $obj->prenom_adh, $w_adherent), 'B', 0, 'L', $obj->is_home_location);
    $pdf->Cell($w_location, 0, $obj->date_forecast_short, 'B', 0, 'L', $obj->is_home_location);
    //$pdf->Cell($w_location, 0, $obj->is_home_location ? 'X' : '', 'B', 0, 'C');
    $pdf->Ln();

    $sum_price += floatval(str_replace(array(',', ' '), array('.', ''), $obj->price));
    $grant_total += floatval(str_replace(array(',', ' '), array('.', ''), $obj->price));

    $old_category_name = $obj->category_name;
}

if ($login->isAdmin() || $login->isStaff()) {
    $pdf->SetFont(Pdf::FONT, 'B', 9);
    $pdf->Cell($w_checkbox + $w_name + $w_description + $w_serial + $w_price, 0, number_format($sum_price, 2, ',', ''), '', 0, 'R');
    $pdf->Ln();
    $pdf->Ln();

    $pdf->Cell($w_checkbox + $w_name + $w_description + $w_serial + $w_price, 0, _T("Sum:") . ' ' . number_format($grant_total, 2, ',', ''), '', 0, 'R');
    $pdf->Ln();
}

$pdf->Ln();

$pdf->SetFont(Pdf::FONT, '', 9);
$pdf->Cell($w_price, 0, '', 'LTRB');
$pdf->Cell(0, 0, _T("Borrowed"));
$pdf->Ln();
$pdf->Cell($w_price, 0, '', '', 0, 'L', true);
$pdf->Cell(0, 0, _T("Available"));
$pdf->Ln();

$pdf->Output('objects_list_' . date('Ymd-Hi') . '.pdf', 'D');

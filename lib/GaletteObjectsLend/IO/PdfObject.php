<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Object card PDF
 *
 * PHP version 5
 *
 * Copyright © 2018 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  IO
 * @package   GaletteObjectsLend
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

namespace GaletteObjectsLend\IO;

use Galette\IO\Pdf;
use Galette\Core\Db;
use Galette\Core\Preferences;
use GaletteObjectsLend\Entity\Preferences as LPreferences;
use Analog\Analog;
use GaletteObjectsLend\Entity\LendObject;
use GaletteObjectsLend\Entity\LendCategory;
use Galette\IO\PdfAdhesionForm;
use Galette\Core\Logo;
use Galette\Entity\Adherent;

/**
 * Object card PDF
 *
 * @category  IO
 * @name      PdfObject
 * @package   GaletteObjectsLend
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2018 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class PdfObject extends Pdf
{
    private $zdb;
    private $prefs;
    private $lprefs;

    /**
     * Main constructor
     *
     * @param Db           $zdb    Database instance
     * @param Preferences  $prefs  Preferences instance
     * @param LPreferences $lprefs Plugin Preferences instance
     */
    public function __construct(Db $zdb, Preferences $prefs, LPreferences $lprefs)
    {
        parent::__construct($prefs);
        $this->zdb = $zdb;
        $this->lprefs = $lprefs;
        //TRANS: this is a filename
        $this->filename = _T('object_card', 'objectslend') . '.pdf';
        $this->init();
    }

    /**
     * Initialize PDF
     *
     * @return void
     */
    private function init()
    {
        // Set document information
        $this->SetTitle(_T('Object card', 'objectslend'));
        $this->SetSubject(_T('Generated by Galette'));
        $this->SetKeywords(_T('Labels', 'objectslend'));

        $this->setMargins(10, 10);

        // Show full page
        $this->SetDisplayMode('fullpage');

        // Disable Auto Page breaks
        $this->SetAutoPageBreak(false, 20);
    }

    /**
     * Draw listed object cards
     *
     * @param LendObject[] $objects Object list
     *
     * @return void
     */
    public function drawCards(array $objects)
    {
        $this->Open();
        foreach ($objects as $object) {
            $this->AddPage();
            $this->drawCard($object);
        }
    }

    /**
     * Draw object card
     *
     * @param LendObject $object Object
     *
     * @return void
     */
    public function drawCard(LendObject $object)
    {
        $this->SetFont(Pdf::FONT, 'B');
        $wpic = 0;
        $hpic = 0;
        if ($object->picture->hasPicture()) {
            $pic = $object->picture;
            // Set picture size to max width 30 mm or max height 30 mm
            $tw = $pic->getOptimalThumbWidth($this->lprefs);
            $th = $pic->getOptimalThumbHeight($this->lprefs);
            $ratio = $tw / $th;
            if ($ratio < 1) {
                if ($tw > 16) {
                    $hpic = 30;
                } else {
                    $hpic = $th;
                }
                $wpic = round($hpic * $ratio);
            } else {
                if ($tw > 16) {
                    $wpic = 30;
                } else {
                    $wlogo = $tw;
                }
                $hpic = round($wpic / $ratio);
            }

            $this->Image($object->picture->getThumbPath(), 10, 10, $wpic, $hpic);
        }

        $this->addCell(_T("Name", "objectslend"), $object->name, $wpic);
        if ($this->lprefs->{LPreferences::PARAM_VIEW_DESCRIPTION}) {
            $this->addCell(_T("Description", "objectslend"), $object->description, $wpic);
        }
        if ($this->lprefs->{LPreferences::PARAM_VIEW_CATEGORY}) {
            $this->addCell(_T("Category", "objectslend"), $object->cat_name, $wpic);
        }
        if ($this->lprefs->{LPreferences::PARAM_VIEW_SERIAL}) {
            $this->addCell(_T("Serial number", "objectslend"), $object->serial_number, $wpic);
        }
        if ($this->lprefs->{LPreferences::PARAM_VIEW_PRICE}) {
            $this->addCell(_T("Price", "objectslend"), $object->price, $wpic);
        }
        if ($this->lprefs->{LPreferences::PARAM_VIEW_LEND_PRICE}) {
            $this->addCell(
                _T("Borrow price"),
                $object->rent_price . ' ' . $object->getCurrency(),
                $wpic
            );
            $this->addCell(
                _T("Price per rental day", "objectslend"),
                $object->price_per_day . ' ' . $object->getCurrency(),
                $wpic
            );
        }
        if ($this->lprefs->{LPreferences::PARAM_VIEW_DIMENSION}) {
            $this->addCell(_T("Dimensions", "objectslend"), $object->dimension . ' ' . _T('Cm', 'objectslend'), $wpic);
        }
        if ($this->lprefs->{LPreferences::PARAM_VIEW_WEIGHT}) {
            $this->addCell(_T("Weight", "objectslend"), $object->weight . ' ' . _T('Kg', 'objectslend'), $wpic);
        }
        $this->addCell(_T("Active", "objectslend"), $object->is_active ? 'X' : '', $wpic);
        $this->addCell(_T("Location", "objectslend"), $object->status_text, $wpic);
        $this->addCell(_T("Since", "objectslend"), $object->date_begin, $wpic);
        $this->addCell(_T("Member", "objectslend"), $object->member->sname, $wpic);
        if ($this->lprefs->{LPreferences::PARAM_VIEW_DATE_FORECAST}) {
            $this->addCell(_T("Return", "objectslend"), $object->date_forecast, $wpic);
        }

        if ($this->GetY() < $hpic) {
            $this->SetY($hpic);
        }
        $this->Ln();

        $rents = $object->rents;

        $col_begin = 33;
        $col_end = 33;
        $col_status = 30;
        $col_stock = 25;
        $col_adh = 30;
        $col_comments = 40;

        $this->SetFont(Pdf::FONT, 'B', 10);
        $this->Cell(0, 0, _T("History of object loans", "objectslend"), 0, 1, 'C');
        $this->Ln();
        $this->Cell($col_begin, 0, $this->stretchHead(_T("Begin", "objectslend"), $col_begin), 'B');
        $this->Cell($col_end, 0, $this->stretchHead(_T("End", "objectslend"), $col_end), 'B');
        $this->Cell($col_status, 0, $this->stretchHead(_T("Status", "objectslend"), $col_status), 'B');
        $this->Cell($col_stock, 0, $this->stretchHead(_T("In stock", "objectslend"), $col_stock), 'B');
        $this->Cell($col_adh, 0, $this->stretchHead(_T("Member", "objectslend"), $col_adh), 'B');
        $this->Cell($col_comments, 0, $this->stretchHead(_T("Comments", "objectslend"), $col_comments), 'B');
        $this->Ln();
        $this->SetFont(Pdf::FONT, '', 9);

        foreach ($rents as $rt) {
            $this->Cell($col_begin, 0, $this->cut($rt->date_begin, $col_begin), 'B');
            $this->Cell($col_end, 0, $this->cut($rt->date_end, $col_end), 'B');
            $this->Cell($col_status, 0, $this->cut($rt->status_text, $col_status), 'B');
            $this->Cell($col_stock, 0, $rt->in_stock ? '    X' : '', 'B');
            $this->Cell($col_adh, 0, $this->cut($rt->nom_adh . ' ' . $rt->prenom_adh, $col_adh), 'B');
            $this->Cell($col_comments, 0, $this->cut($rt->comments, $col_comments), 'B');
            $this->Ln();
        }
    }


    /**
     * Add a line in the array
     *
     * @param string  $title Line title
     * @param string  $value Line value
     * @param integer $width Cell width
     *
     * @return void
     */
    private function addCell($title, $value, $width)
    {
        if ($width > 0) {
            $this->Cell($width, 0, '');
        }
        $this->SetFont(Pdf::FONT, 'B', 9);
        $padding = 50;
        $this->Cell($padding, 0, $this->cut($title, $padding));

        $this->SetFont(Pdf::FONT, '', 9);
        $wrapped = explode("\n", wordwrap($value, 150 - $padding - $width, "\n"));
        $i = 0;
        foreach ($wrapped as $w) {
            if ($i++ > 0) {
                $this->Cell($width + $padding, 0, '');
            }
            $this->MultiCell(0, 0, $w, 0, 'L');
        }
    }
}

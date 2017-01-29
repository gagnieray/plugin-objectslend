<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Public Class Preferences
 * Store the parameters of the Plugin
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

namespace GaletteObjectsLend;

use Analog\Analog;
use \Zend\Db\Sql\Predicate;

class Preferences
{

    const TABLE = 'parameters';
    const PK = 'code';

    private $zdb;
    private $prefs;

    /**
     * Paramètre : voir la liste des catégories en en-têtes de la liste des objets
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_CATEGORY = 'VIEW_CATEGORY';

    /**
     * Paramètre : voir la colonne "no de série"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_SERIAL = 'VIEW_SERIAL';

    /**
     * Paramètre : voir la colonne "photo/minitature"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_THUMBNAIL = 'VIEW_THUMBNAIL';

    /**
     * Paramètre : voir la colonne "nom"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_NAME = 'VIEW_NAME';

    /**
     * Paramètre : voir la colonne "description"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_DESCRIPTION = 'VIEW_DESCRIPTION';

    /**
     * Paramètre :  voir la colonne "prix"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_PRICE = 'VIEW_PRICE';

    /**
     * Paramètre : voir la colonne "dimensions"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_DIMENSION = 'VIEW_DIMENSION';

    /**
     * Paramètre : voir la colonne "poids"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_WEIGHT = 'VIEW_WEIGHT';

    /**
     * Paramètre : voir la colonne "prix de location"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_LEND_PRICE = 'VIEW_LEND_PRICE';

    /**
     * Parametre : voir la colonne "retour prevu le"
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_DATE_FORECAST = 'VIEW_DATE_FORECAST';

    /**
     * Paramètre : voir une miniature pour l'image des catégories
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_CATEGORY_THUMB = 'VIEW_CATEGORY_THUMB';

    /**
     * Parametre : voir la somme des prix sur la liste des objects
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_LIST_PRICE_SUM = 'VIEW_LIST_PRICE_SUM';

    /**
     * Paramètre : voir une miniature pour l'image des objets
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_VIEW_OBJECT_THUMB = 'VIEW_OBJECT_THUMB';

    /**
     * Paramètre : largeur max d'une miniature (appliquée aux objets/catégories)
     * Valeur : largeur en pixels
     */
    const PARAM_THUMB_MAX_WIDTH = 'THUMB_MAX_WIDTH';

    /**
     * Paramètre : hauteur max d'une miniature (appliquée aux objets/catégories)
     * Valeur : largeur en pixels
     */
    const PARAM_THUMB_MAX_HEIGHT = 'THUMB_MAX_HEIGHT';

    /**
     * Paramètre : Générer automatiquement une contribution lors de la location d'un objet
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_AUTO_GENERATE_CONTRIBUTION = 'AUTO_GENERATE_CONTRIBUTION';

    /**
     * Paramètre : Id du type de contribution si auto-génération d'une contribution
     * Valeur : ID du type de contribution
     */
    const PARAM_GENERATED_CONTRIBUTION_TYPE_ID = 'GENERATED_CONTRIBUTION_TYPE_ID';

    /**
     * Paramètre : Texte pour la contribution
     * Valeur : texte d'info à mettre avec des placeholders à remplacer
     */
    const PARAM_GENERATED_CONTRIB_INFO_TEXT = 'GENERATED_CONTRIB_INFO_TEXT';

    /**
     * Paramètre : Autoriser les membres non staff ni admin à pouvoir louer un objet = accès à la page take_object.php
     * Valeur : 0 = false / 1 = true
     */
    const PARAM_ENABLE_MEMBER_RENT_OBJECT = 'ENABLE_MEMBER_RENT_OBJECT';

    /**
     * Parametre : Liste des nombres d'objets par page, separes par des ';'
     * Valeur : w;x;y;z
     */
    const PARAM_OBJECTS_PER_PAGE_NUMBER_LIST = 'OBJECTS_PER_PAGE_NUMBER_LIST';

    /**
     * Parametre : Valeur par defaut pour le nombre d'objets par page - c'est mieux si c'est une valeur du dessus
     * Valeur : n
     */
    const PARAM_OBJECTS_PER_PAGE_DEFAULT = 'OBJECTS_PER_PAGE_DEFAULT';

    private $_fields = array(
        '_parameter_id' => 'int',
        '_code' => 'string',
        '_is_date' => 'bool',
        '_value_date' => 'date',
        '_is_text' => 'bool',
        '_value_text' => 'string',
        '_is_numeric' => 'bool',
        '_nb_digits' => 'int',
        '_value_numeric' => 'double',
        '_date_creation' => 'datetime',
        '_date_modification' => 'datetime'
    );
    private $_parameter_id;
    private $_code;
    private $_is_date;
    private $_value_date;
    private $_is_text;
    private $_value_text;
    private $_is_numeric;
    private $_nb_digits;
    private $_value_numeric;
    private $_date_creation;
    private $_date_modification;
    private static $_parameters_values = array();

    /**
     * Indique si le paramètre est un paramètre de couleur
     *
     * @return bool
     */
    public function isColor()
    {
        return substr($this->_valeur_texte, 0, 1) == '#';
    }

    /**
     * Default constructor
     *
     * @param Db      $zdb  Db instance
     * @param boolean $load Automatically load preferences on load
     *
     * @return void
     */
    public function __construct($zdb, $load = true)
    {
        $this->zdb = $zdb;
        if ($load) {
            $this->load();
        }
    }

    /**
     * Get preferences
     *
     * @return array
     */
    public function getpreferences()
    {
        if (count($this->prefs) == 0) {
            $this->load();
        }
        return $this->prefs;
    }

    /**
     * Global getter method
     *
     * @param string $name name of the property we want to retrive
     *
     * @return false|object the called property
     */
    public function __get($name)
    {
        $forbidden = array();

        if (!in_array($name, $forbidden) && isset($this->prefs[$name])) {
            return $this->prefs[$name];
        } else {
            Analog::log(
                'Preference `' . $name . '` is not set or is forbidden',
                Analog::INFO
            );
            return false;
        }
    }

    /**
     * Store preferences
     *
     * @param array $data   Posted data
     * @param array $errors Errors
     *
     * @return boolean
     */
    public function store($data, &$errors)
    {
        foreach ($data as $key => $value) {
            $this->prefs[$key] = $value;
        }

        try {
            $this->zdb->connection->beginTransaction();
            $update = $this->zdb->update(LEND_PREFIX . self::TABLE);
            $update->set(
                array(
                    'value_text'  => $data['GENERATED_CONTRIB_INFO_TEXT']
                )
            )->where->equalTo(self::PK, 'GENERATED_CONTRIB_INFO_TEXT');
            $this->zdb->execute($update);

            $update = $this->zdb->update(LEND_PREFIX . self::TABLE);
            $update->set(
                array(
                    'value_numeric'     => ':value_numeric',
                    'date_modification' => ':date_modification'
                )
            )->where->equalTo(self::PK, ':' . self::PK);
            $stmt = $this->zdb->sql->prepareStatementForSqlObject($update);

            foreach ($this->prefs as $key => $value) {
                $stmt->execute(
                    [
                        'value_numeric'     => $value,
                        'date_modification' => date('Y-m-d H:i:s'),
                        'where1'            => $key
                    ]
                );
            }

            $this->zdb->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->zdb->connection->rollBack();
            throw $e;
            Analog::log(
                'Something went wrong :\'( | ' . $e->getMessage() . "\n" .
                    $e->getTraceAsString(),
                Analog::ERROR
            );
            $errors[] = _T("Unable to store preferences :(");
            return false;
        }
    }

    /**
     * Load current preferences from database.
     *
     * @return boolean
     */
    public function load()
    {
        $this->prefs = array();

        try {
            $result = $this->zdb->selectAll(LEND_PREFIX . self::TABLE);
            foreach ($result as $pref) {
                $pk_field = self::PK;
                $value_field = 'value_numeric';
                if ($pref->is_text == '1') {
                    $value_field = 'value_text';
                }
                $this->prefs[$pref->$pk_field] = $pref->$value_field;
            }
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'ObjectsLend Preferences cannot be loaded. Plugin should not work without ' .
                'it. Exiting. '  . $e->getMessage(),
                Analog::URGENT
            );
            return false;
        }
    }
    /**
     * Ecrit la pagination d'une liste selon le nombre d'objets total dans la liste, le tri et
     * la direction définis. Renvoi la pagination au format HTML prêt à être inséré dans la page
     * finale.
     *
     * @param int $no_page No actuel de la page vue pour mise en surbrillance
     * @param string $tri Valeur de la variable de tri choisi
     * @param string $direction Valeur de la direction du tri choisi
     * @param int $nb_objet Nombre d'objets au total dans la liste
     * @param int $nb_lignes Nombre d'enregistrements par page
     * @param string $complement Complèment à mettre dans l'URL (faire précéder d'un "&")
     *
     * @return string La pagination faite
     */
    public static function paginate($no_page, $nb_objet, $nb_lignes, $complement)
    {
        if ($nb_objet < $nb_lignes) {
            return '';
        }
        if ($nb_lignes <= 0) {
            return '';
        }

        $pagination = '<table align="center">' .
                '<tr>';
        if ($no_page > 1) {
            $pagination.= '<td>' .
                    '<a href="?page=1' . $complement . '" class="tooltip_lend" title="' . _T("PAGINATE.FIRST PAGE") . '">' .
                    '<img src="picts/first.png"/>' .
                    '</a>' .
                    '</td>' .
                    '<td>' .
                    '<a href="?page=' . ($no_page - 1) . $complement . '" class="tooltip_lend" title="' . _T("PAGINATE.PREVIOUS PAGE") . '">' .
                    '<img src="picts/previous.png"/>' .
                    '</a>' .
                    '</td>';
        }
        $pagination.= '<td>';
        for ($i = 1; $i <= ceil($nb_objet / $nb_lignes); $i++) {
            if ($no_page == $i) {
                $pagination .= '<span style="font-weight: bold; color: #EEA423;">[' . $i . ']</span>';
            } else {
                $pagination .= '<a href="?page=' . $i . $complement . '" class="tooltip_lend" title="' . _T("PAGINATE.GO TO PAGE") . $i . '">' . $i . '</a>';
            }
            if ($i < ceil($nb_objet / $nb_lignes)) {
                $pagination .= ' &#xB7; ';
            }
        }
        $pagination .= '</td>';
        if ($no_page < ceil($nb_objet / $nb_lignes)) {
            $pagination.= '<td>' .
                    '<a href="?page=' . ($no_page + 1) . $complement . '" class="tooltip_lend" title="' . _T("PAGINATE.NEXT PAGE") . '">' .
                    '<img src="picts/next.png"/>' .
                    '</a>' .
                    '</td>' .
                    '<td>' .
                    '<a href="?page=' . ceil($nb_objet / $nb_lignes) . $complement . '" class="tooltip_lend" title="' . _T("PAGINATE.LAST PAGE") . '">' .
                    '<img src="picts/last.png"/>' .
                    '</a>' .
                    '</td>';
        }
        $pagination.= '</tr>' .
                '</table>';

        return $pagination;
    }

    /**
     * Get thumbnail maximum with
     *
     * @return integer
     */
    public function getThumbWidth()
    {
        return $this->prefs['THUMB_MAX_WIDTH'];
    }

    /**
     * Get thumbnail maximum height
     *
     * @return integer
     */
    public function getThumbHeight()
    {
        return $this->prefs['THUMB_MAX_HEIGHT'];
    }
}

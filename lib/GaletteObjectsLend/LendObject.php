<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Public Class LendObject
 * Store informations about an object to lend
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
use Galette\Entity\Adherent;
use GaletteObjectsLend\Filters\ObjectsList;
use GaletteObjectsLend\Repository\Objects;

class LendObject
{
    const TABLE = 'objects';
    const PK = 'object_id';

    private $fields = array(
        'object_id' => 'integer',
        'name' => 'varchar(100)',
        'description' => 'varchar(500)',
        'serial_number' => 'varchar(30)',
        'price' => 'decimal',
        'rent_price' => 'decimal',
        'price_per_day' => 'boolean',
        'dimension' => 'varchar(100)',
        'weight' => 'decimal',
        'is_active' => 'boolean',
        'category_id' => 'int',
        'nb_available' => 'int',
    );
    private $object_id;
    private $name = '';
    private $description = '';
    private $serial_number = '';
    private $price = 0.0;
    private $rent_price = 0.0;
    private $price_per_day = false;
    private $dimension = '';
    private $weight = 0.0;
    private $is_active = true;
    private $category_id;
    private $nb_available = 1;
    // Nom de la catégorie
    private $category_name = '';
    // Requête sur le dernier statut de l'objet
    private $date_begin;
    private $date_forecast;
    private $date_end;
    private $status_text;
    private $is_home_location;
    // Requête sur l'adhérent associé au statut
    private $nom_adh = '';
    private $prenom_adh = '';
    private $email_adh = '';
    private $id_adh;

    private $currency = '€';
    private $picture;
    private $cat_active = true;

    private $deps = [
        'picture'   => true,
        'rents'     => false,
        'last_rent' => false
    ];

    /**
     * @var LendRent[]
     * Rents list for the object
     */
    private $rents;

    /**
     * Default constructor
     *
     * @param int|object $args   Maybe null, an RS object or an id from database
     * @param boolean    $cloned Ask to clone specified object
     * @param array      $deps   Dependencies configuration, see LendOb::$deps
     */
    public function __construct($args = null, $cloned = false, $deps = null)
    {
        global $zdb, $plugins;

        if ($deps !== null && is_array($deps)) {
            $this->deps = array_merge(
                $this->deps,
                $deps
            );
        } elseif ($deps !== null) {
            Analog::log(
                '$deps should be an array, ' . gettype($deps) . ' given!',
                Analog::WARNING
            );
        }

        if ($this->deps['picture'] === true) {
            $this->picture = new ObjectPicture($plugins);
        }

        if (is_int($args)) {
            try {
                $select = $zdb->select(LEND_PREFIX . self::TABLE)
                        ->where(array(self::PK => $args));
                $results = $zdb->execute($select);
                if ($results->count() == 1) {
                    $this->loadFromRS($results->current());
                }
            } catch (\Exception $e) {
                Analog::log(
                    'Something went wrong :\'( | ' . $e->getMessage() . "\n" .
                        $e->getTraceAsString(),
                    Analog::ERROR
                );
            }
        } elseif (is_object($args)) {
            $this->loadFromRS($args);
        }

        if ($args !== null && $cloned) {
            unset($this->object_id);
            if ($this->deps['picture'] === true) {
                $this->picture = new ObjectPicture($plugins);
            }
        }
    }

    /**
     * Populate object from a resultset row
     *
     * @param ResultSet $r the resultset row
     *
     * @return void
     */
    private function loadFromRS($r)
    {
        global $plugins;

        $this->object_id = $r->object_id;
        $this->name = self::protectQuote($r->name);
        $this->description = self::protectQuote($r->description);
        $this->serial_number = self::protectQuote($r->serial_number);
        $this->price = is_numeric($r->price) ? floatval($r->price) : 0.0;
        $this->rent_price = is_numeric($r->rent_price) ? floatval($r->rent_price) : 0.0;
        $this->price_per_day = $r->price_per_day == '1';
        $this->dimension = self::protectQuote($r->dimension);
        $this->weight = is_numeric($r->weight) ? floatval($r->weight) : 0.0;
        $this->is_active = $r->is_active == '1' ? true : false;
        if (property_exists($r, 'cat_active') && ($r->cat_active == 1 || $r->cat_active === null)) {
            $this->cat_active = true;
        } else {
            $this->cat_active = false;
        }
        $this->category_id = $r->category_id;
        $this->nb_available = $r->nb_available;
        $this->category_name = isset($r->category_name) ? $r->category_name : '';

        if ($this->object_id) {
            $only_last = false;
            if ($this->deps['rents'] === false && $this->deps['last_rent'] === true) {
                $only_last = true;
            }
            $this->rents = LendRent::getRentsForObjectId($this->object_id, $only_last);
        }

        if ($this->deps['picture'] === true) {
            $this->picture = new ObjectPicture($plugins, (int)$this->object_id);
        }
    }

    /**
     * Protège les guillemets et apostrophes en les transformant en caractères qui ne gênent pas en HTML
     *
     * @param string $str Chaîne à transformer
     *
     * @return string Chaîne protégée
     */
    private static function protectQuote($str)
    {
        return str_replace(array('\'', '"'), array(html_entity_decode('&rsquo;'), html_entity_decode('&rdquo;')), $str);
    }

    /**
     * Enregistre l'élément en cours que ce soit en insert ou update
     *
     * @return bool False si l'enregistrement a échoué, true si aucune erreur
     */
    public function store()
    {
        global $zdb;

        try {
            $values = array();

            foreach ($this->fields as $k => $v) {
                if (($k === 'is_active' || $k === 'price_per_day')
                    && $this->$k === false
                ) {
                    //Handle booleans for postgres ; bugs #18899 and #19354
                    $values[$k] = $zdb->isPostgres() ? 'false' : 0;
                } else {
                    $values[$k] = $this->$k;
                }
            }

            if (!isset($this->object_id) || $this->object_id == '') {
                unset($values[self::PK]);
                $insert = $zdb->insert(LEND_PREFIX . self::TABLE)
                        ->values($values);
                $result = $zdb->execute($insert);
                if ($result->count() > 0) {
                    $this->object_id = $zdb->driver->getLastGeneratedValue();
                } else {
                    throw new \Exception(_T("Object has not been added :("));
                }
            } else {
                $update = $zdb->update(LEND_PREFIX . self::TABLE)
                        ->set($values)
                        ->where(array(self::PK => $this->object_id));
                $zdb->execute($update);
            }
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'Something went wrong :\'( | ' . $e->getMessage() . "\n" .
                    $e->getTraceAsString(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Exécute une requête SQL pour récupérer le statut de location d'un objet, ainsi que l'utilisateur
     * qui loue l'objet.
     * Ne retourne rien.
     *
     * @param LendObject $object L'objet dont on cherche le statut. Est automatiquement modifié.
     */
    public static function getStatusForObject($object)
    {
        global $zdb;

        // Statut
        $select_rent = $zdb->select(LEND_PREFIX . LendRent::TABLE)
                ->join(PREFIX_DB . LEND_PREFIX . LendStatus::TABLE, PREFIX_DB . LEND_PREFIX . LendRent::TABLE . '.status_id = ' . PREFIX_DB . LEND_PREFIX . LendStatus::TABLE . '.status_id')
                ->join(PREFIX_DB . Adherent::TABLE, PREFIX_DB . Adherent::TABLE . '.id_adh = ' . PREFIX_DB . LEND_PREFIX . LendRent::TABLE . '.adherent_id', '*', 'left')
                ->where(array('object_id' => $object->object_id))
                ->limit(1)
                ->offset(0)
                ->order('date_begin desc');

        $results = $zdb->execute($select_rent);
        if ($results->count() == 1) {
            $rent = $results->current();
            $object->date_begin = $rent->date_begin;
            $object->date_forecast = $rent->date_forecast;
            $object->date_end = $rent->date_end;
            $object->status_text = $rent->status_text;
            $object->is_home_location = $rent->is_home_location == '1' ? true : false;
            $object->nom_adh = $rent->nom_adh;
            $object->prenom_adh = $rent->prenom_adh;
            $object->email_adh = $rent->email_adh;
            $object->id_adh = $rent->id_adh;
        } else {
            $object->is_home_location = true;
        }
    }

    /**
     * Renvoit tous les objects correspondant aux IDs donnés.
     *
     * @param array $ids Tableau des IDs pour lequels on souhaite avoir les objects
     *
     * @return LendObject[] Tableau des objets correspondant aux IDs
     */
    public static function getMoreObjectsByIds($ids)
    {
        global $zdb;

        $myids = array();
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $myids[] = $id;
            }
        }

        try {
            $select = $zdb->select(LEND_PREFIX . self::TABLE)
                    ->where(array(self::PK => $myids));

            $results = array();

            $rows = $zdb->execute($select);
            foreach ($rows as $r) {
                $o = new self($r);

                self::getStatusForObject($o);

                $results[] = $o;
            }

            return $results;
        } catch (\Exception $e) {
            Analog::log(
                'Something went wrong :\'( | ' . $e->getMessage() . "\n" .
                    $e->getTraceAsString(),
                Analog::ERROR
            );
            return false;
        }
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
        switch ($name) {
            case 'date_begin_ihm':
                if ($this->date_begin == '' || $this->date_begin == null) {
                    return '';
                }
                $dtb = new \DateTime($this->date_begin);
                return $dtb->format('j M Y');
            case 'date_begin_short':
                if ($this->date_begin == '' || $this->date_begin == null) {
                    return '';
                }
                $dtb = new \DateTime($this->date_begin);
                return $dtb->format('d/m/Y');
            case 'date_end_ihm':
                if ($this->date_end == '' || $this->date_end == null) {
                    return '';
                }
                $dtb = new \DateTime($this->date_end);
                return $dtb->format('j M Y');
            case 'date_forecast_ihm':
                if ($this->date_forecast == '' || $this->date_forecast == null) {
                    return '';
                }
                $dtb = new \DateTime($this->date_forecast);
                return $dtb->format('j M Y');
            case 'date_forecast_short':
                if ($this->date_forecast == '' || $this->date_forecast == null) {
                    return '';
                }
                $dtb = new \DateTime($this->date_forecast);
                return $dtb->format('d/m/Y');
            case 'price':
            case 'rent_price':
                return number_format($this->$name, 2, ',', ' ');
            case 'value_rent_price':
                return $this->rent_price;
            case 'weight_bulk':
                return $this->weight;
            case 'weight':
                return number_format($this->weight, 3, ',', ' ');
            default:
                return $this->$name;
        }
    }

    /**
     * Global setter method
     *
     * @param string $name  name of the property we want to assign a value to
     * @param object $value a relevant value for the property
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $forbidden = ['currency'];
        if (!in_array($name, $forbidden)) {
            switch ($name) {
                case 'category_id':
                    if ($value == '') {
                        $value = null;
                    }
                    //no break for value to be set in default
                default:
                    $this->$name = $value;
                    break;
            }
        }
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Get current rent
     *
     * @return LendRent
     */
    public function getCurrentRent()
    {
        if (is_array($this->rents) && count($this->rents) > 0) {
            return $this->rents[0];
        }
    }

    /**
     * Is current object active?
     *
     * Check for activity from object and from its parent category if any
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->is_active && $this->cat_active;
    }

    /**
     * Get highlighted string
     *
     * @param ObjectsList $filters Filters
     * @param string      $field   Field name
     *
     * @return string
     */
    private function getHighlighted(ObjectsList $filters, $field)
    {
        //check if search concerns field
        $process = false;
        switch ($field) {
            case 'description':
            case 'name':
                if ($filters->field_filter == Objects::FILTER_NAME) {
                    $process = true;
                    continue;
                }
                break;
            case 'serial_number':
                if ($filters->field_filter == Objects::FILTER_SERIAL) {
                    $process = true;
                    continue;
                }
                break;
            case 'dimension':
                if ($filters->field_filter == Objects::FILTER_DIM) {
                    $process = true;
                    continue;
                }
                break;
            case 'object_id':
                if ($filters->field_filter === Objects::FILTER_ID) {
                    $process = true;
                    continue;
                }
                break;
        }

        if ($process === false) {
            return $this->$field;
        }

        $untokenized = trim($filters->filter_str, '%');
        mb_internal_encoding('UTF-8');
        return preg_replace(
            '/(' . $untokenized . ')/iu',
            '<span class="search">$1</span>',
            $this->$field
        );
    }

    /**
     * Displays name, with search terms highlighted
     *
     * @param ObjectsList $filters Filters
     *
     * @return string
     */
    public function displayName(ObjectsList $filters)
    {
        return $this->getHighlighted($filters, 'name');
    }

    /**
     * Displays description, with search terms highlighted
     *
     * @param ObjectsList $filters Filters
     *
     * @return string
     */
    public function displayDescription(ObjectsList $filters)
    {
        return $this->getHighlighted($filters, 'description');
    }

    /**
     * Displays serial number, with search terms highlighted
     *
     * @param ObjectsList $filters Filters
     *
     * @return string
     */
    public function displaySerial(ObjectsList $filters)
    {
        return $this->getHighlighted($filters, 'serial_number');
    }

    /**
     * Displays dimension, with search terms highlighted
     *
     * @param ObjectsList $filters Filters
     *
     * @return string
     */
    public function displayDimension(ObjectsList $filters)
    {
        return $this->getHighlighted($filters, 'dimension');
    }
}

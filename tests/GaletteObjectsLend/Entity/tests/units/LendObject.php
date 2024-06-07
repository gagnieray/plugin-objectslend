<?php

/**
 * Copyright © 2003-2024 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
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
 */

namespace GaletteObjectsLends\Entity\tests\units;

use Galette\GaletteTestCase;

/**
 * Status tests
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class LendObject extends GaletteTestCase
{
    protected int $seed = 20240522000325;

    protected \Galette\Core\Plugins $plugins;

    private int $active_category_id;
    private int $inactive_category_id;
    private int $active_instock_status;
    private int $active_notinstock_status;
    private int $inactive_instock_status;

    /**
     * Set up tests
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->plugins = $this->container->get('plugins');
        $this->createCategories();
        $this->createStatus();
    }

    /**
     * Cleanup after each test method
     *
     * @return void
     */
    public function tearDown(): void
    {
        $delete = $this->zdb->delete(LEND_PREFIX . \GaletteObjectsLend\Entity\LendRent::TABLE);
        $this->zdb->execute($delete);

        $delete = $this->zdb->delete(LEND_PREFIX . \GaletteObjectsLend\Entity\LendObject::TABLE);
        $this->zdb->execute($delete);

        $delete = $this->zdb->delete(LEND_PREFIX . \GaletteObjectsLend\Entity\LendCategory::TABLE);
        $this->zdb->execute($delete);

        $delete = $this->zdb->delete(LEND_PREFIX . \GaletteObjectsLend\Entity\LendStatus::TABLE);
        $this->zdb->execute($delete);

        parent::tearDown();
    }

    /**
     * Test empty
     *
     * @return void
     */
    public function testEmpty(): void
    {
        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins);
        $this->assertSame('€', $object->getCurrency());
        $this->assertNull($object->getCurrentRent());
        $this->assertTrue($object->isActive());
        $this->assertNull($object->getId());
        $this->assertNull($object->getCategoryId());
        $this->assertSame('', $object->getName());
        $this->assertSame(0.0, $object->getPrice());
        $this->assertSame(0.0, $object->getRentPrice());
        $this->assertFalse($object->isPricePerDay());
        $this->assertSame(0.0, $object->getWeight());
        $this->assertSame('', $object->getStatusText());
        $this->assertTrue($object->inStock());
        $this->assertSame('', $object->getDateBegin());
        $this->assertSame('', $object->getDateForecast());
        $this->assertNull($object->getIdAdh());
        $this->assertNull($object->getRentId());
        $this->assertNull($object->getCategoryId());
        $this->assertSame('', $object->getSerialNumber());
    }

    /**
     * Test add and update
     *
     * @return void
     */
    public function testCrud(): void
    {
        $deps = [
            'category' => true,
            'status' => true,
            'last_rent' => true
        ];
        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, null, $deps);

        $object->name = 'An object';
        $object->category_id = $this->active_category_id;
        $object->is_active = true;
        $object->price = 1500.00;
        $object->rent_price = 10.00;
        $object->price_per_day = true;
        $object->weight = 186.00;

        $this->assertTrue($object->store());
        $oid = $object->getId();
        $this->assertGreaterThan(0, $oid);

        $rent = new \GaletteObjectsLend\Entity\LendRent();
        $bdate = new \DateTime('2024-05-22 19:46:21');
        $rent->date_begin = $bdate->format('Y-m-d H:i:s');
        $edate = clone $bdate;
        $edate->add(new \DateInterval('P1Y'));
        $rent->date_end = $edate->format('Y-m-d H:i:s');
        $rent->status_id = $this->active_instock_status;
        $rent->object_id = $oid;
        $this->assertTrue($rent->store());

        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $oid, $deps);
        $this->assertTrue($object->isActive());
        $this->assertSame(1500.00, $object->getPrice());
        $this->assertSame('1 500,00', $object->price);
        $this->assertSame(10.00, $object->getRentPrice());
        $this->assertSame('10,00', $object->rent_price);
        $this->assertSame(10.00, $object->value_rent_price);
        $this->assertSame(186.00, $object->getWeight());
        $this->assertSame('186,000', $object->weight);

        $this->assertSame('2024-05-22', $object->getDateBegin());
        $this->assertSame('2024-05-22', $object->date_begin);
        $object->name = 'An object (edited)';
        $object->description = 'An object description';
        $object->serial_number = 'SE-aBc-RI@L';
        $object->dimension = '10x50';
        $this->assertTrue($object->store());

        $filter = new \GaletteObjectsLend\Filters\ObjectsList();
        $filter->field_filter = \GaletteObjectsLend\Repository\Objects::FILTER_NAME;
        $filter->filter_str = 'object';
        $this->assertSame('An <span class="search">object</span> (edited)', $object->displayName($filter));
        $this->assertSame('An <span class="search">object</span> description', $object->displayDescription($filter));

        $filter = new \GaletteObjectsLend\Filters\ObjectsList();
        $filter->field_filter = \GaletteObjectsLend\Repository\Objects::FILTER_SERIAL;
        $filter->filter_str = 'abc';
        $this->assertSame('SE-<span class="search">aBc</span>-RI@L', $object->displaySerial($filter));

        $filter = new \GaletteObjectsLend\Filters\ObjectsList();
        $filter->field_filter = \GaletteObjectsLend\Repository\Objects::FILTER_DIM;
        $filter->filter_str = '50';
        $this->assertSame('10x<span class="search">50</span>', $object->displayDimension($filter));

        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $oid, $deps);
        $this->assertSame('An object (edited)', $object->getName());

        //edit category to inactive one
        $object->category_id = $this->inactive_category_id;
        $this->assertNull($object->member);
        $this->assertNull($object->rents);
        $this->assertTrue($object->store());
        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $oid, $deps + ['member' => true, 'rents' => true]);
        $this->assertFalse($object->isActive());
        $this->assertInstanceOf(\Galette\Entity\Adherent::class, $object->member);

        //removing category
        $rm_category = new \GaletteObjectsLend\Entity\LendCategory($this->zdb, $this->plugins);

        $rm_category->name = 'Category to be removed';
        $rm_category->is_active = true;
        $this->assertTrue($rm_category->store());
        $category_id = $rm_category->getId();

        $object->category_id = $category_id;
        $this->assertTrue($object->store());

        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $oid, $deps);
        $this->assertSame($category_id, $object->getCategoryId());

        $this->assertTrue($rm_category->delete());
        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $oid, $deps);
        $this->assertNull($object->getCategoryId());

        //clone
        $this->assertTrue($object->clone());
        $clone_id = $object->getId();
        $this->assertNotEquals($oid, $clone_id);
        $this->assertSame('An object (edited)', $object->getName());

        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $oid);
        $this->assertTrue($object->delete());
        $object = new \GaletteObjectsLend\Entity\LendObject($this->zdb, $this->plugins, $clone_id);
        $this->assertTrue($object->delete());
    }

    /**
     * Create few status
     *
     * @return void
     */
    private function createStatus(): void
    {
        $status = new \GaletteObjectsLend\Entity\LendStatus($this->zdb);
        $status->status_text = 'One active status';
        $status->in_stock = true;
        $status->is_active = true;
        $this->assertTrue($status->store());
        $this->active_instock_status = $status->status_id;

        $status = new \GaletteObjectsLend\Entity\LendStatus($this->zdb);
        $status->status_text = 'Another active status';
        $status->in_stock = false;
        $status->is_active = true;
        $this->assertTrue($status->store());
        $this->active_notinstock_status = $status->status_id;

        $status = new \GaletteObjectsLend\Entity\LendStatus($this->zdb);
        $status->status_text = 'One inactive status';
        $status->in_stock = true;
        $status->is_active = false;
        $this->assertTrue($status->store());
        $this->inactive_instock_status = $status->status_id;
    }

    /**
     * Create few categories
     *
     * @return void
     */
    private function createCategories(): void
    {
        $category = new \GaletteObjectsLend\Entity\LendCategory($this->zdb, $this->plugins);

        $category->name = 'Active test category';
        $category->is_active = true;

        $this->assertTrue($category->store());
        $this->active_category_id = $category->getId();
        $this->assertGreaterThan(0, $this->active_category_id);

        $category = new \GaletteObjectsLend\Entity\LendCategory($this->zdb, $this->plugins);

        $category->name = 'Inactive test category';
        $category->is_active = false;

        $this->assertTrue($category->store());
        $this->inactive_category_id = $category->getId();
        $this->assertGreaterThan(0, $this->inactive_category_id);
    }
}
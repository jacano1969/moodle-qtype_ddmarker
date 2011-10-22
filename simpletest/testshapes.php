<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the drag-and-drop words shape code.
 *
 * @package    qtype
 * @subpackage ddmarker
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/ddmarker/shapes.php');


/**
 * Unit tests for shape code
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddmarker_shapes_test extends UnitTestCase {

    public function test_polygon_hit_test() {
        $shape = new qtype_ddmarker_shape_polygon('10,10;20,10;20,20;10,20');
        $this->assertTrue($shape->is_point_in_shape(array(15,15)));
        $this->assertFalse($shape->is_point_in_shape(array(5,5)));
        $this->assertFalse($shape->is_point_in_shape(array(5,15)));
        $this->assertFalse($shape->is_point_in_shape(array(15,25)));
        $this->assertFalse($shape->is_point_in_shape(array(25,15)));
        $this->assertTrue($shape->is_point_in_shape(array(11,11)));
        $this->assertTrue($shape->is_point_in_shape(array(19,19)));

        //should accept closed polygon coords or unclosed and it will model a closed polygon
        $shape = new qtype_ddmarker_shape_polygon('10,10;20,10;20,20;10,20;10,10');
        $this->assertTrue($shape->is_point_in_shape(array(15,15)));
        $this->assertFalse($shape->is_point_in_shape(array(5,5)));
        $this->assertFalse($shape->is_point_in_shape(array(5,15)));
        $this->assertFalse($shape->is_point_in_shape(array(15,25)));
        $this->assertFalse($shape->is_point_in_shape(array(25,15)));
        $this->assertTrue($shape->is_point_in_shape(array(11,11)));
        $this->assertTrue($shape->is_point_in_shape(array(19,19)));

        $shape = new qtype_ddmarker_shape_polygon('10,10;15,5;20,10;20,20;10,20');
        $this->assertTrue($shape->is_point_in_shape(array(15,15)));
        $this->assertFalse($shape->is_point_in_shape(array(5,5)));
        $this->assertFalse($shape->is_point_in_shape(array(5,15)));
        $this->assertFalse($shape->is_point_in_shape(array(15,25)));
        $this->assertFalse($shape->is_point_in_shape(array(25,15)));
        $this->assertTrue($shape->is_point_in_shape(array(11,11)));
        $this->assertTrue($shape->is_point_in_shape(array(19,19)));
        $this->assertTrue($shape->is_point_in_shape(array(15,9)));
        $this->assertTrue($shape->is_point_in_shape(array(15,10)));

        $shape = new qtype_ddmarker_shape_polygon('15,5;20,10;20,20;10,20;10,10');
        $this->assertTrue($shape->is_point_in_shape(array(15,10)));

        $shape = new qtype_ddmarker_shape_polygon('15,5;20,10;20,20;10,20;10,10');
        $this->assertFalse($shape->is_point_in_shape(array(25,10)));

        $shape = new qtype_ddmarker_shape_polygon('0,0;500,0;600,1000;0,1200;10,10');
        $this->assertTrue($shape->is_point_in_shape(array(25,10)));
    }
}

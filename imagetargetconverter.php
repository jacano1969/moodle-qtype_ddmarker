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
 * This page lets admins check the environment requirements for this question type.
 *
 * @package    qtype
 * @subpackage pmatch
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

abstract class qtype_ddmarker_list_item {
    /**
     * @var count of questions contained in this item and sub items.
     */
    protected $qcount = 0;
    /**
     * @var children array of pointers to list_items either category_list_items or context_list_items
     */
    protected $children = array();

    protected $record;


    public function add_child($child) {
        $this->children[] = $child;
        //array_unique relies on __toString() returning a unique string to determine if objects in array
        //are the same or not
        $this->children = array_unique($this->children);
    }

    abstract protected function parent_node ();

    abstract public function render($stringidentifier, $link);

    public function leaf_to_root($qcount) {
        $this->qcount += $qcount;
        $parent = $this->parent_node();
        if ($parent !== null) {
            $parent->add_child($this);
            $parent->leaf_to_root($qcount);
        }
    }

    protected function render_children($stringidentifier, $link) {
        $children = array();
        foreach ($this->children as $child) {
            $children[] = $child->render($stringidentifier, $link);
        }
        return html_writer::alist($children);
    }

    public function __toString() {
        return get_class($this).' '.$this->record->id;
    }

}
class qtype_ddmarker_category_list_item extends qtype_ddmarker_list_item {

    public function __construct($record, $list, $parentlist) {
        $this->record = $record;
        $this->list = $list;
        $this->parentlist = $parentlist;
    }

    public function parent_node() {
        if ($this->record->parent == 0) {
            return $this->parentlist->get_instance($this->record->contextid);
        } else {
            return $this->list->get_instance($this->record->parent);
        }
    }

    public function render ($stringidentifier, $link) {
        global $PAGE;
        $a = new stdClass();
        $a->qcount = $this->qcount;
        $a->name = $this->record->name;
        $thisitem = get_string($stringidentifier.'category', 'qtype_ddmarker', $a);
        if ($link) {
            $actionurl = new moodle_url($PAGE->url, array('categoryid'=> $this->record->id));
            $thisitem = html_writer::tag('a', $thisitem, array('href' => $actionurl));
        }

        return $thisitem.$this->render_children($stringidentifier, $link);
    }
}
class qtype_ddmarker_question_list_item extends qtype_ddmarker_list_item {

    public function __construct($record, $list, $parentlist) {
        $this->record = $record;
        $this->list = $list;
        $this->parentlist = $parentlist;
    }

    public function parent_node() {
        return $this->parentlist->get_instance($this->record->cat_id);
    }

    public function render ($stringidentifier, $link) {
        global $PAGE;
        $a = new stdClass();
        $a->name = $this->record->name;
        $thisitem = get_string('listitemquestion', 'qtype_ddmarker', $a);
        return $thisitem;
    }
}
class qtype_ddmarker_context_list_item extends qtype_ddmarker_list_item {

    protected $list;
    protected $parentlist = null;

    public function __construct($record, $list) {
        $this->record = $record;
        $this->list = $list;
    }

    public function parent_node() {
        $pathids = explode('/', $this->record->path);
        if (count($pathids) >= 3) {
            return $this->list->get_instance($pathids[count($pathids)-2]);
        } else {
            return null;
        }
    }

    public function render ($stringidentifier, $link) {
        global $PAGE;
        $a = new stdClass();
        $a->qcount = $this->qcount;
        $a->name = $this->record->get_context_name();
        $thisitem = get_string($stringidentifier.'context', 'qtype_ddmarker', $a);
        if ($link) {
            $actionurl = new moodle_url($PAGE->url, array('contextid'=> $this->record->id));
            $thisitem = html_writer::tag('a', $thisitem, array('href' => $actionurl));
        }
        return $thisitem.$this->render_children($stringidentifier, $link);
    }
}
/**
 * Describes a nested list of listitems. This class and sub classes contain the functionality to build the nested list.
 **/
abstract class qtype_ddmarker_list {
    protected $records = array();
    protected $instances = array();
    abstract protected function new_list_item($record);
    protected function make_list_item_instances_from_records ($contextids = null) {
        if (!empty($this->records)) {
            foreach ($this->records as $id => $record) {
                $this->instances[$id] = $this->new_list_item($record);
            }
        }
    }
    public function get_instance($id) {
        return $this->instances[$id];
    }

    public function leaf_node ($id, $qcount) {
        $instance = $this->get_instance($id);
        $instance->leaf_to_root($qcount);
        //print_object(array('list' => $this)+ compact('id', 'qcount'));
    }

}

class qtype_ddmarker_context_list extends qtype_ddmarker_list {

    protected function new_list_item($record) {
        return new qtype_ddmarker_context_list_item($record, $this);
    }

    public function __construct($contextids) {
        global $DB;
        $this->records = array();
        foreach ($contextids as $contextid) {
            if (!isset($records[$contextid])) {
                $this->records[$contextid] = context::instance_by_id($contextid);
            }
            $this->records += $this->records[$contextid]->get_parent_contexts();
        }
        parent::make_list_item_instances_from_records ($contextids);
    }
    public function render($stringidentifier, $link, $roottorender = null) {
        if ($roottorender === null) {
            $roottorender = $this->root_node();
        }
        $rootitem = html_writer::tag('li', $roottorender->render($stringidentifier, $link));
        return html_writer::tag('ul', $rootitem);
    }
    public function root_node () {
        return $this->get_instance(get_context_instance(CONTEXT_SYSTEM)->id);
    }
}



class qtype_ddmarker_category_list extends qtype_ddmarker_list {
    protected $contextlist;
    protected function new_list_item($record) {
        return new qtype_ddmarker_category_list_item($record, $this, $this->contextlist);
    }
    public function __construct($contextids, $contextlist) {
        global $DB;
        $this->contextlist = $contextlist;
        //probably most efficient way to reconstruct question category tree is to load all q cats in relevant contexts
        list($sql, $params) = $DB->get_in_or_equal($contextids);
        $this->records = $DB->get_records_select('question_categories', "contextid ".$sql, $params);
        parent::make_list_item_instances_from_records ($contextids);
    }
}

class qtype_ddmarker_question_list extends qtype_ddmarker_list {
    protected $categorylist;
    protected function new_list_item($record) {
        return new qtype_ddmarker_question_list_item($record, $this, $this->categorylist);
    }
    public function __construct($questions, $categorylist) {
        global $DB;
        $this->categorylist = $categorylist;
        $this->records = $questions;
        parent::make_list_item_instances_from_records ();
    }
}

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$qcontextid = optional_param('contextid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
// Check the user is logged in.
require_login();
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/question:config', $context);

admin_externalpage_setup('qtypeddmarkerfromimagetarget');

// Header.
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help(get_string('imagetargetconverter', 'qtype_ddmarker'), '', 'qtype_ddmarker');


$params = array();
$from = 'FROM {question_categories} cat, {question} q';
$where = ' WHERE q.category =  cat.id ';

if ($qcontextid) {
    $qcontext = get_context_instance_by_id($qcontextid, MUST_EXIST);
    $from  .= ', {context} context';
    $where .= 'AND cat.contextid = context.id AND (context.path LIKE :path OR context.id = :id) ';
    $params['path'] = $qcontext->path.'/%';
    $params['id'] = $qcontext->id;
} else if ($categoryid) {
    $from  .= ', {context} context, {question_categories} cat2';
    $where .= 'AND cat.contextid = cat2.contextid AND cat2.id = :categoryid ';
    $params['categoryid'] = $categoryid;
}
$sql = 'SELECT q.id, cat.id AS cat_id, cat.contextid, q.name '.
                                            $from.
                                            $where.
                                            'ORDER BY cat.id';


$questions = $DB->get_records_sql($sql, $params);

$contextids = array();
foreach ($questions as $question) {
    $contextids[] = $question->contextid;
}

$contextlist = new qtype_ddmarker_context_list(array_unique($contextids));
$categorylist = new qtype_ddmarker_category_list($contextids, $contextlist);
$questionlist = new qtype_ddmarker_question_list($questions, $categorylist);

foreach ($questions as $question) {
    $questionlist->leaf_node($question->id, 1);
}
if ($categoryid || $qcontextid) {
    if (!$confirm) {
        $cofirmedurl = new moodle_url($PAGE->url, compact('categoryid', 'contextid')+array('confirm'=>1));
        $cancelurl = new moodle_url($PAGE->url);
        if ($categoryid) {
            $torender = $categorylist->get_instance($categoryid);
        } else if ($qcontextid) {
            $torender = $contextlist->get_instance($qcontextid);
        } else {
            $torender = $contextlist->root_node();
        }
        echo $contextlist->render('listitem', false, $torender);
        echo $OUTPUT->confirm(get_string('confirmimagetargetconversion', 'qtype_ddmarker'), $cofirmedurl, $cancelurl);
    } else if (confirm_sesskey()) {

    }
} else {
    echo $contextlist->render('listitemaction', true, $contextlist->root_node());
}

// Footer.
echo $OUTPUT->footer();

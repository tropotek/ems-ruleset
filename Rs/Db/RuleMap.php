<?php
namespace Rs\Db;

use Tk\Db\Exception;
use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class RuleMap extends \App\Db\Mapper
{

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('uid'));
            $this->dbMap->addPropertyMap(new Db\Integer('profileId', 'profile_id'));
            //$this->dbMap->addPropertyMap(new Db\Integer('subjectId', 'subject_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('label'));
            $this->dbMap->addPropertyMap(new Db\Text('description'));
            $this->dbMap->addPropertyMap(new Db\Decimal('min'));
            $this->dbMap->addPropertyMap(new Db\Decimal('max'));
            $this->dbMap->addPropertyMap(new Db\Text('assert'));
            $this->dbMap->addPropertyMap(new Db\Text('script'));
            //$this->dbMap->addPropertyMap(new Db\Boolean('active'));
            $this->dbMap->addPropertyMap(new Db\Integer('orderBy', 'order_by'));
            $this->dbMap->addPropertyMap(new Db\Date('created'));
        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('uid'));
            $this->formMap->addPropertyMap(new Form\Integer('profileId'));
            //$this->formMap->addPropertyMap(new Form\Integer('subjectId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('label'));
            $this->formMap->addPropertyMap(new Form\Text('description'));
            $this->formMap->addPropertyMap(new Form\Decimal('min'));
            $this->formMap->addPropertyMap(new Form\Decimal('max'));
            $this->formMap->addPropertyMap(new Form\Text('assert'));
            $this->formMap->addPropertyMap(new Form\Text('script'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));
        }
        return $this->formMap;
    }
    /**
     * @param array|\Tk\Db\Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Role[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        return $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
    }

    /**
     * @param \Tk\Db\Filter $filter
     * @return \Tk\Db\Filter
     */
    public function makeQuery(\Tk\Db\Filter $filter)
    {
        $filter->appendFrom('%s a ', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.label LIKE %s OR ', $this->quote($kw));
            $w .= sprintf('a.description LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }
        
        if (!empty($filter['profileId'])) { // deprecated use subjectId
            $filter->appendWhere('a.profile_id = %s AND ', (int)$filter['profileId']);
        }

        if (!empty($filter['subjectId'])) {
            $from .= sprintf(', (SELECT a.id as \'rule_id\', IFNULL(b.active, 1) as \'active\' FROM rule a LEFT JOIN rule_subject b ON (a.id = b.rule_id AND b.subject_id = %s) ) b', (int)$filter['subjectId']);
            $filter->appendWhere('a.id = b.rule_id AND b.active = 1 AND ');
        }

        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->quote($filter['name']));
        }

        if (!empty($filter['assert'])) {
            $filter->appendWhere('a.assert = %s AND ', $this->quote($filter['assert']));
        }

        if (!empty($filter['label'])) {
            $filter->appendWhere('a.label = %s AND ', $this->quote($filter['label']));
        }

        if (!empty($filter['placementId'])) {
            $filter->appendFrom(' ,%s d', $this->quoteTable('rule_has_placement'));
            $filter->appendWhere('a.id = d.rule_id AND d.placement_id = %s AND ', (int)$filter['placementId']);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }


    // ------------------------------------------------------

    /**
     * @param int $ruleId
     * @param int $placementId
     * @return boolean
     */
    public function hasPlacement($ruleId, $placementId)
    {
        try {
            $stm = $this->getDb()->prepare('SELECT * FROM rule_has_placement WHERE rule_id = ? AND placement_id = ?');
            $stm->execute($ruleId, $placementId);
            return ($stm->rowCount() > 0);
        } catch (Exception $e) {}
        return false;

    }

    /**
     * @param int $ruleId
     * @param int $placementId (optional) If null all placements are to be removed
     */
    public function removePlacement($ruleId = null, $placementId = null)
    {
        try {
            if (!$ruleId && !$placementId) return;
            $where = '';
            if ($ruleId) {
                $where = sprintf('rule_id = %d AND ', (int)$ruleId);
            }
            if ($placementId) {
                $where = sprintf('placement_id = %d AND ', (int)$placementId);
            }
            if ($where) {
                $where = substr($where, 0, -4);
            }
            $stm = $this->getDb()->prepare('DELETE FROM rule_has_placement WHERE ' . $where);
            $stm->execute();
        } catch (Exception $e) {}
    }

    /**
     * @param int $ruleId
     * @param int $placementId
     */
    public function addPlacement($ruleId, $placementId)
    {
        try {
            if ($this->hasPlacement($ruleId, $placementId)) return;
            $stm = $this->getDb()->prepare('INSERT INTO rule_has_placement (rule_id, placement_id) VALUES (?, ?) ');
            $stm->execute($ruleId, $placementId);
        } catch (Exception $e) {}
    }

    public function isActive($ruleId, $subjectId)
    {
        try {
            $stm = $this->getDb()->prepare('SELECT active FROM rule_subject WHERE rule_id = ? AND subject_id = ?');
            $stm->execute($ruleId, $subjectId);
            if ($stm->rowCount()) {
                return (bool)$stm->fetchColumn();
            }
        } catch (Exception $e) {}
        return true;        // All rules are active if no subject record available.
    }

    public function setActive($ruleId, $subjectId, $active)
    {
        try {
            $stm = $this->getDb()->prepare('INSERT INTO rule_subject (rule_id, subject_id, active) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE active = ?');
            $stm->execute((int)$ruleId, (int)$subjectId, (int)$active, (int)$active);
        } catch (Exception $e) {}
    }

    public function hasActive($ruleId, $subjectId)
    {
        try {
            $stm = $this->getDb()->prepare('SELECT * FROM rule_subject WHERE rule_id = ? AND subject_id = ?');
            $stm->execute($ruleId, $subjectId);
            return ($stm->rowCount() > 0);
        } catch (Exception $e) {}
        return false;
    }

    public function removeActive($ruleId, $subjectId)
    {
        try {
            $stm = $this->getDb()->prepare('DELETE FROM rule_subject WHERE rule_id = ? AND subject_id = ?');
            $stm->execute($ruleId, $subjectId);
        } catch (Exception $e) {}
    }

}
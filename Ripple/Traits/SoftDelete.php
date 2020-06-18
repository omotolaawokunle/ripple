<?php

namespace ORM\Ripple\Traits;

trait SoftDelete
{
    protected static $column;
    protected $forceDeleting = FALSE;
    protected static $withTrashed = FALSE;
    public $deleted_at;

    public static function bootSoftDelete()
    {
    }

    public function initializeSoftDelete()
    {
        $this->{$this->getDeletedAtColumn()} = null;
    }


    public static function withTrashed()
    {
        $instance = new static;
        $instance::$withTrashed = TRUE;
        return $instance;
    }

    public function onlyTrashed()
    {
        static::$withTrashed = TRUE;
        $result = static::where('deleted_at', "!=", '');
        static::$withTrashed = FALSE;
        return $result;
    }

    public function forceDelete()
    {
        $this->forceDeleting = true;
        if ($this->delete()) {
            $this->forceDeleting = false;
        }
    }

    protected function runSoftDelete()
    {
        $this->{$this->getDeletedAtColumn()} = date('Y-m-d H:i:s');
        $this->save();
    }

    protected function performDelete()
    {
        if ($this->forceDeleting) {
            return $this->db->delete($this->table, ['id' => ['=', $this->id], '']);
        }
        return $this->runSoftDelete();
    }


    public function restore()
    {
        $this->{$this->getDeletedAtColumn()} = null;
        $result = $this->save();
        return $result;
    }

    public function getDeletedAtColumn()
    {
        $column =  defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
        static::$column = $column;
        return $column;
    }

    /**
     * Determine if the model entity has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    public function buildArray($result)
    {
        if (is_object($result)) {
            foreach ($result as $key => $value) {
                if (property_exists($value, 'deleted_at')) {
                    if (!is_null($value->deleted_at)) {
                        unset($result[$key]);
                    }
                }
            }
        } else {
            if (!empty($result)) {
                $values = collect($result);
                $values = $values->filter(function ($value) {
                    if (array_key_exists('deleted_at', $value)) {
                        return is_null($value['deleted_at']) ? true : false;
                    }
                    return true;
                });


                $result = $values->all();
            }
        }

        return parent::buildArray($result);
    }
}

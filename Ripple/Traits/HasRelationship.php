<?php 
namespace Ripple\Traits;

use Ripple\Relationships\OneToOne;
use Ripple\Relationships\OneToMany;
use Ripple\Relationships\ManyToOne;
use Ripple\Relationships\ManyToMany;
use Ripple\Entity;

trait HasRelationship
{
    public function hasMany($childClass, $foreign_key = null)
    {
        $class = new \ReflectionClass($this);
        $classname = strtolower($class->getShortName());
        $foreign_key = is_null($foreign_key) ? $classname . '_id' : $foreign_key;
        $result = new OneToMany($this, $childClass, $foreign_key);
        return $this->traitsFunctions($result);
    }

    public function belongsTo($parentClass, $foreign_key)
    {
        $class = new \ReflectionClass($this);
        $classname = strtolower($class->getShortName());
        $foreign_key = is_null($foreign_key) ? $classname . '_id' : $foreign_key;
        $result =  new ManyToOne($this, $parentClass, $foreign_key);
        return $this->traitsFunctions($result);
    }

    /**
     * Many-to-many relationship
     * @param string $relatedClass Related Class
     * @param string $pivotTable Pivot table
     * @param string $parentKey Parent Key in the pivot table
     * @param string $relatedKey Related key in the pivot table
     */
    public function belongsToMany($relatedClass, $pivotTable, $parentKey, $relatedKey)
    {
        $result = new ManyToMany($this, $relatedClass, $pivotTable, $parentKey, $relatedKey);
        return $this->traitsFunctions($result);
    }

    /**
     * One-to-one relationship
     * @param string $relatedClass Related Class
     * @param string $foreignKey foreign key in the child table
     */
    public function hasOne($relatedClass, $foreignKey)
    {
        $result = new OneToOne($this->id, $relatedClass, $foreignKey);
        return $this->traitsFunctions($result);
    }
}

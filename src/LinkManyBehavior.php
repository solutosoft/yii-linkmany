<?php

namespace solutosoft\linkmany;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class LinkManyBehavior extends Behavior
{
    /**
     * @var \yii\db\ActiveRecord|null the owner of this behavior
     */
    public $owner;

    /**
     * @var array the relation definitions
     */
    public $relations = [];

    /**
     * @var \yii\db\ActiveRecord[] the changed models
     */
    private $_inserteds = [];

    /**
     * @var \yii\db\ActiveRecord[] the deleted models
     */
    private $_updateds = [];

    /**
     * @var \yii\db\ActiveRecord[] the deleted models
     */
    private $_deleteds = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->initRelations();
    }

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate'
        ];
    }


    /**
     * Populates the model with input data.
     * @param array $data the data array to load, typically `$_POST` or `$_GET`.
     * @param string $formName the form name to use to load the data into the model.
     * If not set, [[formName()]] is used.
     * @return void
     */
    public function fill($data, $formName = null)
    {
        if ($this->owner->load($data, $formName) === false) {
            return false;
        }

        foreach ($this->relations as $definition) {
            if (isset($data[$definition->name])) {
                $this->prepareRelation($definition, $data);
            }
        }
    }

    /**
     * Handles owner 'afterInsert' and 'afterUpdate' events, ensuring related models are linked.
     */
    public function afterSave()
    {
        $owner = $this->owner;

        $class = get_class($owner);
        $class::populateRecord($owner, $owner->getAttributes());

        foreach ($this->relations as $definition) {
            $name = $definition->name;

            $models = ArrayHelper::getValue($this->_inserteds, $name, []);
            foreach ($models as $model) {
                $owner->link($name, $model);
            }

            $models = ArrayHelper::getValue($this->_updateds, $name, []);
            foreach ($models as $model) {
               $model->save(false);
            }

            $models = ArrayHelper::getValue($this->_deleteds, $name, []);
            foreach ($models as $model) {
                $owner->unlink($name, $model, $definition->deleteOnUnlink);
            }
        }

        $this->_inserteds = [];
        $this->_updateds = [];
        $this->_deleteds = [];
    }


    public function afterValidate()
    {
        foreach ($this->relations as $definition) {
            $name = $definition->name;

            if (!$definition->validate) {
                continue;
            }

            $errors = [];
            $models = isset($this->_inserteds[$name]) ? $this->_inserteds[$name] : [];

            foreach ($models as $model) {
                if (!$model->validate()) {
                    $errors[] = $model->getErrors();
                }
            }

            foreach ($errors as $error) {
                $this->owner->addError($name, $error);
            }
        }
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that relation attribute can be accessed like property.
     * @param string $name property name
     * @param mixed $value property value
     * @throws UnknownPropertyException if the property is not defined
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $exception) {
            $definition = $this->findDefinition($name);
            if ($definition !== null) {
                $this->prepareRelation($definition, [$name => $value]);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if (parent::canSetProperty($name, $checkVars)) {
            return true;
        }
        return ($this->findDefinition($name) !== null);
    }

    /**
     * Prepares the relations changes
     * @param RelationDefinition $definition
     * @param array $data
     * @return void
     */
    protected function prepareRelation($definition, $data)
    {
        $owner = $this->owner;
        $name = $definition->name;

        $relation = $owner->getRelation($name);
        $records = [];

        if ($owner->getIsNewRecord()) {
            foreach ($data[$name] as $item) {
                $modelClass = $relation->modelClass;
                $model = $relation->via ? $modelClass::findOne($item) : new $modelClass();

                if (!$relation->via) {
                    $this->fillRelation($model, $item, $definition->formName);
                }

                if ($model !== null) {
                    $this->_inserteds[$name][] = $model;
                    $records[] = $model;
                }
            }
        } else {
            $relateds =  $owner->{$name};
            $references = $this->initReferences($relateds);
            $relatedData = !$relation->via ? $this->normalizeData($data[$name], $relation) : $data[$name];

            foreach ($relatedData as $item) {
                $primaryKey = $this->extractPrimaryKey($item, $relation);

                if (($index = array_search($primaryKey, $references)) === false) {
                    $modelClass = $relation->modelClass;
                    $model = $relation->via ? $modelClass::findOne($item) : new $modelClass();

                    if ($model !== null) {
                        $this->_inserteds[$name][] = $model;
                    }
                } else {
                    $model = $relateds[$index];
                    $this->_updateds[$name][] = $model;
                }

                if ($model !== null) {
                    if (!$relation->via) {
                        $this->fillRelation($model, $item, $definition->formName);
                    }

                    $records[] = $model;
                }
            }

            $references = $this->initReferences($records);
            foreach ($relateds as $i => $related) {
                $primaryKey = $this->normalizePrimaryKey($related->getPrimaryKey());

                if (array_search($primaryKey, $references) === false) {
                    $this->_deleteds[$name][] = $relateds[$i];
                }
            }
        }

        $owner->populateRelation($name, $records);
    }

     /**
     * Creates definition objects and initializes them.
     */
    protected function initRelations()
    {
        foreach ($this->relations as $i => $value) {
            if (is_string($value)) {
                $name = $value;
            } elseif (is_string($i)) {
                $name = $i;
            } else {
                throw new InvalidConfigException('The "relations" property must be a map of RelationDefinition object');
            }

            $config = [
                'class' => RelationDefinition::class,
                'name' => $name,
            ];

            if (is_array($value)) {
                $config = array_merge($config, $value);
            }

            $this->relations[$i] = Yii::createObject($config);
        }
    }

    /**
     * Populates references values in data
     * @param array $data
     * @param array \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $relation
     * @return array
     */
    protected function normalizeData($data, $relation)
    {
        $result = [];
        $modelClass = $relation->modelClass;

        foreach ($data as $item) {
            foreach ($relation->link as $attribute => $reference) {
                if (!isset($item[$attribute]) && in_array($attribute, $modelClass::primaryKey())) {
                    $item[$attribute] = $this->owner->{$reference};
                }
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param mixed $primaryKey raw primary key value.
     * @return string|integer normalized value.
     */
    protected function normalizePrimaryKey($primaryKey)
    {
        if (is_object($primaryKey) && method_exists($primaryKey, '__toString')) {
            // handle complex types like [[\MongoId]] :
            $primaryKey = $primaryKey->__toString();
        }
        return $primaryKey;
    }

    /**
     * Populates the relational model with input data.
     * @param ActiveRecord $model
     * @param array $data
     * @param string $formName
     * @return void
     */
    protected function fillRelation($model, $data, $formName)
    {
        if ($model->hasMethod('fill')) {
            $model->fill($data, $formName);
        } else {
            $model->load($data, $formName);
        }
    }

    /**
     * Extracts primary key from data
     * @param array $data
     * @param \yii\db\ActiveQuery $relation
     * @return mixed
     */
    protected function extractPrimaryKey($data, $relation)
    {
        if ($relation->via) {
            return $data;
        } else {
            $modelClass = $relation->modelClass;
            $primaryKey = $modelClass::primaryKey();

            if (count($primaryKey) === 1) {
                return ArrayHelper::getValue($data, $primaryKey[0]);
            }

            $result = [];
            foreach ($primaryKey as $key) {
                if (isset($data[$key])) {
                    $result[$key] = $data[$key];
                }
            }
            return $result;
        }
    }

    /**
     * Initializes related references values
     * @param ActiveRecord
     * @return array relation attribute value.
     */
    protected function initReferences($records)
    {
        $result = [];

        if (!empty($records)) {
            foreach ($records as $record) {
                $result[] = $this->normalizePrimaryKey($record->getPrimaryKey());
            }
        }
        return $result;
    }

    /**
     * Finds relation definition by name
     * @param string $name The definition name
     * @return RelationDefinition
     */
    private function findDefinition($name)
    {
        foreach ($this->relations as $relation) {
            if ($relation->name === $name) {
                return $relation;
            }
        }
        return null;
    }
}

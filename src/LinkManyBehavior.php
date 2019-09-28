<?php

namespace solutosoft\linkmany;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

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
    private $_changeds = [];

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
        $owner = $this->owner;

        if ($owner->load($data, $formName) === false) {
            return false;
        }

        foreach ($this->relations as $definition) {
            $name = $definition->name;

            if (!isset($data[$name])) {
                continue;
            }

            $relation = $owner->getRelation($name);
            $records = [];

            if ($owner->getIsNewRecord()) {
                foreach ($data[$name] as $item) {
                    $modelClass = $relation->modelClass;
                    $model = $relation->via ? $modelClass::findOne($item) : new $modelClass();

                    if (!$relation->via) {
                        $this->fillRelation($model, $item, $definition);
                    }

                    if ($model !== null) {
                        $this->_changeds[$name][] = $model;
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
                            $this->_changeds[$name][] = $model;
                        }
                    } else {
                        $model = $relateds[$index];
                    }

                    if ($model !== null) {
                        if (!$relation->via) {
                            $this->fillRelation($model, $item, $definition);
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

            $models = isset($this->_changeds[$name]) ? $this->_changeds[$name] : [];
            foreach ($models as $model) {
                $owner->link($name, $model);
            }

            $models = isset($this->_deleteds[$name]) ? $this->_deleteds[$name] : [];
            foreach ($models as $model) {
                $owner->unlink($name, $model, $definition->deleteOnUnlink);
            }
        }

        $this->_changeds = [];
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
            $models = isset($this->_changeds[$name]) ? $this->_changeds[$name] : [];

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
     * @param RelationDefinition $definition
     * @return void
     */
    protected function fillRelation($model, $data, $definition)
    {
        $model->load($data, $definition->formName);
    }

    /**
     * Extracts primary key from data
     * @param array $data
     * @param \yii\db\ActiveQuery $relation
     * @return array
     */
    protected function extractPrimaryKey($data, $relation)
    {
        if ($relation->via) {
            return $data;
        } else {
            $result = [];
            $modelClass = $relation->modelClass;

            foreach ($modelClass::primaryKey() as $key) {
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
}

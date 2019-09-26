<?php

namespace soluto\relations;

use yii\base\BaseObject;

class RelationDefinition extends BaseObject
{
    public $name;

    public $formName = '';

    public $validate = true;

    public $deleteCascade = true;
}

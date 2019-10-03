<?php

namespace solutosoft\linkmany;

use yii\base\BaseObject;

class RelationDefinition extends BaseObject
{
    /**
     * @var string the relation name
     */
    public $name;

    /**
     * @var string the relation form name
     */
    public $formName = '';

    /**
     * @var string whether the relation validation is required.
     */
    public $validate = true;

    /**
     * @var boolean whether to delete the pivot model or table row on unlink.
     */
    public $deleteOnUnlink = true;

    /**
     * @var string The scenario that will be used
     */
    public $scenario;
}

<?php

namespace solutosoft\linkmany\tests\models;

use yii\db\ActiveRecord;

/**
 * @property string $name
 * @property string $color
 */
class Tag extends ActiveRecord
{
    const SCENARIO_LINK = 'tag_link';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'color'], 'string']
        ];
    }
}

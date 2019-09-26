<?php

namespace soluto\relations\tests\models;

use yii\db\ActiveRecord;

/**
 * @property string $name
 * @property string $color
 */
class Tag extends ActiveRecord
{
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

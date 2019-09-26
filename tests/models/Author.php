<?php

namespace solutosoft\linkmany\tests\models;

use yii\db\ActiveRecord;

/**
 * @property string $name
 * @property string $image
 */
class Author extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'image'], 'string']
        ];
    }
}

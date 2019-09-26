<?php

namespace solutosoft\linkmany\tests\models;

use yii\db\ActiveRecord;

/**
 * @property int $post_id
 * @property string $language
 * @property string $url
 */
class PostLanguage extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['post_id'], 'integer'],
            [['language', 'url'], 'string']
        ];
    }
}

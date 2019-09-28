<?php

namespace solutosoft\linkmany\tests\models;

use yii\db\ActiveRecord;

/**
 * @property int $post_id
 * @property string $content
 */
class PostComment extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['post_id'], 'integer'],
            [['subject', 'content'], 'string'],
            [['subject', 'content'], 'required']
        ];
    }
}

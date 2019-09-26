<?php

namespace soluto\relations\tests\models;

use soluto\relations\RelationBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property string $title
 * @property string $content
 * @property Author[] $authors
 * @property Tag[] $tags
 */
class Post extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'relation' => [
                'class' => RelationBehavior::class,
                'relations' => [
                    'languages',
                    'tags'
                ]
            ]
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(Author::class, ['author_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguages()
    {
        return $this->hasMany(PostLanguage::class, ['post_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('post_tag', ['post_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'content'], 'string'],
            [['title', 'content'], 'required']
        ];
    }
}
